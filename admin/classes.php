<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }

$pdo = getDBConnection();
$message = '';

// Ensure classes table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS classes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  academic_year VARCHAR(20) NOT NULL,
  description TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$action = $_GET['action'] ?? 'list';

// Add/Edit submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $year = trim($_POST['academic_year'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!$name || !$year) { throw new Exception('Please fill required fields.'); }
        if (($_POST['action'] ?? '') === 'add') {
            $stmt = $pdo->prepare('INSERT INTO classes (name, academic_year, description, created_at, updated_at) VALUES (?,?,?,NOW(),NOW())');
            $stmt->execute([$name, $year, $desc ?: null]);
            header('Location: classes.php?success=1'); exit;
        }
        if (($_POST['action'] ?? '') === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid class.');
            $stmt = $pdo->prepare('UPDATE classes SET name=?, academic_year=?, description=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$name, $year, $desc ?: null, $id]);
            header('Location: classes.php?updated=1'); exit;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $action = $_POST['action'] ?? 'list';
    }
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid class.');
        $pdo->prepare('DELETE FROM classes WHERE id=?')->execute([$id]);
        header('Location: classes.php?deleted=1'); exit;
    } catch (Exception $e) {
        $message = $e->getMessage();
        $action = 'list';
    }
}

// Fetch for edit
$editClass = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM classes WHERE id=?');
    $stmt->execute([(int)$_GET['id']]);
    $editClass = $stmt->fetch();
    if (!$editClass) { $action = 'list'; }
}

// List
$classes = [];
if ($action === 'list') {
    $classes = $pdo->query('SELECT * FROM classes ORDER BY created_at DESC')->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
<?php $active='dashboard'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <?php if (isset($_GET['success'])): ?><div class="alert alert-success">Class added.</div><?php endif; ?>
    <?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Class updated.</div><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Class deleted.</div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4"><?php echo $action==='add'?'Add Class':'Edit Class'; ?></h1>
            <a href="classes.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
        </div>
        <div class="card"><div class="card-body">
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action==='edit'): ?><input type="hidden" name="id" value="<?php echo (int)$editClass['id']; ?>"><?php endif; ?>
                <div class="col-md-6">
                    <label class="form-label">Class Name *</label>
                    <input name="name" class="form-control" required value="<?php echo htmlspecialchars($editClass['name'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Academic Year *</label>
                    <input name="academic_year" class="form-control" required value="<?php echo htmlspecialchars($editClass['academic_year'] ?? ''); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($editClass['description'] ?? ''); ?></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <a href="classes.php" class="btn btn-secondary me-2">Cancel</a>
                    <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Save</button>
                </div>
            </form>
        </div></div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4">Classes</h1>
            <div>
                <a href="subjects.php" class="btn btn-outline-secondary me-2"><i class="fas fa-book me-2"></i>Subjects</a>
                <a href="classes.php?action=add" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Class</a>
            </div>
        </div>
        <div class="card"><div class="card-body">
            <div class="table-responsive rounded-lg">
                <table class="table table-striped">
                    <thead><tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Academic Year</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($classes as $c): ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td><?php echo htmlspecialchars($c['name']); ?></td>
                            <td><?php echo htmlspecialchars($c['academic_year']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                            <td>
                            <div class="btn-group">
    <a href="classes.php?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
    <a href="class_subjects.php?class_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary" title="Manage Subjects"><i class="fas fa-list"></i></a>
    <a href="exams.php?class_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-success" title="Manage Exams"><i class="fas fa-file-signature"></i></a>
    <a href="class_students.php?class_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-info" title="Manage Students"><i class="fas fa-users"></i></a>
    <a href="class_attendance.php?class_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Mark Attendance"><i class="fas fa-user-check"></i></a>
    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['name']); ?>')"><i class="fas fa-trash"></i></button>
</div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div></div>
    <?php endif; ?>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Delete Class</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><p>Delete class: <strong id="delName"></strong>?</p><p class="text-danger mb-0">This cannot be undone.</p></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <form method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delId"><button class="btn btn-danger">Delete</button></form>
        </div>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(id, name){
    document.getElementById('delId').value = id;
    document.getElementById('delName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
</body>
</html> 