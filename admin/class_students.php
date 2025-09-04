<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }

$pdo = getDBConnection();
$message = '';

// Ensure enrollments table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS enrollments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  student_id INT NOT NULL,
  class_id INT NOT NULL,
  enrollment_date DATE NOT NULL,
  status ENUM('active','completed','dropped') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_enrollment (student_id, class_id),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
if ($classId <= 0) { redirect('classes.php'); }

// Fetch class
$stmt = $pdo->prepare('SELECT * FROM classes WHERE id=?');
$stmt->execute([$classId]);
$class = $stmt->fetch();
if (!$class) { redirect('classes.php'); }

// Handle enroll
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enroll') {
    try {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $date = $_POST['enrollment_date'] ?? date('Y-m-d');
        $status = in_array(($_POST['status'] ?? 'active'), ['active','completed','dropped']) ? $_POST['status'] : 'active';
        if ($studentId <= 0) throw new Exception('Select a student.');
        
        // Block if student already has an active enrollment in any class
        $chk = $pdo->prepare('SELECT e.class_id, c.name FROM enrollments e JOIN classes c ON c.id = e.class_id WHERE e.student_id = ? AND e.status = "active"');
        $chk->execute([$studentId]);
        $existing = $chk->fetch();
        if ($existing && (int)$existing['class_id'] !== $classId) {
            throw new Exception('This student is already enrolled in an active class: '.htmlspecialchars($existing['name']).'. Unenroll or complete that enrollment first.');
        }
        
        $stmt = $pdo->prepare('INSERT INTO enrollments (student_id, class_id, enrollment_date, status, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE enrollment_date=VALUES(enrollment_date), status=VALUES(status), updated_at=NOW()');
        $stmt->execute([$studentId, $classId, $date, $status]);
        header('Location: class_students.php?class_id='.$classId.'&enrolled=1'); exit;
    } catch (Exception $e) { $message = $e->getMessage(); }
}

// Handle update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    try {
        $id = (int)$_POST['id'];
        $status = in_array(($_POST['status'] ?? 'active'), ['active','completed','dropped']) ? $_POST['status'] : 'active';
        if ($id <= 0) throw new Exception('Invalid enrollment.');
        $stmt = $pdo->prepare('UPDATE enrollments SET status=?, updated_at=NOW() WHERE id=? AND class_id=?');
        $stmt->execute([$status, $id, $classId]);
        header('Location: class_students.php?class_id='.$classId.'&updated=1'); exit;
    } catch (Exception $e) { $message = $e->getMessage(); }
}

// Handle unenroll
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'unenroll') {
    try {
        $id = (int)$_POST['id'];
        if ($id <= 0) throw new Exception('Invalid enrollment.');
        $pdo->prepare('DELETE FROM enrollments WHERE id=? AND class_id=?')->execute([$id, $classId]);
        header('Location: class_students.php?class_id='.$classId.'&unenrolled=1'); exit;
    } catch (Exception $e) { $message = $e->getMessage(); }
}

// Load available students (not enrolled active in ANY class)
$allStudents = $pdo->query("SELECT id, first_name, middle_name, last_name, phone FROM students ORDER BY first_name, last_name")->fetchAll();
$activeIdsStmt = $pdo->query('SELECT DISTINCT student_id FROM enrollments WHERE status = "active"');
$activeIds = array_map(fn($r)=> (int)$r['student_id'], $activeIdsStmt->fetchAll());
$available = array_filter($allStudents, fn($s)=> !in_array((int)$s['id'], $activeIds, true));

// Load enrolled list
$enrolled = $pdo->prepare('SELECT e.id, e.enrollment_date, e.status, s.first_name, s.middle_name, s.last_name, s.phone, s.id AS student_id
                           FROM enrollments e JOIN students s ON s.id=e.student_id WHERE e.class_id=? ORDER BY s.first_name, s.last_name');
$enrolled->execute([$classId]);
$enrolled = $enrolled->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Students - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
<?php $active='classes'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <?php if (isset($_GET['enrolled'])): ?><div class="alert alert-success">Student enrolled.</div><?php endif; ?>
    <?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Enrollment updated.</div><?php endif; ?>
    <?php if (isset($_GET['unenrolled'])): ?><div class="alert alert-success">Student unenrolled.</div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-danger"><?php echo $message; ?></div><?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Students in <?php echo htmlspecialchars($class['name']); ?> (<?php echo htmlspecialchars($class['academic_year']); ?>)</h1>
        <a href="classes.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Classes</a>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="action" value="enroll">
            <div class="col-md-5">
                <label class="form-label">Student</label>
                <select name="student_id" class="form-select" required>
                    <option value="">Select student</option>
                    <?php foreach ($available as $s): $full = trim($s['first_name'].' '.($s['middle_name']??'').' '.$s['last_name']); ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($full); ?><?php echo $s['phone']? ' — '.htmlspecialchars($s['phone']):''; ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Only students without an active enrollment are listed.</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">Enrollment Date</label>
                <input type="date" name="enrollment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="dropped">Dropped</option>
                </select>
            </div>
            <div class="col-md-2 align-self-end">
                <button class="btn btn-primary"><i class="fas fa-user-plus me-2"></i>Enroll</button>
            </div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <div class="table-responsive rounded-lg">
            <table class="table table-striped">
                <thead><tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Phone</th>
                    <th>Enrollment Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($enrolled as $row): $full = trim($row['first_name'].' '.($row['middle_name']??'').' '.$row['last_name']); ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($full); ?></td>
                        <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['enrollment_date']); ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="active" <?php echo $row['status']==='active'?'selected':''; ?>>Active</option>
                                    <option value="completed" <?php echo $row['status']==='completed'?'selected':''; ?>>Completed</option>
                                    <option value="dropped" <?php echo $row['status']==='dropped'?'selected':''; ?>>Dropped</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Unenroll this student from the class?');" class="d-inline">
                                <input type="hidden" name="action" value="unenroll">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-user-minus"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 