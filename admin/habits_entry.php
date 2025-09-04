<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }

$pdo = getDBConnection();
$message = '';

// Ensure habits table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS student_habits (
  id INT PRIMARY KEY AUTO_INCREMENT,
  exam_id INT NOT NULL,
  student_id INT NOT NULL,
  islamic_manners ENUM('A','B','C') DEFAULT NULL,
  punctual ENUM('A','B','C') DEFAULT NULL,
  well_behaved ENUM('A','B','C') DEFAULT NULL,
  follow_instructions ENUM('A','B','C') DEFAULT NULL,
  neatness ENUM('A','B','C') DEFAULT NULL,
  health ENUM('A','B','C') DEFAULT NULL,
  homework ENUM('A','B','C') DEFAULT NULL,
  get_sign_daily ENUM('A','B','C') DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_student (exam_id, student_id),
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

$classes = $pdo->query('SELECT id, name, academic_year FROM classes ORDER BY created_at DESC')->fetchAll();
$exams = [];
if ($classId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, exam_type FROM exams WHERE class_id=? ORDER BY created_at DESC');
    $stmt->execute([$classId]);
    $exams = $stmt->fetchAll();
}

$students = [];
$exam = null;
if ($classId > 0 && $examId > 0) {
    $st = $pdo->prepare('SELECT * FROM exams WHERE id=? AND class_id=?');
    $st->execute([$examId, $classId]);
    $exam = $st->fetch();
    if ($exam) {
        $st = $pdo->prepare('SELECT s.id, s.first_name, s.middle_name, s.last_name, s.student_code FROM enrollments e JOIN students s ON s.id=e.student_id WHERE e.class_id=? AND e.status="active" ORDER BY s.first_name, s.last_name');
        $st->execute([$classId]);
        $students = $st->fetchAll();
    }
}

// Save habits
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_habits'])) {
    try {
        if ($classId <= 0 || $examId <= 0) throw new Exception('Select class and exam.');
        $data = $_POST['habits'] ?? [];
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO student_habits (exam_id, student_id, islamic_manners, punctual, well_behaved, follow_instructions, neatness, health, homework, get_sign_daily, created_at, updated_at)
                               VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW())
                               ON DUPLICATE KEY UPDATE islamic_manners=VALUES(islamic_manners), punctual=VALUES(punctual), well_behaved=VALUES(well_behaved), follow_instructions=VALUES(follow_instructions), neatness=VALUES(neatness), health=VALUES(health), homework=VALUES(homework), get_sign_daily=VALUES(get_sign_daily), updated_at=NOW()');
        foreach ($data as $studentId => $vals) {
            $stmt->execute([
                $examId,
                (int)$studentId,
                $vals['islamic_manners'] ?? null,
                $vals['punctual'] ?? null,
                $vals['well_behaved'] ?? null,
                $vals['follow_instructions'] ?? null,
                $vals['neatness'] ?? null,
                $vals['health'] ?? null,
                $vals['homework'] ?? null,
                $vals['get_sign_daily'] ?? null,
            ]);
        }
        $pdo->commit();
        header('Location: habits_entry.php?class_id='.$classId.'&exam_id='.$examId.'&saved=1'); exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = $e->getMessage();
    }
}

// Load existing to prefill
$habitMap = [];
if ($examId > 0) {
    $st = $pdo->prepare('SELECT * FROM student_habits WHERE exam_id=?');
    $st->execute([$examId]);
    while ($r = $st->fetch()) { $habitMap[$r['student_id']] = $r; }
}

$opts = ['A'=>'A','B'=>'B','C'=>'C'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habits Entry - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
<?php $active='classes'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Habits saved.</div><?php endif; ?>
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
        <?php if (!$students): ?>
            <div class="alert alert-warning mb-0">No active students enrolled in this class.</div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="save_habits" value="1">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Islamic Manners</th>
                                <th>Punctual</th>
                                <th>Well Behaved</th>
                                <th>Follow Instructions</th>
                                <th>Neatness</th>
                                <th>Health</th>
                                <th>Homework</th>
                                <th>Get Sign. Daily</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $stu): $full = trim(($stu['student_code']? $stu['student_code'].' - ' : '').$stu['first_name'].' '.($stu['middle_name']??'').' '.$stu['last_name']); $h=$habitMap[$stu['id']]??[]; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($full); ?></td>
                                    <?php $fields=['islamic_manners','punctual','well_behaved','follow_instructions','neatness','health','homework','get_sign_daily']; foreach ($fields as $f): ?>
                                        <td>
                                            <select name="habits[<?php echo $stu['id']; ?>][<?php echo $f; ?>]" class="form-select form-select-sm">
                                                <option value="">—</option>
                                                <?php foreach ($opts as $val=>$label): ?>
                                                    <option value="<?php echo $val; ?>" <?php echo (($h[$f]??'')===$val)?'selected':''; ?>><?php echo $label; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end">
                    <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Habits</button>
                </div>
            </form>
        <?php endif; ?>
    </div></div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 