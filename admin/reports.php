<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }

$pdo = getDBConnection();
$message = '';

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'students') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_export_'.date('Ymd_His').'.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'id','first_name','middle_name','last_name','sex','date_of_birth','birth_place','religion','address','city','phone','status','application_date','created_at'
    ]);
    $stmt = $pdo->query("SELECT id,first_name,middle_name,last_name,sex,date_of_birth,birth_place,religion,address,city,phone,status,application_date,created_at FROM students ORDER BY created_at DESC");
    while ($row = $stmt->fetch()) { fputcsv($output, $row); }
    fclose($output);
    exit;
}

// Overview stats
$stats = [
    'total_students' => 0,
    'by_status' => [],
    'recent' => []
];
try {
    $stats['total_students'] = (int)$pdo->query("SELECT COUNT(*) AS c FROM students")->fetch()['c'];
    $by = $pdo->query("SELECT status, COUNT(*) AS c FROM students GROUP BY status ORDER BY c DESC")->fetchAll();
    foreach ($by as $row) { $stats['by_status'][$row['status']] = (int)$row['c']; }
    $recentStmt = $pdo->query("SELECT id,first_name,last_name,status,created_at FROM students ORDER BY created_at DESC LIMIT 20");
    $stats['recent'] = $recentStmt->fetchAll();
} catch (Exception $e) {
    $message = 'Error loading reports.';
}

function statusBadge($s) {
    switch ($s) {
        case 'pending': return 'badge bg-warning';
        case 'active': return 'badge bg-success';
        case 'inactive': return 'badge bg-secondary';
        case 'withdrawn': return 'badge bg-danger';
        case 'completed': return 'badge bg-info';
        case 'suspended': return 'badge bg-warning';
        case 'expelled': return 'badge bg-danger';
        case 'transferred': return 'badge bg-secondary';
        case 'graduated': return 'badge bg-success';
        case 'on_leave': return 'badge bg-info';
        default: return 'badge bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
<?php $active='reports'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Reports</h1>
        <div>
            <a href="reports.php?export=students" class="btn btn-outline-success"><i class="fas fa-file-csv me-2"></i>Export Students CSV</a>
        </div>
    </div>

    <?php if ($message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card">
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
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted mb-2">Students by Status</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!$stats['by_status']) echo '<span class="text-muted">No data</span>'; ?>
                        <?php foreach ($stats['by_status'] as $status => $count): ?>
                            <span class="<?php echo statusBadge($status); ?>">
                                <?php echo ucfirst($status); ?>: <?php echo $count; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Applications</h5>
            <a href="students.php" class="btn btn-sm btn-outline-primary">Manage Students</a>
        </div>
        <div class="card-body">
            <div class="table-responsive rounded-lg">
                <table class="table table-striped">
                    <thead><tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Applied</th>
                        <th>Actions</th>
                    </tr></thead>
                    <tbody>
                        <?php if (!$stats['recent']): ?>
                            <tr><td colspan="5" class="text-center text-muted">No records</td></tr>
                        <?php else: foreach ($stats['recent'] as $r): ?>
                            <tr>
                                <td><?php echo $r['id']; ?></td>
                                <td><?php echo htmlspecialchars($r['first_name'].' '.$r['last_name']); ?></td>
                                <td><span class="<?php echo statusBadge($r['status']); ?>"><?php echo ucfirst($r['status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="view_student.php?id=<?php echo $r['id']; ?>"><i class="fas fa-eye"></i></a>
                                    <a class="btn btn-sm btn-outline-warning" href="edit_student.php?id=<?php echo $r['id']; ?>"><i class="fas fa-edit"></i></a>
                                </td>
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