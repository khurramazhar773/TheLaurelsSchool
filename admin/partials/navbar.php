<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($active)) { $active = ''; }
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-graduation-cap me-2"></i>
            The Laurels School LMS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $active==='dashboard'?'active':''; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $active==='students'?'active':''; ?>" href="students.php">
                        <i class="fas fa-users me-1"></i>Students
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $active==='users'?'active':''; ?>" href="users.php">
                        <i class="fas fa-user-cog me-1"></i>Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $active==='classes'?'active':''; ?>" href="classes.php">
                        <i class="fas fa-layer-group me-1"></i>Classes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $active==='subjects'?'active':''; ?>" href="subjects.php">
                        <i class="fas fa-book me-1"></i>Subjects
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo $active==='attendance'?'active':''; ?>" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-check me-1"></i>Attendance
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="student_attendance.php"><i class="fas fa-users me-2"></i>Student Attendance</a></li>
                        <li><a class="dropdown-item" href="attendance_reports.php"><i class="fas fa-chart-bar me-2"></i>Attendance Reports</a></li>
                        <li><a class="dropdown-item" href="attendance_analytics.php"><i class="fas fa-chart-line me-2"></i>Analytics</a></li>
                        <li><a class="dropdown-item" href="attendance_notifications.php"><i class="fas fa-bell me-2"></i>Notifications</a></li>
                        <li><a class="dropdown-item" href="teacher_attendance.php"><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Attendance</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="attendance_settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $active==='reports'?'active':''; ?>" href="reports.php">
                        <i class="fas fa-chart-bar me-1"></i>Reports
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav> 