<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }

$pdo = getDBConnection();
$message = '';

// Ensure subjects table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS subjects (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  code VARCHAR(20) UNIQUE,
  description TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$action = $_GET['action'] ?? 'list';

// Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!$name) throw new Exception('Name is required.');
        if (($_POST['action'] ?? '') === 'add') {
            $stmt = $pdo->prepare('INSERT INTO subjects (name, code, description, created_at, updated_at) VALUES (?,?,?,NOW(),NOW())');
            $stmt->execute([$name, $code ?: null, $desc ?: null]);
            header('Location: subjects.php?success=1'); exit;
        }
        if (($_POST['action'] ?? '') === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid subject.');
            $stmt = $pdo->prepare('UPDATE subjects SET name=?, code=?, description=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$name, $code ?: null, $desc ?: null, $id]);
            header('Location: subjects.php?updated=1'); exit;
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
        if ($id <= 0) throw new Exception('Invalid subject.');
        $pdo->prepare('DELETE FROM subjects WHERE id=?')->execute([$id]);
        header('Location: subjects.php?deleted=1'); exit;
    } catch (Exception $e) {
        $message = $e->getMessage();
        $action = 'list';
    }
}

// Edit fetch
$editSubject = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM subjects WHERE id=?');
    $stmt->execute([(int)$_GET['id']]);
    $editSubject = $stmt->fetch();
    if (!$editSubject) { $action = 'list'; }
}

// List
$subjects = [];
if ($action === 'list') {
    $subjects = $pdo->query('SELECT * FROM subjects ORDER BY name ASC')->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
<?php $active='dashboard'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <?php if (isset($_GET['success'])): ?><div class="alert alert-success">Subject added.</div><?php endif; ?>
    <?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Subject updated.</div><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Subject deleted.</div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4"><?php echo $action==='add'?'Add Subject':'Edit Subject'; ?></h1>
            <a href="subjects.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
        </div>
        <div class="card"><div class="card-body">
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action==='edit'): ?><input type="hidden" name="id" value="<?php echo (int)$editSubject['id']; ?>"><?php endif; ?>
                <div class="col-md-6">
                    <label class="form-label">Name *</label>
                    <input name="name" class="form-control" required value="<?php echo htmlspecialchars($editSubject['name'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Code</label>
                    <input name="code" class="form-control" value="<?php echo htmlspecialchars($editSubject['code'] ?? ''); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($editSubject['description'] ?? ''); ?></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <a href="subjects.php" class="btn btn-secondary me-2">Cancel</a>
                    <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Save</button>
                </div>
            </form>
        </div></div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4">Subjects</h1>
            <a href="subjects.php?action=add" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Subject</a>
        </div>
        <div class="card"><div class="card-body">
            <div class="table-responsive rounded-lg">
                <table class="table table-striped">
                    <thead><tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($subjects as $s): ?>
                        <tr>
                            <td><?php echo $s['id']; ?></td>
                            <td><?php echo htmlspecialchars($s['name']); ?></td>
                            <td><?php echo htmlspecialchars($s['code']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($s['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="subjects.php?action=edit&id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['name']); ?>')"><i class="fas fa-trash"></i></button>
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
        <div class="modal-header"><h5 class="modal-title">Delete Subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><p>Delete subject: <strong id="delName"></strong>?</p><p class="text-danger mb-0">This cannot be undone.</p></div>
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