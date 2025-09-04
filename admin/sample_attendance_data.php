<?php
/**
 * Sample Attendance Data Generator
 * This file creates sample attendance data for testing purposes
 * Run this once to populate the database with test data
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$pdo = getDBConnection();
$message = '';

// Ensure student_attendance table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS student_attendance (
  id INT PRIMARY KEY AUTO_INCREMENT,
  student_id INT NOT NULL,
  class_id INT NOT NULL,
  attendance_date DATE NOT NULL,
  status ENUM('present','absent','late','excused') DEFAULT 'absent',
  marked_by INT NOT NULL,
  remarks TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_student_date (student_id, attendance_date),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_data'])) {
    try {
        $pdo->beginTransaction();
        
        // Get existing students and classes
        $students = $pdo->query("SELECT id FROM students WHERE status = 'active'")->fetchAll();
        $classes = $pdo->query("SELECT id FROM classes")->fetchAll();
        $adminId = $_SESSION['user_id'];
        
        if (empty($students) || empty($classes)) {
            throw new Exception('No students or classes found. Please add some students and classes first.');
        }
        
        // Generate attendance for the last 7 days
        $statuses = ['present', 'absent', 'late', 'excused'];
        $statusWeights = [70, 20, 8, 2]; // 70% present, 20% absent, 8% late, 2% excused
        
        $totalRecords = 0;
        
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            
            foreach ($classes as $class) {
                // Get students enrolled in this class
                $stmt = $pdo->prepare("
                    SELECT s.id 
                    FROM enrollments e 
                    JOIN students s ON s.id = e.student_id 
                    WHERE e.class_id = ? AND e.status = 'active'
                ");
                $stmt->execute([$class['id']]);
                $classStudents = $stmt->fetchAll();
                
                foreach ($classStudents as $student) {
                    // Randomly select status based on weights
                    $rand = mt_rand(1, 100);
                    $cumulative = 0;
                    $selectedStatus = 'present';
                    
                    for ($j = 0; $j < count($statuses); $j++) {
                        $cumulative += $statusWeights[$j];
                        if ($rand <= $cumulative) {
                            $selectedStatus = $statuses[$j];
                            break;
                        }
                    }
                    
                    // Skip some students randomly (simulate unmarked attendance)
                    if (mt_rand(1, 100) <= 10) { // 10% chance of being unmarked
                        continue;
                    }
                    
                    // Add some remarks for certain statuses
                    $remarks = '';
                    if ($selectedStatus === 'late') {
                        $remarks = 'Arrived after 8:15 AM';
                    } elseif ($selectedStatus === 'excused') {
                        $remarks = 'Medical appointment';
                    } elseif ($selectedStatus === 'absent') {
                        $remarks = 'Sick leave';
                    }
                    
                    // Check if record already exists
                    $checkStmt = $pdo->prepare("SELECT id FROM student_attendance WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
                    $checkStmt->execute([$student['id'], $class['id'], $date]);
                    
                    if (!$checkStmt->fetch()) {
                        $stmt = $pdo->prepare("
                            INSERT INTO student_attendance (student_id, class_id, attendance_date, status, marked_by, remarks) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$student['id'], $class['id'], $date, $selectedStatus, $adminId, $remarks]);
                        $totalRecords++;
                    }
                }
            }
        }
        
        $pdo->commit();
        $message = "Successfully generated $totalRecords attendance records for the last 7 days!";
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = 'Error: ' . $e->getMessage();
    }
}

// Get current attendance statistics
$stats = [];
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM student_attendance");
    $stats['total_records'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM student_attendance WHERE attendance_date = CURDATE()");
    $stats['today_records'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM student_attendance WHERE status = 'present'");
    $stats['present_records'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM student_attendance WHERE status = 'absent'");
    $stats['absent_records'] = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    $stats = ['total_records' => 0, 'today_records' => 0, 'present_records' => 0, 'absent_records' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Attendance Data - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
    <?php $active='attendance'; include __DIR__.'/partials/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3"><i class="fas fa-database me-2"></i>Sample Attendance Data Generator</h1>
            <a href="student_attendance.php" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Attendance
            </a>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo strpos($message, 'Error') !== false ? 'exclamation-triangle' : 'check-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Current Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Current Attendance Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-primary"><?php echo $stats['total_records']; ?></div>
                                    <small class="text-muted">Total Records</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-info"><?php echo $stats['today_records']; ?></div>
                                    <small class="text-muted">Today's Records</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-success"><?php echo $stats['present_records']; ?></div>
                                    <small class="text-muted">Present Records</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-danger"><?php echo $stats['absent_records']; ?></div>
                                    <small class="text-muted">Absent Records</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Generation Form -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Generate Sample Data</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> This will generate sample attendance data for the last 7 days. 
                            The data will be randomly distributed with approximately 70% present, 20% absent, 8% late, and 2% excused.
                        </div>
                        
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-12">
                                    <h6>What will be generated:</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>Attendance records for the last 7 days</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Random status distribution (Present/Absent/Late/Excused)</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Sample remarks for different statuses</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Data for all active students in all classes</li>
                                    </ul>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="generate_data" class="btn btn-primary btn-lg" onclick="return confirm('Are you sure you want to generate sample attendance data? This will add records to the database.')">
                                        <i class="fas fa-magic me-2"></i>Generate Sample Data
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <a href="student_attendance.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-users me-2"></i>View Student Attendance
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="attendance_reports.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-chart-bar me-2"></i>Attendance Reports
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="attendance_settings.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-cog me-2"></i>Attendance Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
