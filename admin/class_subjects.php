<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }

$pdo = getDBConnection();
$message = '';

// Ensure tables
$pdo->exec("CREATE TABLE IF NOT EXISTS class_subjects (
  id INT PRIMARY KEY AUTO_INCREMENT,
  class_id INT NOT NULL,
  subject_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_class_subject (class_id, subject_id),
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
if ($classId <= 0) { redirect('classes.php'); }

// Fetch class
$stmt = $pdo->prepare('SELECT * FROM classes WHERE id=?');
$stmt->execute([$classId]);
$class = $stmt->fetch();
if (!$class) { redirect('classes.php'); }

// Handle attach
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'attach') {
    try {
        $subjectId = (int)($_POST['subject_id'] ?? 0);
        if ($subjectId <= 0) throw new Exception('Select a subject.');
        $stmt = $pdo->prepare('INSERT IGNORE INTO class_subjects (class_id, subject_id, created_at) VALUES (?,?,NOW())');
        $stmt->execute([$classId, $subjectId]);
        header('Location: class_subjects.php?class_id='.$classId.'&attached=1'); exit;
    } catch (Exception $e) { $message = $e->getMessage(); }
}

// Handle detach
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'detach') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid mapping.');
        $pdo->prepare('DELETE FROM class_subjects WHERE id=?')->execute([$id]);
        header('Location: class_subjects.php?class_id='.$classId.'&detached=1'); exit;
    } catch (Exception $e) { $message = $e->getMessage(); }
}

// Data lists
$allSubjects = $pdo->query('SELECT id, name, code FROM subjects ORDER BY name ASC')->fetchAll();
$attached = $pdo->prepare('SELECT cs.id, s.name, s.code, cs.created_at FROM class_subjects cs JOIN subjects s ON s.id=cs.subject_id WHERE cs.class_id=? ORDER BY s.name');
$attached->execute([$classId]);
$attached = $attached->fetchAll();

// Compute available subjects
$attachedIds = array_map(fn($r)=> (int)$r['id'], $attached); // careful: this is cs.id, need subject ids; re-query ids
$attachedSubjectIds = $pdo->prepare('SELECT subject_id FROM class_subjects WHERE class_id=?');
$attachedSubjectIds->execute([$classId]);
$attachedSubjectIds = array_map(fn($r)=> (int)$r['subject_id'], $attachedSubjectIds->fetchAll());
$available = array_filter($allSubjects, fn($s)=> !in_array((int)$s['id'], $attachedSubjectIds, true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Subjects - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
<?php $active='dashboard'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <?php if (isset($_GET['attached'])): ?><div class="alert alert-success">Subject attached.</div><?php endif; ?>
    <?php if (isset($_GET['detached'])): ?><div class="alert alert-success">Subject detached.</div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Subjects for: <?php echo htmlspecialchars($class['name']); ?> (<?php echo htmlspecialchars($class['academic_year']); ?>)</h1>
        <div>
            <a href="classes.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Classes</a>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="action" value="attach">
            <div class="col-md-6">
                <label class="form-label">Add Subject</label>
                <select name="subject_id" class="form-select" required>
                    <option value="">Select subject</option>
                    <?php foreach ($available as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?><?php echo $s['code']? ' ('.htmlspecialchars($s['code']).')':''; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 align-self-end"><button class="btn btn-primary"><i class="fas fa-plus me-2"></i>Attach</button></div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <div class="table-responsive rounded-lg">
            <table class="table table-striped">
                <thead><tr>
                    <th>#</th>
                    <th>Subject</th>
                    <th>Code</th>
                    <th>Attached</th>
                    <th>Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($attached as $row): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['code']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Detach this subject?');">
                                <input type="hidden" name="action" value="detach">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-unlink"></i> Detach</button>
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