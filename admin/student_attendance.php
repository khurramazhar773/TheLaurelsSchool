<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$pdo = getDBConnection();
$message = '';
$success = '';

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

// Get filter parameters
$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$attendanceDate = $_GET['date'] ?? date('Y-m-d');
$statusFilter = $_GET['status'] ?? '';

// Load classes for dropdown
$classes = [];
try {
    $stmt = $pdo->query("SELECT id, name, academic_year FROM classes ORDER BY created_at DESC");
    $classes = $stmt->fetchAll();
} catch (Exception $e) {
    $message = 'Error loading classes: ' . $e->getMessage();
}

// Load students for selected class
$students = [];
$class = null;
if ($classId > 0) {
    try {
        // Get class info
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
        $stmt->execute([$classId]);
        $class = $stmt->fetch();
        
        if ($class) {
            // Get enrolled students
            $stmt = $pdo->prepare("
                SELECT s.id, s.first_name, s.middle_name, s.last_name, s.student_code, s.phone
                FROM enrollments e 
                JOIN students s ON s.id = e.student_id 
                WHERE e.class_id = ? AND e.status = 'active' 
                ORDER BY s.first_name, s.last_name
            ");
            $stmt->execute([$classId]);
            $students = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        $message = 'Error loading students: ' . $e->getMessage();
    }
}

// Load existing attendance for the selected date and class
$attendanceRecords = [];
if ($classId > 0 && $attendanceDate) {
    try {
        $stmt = $pdo->prepare("
            SELECT sa.*, s.first_name, s.middle_name, s.last_name, s.student_code
            FROM student_attendance sa
            JOIN students s ON s.id = sa.student_id
            WHERE sa.class_id = ? AND sa.attendance_date = ?
            ORDER BY s.first_name, s.last_name
        ");
        $stmt->execute([$classId, $attendanceDate]);
        $attendanceRecords = $stmt->fetchAll();
    } catch (Exception $e) {
        $message = 'Error loading attendance records: ' . $e->getMessage();
    }
}

// Create attendance map for easy lookup
$attendanceMap = [];
foreach ($attendanceRecords as $record) {
    $attendanceMap[$record['student_id']] = $record;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'mark_attendance') {
            $pdo->beginTransaction();
            
            $attendanceData = $_POST['attendance'] ?? [];
            $remarks = $_POST['remarks'] ?? [];
            
            foreach ($attendanceData as $studentId => $status) {
                $studentId = (int)$studentId;
                $status = sanitizeInput($status);
                $remark = sanitizeInput($remarks[$studentId] ?? '');
                
                if (!in_array($status, ['present', 'absent', 'late', 'excused'])) {
                    $status = 'absent';
                }
                
                // Check if record exists
                $checkStmt = $pdo->prepare("SELECT id FROM student_attendance WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
                $checkStmt->execute([$studentId, $classId, $attendanceDate]);
                $existingRecord = $checkStmt->fetch();
                
                if ($existingRecord) {
                    // Update existing record
                    $stmt = $pdo->prepare("UPDATE student_attendance SET status = ?, remarks = ?, marked_by = ?, updated_at = NOW() WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
                    $stmt->execute([$status, $remark, $_SESSION['user_id'], $studentId, $classId, $attendanceDate]);
                } else {
                    // Insert new record
                    $stmt = $pdo->prepare("INSERT INTO student_attendance (student_id, class_id, attendance_date, status, marked_by, remarks) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$studentId, $classId, $attendanceDate, $status, $_SESSION['user_id'], $remark]);
                }
            }
            
            $pdo->commit();
            $success = 'Attendance marked successfully!';
            
            // Reload attendance records
            $stmt = $pdo->prepare("
                SELECT sa.*, s.first_name, s.middle_name, s.last_name, s.student_code
                FROM student_attendance sa
                JOIN students s ON s.id = sa.student_id
                WHERE sa.class_id = ? AND sa.attendance_date = ?
                ORDER BY s.first_name, s.last_name
            ");
            $stmt->execute([$classId, $attendanceDate]);
            $attendanceRecords = $stmt->fetchAll();
            
            // Recreate attendance map
            $attendanceMap = [];
            foreach ($attendanceRecords as $record) {
                $attendanceMap[$record['student_id']] = $record;
            }
            
        } elseif ($_POST['action'] === 'bulk_mark') {
            $bulkStatus = sanitizeInput($_POST['bulk_status'] ?? '');
            $selectedStudents = $_POST['selected_students'] ?? [];
            
            if (!in_array($bulkStatus, ['present', 'absent', 'late', 'excused'])) {
                throw new Exception('Invalid bulk status selected');
            }
            
            if (empty($selectedStudents)) {
                throw new Exception('No students selected for bulk marking');
            }
            
            $pdo->beginTransaction();
            
            foreach ($selectedStudents as $studentId) {
                $studentId = (int)$studentId;
                
                // Check if record exists
                $checkStmt = $pdo->prepare("SELECT id FROM student_attendance WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
                $checkStmt->execute([$studentId, $classId, $attendanceDate]);
                $existingRecord = $checkStmt->fetch();
                
                if ($existingRecord) {
                    // Update existing record
                    $stmt = $pdo->prepare("UPDATE student_attendance SET status = ?, marked_by = ?, updated_at = NOW() WHERE student_id = ? AND class_id = ? AND attendance_date = ?");
                    $stmt->execute([$bulkStatus, $_SESSION['user_id'], $studentId, $classId, $attendanceDate]);
                } else {
                    // Insert new record
                    $stmt = $pdo->prepare("INSERT INTO student_attendance (student_id, class_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$studentId, $classId, $attendanceDate, $bulkStatus, $_SESSION['user_id']]);
                }
            }
            
            $pdo->commit();
            $success = 'Bulk attendance marked successfully!';
            
            // Reload attendance records
            $stmt = $pdo->prepare("
                SELECT sa.*, s.first_name, s.middle_name, s.last_name, s.student_code
                FROM student_attendance sa
                JOIN students s ON s.id = sa.student_id
                WHERE sa.class_id = ? AND sa.attendance_date = ?
                ORDER BY s.first_name, s.last_name
            ");
            $stmt->execute([$classId, $attendanceDate]);
            $attendanceRecords = $stmt->fetchAll();
            
            // Recreate attendance map
            $attendanceMap = [];
            foreach ($attendanceRecords as $record) {
                $attendanceMap[$record['student_id']] = $record;
            }
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = 'Error: ' . $e->getMessage();
    }
}

// Get attendance statistics for the selected date and class
$attendanceStats = [
    'total' => count($students),
    'present' => 0,
    'absent' => 0,
    'late' => 0,
    'excused' => 0,
    'unmarked' => 0
];

foreach ($students as $student) {
    if (isset($attendanceMap[$student['id']])) {
        $status = $attendanceMap[$student['id']]['status'];
        $attendanceStats[$status]++;
    } else {
        $attendanceStats['unmarked']++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        .attendance-card {
            transition: all 0.3s ease;
        }
        .attendance-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .status-present { background-color: #d4edda; border-left: 4px solid #28a745; }
        .status-absent { background-color: #f8d7da; border-left: 4px solid #dc3545; }
        .status-late { background-color: #fff3cd; border-left: 4px solid #ffc107; }
        .status-excused { background-color: #d1ecf1; border-left: 4px solid #17a2b8; }
        .status-unmarked { background-color: #f8f9fa; border-left: 4px solid #6c757d; }
        .bulk-actions {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php $active='attendance'; include __DIR__.'/partials/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3"><i class="fas fa-user-check me-2"></i>Student Attendance Management</h1>
            <div>
                <a href="attendance_settings.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-cog me-2"></i>Settings
                </a>
                <a href="attendance_reports.php" class="btn btn-outline-primary">
                    <i class="fas fa-chart-bar me-2"></i>Reports
                </a>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Select Class and Date</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="class_id" class="form-label">Class</label>
                                <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()">
                                    <option value="0">Select Class</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo $classId == $c['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['name'] . ' (' . $c['academic_year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" name="date" id="date" class="form-control" value="<?php echo htmlspecialchars($attendanceDate); ?>" onchange="this.form.submit()">
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">Filter by Status</label>
                                <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="present" <?php echo $statusFilter === 'present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="absent" <?php echo $statusFilter === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="late" <?php echo $statusFilter === 'late' ? 'selected' : ''; ?>>Late</option>
                                    <option value="excused" <?php echo $statusFilter === 'excused' ? 'selected' : ''; ?>>Excused</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($classId > 0 && $class): ?>
            <!-- Attendance Statistics -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                Attendance Summary - <?php echo htmlspecialchars($class['name']); ?> 
                                <small class="text-muted">(<?php echo date('M d, Y', strtotime($attendanceDate)); ?>)</small>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <div class="h4 text-primary"><?php echo $attendanceStats['total']; ?></div>
                                        <small class="text-muted">Total Students</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <div class="h4 text-success"><?php echo $attendanceStats['present']; ?></div>
                                        <small class="text-muted">Present</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <div class="h4 text-danger"><?php echo $attendanceStats['absent']; ?></div>
                                        <small class="text-muted">Absent</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <div class="h4 text-warning"><?php echo $attendanceStats['late']; ?></div>
                                        <small class="text-muted">Late</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <div class="h4 text-info"><?php echo $attendanceStats['excused']; ?></div>
                                        <small class="text-muted">Excused</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <div class="h4 text-secondary"><?php echo $attendanceStats['unmarked']; ?></div>
                                        <small class="text-muted">Unmarked</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="bulk-actions">
                        <form method="POST" id="bulkForm">
                            <input type="hidden" name="action" value="bulk_mark">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label text-white">Bulk Action</label>
                                    <select name="bulk_status" class="form-select" required>
                                        <option value="">Select Status</option>
                                        <option value="present">Mark All Present</option>
                                        <option value="absent">Mark All Absent</option>
                                        <option value="late">Mark All Late</option>
                                        <option value="excused">Mark All Excused</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white">Select Students</label>
                                    <div class="form-check">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                        <label for="selectAll" class="form-check-label text-white">Select All Students</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-light" onclick="return confirm('Are you sure you want to apply bulk action to selected students?')">
                                        <i class="fas fa-check-double me-2"></i>Apply Bulk Action
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Attendance Form -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Mark Attendance</h5>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="markAllPresent()">
                                    <i class="fas fa-check me-1"></i>All Present
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="markAllAbsent()">
                                    <i class="fas fa-times me-1"></i>All Absent
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($students)): ?>
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No students enrolled in this class.
                                </div>
                            <?php else: ?>
                                <form method="POST" id="attendanceForm">
                                    <input type="hidden" name="action" value="mark_attendance">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th width="5%">
                                                        <input type="checkbox" id="selectAllTable" class="form-check-input">
                                                    </th>
                                                    <th width="10%">Student ID</th>
                                                    <th width="25%">Student Name</th>
                                                    <th width="15%">Phone</th>
                                                    <th width="15%">Status</th>
                                                    <th width="20%">Remarks</th>
                                                    <th width="10%">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students as $student): ?>
                                                    <?php 
                                                    $attendance = $attendanceMap[$student['id']] ?? null;
                                                    $currentStatus = $attendance ? $attendance['status'] : 'unmarked';
                                                    $statusClass = 'status-' . $currentStatus;
                                                    ?>
                                                    <tr class="attendance-card <?php echo $statusClass; ?>">
                                                        <td>
                                                            <input type="checkbox" name="selected_students[]" value="<?php echo $student['id']; ?>" class="form-check-input student-checkbox">
                                                        </td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($student['student_code'] ?? 'N/A'); ?></strong>
                                                        </td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']); ?></strong>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                                                        <td>
                                                            <select name="attendance[<?php echo $student['id']; ?>]" class="form-select form-select-sm status-select" data-student-id="<?php echo $student['id']; ?>">
                                                                <option value="unmarked" <?php echo $currentStatus === 'unmarked' ? 'selected' : ''; ?>>Unmarked</option>
                                                                <option value="present" <?php echo $currentStatus === 'present' ? 'selected' : ''; ?>>Present</option>
                                                                <option value="absent" <?php echo $currentStatus === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                                                <option value="late" <?php echo $currentStatus === 'late' ? 'selected' : ''; ?>>Late</option>
                                                                <option value="excused" <?php echo $currentStatus === 'excused' ? 'selected' : ''; ?>>Excused</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="remarks[<?php echo $student['id']; ?>]" class="form-control form-control-sm" 
                                                                   value="<?php echo htmlspecialchars($attendance['remarks'] ?? ''); ?>" 
                                                                   placeholder="Optional remarks...">
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-sm btn-success" onclick="markStudent(<?php echo $student['id']; ?>, 'present')" title="Mark Present">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-danger" onclick="markStudent(<?php echo $student['id']; ?>, 'absent')" title="Mark Absent">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-warning" onclick="markStudent(<?php echo $student['id']; ?>, 'late')" title="Mark Late">
                                                                    <i class="fas fa-clock"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between mt-3">
                                        <div>
                                            <button type="button" class="btn btn-outline-secondary" onclick="clearAll()">
                                                <i class="fas fa-eraser me-2"></i>Clear All
                                            </button>
                                        </div>
                                        <div>
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-save me-2"></i>Save Attendance
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                Please select a class and date to view and mark attendance.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        document.getElementById('selectAllTable').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Status change handler
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                const studentId = this.dataset.studentId;
                const row = this.closest('tr');
                const status = this.value;
                
                // Remove all status classes
                row.classList.remove('status-present', 'status-absent', 'status-late', 'status-excused', 'status-unmarked');
                
                // Add new status class
                if (status !== 'unmarked') {
                    row.classList.add('status-' + status);
                } else {
                    row.classList.add('status-unmarked');
                }
            });
        });

        // Mark individual student
        function markStudent(studentId, status) {
            const select = document.querySelector(`select[data-student-id="${studentId}"]`);
            const row = select.closest('tr');
            
            select.value = status;
            
            // Remove all status classes
            row.classList.remove('status-present', 'status-absent', 'status-late', 'status-excused', 'status-unmarked');
            
            // Add new status class
            row.classList.add('status-' + status);
        }

        // Mark all present
        function markAllPresent() {
            document.querySelectorAll('.status-select').forEach(select => {
                select.value = 'present';
                const row = select.closest('tr');
                row.classList.remove('status-present', 'status-absent', 'status-late', 'status-excused', 'status-unmarked');
                row.classList.add('status-present');
            });
        }

        // Mark all absent
        function markAllAbsent() {
            document.querySelectorAll('.status-select').forEach(select => {
                select.value = 'absent';
                const row = select.closest('tr');
                row.classList.remove('status-present', 'status-absent', 'status-late', 'status-excused', 'status-unmarked');
                row.classList.add('status-absent');
            });
        }

        // Clear all
        function clearAll() {
            document.querySelectorAll('.status-select').forEach(select => {
                select.value = 'unmarked';
                const row = select.closest('tr');
                row.classList.remove('status-present', 'status-absent', 'status-late', 'status-excused', 'status-unmarked');
                row.classList.add('status-unmarked');
            });
        }

        // Auto-save functionality (optional)
        let autoSaveTimeout;
        document.querySelectorAll('.status-select, input[name^="remarks"]').forEach(element => {
            element.addEventListener('change', function() {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(() => {
                    // Optional: Auto-save after 2 seconds of inactivity
                    // document.getElementById('attendanceForm').submit();
                }, 2000);
            });
        });
    </script>
</body>
</html>
