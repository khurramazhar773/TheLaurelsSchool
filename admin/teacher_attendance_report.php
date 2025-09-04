<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }

$pdo = getDBConnection();
$message = '';

// Load settings
$settings = $pdo->query("SELECT duty_start, grace_minutes FROM attendance_settings WHERE id = 1")->fetch();
if (!$settings) { $settings = ['duty_start' => '08:00:00', 'grace_minutes' => 0]; }
$dutyStart = $settings['duty_start'];
$graceMinutes = (int)$settings['grace_minutes'];

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-t');
$statusFilter = $_GET['status'] ?? 'late'; // default late

// Build report: count late days per teacher within range
$params = [$from, $to];
$sql = "SELECT u.id, u.first_name, u.last_name, u.email,
        SUM(CASE WHEN ta.status = 'late' THEN 1 ELSE 0 END) AS late_days,
        SUM(CASE WHEN ta.status = 'present' THEN 1 ELSE 0 END) AS present_days,
        SUM(CASE WHEN ta.status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
        COUNT(*) AS total_marked
        FROM users u
        LEFT JOIN teacher_attendance ta ON ta.teacher_id = u.id AND ta.attendance_date BETWEEN ? AND ?
        WHERE u.role = 'teacher' AND u.status <> 'suspended'
        GROUP BY u.id, u.first_name, u.last_name, u.email
        ORDER BY late_days DESC, u.first_name, u.last_name";
$rows = $pdo->prepare($sql);
$rows->execute($params);
$rows = $rows->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Late Report - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
<?php $active='attendance'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">Teacher Late Report</h1>
        <div>
            <a href="attendance_settings.php" class="btn btn-outline-secondary me-2"><i class="fas fa-gear me-2"></i>Settings</a>
            <a href="teacher_attendance.php" class="btn btn-outline-primary"><i class="fas fa-user-check me-2"></i>Mark Attendance</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">Filters</h5></div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($from); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($to); ?>">
                </div>
                <div class="col-md-3 align-self-end">
                    <button class="btn btn-primary"><i class="fas fa-filter me-2"></i>Apply</button>
                </div>
                <div class="col-md-3 align-self-end text-md-end">
                    <a href="teacher_attendance_report.php?from=<?php echo urlencode(date('Y-m-01')); ?>&to=<?php echo urlencode(date('Y-m-t')); ?>" class="btn btn-outline-secondary"><i class="fas fa-rotate me-2"></i>This Month</a>
                </div>
            </form>
            <p class="text-muted mt-3 mb-0">Duty start: <?php echo htmlspecialchars(substr($dutyStart,0,5)); ?>, Grace: <?php echo (int)$graceMinutes; ?> minutes.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Summary</h5>
            <a href="attendance_settings.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-gear me-1"></i>Adjust Duty Hours</a>
        </div>
        <div class="card-body">
            <div class="table-responsive rounded-lg">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Email</th>
                            <th>Late Days</th>
                            <th>Present Days</th>
                            <th>Absent Days</th>
                            <th>Total Marked</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="6" class="text-center text-muted">No data for selected range.</td></tr>
                        <?php else: foreach ($rows as $r): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['first_name'].' '.$r['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['email']); ?></td>
                                <td><span class="badge <?php echo ((int)$r['late_days']>0)?'bg-warning':'bg-secondary'; ?>"><?php echo (int)$r['late_days']; ?></span></td>
                                <td><?php echo (int)$r['present_days']; ?></td>
                                <td><?php echo (int)$r['absent_days']; ?></td>
                                <td><?php echo (int)$r['total_marked']; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 