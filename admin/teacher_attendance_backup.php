<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }

$pdo = getDBConnection();
$message = '';

// Ensure attendance table has entry/exit and computed flags
$pdo->exec("CREATE TABLE IF NOT EXISTS teacher_attendance (
  id INT PRIMARY KEY AUTO_INCREMENT,
  teacher_id INT NOT NULL,
  attendance_date DATE NOT NULL,
  entry_time TIME DEFAULT NULL,
  exit_time TIME DEFAULT NULL,
  status ENUM('present','absent','late') NOT NULL DEFAULT 'present',
  is_late TINYINT(1) NOT NULL DEFAULT 0,
  left_early TINYINT(1) NOT NULL DEFAULT 0,
  remarks VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_teacher_date (teacher_id, attendance_date),
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Load settings
$settings = $pdo->query("SELECT duty_start, duty_end, grace_minutes, early_grace_minutes FROM attendance_settings WHERE id=1")->fetch();
$dutyStart = $settings['duty_start'] ?? '08:00:00';
$dutyEnd = $settings['duty_end'] ?? '14:00:00';
$grace = (int)($settings['grace_minutes'] ?? 0);
$earlyGrace = (int)($settings['early_grace_minutes'] ?? 0);

// Handle mark attendance (entry/exit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark') {
    try {
        $teacherId = (int)($_POST['teacher_id'] ?? 0);
        $date = $_POST['attendance_date'] ?? date('Y-m-d');
        $entry = $_POST['entry_time'] ?? null;
        $exit = $_POST['exit_time'] ?? null;
        $remarks = trim($_POST['remarks'] ?? '');
        if ($teacherId <= 0) throw new Exception('Invalid teacher.');

        // Compute late/early
        $isLate = 0; $leftEarly = 0; $status = 'present';
        if ($entry) {
            $allowedStart = (new DateTime($dutyStart))->modify("+{$grace} minutes")->format('H:i:s');
            if ($entry > $allowedStart) { $isLate = 1; $status = 'late'; }
        }
        if ($exit) {
            $allowedEnd = (new DateTime($dutyEnd))->modify("-{$earlyGrace} minutes")->format('H:i:s');
            if ($exit < $allowedEnd) { $leftEarly = 1; }
        }

        // Upsert
        $stmt = $pdo->prepare("INSERT INTO teacher_attendance (teacher_id, attendance_date, entry_time, exit_time, status, is_late, left_early, remarks)
            VALUES (?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE entry_time=VALUES(entry_time), exit_time=VALUES(exit_time), status=VALUES(status), is_late=VALUES(is_late), left_early=VALUES(left_early), remarks=VALUES(remarks)");
        $stmt->execute([$teacherId, $date, $entry ?: null, $exit ?: null, $status, $isLate, $leftEarly, $remarks ?: null]);
        header('Location: teacher_attendance.php?marked=1&date=' . urlencode($date));
        exit;
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}

// Filters
$filterDate = $_GET['date'] ?? date('Y-m-d');
$filterStatus = $_GET['status'] ?? '';

// Load teachers (active)
$teachers = $pdo->query("SELECT id, first_name, last_name, email FROM users WHERE role = 'teacher' AND status <> 'suspended' ORDER BY first_name, last_name")->fetchAll();

// Load attendance for selected date
$params = [$filterDate];
$sqlAttendance = "SELECT ta.*, u.first_name, u.last_name, u.email
                  FROM teacher_attendance ta
                  JOIN users u ON u.id = ta.teacher_id
                  WHERE ta.attendance_date = ?";
if (in_array($filterStatus, ['present','absent','late'])) {
    $sqlAttendance .= " AND ta.status = ?";
    $params[] = $filterStatus;
}
$sqlAttendance .= " ORDER BY COALESCE(ta.entry_time, '23:59:59') ASC";
$attendanceRows = $pdo->prepare($sqlAttendance);
$attendanceRows->execute($params);
$attendanceRows = $attendanceRows->fetchAll();

// Build map teacher_id => record
$todayMap = [];
foreach ($attendanceRows as $r) { $todayMap[(int)$r['teacher_id']] = $r; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Attendance - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
<?php $active='attendance'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <?php if (isset($_GET['marked'])): ?><div class="alert alert-success">Attendance saved.</div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">Mark Attendance</h5></div>
        <div class="card-body">
            <form method="GET" class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($filterDate); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="present" <?php echo $filterStatus==='present'?'selected':''; ?>>Present</option>
                        <option value="absent" <?php echo $filterStatus==='absent'?'selected':''; ?>>Absent</option>
                        <option value="late" <?php echo $filterStatus==='late'?'selected':''; ?>>Late</option>
                    </select>
                </div>
                <div class="col-md-3 align-self-end"><button class="btn btn-primary"><i class="fas fa-filter me-2"></i>Apply</button></div>
                <div class="col-md-3 align-self-end text-md-end">
                    <a href="attendance_settings.php" class="btn btn-outline-secondary"><i class="fas fa-gear me-2"></i>Settings</a>
                </div>
            </form>

            <div class="table-responsive rounded-lg">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Email</th>
                            <th>Entry</th>
                            <th>Exit</th>
                            <th>Status</th>
                            <th>Late</th>
                            <th>Left Early</th>
                            <th>Mark</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $t): $tid=(int)$t['id']; $rec=$todayMap[$tid]??null; ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($t['first_name'].' '.$t['last_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($t['email']); ?></td>
                            <td><?php echo htmlspecialchars($rec['entry_time'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($rec['exit_time'] ?? '—'); ?></td>
                            <td><span class="badge <?php echo ($rec['status']??'present')==='late'?'bg-warning':'bg-success'; ?>"><?php echo htmlspecialchars($rec['status'] ?? 'present'); ?></span></td>
                            <td><?php echo !empty($rec['is_late'])?'Yes':'No'; ?></td>
                            <td><?php echo !empty($rec['left_early'])?'Yes':'No'; ?></td>
                            <td>
                                <form method="POST" class="d-flex gap-2 align-items-center">
                                    <input type="hidden" name="action" value="mark">
                                    <input type="hidden" name="teacher_id" value="<?php echo $tid; ?>">
                                    <input type="date" name="attendance_date" class="form-control form-control-sm" style="max-width: 150px;" value="<?php echo htmlspecialchars($filterDate); ?>">
                                    <input type="time" name="entry_time" class="form-control form-control-sm" style="max-width: 120px;" value="<?php echo htmlspecialchars($rec['entry_time'] ?? ''); ?>" placeholder="Entry">
                                    <input type="time" name="exit_time" class="form-control form-control-sm" style="max-width: 120px;" value="<?php echo htmlspecialchars($rec['exit_time'] ?? ''); ?>" placeholder="Exit">
                                    <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Remarks" style="max-width: 200px;" value="<?php echo htmlspecialchars($rec['remarks'] ?? ''); ?>">
                                    <button class="btn btn-sm btn-primary"><i class="fas fa-check me-1"></i>Save</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 