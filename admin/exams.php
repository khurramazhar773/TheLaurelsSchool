<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }

$pdo = getDBConnection();
$message = '';

// Ensure exams table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS exams (
  id INT PRIMARY KEY AUTO_INCREMENT,
  class_id INT NOT NULL,
  exam_type ENUM('assessment','term') NOT NULL,
  name VARCHAR(150) NOT NULL,
  exam_date DATE DEFAULT NULL,
  description TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
if ($classId <= 0) { redirect('classes.php'); }

// Load class
$stmt = $pdo->prepare('SELECT * FROM classes WHERE id=?');
$stmt->execute([$classId]);
$class = $stmt->fetch();
if (!$class) { redirect('classes.php'); }

$action = $_GET['action'] ?? 'list';

// Add/Edit exam
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $type = $_POST['exam_type'] ?? 'assessment';
        if (!in_array($type, ['assessment','term'])) { $type = 'assessment'; }
        $name = trim($_POST['name'] ?? '');
        $date = $_POST['exam_date'] ?? null;
        $desc = trim($_POST['description'] ?? '');
        if (!$name) throw new Exception('Exam name is required.');
        if (($_POST['action'] ?? '') === 'add') {
            $stmt = $pdo->prepare('INSERT INTO exams (class_id, exam_type, name, exam_date, description, created_at, updated_at) VALUES (?,?,?,?,?,NOW(),NOW())');
            $stmt->execute([$classId, $type, $name, $date ?: null, $desc ?: null]);
            header('Location: exams.php?class_id='.$classId.'&success=1'); exit;
        }
        if (($_POST['action'] ?? '') === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid exam.');
            $stmt = $pdo->prepare('UPDATE exams SET exam_type=?, name=?, exam_date=?, description=?, updated_at=NOW() WHERE id=? AND class_id=?');
            $stmt->execute([$type, $name, $date ?: null, $desc ?: null, $id, $classId]);
            header('Location: exams.php?class_id='.$classId.'&updated=1'); exit;
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
        if ($id <= 0) throw new Exception('Invalid exam.');
        $pdo->prepare('DELETE FROM exams WHERE id=? AND class_id=?')->execute([$id, $classId]);
        header('Location: exams.php?class_id='.$classId.'&deleted=1'); exit;
    } catch (Exception $e) { $message = $e->getMessage(); }
}

// Edit fetch
$editExam = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM exams WHERE id=? AND class_id=?');
    $stmt->execute([(int)$_GET['id'], $classId]);
    $editExam = $stmt->fetch();
    if (!$editExam) { $action = 'list'; }
}

// List exams
$exams = [];
if ($action === 'list') {
    $stmt = $pdo->prepare('SELECT * FROM exams WHERE class_id=? ORDER BY exam_date IS NULL, exam_date DESC, created_at DESC');
    $stmt->execute([$classId]);
    $exams = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exams - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
<?php $active='dashboard'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <?php if (isset($_GET['success'])): ?><div class="alert alert-success">Exam added.</div><?php endif; ?>
    <?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Exam updated.</div><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Exam deleted.</div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4"><?php echo $action==='add'?'Add Exam':'Edit Exam'; ?> - <?php echo htmlspecialchars($class['name']); ?></h1>
            <div>
                <a href="exams.php?class_id=<?php echo $classId; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>
        </div>
        <div class="card"><div class="card-body">
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action==='edit'): ?><input type="hidden" name="id" value="<?php echo (int)$editExam['id']; ?>"><?php endif; ?>
                <div class="col-md-4">
                    <label class="form-label">Type *</label>
                    <select name="exam_type" class="form-select" required>
                        <option value="assessment" <?php echo (($editExam['exam_type']??'assessment')==='assessment')?'selected':''; ?>>Assessment</option>
                        <option value="term" <?php echo (($editExam['exam_type']??'')==='term')?'selected':''; ?>>Term</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Name *</label>
                    <input name="name" class="form-control" required value="<?php echo htmlspecialchars($editExam['name'] ?? ''); ?>" placeholder="e.g., Assessment 1, Mid Term, Final Term">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Exam Date</label>
                    <input type="date" name="exam_date" class="form-control" value="<?php echo htmlspecialchars($editExam['exam_date'] ?? ''); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($editExam['description'] ?? ''); ?></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <a href="exams.php?class_id=<?php echo $classId; ?>" class="btn btn-secondary me-2">Cancel</a>
                    <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Save</button>
                </div>
            </form>
        </div></div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4">Exams for <?php echo htmlspecialchars($class['name']); ?></h1>
            <div>
                <a href="classes.php" class="btn btn-outline-secondary me-2"><i class="fas fa-arrow-left me-2"></i>Back to Classes</a>
                <a href="exams.php?class_id=<?php echo $classId; ?>&action=add" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Exam</a>
            </div>
        </div>
        <div class="card"><div class="card-body">
            <div class="table-responsive rounded-lg">
                <table class="table table-striped">
                    <thead><tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($exams as $e): ?>
                        <tr>
                            <td><?php echo $e['id']; ?></td>
                            <td><span class="badge <?php echo $e['exam_type']==='term'?'bg-info':'bg-secondary'; ?>"><?php echo ucfirst($e['exam_type']); ?></span></td>
                            <td><?php echo htmlspecialchars($e['name']); ?></td>
                            <td><?php echo htmlspecialchars($e['exam_date'] ?? '—'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($e['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="exams.php?class_id=<?php echo $classId; ?>&action=edit&id=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                                    <a href="exam_subjects.php?exam_id=<?php echo $e['id']; ?>&class_id=<?php echo $classId; ?>" class="btn btn-sm btn-outline-success" title="Subjects & Marks"><i class="fas fa-list-ol"></i></a>
                                    <a href="result_card.php?class_id=<?php echo $classId; ?>&exam_id=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline-primary" title="Result Card"><i class="fas fa-file-lines"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $e['id']; ?>, '<?php echo htmlspecialchars($e['name']); ?>')"><i class="fas fa-trash"></i></button>
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
        <div class="modal-header"><h5 class="modal-title">Delete Exam</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><p>Delete exam: <strong id="delName"></strong>?</p><p class="text-danger mb-0">This cannot be undone.</p></div>
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