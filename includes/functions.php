<?php
/**
 * MOEEN  - Global Utility Functions
 */
require_once __DIR__ . '/../config/config.php';

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($userId)
{
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetch()['count'];
}

/**
 * Get recent notifications
 */
function getRecentNotifications($userId, $limit = 5)
{
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

/**
 * Create notification
 */
function createNotification($userId, $type, $title, $message, $link = null)
{
    $db = getDBConnection();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $type, $title, $message, $link]);
}

/**
 * Calculate student risk score for a section
 */
function calculateRiskScore($studentId, $sectionId = null)
{
    $db = getDBConnection();

    $where = "WHERE g.student_id = ?";
    $params = [$studentId];

    if ($sectionId) {
        $where .= " AND a.section_id = ?";
        $params[] = $sectionId;
    }

    // Get all grades
    $stmt = $db->prepare("
        SELECT g.score, a.max_score, a.due_date, a.type
        FROM grades g 
        JOIN assessments a ON g.assessment_id = a.id 
        $where
        ORDER BY a.due_date ASC
    ");
    $stmt->execute($params);
    $grades = $stmt->fetchAll();

    if (empty($grades))
        return ['score' => 100, 'level' => 'stable'];

    // Factor 1: Grade Average (40%)
    $totalPercent = 0;
    $gradeCount = count($grades);
    foreach ($grades as $g) {
        $totalPercent += ($g['score'] / $g['max_score']) * 100;
    }
    $avgPercent = $totalPercent / $gradeCount;

    $gradeScore = min(100, ($avgPercent / 100) * 100);

    // Factor 2: Grade Trend (25%) - compare last 3 to first 3
    $trendScore = 50; // neutral
    if ($gradeCount >= 3) {
        $recent = array_slice($grades, -3);
        $earlier = array_slice($grades, 0, min(3, $gradeCount - 3));
        if (!empty($earlier)) {
            $recentAvg = array_sum(array_map(fn($g) => ($g['score'] / $g['max_score']) * 100, $recent)) / count($recent);
            $earlierAvg = array_sum(array_map(fn($g) => ($g['score'] / $g['max_score']) * 100, $earlier)) / count($earlier);
            $trendScore = 50 + ($recentAvg - $earlierAvg);
            $trendScore = max(0, min(100, $trendScore));
        }
    }

    // Factor 3: Assessment Load (20%)
    $loadStmt = $db->prepare("
        SELECT COUNT(*) as cnt FROM assessments 
        WHERE section_id IN (SELECT section_id FROM section_students WHERE student_id = ?)
        AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
    $loadStmt->execute([$studentId]);
    $upcomingCount = $loadStmt->fetch()['cnt'];
    $loadScore = max(0, 100 - ($upcomingCount * 20));

    // Factor 4: Zero/Missing scores (15%)
    $zeroCount = count(array_filter($grades, fn($g) => $g['score'] == 0));
    $missScore = max(0, 100 - ($zeroCount * 25));

    // Weighted final score
    $finalScore = ($gradeScore * 0.40) + ($trendScore * 0.25) + ($loadScore * 0.20) + ($missScore * 0.15);

    // Determine level
    if ($finalScore >= RISK_STABLE)
        $level = 'stable';
    elseif ($finalScore >= RISK_MONITOR)
        $level = 'monitor';
    elseif ($finalScore >= RISK_ATRISK)
        $level = 'at_risk';
    else
        $level = 'high_risk';

    return [
        'score' => round($finalScore, 1),
        'level' => $level,
        'avg_grade' => round($avgPercent, 1),
        'trend' => $trendScore > 50 ? 'improving' : ($trendScore < 40 ? 'declining' : 'stable')
    ];
}

/**
 * Get risk level label and color
 */
function getRiskBadge($level)
{
    $badges = [
        'stable' => ['label' => 'Stable', 'class' => 'bg-success', 'icon' => 'fa-check-circle'],
        'monitor' => ['label' => 'Needs Monitoring', 'class' => 'bg-warning text-dark', 'icon' => 'fa-eye'],
        'at_risk' => ['label' => 'At Risk', 'class' => 'bg-orange', 'icon' => 'fa-exclamation-triangle'],
        'high_risk' => ['label' => 'High Risk', 'class' => 'bg-danger', 'icon' => 'fa-times-circle'],
    ];
    return $badges[$level] ?? $badges['stable'];
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M d, Y')
{
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime)
{
    return date('M d, Y h:i A', strtotime($datetime));
}

/**
 * Time ago helper
 */
function timeAgo($datetime)
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0)
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0)
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0)
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0)
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0)
        return $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

/**
 * Sanitize output
 */
function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get assessment type badge
 */
function getAssessmentBadge($type)
{
    $badges = [
        'quiz' => ['class' => 'badge-quiz', 'icon' => 'fa-question-circle'],
        'midterm' => ['class' => 'badge-midterm', 'icon' => 'fa-file-alt'],
        'final' => ['class' => 'badge-final', 'icon' => 'fa-graduation-cap'],
        'project' => ['class' => 'badge-project', 'icon' => 'fa-project-diagram'],
        'assignment' => ['class' => 'badge-assignment', 'icon' => 'fa-tasks'],
        'presentation' => ['class' => 'badge-presentation', 'icon' => 'fa-chalkboard-teacher'],
        'lab' => ['class' => 'badge-lab', 'icon' => 'fa-flask'],
        'participation' => ['class' => 'badge-participation', 'icon' => 'fa-users'],
    ];
    return $badges[$type] ?? ['class' => 'bg-secondary', 'icon' => 'fa-circle'];
}

/**
 * Get severity badge
 */
function getSeverityBadge($severity)
{
    $badges = [
        'info' => ['class' => 'bg-info', 'icon' => 'fa-info-circle'],
        'warning' => ['class' => 'bg-warning text-dark', 'icon' => 'fa-exclamation-triangle'],
        'danger' => ['class' => 'bg-danger', 'icon' => 'fa-exclamation-circle'],
        'critical' => ['class' => 'bg-dark', 'icon' => 'fa-skull-crossbones'],
    ];
    return $badges[$severity] ?? $badges['info'];
}

/**
 * Get request status badge
 */
function getRequestStatusBadge($status)
{
    $badges = [
        'sent' => ['label' => 'Sent', 'class' => 'bg-primary', 'icon' => 'fa-paper-plane'],
        'under_review' => ['label' => 'Under Review', 'class' => 'bg-warning text-dark', 'icon' => 'fa-search'],
        'replied' => ['label' => 'Replied', 'class' => 'bg-success', 'icon' => 'fa-check'],
        'closed' => ['label' => 'Closed', 'class' => 'bg-secondary', 'icon' => 'fa-lock'],
    ];
    return $badges[$status] ?? $badges['sent'];
}

/**
 * Get user initials for avatar
 */
function getInitials($name)
{
    $parts = explode(' ', $name);
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return $initials;
}

/**
 * Paginate results
 */
function paginate($query, $params, $page = 1, $perPage = 15)
{
    $db = getDBConnection();

    // Count total
    $countQuery = "SELECT COUNT(*) as total FROM (" . $query . ") as sub";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    $totalPages = ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;

    $stmt = $db->prepare($query . " LIMIT ? OFFSET ?");
    $allParams = array_merge($params, [$perPage, $offset]);
    $stmt->execute($allParams);
    $results = $stmt->fetchAll();

    return [
        'data' => $results,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages
    ];
}

/**
 * Flash message helper
 */
function setFlash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
