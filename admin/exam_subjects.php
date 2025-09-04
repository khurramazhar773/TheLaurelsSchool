<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }

$pdo = getDBConnection();
$message = '';

// Ensure table
$pdo->exec("CREATE TABLE IF NOT EXISTS exam_subjects (
  id INT PRIMARY KEY AUTO_INCREMENT,
  exam_id INT NOT NULL,
  subject_id INT NOT NULL,
  max_marks DECIMAL(6,2) NOT NULL DEFAULT 100,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_subject (exam_id, subject_id),
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
if ($examId <= 0 || $classId <= 0) { redirect('classes.php'); }

// Load exam and class
$stmt = $pdo->prepare('SELECT e.*, c.name AS class_name, c.academic_year FROM exams e JOIN classes c ON c.id=e.class_id WHERE e.id=? AND e.class_id=?');
$stmt->execute([$examId, $classId]);
$exam = $stmt->fetch();
if (!$exam) { redirect('classes.php'); }

// Subjects in this class
$classSubjects = $pdo->prepare('SELECT s.id, s.name, s.code FROM class_subjects cs JOIN subjects s ON s.id=cs.subject_id WHERE cs.class_id=? ORDER BY s.name');
$classSubjects->execute([$classId]);
$classSubjects = $classSubjects->fetchAll();

// Handle attach/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['action'] ?? '') === 'attach') {
            $subjectId = (int)($_POST['subject_id'] ?? 0);
            $max = (float)($_POST['max_marks'] ?? 100);
            if ($subjectId <= 0) throw new Exception('Select a subject.');
            if ($max <= 0) throw new Exception('Max marks must be positive.');
            $stmt = $pdo->prepare('INSERT INTO exam_subjects (exam_id, subject_id, max_marks, created_at, updated_at) VALUES (?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE max_marks=VALUES(max_marks), updated_at=NOW()');
            $stmt->execute([$examId, $subjectId, $max, date('Y-m-d H:i:s')]);
            header('Location: exam_subjects.php?exam_id='.$examId.'&class_id='.$classId.'&attached=1'); exit;
        }
        if (($_POST['action'] ?? '') === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $max = (float)($_POST['max_marks'] ?? 100);
            if ($id <= 0) throw new Exception('Invalid row.');
            if ($max <= 0) throw new Exception('Max marks must be positive.');
            $stmt = $pdo->prepare('UPDATE exam_subjects SET max_marks=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$max, $id]);
            header('Location: exam_subjects.php?exam_id='.$examId.'&class_id='.$classId.'&updated=1'); exit;
        }
        if (($_POST['action'] ?? '') === 'detach') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid row.');
            $pdo->prepare('DELETE FROM exam_subjects WHERE id=?')->execute([$id]);
            header('Location: exam_subjects.php?exam_id='.$examId.'&class_id='.$classId.'&detached=1'); exit;
        }
    } catch (Exception $e) { $message = $e->getMessage(); }
}

// Data
$assigned = $pdo->prepare('SELECT es.id, s.name, s.code, es.max_marks, es.created_at FROM exam_subjects es JOIN subjects s ON s.id=es.subject_id WHERE es.exam_id=? ORDER BY s.name');
$assigned->execute([$examId]);
$assigned = $assigned->fetchAll();

// Available = class subjects not already assigned to this exam
$assignedSubjectIds = $pdo->prepare('SELECT subject_id FROM exam_subjects WHERE exam_id=?');
$assignedSubjectIds->execute([$examId]);
$assignedSubjectIds = array_map(fn($r)=> (int)$r['subject_id'], $assignedSubjectIds->fetchAll());
$available = array_filter($classSubjects, fn($s)=> !in_array((int)$s['id'], $assignedSubjectIds, true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Subjects & Marks - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
<?php $active='dashboard'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <?php if (isset($_GET['attached'])): ?><div class="alert alert-success">Subject added/updated for this exam.</div><?php endif; ?>
    <?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Max marks updated.</div><?php endif; ?>
    <?php if (isset($_GET['detached'])): ?><div class="alert alert-success">Subject removed from this exam.</div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Exam Subjects & Marks</h1>
        <div>
            <a href="exams.php?class_id=<?php echo $classId; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Exams</a>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <p class="mb-2"><strong>Class:</strong> <?php echo htmlspecialchars($exam['class_name']); ?> (<?php echo htmlspecialchars($exam['academic_year']); ?>)</p>
        <p class="mb-0"><strong>Exam:</strong> <span class="badge <?php echo $exam['exam_type']==='term'?'bg-info':'bg-secondary'; ?>"><?php echo ucfirst($exam['exam_type']); ?></span> <?php echo htmlspecialchars($exam['name']); ?><?php echo $exam['exam_date']? ' — '.htmlspecialchars($exam['exam_date']):''; ?></p>
    </div></div>

    <div class="card mb-3"><div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="action" value="attach">
            <div class="col-md-6">
                <label class="form-label">Subject</label>
                <select name="subject_id" class="form-select" required>
                    <option value="">Select subject</option>
                    <?php foreach ($available as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?><?php echo $s['code']? ' ('.htmlspecialchars($s['code']).')':''; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Max Marks</label>
                <input type="number" step="0.01" min="1" name="max_marks" class="form-control" value="100" required>
            </div>
            <div class="col-md-3 align-self-end">
                <button class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add</button>
            </div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <div class="table-responsive rounded-lg">
            <table class="table table-striped">
                <thead><tr>
                    <th>#</th>
                    <th>Subject</th>
                    <th>Code</th>
                    <th>Max Marks</th>
                    <th>Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($assigned as $r): ?>
                    <tr>
                        <td><?php echo $r['id']; ?></td>
                        <td><?php echo htmlspecialchars($r['name']); ?></td>
                        <td><?php echo htmlspecialchars($r['code']); ?></td>
                        <td>
                            <form method="POST" class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                <input type="number" step="0.01" min="1" name="max_marks" class="form-control form-control-sm" style="max-width:140px;" value="<?php echo htmlspecialchars($r['max_marks']); ?>">
                                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-save"></i></button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Remove this subject from the exam?');">
                                <input type="hidden" name="action" value="detach">
                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
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