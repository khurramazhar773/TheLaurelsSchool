<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$pdo = getDBConnection();

// Get statistics
$stats = [];
try {
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students");
    $stats['total_students'] = $stmt->fetch()['total'];
    
    // Pending students
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE status = 'pending'");
    $stats['pending_students'] = $stmt->fetch()['total'];
    
    // Active students
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
    $stats['active_students'] = $stmt->fetch()['total'];
    
    // Recent applications (last 7 days)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['recent_applications'] = $stmt->fetch()['total'];
    
    // Today's attendance stats
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM student_attendance WHERE attendance_date = ?");
    $stmt->execute([$today]);
    $stats['attendance_marked'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM student_attendance WHERE attendance_date = ? AND status = 'present'");
    $stmt->execute([$today]);
    $stats['attendance_present'] = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    $stats = ['total_students' => 0, 'pending_students' => 0, 'active_students' => 0, 'recent_applications' => 0, 'attendance_marked' => 0, 'attendance_present' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
    <?php $active='dashboard'; include __DIR__.'/partials/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="h3 mb-4">Admin Dashboard</h1>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4 g-3">
            <div class="col-xl-3 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted">Total Students</div>
                                <div class="h4 mb-0"><?php echo $stats['total_students']; ?></div>
                            </div>
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted">Pending Applications</div>
                                <div class="h4 mb-0"><?php echo $stats['pending_students']; ?></div>
                            </div>
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted">Active Students</div>
                                <div class="h4 mb-0"><?php echo $stats['active_students']; ?></div>
                            </div>
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted">Recent Applications</div>
                                <div class="h4 mb-0"><?php echo $stats['recent_applications']; ?></div>
                            </div>
                            <i class="fas fa-calendar fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted">Today's Attendance</div>
                                <div class="h4 mb-0"><?php echo $stats['attendance_marked']; ?></div>
                                <small class="text-success"><?php echo $stats['attendance_present']; ?> Present</small>
                            </div>
                            <i class="fas fa-user-check fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Summary Widget -->
        <?php include 'attendance_summary.php'; ?>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="students.php?action=add" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Add New Student
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="students.php?status=pending" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-clock me-2"></i>Review Applications
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="users.php?action=add" class="btn btn-success w-100">
                                    <i class="fas fa-user-plus me-2"></i>Add New User
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="student_attendance.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-users me-2"></i>Student Attendance
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="teacher_attendance.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-chalkboard-teacher me-2"></i>Teacher Attendance
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="classes.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-layer-group me-2"></i>Manage Classes
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="subjects.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-book me-2"></i>Manage Subjects
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Applications -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Recent Applications</h5>
                        <a href="students.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive rounded-lg">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Application Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $stmt = $pdo->query("SELECT * FROM students ORDER BY created_at DESC LIMIT 5");
                                        while ($student = $stmt->fetch()) {
                                            $statusClass = '';
                                            switch ($student['status']) {
                                                case 'pending': $statusClass = 'badge bg-warning'; break;
                                                case 'active': $statusClass = 'badge bg-success'; break;
                                                case 'inactive': $statusClass = 'badge bg-secondary'; break;
                                                case 'withdrawn': $statusClass = 'badge bg-danger'; break;
                                                default: $statusClass = 'badge bg-secondary';
                                            }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                        </td>
                                        <td><span class="<?php echo $statusClass; ?>"><?php echo ucfirst($student['status']); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                        <td>
                                            <a href="students.php?action=view&id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="students.php?action=edit&id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                        }
                                    } catch (Exception $e) {
                                        echo '<tr><td colspan="4" class="text-center text-muted">No recent applications found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 