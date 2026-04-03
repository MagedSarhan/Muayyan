<?php
/** AALMAS - Charts Data API */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Unauthorized']); exit; }

$db = getDBConnection();
$chart = $_GET['chart'] ?? '';

switch ($chart) {
    case 'grade_distribution':
        $data = $db->query("SELECT 
            CASE 
                WHEN g.score/a.max_score >= 0.9 THEN 'A+/A'
                WHEN g.score/a.max_score >= 0.8 THEN 'B+/B'
                WHEN g.score/a.max_score >= 0.7 THEN 'C+/C'
                WHEN g.score/a.max_score >= 0.6 THEN 'D+/D'
                ELSE 'F'
            END as grade, COUNT(*) as count
            FROM grades g JOIN assessments a ON g.assessment_id = a.id
            GROUP BY grade ORDER BY grade")->fetchAll();
        echo json_encode($data);
        break;
        
    case 'student_risk':
        $studentId = $_GET['student_id'] ?? null;
        if ($studentId) {
            echo json_encode(calculateRiskScore($studentId));
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid chart type']);
}
