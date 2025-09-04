<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }

$pdo = getDBConnection();
$message = '';

// Ensure student_marks exists (needed for result rendering)
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

// Ensure student_habits exists (needed for habits section)
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
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$periodFrom = $_GET['from'] ?? '';
$periodTo = $_GET['to'] ?? '';
$leaves = isset($_GET['leaves']) ? (int)$_GET['leaves'] : 0;
$absences = isset($_GET['absences']) ? (int)$_GET['absences'] : 0;

// Lists
$classes = $pdo->query('SELECT id, name, academic_year FROM classes ORDER BY created_at DESC')->fetchAll();
$exams = [];
$students = [];
$class = null; $exam = null; $student = null;

if ($classId > 0) {
    $st = $pdo->prepare('SELECT * FROM classes WHERE id=?');
    $st->execute([$classId]);
    $class = $st->fetch();
    $st = $pdo->prepare('SELECT id, name, exam_type FROM exams WHERE class_id=? ORDER BY created_at DESC');
    $st->execute([$classId]);
    $exams = $st->fetchAll();
    $st = $pdo->prepare('SELECT s.id, s.first_name, s.middle_name, s.last_name, s.student_code, s.father_name FROM enrollments e JOIN students s ON s.id=e.student_id WHERE e.class_id=? ORDER BY s.first_name, s.last_name');
    $st->execute([$classId]);
    $students = $st->fetchAll();
}
if ($class && $examId > 0) {
    $st = $pdo->prepare('SELECT * FROM exams WHERE id=? AND class_id=?');
    $st->execute([$examId, $classId]);
    $exam = $st->fetch();
}
if ($studentId > 0) {
    foreach ($students as $s) { if ((int)$s['id'] === $studentId) { $student = $s; break; } }
}

// Fetch marks and exam subjects for selected
$examSubjects = []; $marks = []; $totMax = 0.0; $totObt = 0.0;
if ($exam && $student) {
    $st = $pdo->prepare('SELECT es.subject_id, es.max_marks, subj.name FROM exam_subjects es JOIN subjects subj ON subj.id=es.subject_id WHERE es.exam_id=? ORDER BY subj.name');
    $st->execute([$examId]);
    $examSubjects = $st->fetchAll();
    if ($examSubjects) {
        $st = $pdo->prepare('SELECT subject_id, obtained_marks FROM student_marks WHERE exam_id=? AND student_id=?');
        $st->execute([$examId, $studentId]);
        while ($r = $st->fetch()) { $marks[(int)$r['subject_id']] = (float)$r['obtained_marks']; }
        foreach ($examSubjects as $es) {
            $totMax += (float)$es['max_marks'];
            $totObt += (float)($marks[(int)$es['subject_id']] ?? 0);
        }
    }
}

// Habits
$habits = null;
if ($exam && $student) {
    $st = $pdo->prepare('SELECT * FROM student_habits WHERE exam_id=? AND student_id=?');
    $st->execute([$examId, $studentId]);
    $habits = $st->fetch();
}

function formatSession(?array $class): string {
    if (!$class) return '';
    // Expect academic_year like 2024-2025 or similar; if only one year, compute next
    $ay = trim($class['academic_year']);
    if (strpos($ay, '-') !== false) return $ay;
    $y = preg_replace('/[^0-9]/', '', $ay);
    if ($y && strlen($y) === 4) { return $y.'-'.((int)$y+1); }
    return $ay;
}

function workingDaysExcludingSundays(string $from, string $to): int {
    if (!$from || !$to) return 0;
    $start = new DateTime($from);
    $end = new DateTime($to);
    if ($end < $start) return 0;
    $count = 0;
    while ($start <= $end) {
        if ((int)$start->format('w') !== 0) { $count++; } // 0 = Sunday
        $start->modify('+1 day');
    }
    return $count;
}

$totalDays = ($periodFrom && $periodTo) ? workingDaysExcludingSundays($periodFrom, $periodTo) : 0;
$presentDays = max(0, $totalDays - max(0,$leaves) - max(0,$absences));
$attendancePct = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Card - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .card { box-shadow: none; border: 1px solid #ccc; }
        }
        .rc-header { text-align: center; margin-bottom: 1rem; }
        .rc-title { font-family: 'Merriweather', serif; font-weight: 900; color: var(--color-navy); letter-spacing: .5px; }
        .rc-subtitle { color: #374151; }
        .sig-box { height: 60px; border-top: 1px solid #ccc; }
    </style>
</head>
<body>
<?php $active='classes'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="class_id" class="form-select" onchange="this.form.submit()">
                        <option value="0">Select class</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $classId===$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['name'].' ('.$c['academic_year'].')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Exam</label>
                    <select name="exam_id" class="form-select" onchange="this.form.submit()">
                        <option value="0">Select exam</option>
                        <?php foreach ($exams as $e): ?>
                            <option value="<?php echo $e['id']; ?>" <?php echo $examId===$e['id']?'selected':''; ?>><?php echo htmlspecialchars(ucfirst($e['exam_type']).' - '.$e['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select" onchange="this.form.submit()">
                        <option value="0">Select student</option>
                        <?php foreach ($students as $s): $nm=trim(($s['student_code']? $s['student_code'].' - ':'').$s['first_name'].' '.($s['middle_name']??'').' '.$s['last_name']); ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $studentId===$s['id']?'selected':''; ?>><?php echo htmlspecialchars($nm); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 align-self-end text-end">
                    <a href="marks_entry.php?class_id=<?php echo $classId; ?>&exam_id=<?php echo $examId; ?>" class="btn btn-outline-primary no-print"><i class="fas fa-pen me-2"></i>Marks Entry</a>
                    <a href="habits_entry.php?class_id=<?php echo $classId; ?>&exam_id=<?php echo $examId; ?>" class="btn btn-outline-secondary no-print"><i class="fas fa-list-check me-2"></i>Habits Entry</a>
                </div>
                <div class="col-12"><hr></div>
                <div class="col-md-3">
                    <label class="form-label">Attendance From</label>
                    <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($periodFrom); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Attendance To</label>
                    <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($periodTo); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Leaves</label>
                    <input type="number" name="leaves" min="0" class="form-control" value="<?php echo (int)$leaves; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Absences</label>
                    <input type="number" name="absences" min="0" class="form-control" value="<?php echo (int)$absences; ?>">
                </div>
                <div class="col-md-2 align-self-end">
                    <button class="btn btn-primary no-print"><i class="fas fa-filter me-2"></i>Apply</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($exam && $student): ?>
    <div class="card">
        <div class="card-body">
            <div class="rc-header">
                <h2 class="rc-title mb-1">Pupil Progress Report</h2>
                <div class="rc-subtitle">Session: <?php echo htmlspecialchars(formatSession($class)); ?> &nbsp; | &nbsp; Term: <?php echo htmlspecialchars($exam['name']); ?></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Reg No:</strong> <?php echo htmlspecialchars($student['student_code'] ?? ''); ?></p>
                    <p class="mb-1"><strong>Student Name:</strong> <?php echo htmlspecialchars($student['first_name'].' '.($student['middle_name']??'').' '.$student['last_name']); ?></p>
                    <p class="mb-1"><strong>Father's Name:</strong> <?php echo htmlspecialchars($student['father_name']); ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Class:</strong> Grade _____ <?php echo htmlspecialchars($class['name']); ?></p>
                    <p class="mb-1"><strong>Exam Type:</strong> <?php echo htmlspecialchars(ucfirst($exam['exam_type'])); ?></p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-8">
                    <div class="table-responsive rounded-lg">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Sr#</th>
                                    <th>Subject</th>
                                    <th>Total Marks</th>
                                    <th>Obt Marks</th>
                                    <th>%age</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i=1; foreach ($examSubjects as $es): $sid=(int)$es['subject_id']; $max=(float)$es['max_marks']; $obt=(float)($marks[$sid] ?? 0); $perc=$max>0? round(($obt/$max)*100,2):0; ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($es['name']); ?></td>
                                        <td><?php echo $max; ?></td>
                                        <td><?php echo $obt; ?></td>
                                        <td><?php echo $perc; ?>%</td>
                                        <td><?php echo getGradeFromPercentage($perc); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <?php $overallPerc = $totMax>0? round(($totObt/$totMax)*100,2):0; ?>
                                <tr>
                                    <th colspan="2">Total</th>
                                    <th><?php echo $totMax; ?></th>
                                    <th><?php echo $totObt; ?></th>
                                    <th><?php echo $overallPerc; ?>%</th>
                                    <th><?php echo getGradeFromPercentage($overallPerc); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header"><strong>A Stream</strong></div>
                        <div class="card-body p-2">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr><td>90 - 100</td><td>A+</td></tr>
                                    <tr><td>80 - 89</td><td>A</td></tr>
                                    <tr><td>70 - 79</td><td>B</td></tr>
                                    <tr><td>60 - 69</td><td>C</td></tr>
                                    <tr><td>50 - 59</td><td>D</td></tr>
                                    <tr><td>40 - 49</td><td>E</td></tr>
                                    <tr><td>&lt; 40</td><td>F</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><strong>Social and Study Habits</strong></div>
                        <div class="card-body p-2">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr><td>Islamic Manners</td><td><?php echo htmlspecialchars($habits['islamic_manners'] ?? '—'); ?></td></tr>
                                    <tr><td>Punctual</td><td><?php echo htmlspecialchars($habits['punctual'] ?? '—'); ?></td></tr>
                                    <tr><td>Well Behaved</td><td><?php echo htmlspecialchars($habits['well_behaved'] ?? '—'); ?></td></tr>
                                    <tr><td>Follow Instructions</td><td><?php echo htmlspecialchars($habits['follow_instructions'] ?? '—'); ?></td></tr>
                                    <tr><td>Neatness</td><td><?php echo htmlspecialchars($habits['neatness'] ?? '—'); ?></td></tr>
                                    <tr><td>Health</td><td><?php echo htmlspecialchars($habits['health'] ?? '—'); ?></td></tr>
                                    <tr><td>Homework</td><td><?php echo htmlspecialchars($habits['homework'] ?? '—'); ?></td></tr>
                                    <tr><td>Get Sign. Daily</td><td><?php echo htmlspecialchars($habits['get_sign_daily'] ?? '—'); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header"><strong>Attendance</strong></div>
                        <div class="card-body p-2">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr><td>Period</td><td><?php echo ($periodFrom && $periodTo)? htmlspecialchars($periodFrom.' to '.$periodTo): '—'; ?></td></tr>
                                    <tr><td>Total Days (excl. Sundays)</td><td><?php echo $totalDays; ?></td></tr>
                                    <tr><td>Leaves</td><td><?php echo (int)$leaves; ?></td></tr>
                                    <tr><td>Absence</td><td><?php echo (int)$absences; ?></td></tr>
                                    <tr><td>Attendance %</td><td><?php echo $attendancePct; ?>%</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <div class="p-3" style="min-height:80px; border:1px solid #e5e7eb; border-radius:12px;"></div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-6 text-start">
                    <div class="sig-box"></div>
                    <small>Class Teacher Signature</small>
                </div>
                <div class="col-md-6 text-end">
                    <div class="sig-box"></div>
                    <small>Principal Signature</small>
                </div>
            </div>

            <div class="text-end mt-3 no-print">
                <button class="btn btn-outline-primary" onclick="window.print()"><i class="fas fa-print me-2"></i>Print</button>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-info">Please select Class, Exam, and Student to preview the result card.</div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 