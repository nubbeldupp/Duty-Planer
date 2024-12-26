<?php
require_once __DIR__ . '/../vendor/autoload.php';

$auth = new \OnCallDutyPlanner\Classes\Authentication();
session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$current_role = $is_logged_in ? $_SESSION['role'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>On-Call Duty Planner</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.css">
</head>
<body>
    <div class="app-container">
        <header>
            <nav>
                <div class="logo">On-Call Duty Planner</div>
                <ul class="nav-links">
                    <?php if (!$is_logged_in): ?>
                        <li><a href="/login.php">Login</a></li>
                        <li><a href="/register.php">Register</a></li>
                    <?php else: ?>
                        <li><a href="/dashboard.php">Dashboard</a></li>
                        <?php if ($current_role === 'ADMIN'): ?>
                            <li><a href="/admin/users.php">User Management</a></li>
                        <?php endif; ?>
                        <?php if (in_array($current_role, ['ADMIN', 'TEAM_LEAD'])): ?>
                            <li><a href="/schedule/manage.php">Manage Schedules</a></li>
                        <?php endif; ?>
                        <li><a href="/logout.php">Logout</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>

        <main>
            <?php if (!$is_logged_in): ?>
                <section class="landing">
                    <h1>Streamline Your On-Call Duties</h1>
                    <p>Efficient scheduling for database teams across Oracle, Hana, MSSQL, and PostgreSQL</p>
                    <div class="cta-buttons">
                        <a href="/login.php" class="btn btn-primary">Login</a>
                        <a href="/register.php" class="btn btn-secondary">Register</a>
                    </div>
                </section>
            <?php else: ?>
                <section class="dashboard">
                    <div id="calendar"></div>
                </section>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; 2024 On-Call Duty Planner. All rights reserved.</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html>
