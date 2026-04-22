<?php
/** MOEEN  - Landing Page */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . getRoleRedirect($_SESSION['user_role']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="MOEEN  - Academic Assessment Load & Performance Analysis System. Monitor student performance, analyze academic workload, and support early intervention.">
    <title>MOEEN — Academic Assessment Load & Performance Analysis System</title>
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --p900: #0a1628;
            --p800: #0f2744;
            --p700: #143a5c;
            --p600: #1a5276;
            --p500: #1e6fa0;
            --p400: #2e86c1;
            --p300: #5dade2;
            --p200: #85c1e9;
            --p100: #aed6f1;
            --p50: #d6eaf8;
            --accent: #00d4aa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        body {
            font-family: 'Inter', sans-serif;
            color: #1a1a2e;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased
        }

        /* ---- NAVBAR ---- */
        .nav-main {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 18px 0;
        }

        .nav-main.scrolled {
            background: #0a1628;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none
        }

        .nav-brand img {
            width: 38px;
            height: 38px;
        }

        .nav-brand span {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 30px
        }

        .nav-links a {
            color: rgba(255, 255, 255, .75);
            font-size: .85rem;
            font-weight: 500;
            text-decoration: none;
        }

        .nav-links a:hover {
            color: #fff
        }

        .btn-cta {
            background: #1e6fa0;
            color: #fff !important;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            font-size: .85rem;
        }

        /* ---- HERO ---- */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: #0f2744;
            padding-top: 80px;
        }

        .hero-content {
            position: relative;
        }

        .hero-title {
            font-size: 2.8rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 20px
        }

        .hero-title .gradient-text {
            color: var(--p200);
        }

        .hero-subtitle {
            font-size: 1rem;
            color: rgba(255, 255, 255, .7);
            line-height: 1.7;
            max-width: 520px;
            margin-bottom: 35px
        }

        .hero-btns {
            display: flex;
            gap: 15px;
            flex-wrap: wrap
        }

        .btn-hero-primary {
            background: #00b894;
            color: #fff;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: .9rem;
            border: none;
            text-decoration: none;
            display: inline-block;
        }

        .btn-hero-primary:hover {
            background: #00a381;
            color: #fff
        }

        .btn-hero-outline {
            border: 1px solid rgba(255, 255, 255, .4);
            color: #fff;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 500;
            font-size: .9rem;
            text-decoration: none;
            display: inline-block;
            background: transparent
        }

        .btn-hero-outline:hover {
            border-color: #fff;
            color: #fff
        }

        .hero-visual {
            position: relative;
        }

        .hero-card {
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 8px;
            padding: 25px;
        }

        .hero-stat {
            text-align: center;
            padding: 12px
        }

        .hero-stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff
        }

        .hero-stat-label {
            font-size: .72rem;
            color: var(--p200);
            margin-top: 4px
        }

        .hero-logo-float {
            position: absolute;
            top: -20px;
            right: -15px;
            width: 90px;
            height: 90px;
        }

        .hero-logo-float img {
            width: 100%;
        }

        /* ---- FEATURES ---- */
        .section {
            padding: 100px 0;
            position: relative
        }

        .section-light {
            background: #fafafc
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--p900);
            margin-bottom: 12px
        }

        .section-subtitle {
            font-size: 1rem;
            color: #6c6c8a;
            max-width: 600px;
            margin: 0 auto 50px
        }

        .feature-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 25px;
            height: 100%;
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 20px
        }

        .feature-icon.blue {
            background: rgba(30, 111, 160, .1);
            color: var(--p500)
        }

        .feature-icon.green {
            background: rgba(0, 212, 170, .1);
            color: var(--accent)
        }

        .feature-icon.orange {
            background: rgba(243, 156, 18, .1);
            color: #f39c12
        }

        .feature-icon.red {
            background: rgba(231, 76, 60, .1);
            color: #e74c3c
        }

        .feature-icon.purple {
            background: rgba(155, 89, 182, .1);
            color: #9b59b6
        }

        .feature-icon.teal {
            background: rgba(26, 188, 156, .1);
            color: #1abc9c
        }

        .feature-card h5 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--p900);
            margin-bottom: 10px
        }

        .feature-card p {
            font-size: .85rem;
            color: #6c6c8a;
            line-height: 1.6
        }

        /* ---- USERS SECTION ---- */
        .user-type {
            text-align: center;
            padding: 30px 20px
        }

        .user-type-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8rem;
            color: #fff;
            box-shadow: 0 8px 25px rgba(0, 0, 0, .15)
        }

        .user-type-icon.admin {
            background: var(--p700)
        }

        .user-type-icon.faculty {
            background: var(--p500)
        }

        .user-type-icon.advisor {
            background: #27ae60
        }

        .user-type-icon.student {
            background: #e67e22
        }

        .user-type h5 {
            font-weight: 700;
            color: var(--p900);
            margin-bottom: 8px
        }

        .user-type p {
            font-size: .82rem;
            color: #6c6c8a;
            line-height: 1.5
        }

        .user-type ul {
            list-style: none;
            text-align: left;
            padding: 0;
            margin-top: 15px
        }

        .user-type ul li {
            font-size: .8rem;
            padding: 4px 0;
            color: #404060
        }

        .user-type ul li i {
            color: var(--accent);
            margin-right: 8px;
            font-size: .7rem
        }

        /* ---- HOW IT WORKS ---- */
        .step-card {
            text-align: center;
            position: relative
        }

        .step-num {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--p500);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0 auto 20px;
            border: 2px solid var(--p300)
        }

        .step-card h5 {
            font-weight: 700;
            color: var(--p900);
            margin-bottom: 8px
        }

        .step-card p {
            font-size: .85rem;
            color: #6c6c8a
        }

        /* ---- CTA ---- */
        .cta-section {
            background: var(--p800);
            padding: 60px 0;
        }

        .cta-section h2 {
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 15px
        }

        .cta-section p {
            color: rgba(255, 255, 255, .7);
            margin-bottom: 30px;
            font-size: 1rem
        }

        /* ---- FOOTER ---- */
        .footer {
            background: var(--p900);
            padding: 50px 0 30px;
            color: rgba(255, 255, 255, .5)
        }

        .footer-brand img {
            width: 40px;
            margin-bottom: 12px;
            filter: drop-shadow(0 2px 8px rgba(93, 173, 226, .3))
        }

        .footer-brand h5 {
            color: #fff;
            font-weight: 800;
            letter-spacing: 2px;
            margin-bottom: 5px
        }

        .footer-brand p {
            font-size: .78rem;
            max-width: 300px
        }

        .footer-links h6 {
            color: var(--p200);
            font-weight: 700;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px
        }

        .footer-links a {
            display: block;
            color: rgba(255, 255, 255, .5);
            font-size: .82rem;
            padding: 3px 0;
            text-decoration: none;
            transition: color .2s
        }

        .footer-links a:hover {
            color: #fff
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, .08);
            padding-top: 25px;
            margin-top: 40px;
            font-size: .75rem
        }

        /* ---- RESPONSIVE ---- */
        @media(max-width:991px) {
            .hero-title {
                font-size: 2.5rem
            }

            .hero-visual {
                margin-top: 40px
            }

            .nav-links {
                display: none
            }
        }

        @media(max-width:767px) {
            .hero-title {
                font-size: 2rem
            }

            .section-title {
                font-size: 1.7rem
            }

            .section {
                padding: 60px 0
            }
        }

        @media(max-width:575px) {
            .hero {
                padding-top: 100px
            }

            .hero-btns {
                flex-direction: column
            }

            .hero-btns a {
                text-align: center
            }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="nav-main" id="mainNav">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="#" class="nav-brand"><img src="<?= BASE_URL ?>/images/logo.png" alt="MOEEN "><span>MOEEN
                </span></a>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#users">Users</a>
                <a href="#how">How It Works</a>
                <a href="<?= BASE_URL ?>/login.php" class="btn-cta"><i class="fas fa-sign-in-alt me-1"></i> Sign In</a>
            </div>
            <a href="<?= BASE_URL ?>/login.php" class="btn-cta d-lg-none"><i class="fas fa-sign-in-alt me-1"></i> Sign
                In</a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero" id="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">

                    <h1 class="hero-title">
                        Empower Academic<br>
                        <span class="gradient-text">Excellence & Early</span><br>
                        Intervention
                    </h1>
                    <p class="hero-subtitle">
                        MOEEN helps faculty and advisors monitor student performance, analyze assessment workload,
                        detect at-risk students early, and provide targeted academic support — all in one powerful
                        platform.
                    </p>
                    <div class="hero-btns">
                        <a href="<?= BASE_URL ?>/login.php" class="btn-hero-primary"><i
                                class="fas fa-rocket me-2"></i>Get Started</a>
                        <a href="#features" class="btn-hero-outline"><i class="fas fa-play-circle me-2"></i>Explore
                            Features</a>
                    </div>
                </div>
                <div class="col-lg-6 hero-visual">
                    <div class="hero-card">
                        <div class="hero-logo-float"><img src="<?= BASE_URL ?>/images/logo.png" alt="MOEEN "></div>
                        <div class="row g-3">
                            <div class="col-4">
                                <div class="hero-stat">
                                    <div class="hero-stat-value" style="color:var(--accent)">98%</div>
                                    <div class="hero-stat-label">Detection Rate</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="hero-stat">
                                    <div class="hero-stat-value">4</div>
                                    <div class="hero-stat-label">Risk Levels</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="hero-stat">
                                    <div class="hero-stat-value" style="color:#f39c12">Live</div>
                                    <div class="hero-stat-label">Monitoring</div>
                                </div>
                            </div>
                        </div>
                        <hr style="border-color:rgba(255,255,255,.1);margin:20px 0">
                        <div class="d-flex gap-2 flex-wrap">
                            <span
                                style="background:rgba(39,174,96,.15);color:#27ae60;padding:5px 12px;border-radius:20px;font-size:.72rem;font-weight:600"><i
                                    class="fas fa-check-circle me-1"></i>Stable: 3</span>
                            <span
                                style="background:rgba(243,156,18,.15);color:#f39c12;padding:5px 12px;border-radius:20px;font-size:.72rem;font-weight:600"><i
                                    class="fas fa-eye me-1"></i>Monitor: 1</span>
                            <span
                                style="background:rgba(231,76,60,.15);color:#e74c3c;padding:5px 12px;border-radius:20px;font-size:.72rem;font-weight:600"><i
                                    class="fas fa-exclamation-triangle me-1"></i>At Risk: 1</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="section section-light" id="features">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Powerful Features</h2>
                <p class="section-subtitle">Everything you need to manage academic assessments, monitor performance, and
                    support students proactively.</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon blue"><i class="fas fa-clipboard-list"></i></div>
                        <h5>Assessment Management</h5>
                        <p>Create, organize, and manage quizzes, midterms, finals, projects, and assignments with
                            flexible grading weights.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon green"><i class="fas fa-chart-line"></i></div>
                        <h5>Performance Analytics</h5>
                        <p>Track student performance with trend analysis, radar charts, and percentage breakdowns across
                            all courses.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon red"><i class="fas fa-exclamation-triangle"></i></div>
                        <h5>Early Risk Detection</h5>
                        <p>Automated 4-level risk scoring (Stable, Monitor, At Risk, High Risk) using grade trends,
                            workload density, and patterns.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon orange"><i class="fas fa-calendar-alt"></i></div>
                        <h5>Workload Analysis</h5>
                        <p>Weekly and monthly assessment density heatmaps help identify overloaded periods and prevent
                            academic burnout.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon purple"><i class="fas fa-headset"></i></div>
                        <h5>Advisor Communication</h5>
                        <p>Direct student-to-advisor contact with file attachments, priority levels, and threaded
                            conversation tracking.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon teal"><i class="fas fa-bell"></i></div>
                        <h5>Smart Notifications</h5>
                        <p>Real-time alerts for new grades, academic risks, contact requests, and assessment reminders —
                            always stay informed.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Users -->
    <section class="section" id="users">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Designed for Every Role</h2>
                <p class="section-subtitle">Each user type gets a tailored experience with role-specific dashboards and
                    features.</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="user-type">
                            <div class="user-type-icon admin"><i class="fas fa-shield-alt"></i></div>
                            <h5>Admin</h5>
                            <p>Full system control and monitoring</p>
                            <ul>
                                <li><i class="fas fa-check"></i>User & role management</li>
                                <li><i class="fas fa-check"></i>Course & section setup</li>
                                <li><i class="fas fa-check"></i>System-wide reports</li>
                                <li><i class="fas fa-check"></i>Settings & configuration</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="user-type">
                            <div class="user-type-icon faculty"><i class="fas fa-chalkboard-teacher"></i></div>
                            <h5>Faculty</h5>
                            <p>Assessment & grade management</p>
                            <ul>
                                <li><i class="fas fa-check"></i>Create assessments</li>
                                <li><i class="fas fa-check"></i>Enter & manage grades</li>
                                <li><i class="fas fa-check"></i>Monitor students</li>
                                <li><i class="fas fa-check"></i>Workload density view</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="user-type">
                            <div class="user-type-icon advisor"><i class="fas fa-user-tie"></i></div>
                            <h5>Advisor</h5>
                            <p>Student guidance & follow-up</p>
                            <ul>
                                <li><i class="fas fa-check"></i>Risk monitoring</li>
                                <li><i class="fas fa-check"></i>Contact request handling</li>
                                <li><i class="fas fa-check"></i>Academic notes</li>
                                <li><i class="fas fa-check"></i>Performance reports</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="user-type">
                            <div class="user-type-icon student"><i class="fas fa-user-graduate"></i></div>
                            <h5>Student</h5>
                            <p>Academic visibility & support</p>
                            <ul>
                                <li><i class="fas fa-check"></i>View grades & assessments</li>
                                <li><i class="fas fa-check"></i>Weekly workload view</li>
                                <li><i class="fas fa-check"></i>Risk level awareness</li>
                                <li><i class="fas fa-check"></i>Contact advisor</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="section section-light" id="how">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">How MOEEN Works</h2>
                <p class="section-subtitle">A seamless workflow that connects faculty, advisors, and students for
                    academic success.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-num">1</div>
                        <h5>Setup</h5>
                        <p>Admin creates courses, sections, and assigns faculty and advisors.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-num">2</div>
                        <h5>Assess</h5>
                        <p>Faculty creates assessments, enters grades, and monitors completion.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-num">3</div>
                        <h5>Analyze</h5>
                        <p>System auto-calculates risk scores and generates alerts for at-risk students.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-num">4</div>
                        <h5>Intervene</h5>
                        <p>Advisors review alerts, respond to students, and provide academic guidance.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="container text-center position-relative" style="z-index:1">
            <h2>Ready to Transform Academic Monitoring?</h2>
            <p>Start using MOEEN today and empower your institution with data-driven academic support.</p>
            <a href="<?= BASE_URL ?>/login.php" class="btn-hero-primary"><i class="fas fa-sign-in-alt me-2"></i>Sign In
                Now</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="footer-brand">
                        <img src="<?= BASE_URL ?>/images/logo.png" alt="MOEEN ">
                        <h5>MOEEN </h5>
                        <p>Academic Assessment Load & Performance Analysis System — empowering institutions for academic
                            excellence.</p>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <div class="footer-links">
                        <h6>Platform</h6>
                        <a href="#features">Features</a>
                        <a href="#users">Users</a>
                        <a href="#how">How It Works</a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <div class="footer-links">
                        <h6>Access</h6>
                        <a href="<?= BASE_URL ?>/login.php">Sign In</a>
                        <a href="<?= BASE_URL ?>/forgot-password.php">Forgot Password</a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4">
                    <div class="footer-links">
                        <h6>About</h6>
                        <p style="font-size:.82rem;line-height:1.6">MOEEN is a graduation project designed to help
                            educational institutions monitor student academic performance and provide early intervention
                            for at-risk students.</p>
                    </div>
                </div>
            </div>
            <div class="footer-bottom text-center">
                <p>&copy; <?= date('Y') ?> MOEEN . All rights reserved. Built for academic excellence.</p>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 50);
        });
    </script>
</body>

</html>