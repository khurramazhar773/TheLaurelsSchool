<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }

$pdo = getDBConnection();
$message = '';

// Ensure student_marks table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS student_marks (
  id INT PRIMARY KEY AUTO_INCREMENT,
  exam_id INT NOT NULL,
  student_id INT NOT NULL,
  subject_id INT NOT NULL,
  obtained_marks DECIMAL(7,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_student_subject (exam_id, student_id, subject_id),
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// Load classes for select if not chosen
$classes = $pdo->query('SELECT id, name, academic_year FROM classes ORDER BY created_at DESC')->fetchAll();

// If class chosen, load exams for that class
$exams = [];
if ($classId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, exam_type FROM exams WHERE class_id=? ORDER BY created_at DESC');
    $stmt->execute([$classId]);
    $exams = $stmt->fetchAll();
}

// If class and exam chosen, load students and subjects
$students = $subjects = $examSubjects = [];
$exam = null;
if ($classId > 0 && $examId > 0) {
    // Exam meta
    $st = $pdo->prepare('SELECT * FROM exams WHERE id=? AND class_id=?');
    $st->execute([$examId, $classId]);
    $exam = $st->fetch();
    if ($exam) {
        // Enrolled students
        $st = $pdo->prepare('SELECT s.id, s.first_name, s.middle_name, s.last_name, s.student_code FROM enrollments e JOIN students s ON s.id=e.student_id WHERE e.class_id=? AND e.status="active" ORDER BY s.first_name, s.last_name');
        $st->execute([$classId]);
        $students = $st->fetchAll();
        // Subjects attached to this exam
        $st = $pdo->prepare('SELECT es.subject_id, es.max_marks, s.name, s.code FROM exam_subjects es JOIN subjects s ON s.id=es.subject_id WHERE es.exam_id=? ORDER BY s.name');
        $st->execute([$examId]);
        $examSubjects = $st->fetchAll();
    }
}

// Handle save marks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    try {
        if ($classId <= 0 || $examId <= 0) throw new Exception('Select class and exam.');
        $marks = $_POST['marks'] ?? [];
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO student_marks (exam_id, student_id, subject_id, obtained_marks, created_at, updated_at)
                               VALUES (?,?,?,?,NOW(),NOW())
                               ON DUPLICATE KEY UPDATE obtained_marks=VALUES(obtained_marks), updated_at=NOW()');
        foreach ($marks as $studentId => $perSubj) {
            foreach ($perSubj as $subjectId => $obt) {
                $val = is_numeric($obt) ? (float)$obt : 0;
                $stmt->execute([$examId, (int)$studentId, (int)$subjectId, $val]);
            }
        }
        $pdo->commit();
        header('Location: marks_entry.php?class_id='.$classId.'&exam_id='.$examId.'&saved=1'); exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks Entry - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
<?php $active='classes'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Marks saved.</div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Class</label>
                <select name="class_id" class="form-select" onchange="this.form.submit()">
                    <option value="0">Select class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $classId===$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['name'].' ('.$c['academic_year'].')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Exam</label>
                <select name="exam_id" class="form-select" onchange="this.form.submit()">
                    <option value="0">Select exam</option>
                    <?php foreach ($exams as $e): ?>
                        <option value="<?php echo $e['id']; ?>" <?php echo $examId===$e['id']?'selected':''; ?>><?php echo htmlspecialchars(ucfirst($e['exam_type']).' - '.$e['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 align-self-end text-end">
                <a href="exams.php?class_id=<?php echo $classId; ?>" class="btn btn-outline-secondary"><i class="fas fa-file-signature me-2"></i>Manage Exams</a>
            </div>
        </form>
    </div></div>

    <?php if ($classId>0 && $examId>0 && $exam): ?>
    <div class="card"><div class="card-body">
        <?php if (!$examSubjects): ?>
            <div class="alert alert-warning mb-0">No subjects attached to this exam. Add subjects and max marks first.</div>
        <?php elseif (!$students): ?>
            <div class="alert alert-warning mb-0">No active students enrolled in this class.</div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="save_marks" value="1">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <?php foreach ($examSubjects as $subj): ?>
                                    <th><?php echo htmlspecialchars($subj['name']); ?><br><small class="text-muted">Max: <?php echo (float)$subj['max_marks']; ?></small></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // preload existing marks
                            $markMap = [];
                            $st = $pdo->prepare('SELECT student_id, subject_id, obtained_marks FROM student_marks WHERE exam_id=?');
                            $st->execute([$examId]);
                            while ($r = $st->fetch()) { $markMap[$r['student_id']][$r['subject_id']] = $r['obtained_marks']; }
                            foreach ($students as $stu): $full = trim(($stu['student_code']? $stu['student_code'].' - ' : '').$stu['first_name'].' '.($stu['middle_name']??'').' '.$stu['last_name']); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($full); ?></td>
                                    <?php foreach ($examSubjects as $subj): $sid=(int)$subj['subject_id']; $val = $markMap[$stu['id']][$sid] ?? ''; ?>
                                        <td style="min-width:120px;">
                                            <input type="number" step="0.01" min="0" max="<?php echo (float)$subj['max_marks']; ?>" name="marks[<?php echo $stu['id']; ?>][<?php echo $sid; ?>]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($val); ?>">
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end">
                    <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Marks</button>
                </div>
            </form>
        <?php endif; ?>
    </div></div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 