<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$pdo = getDBConnection();
$message = '';
$success = '';

// Get all classes for filtering
$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();

// Default date range (current month)
$start_date = date('Y-m-01');
$end_date = date('Y-m-t');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'] ?? '';
    $start_date = $_POST['start_date'] ?? $start_date;
    $end_date = $_POST['end_date'] ?? $end_date;
    $report_type = $_POST['report_type'] ?? 'summary';
    
    // Validate dates
    if ($start_date > $end_date) {
        $message = 'Start date cannot be after end date.';
    } else {
        $success = 'Report generated successfully.';
    }
} else {
    $class_id = '';
    $report_type = 'summary';
}

// Get attendance data based on filters
$attendance_data = [];
$summary_stats = [];

if ($class_id && $start_date && $end_date) {
    // Get students in the selected class
    $students_query = "SELECT s.*, c.name as class_name 
                      FROM students s 
                      JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
                      JOIN classes c ON e.class_id = c.id 
                      WHERE e.class_id = ? 
                      ORDER BY s.first_name, s.last_name";
    $students_stmt = $pdo->prepare($students_query);
    $students_stmt->execute([$class_id]);
    $students = $students_stmt->fetchAll();
    
    // Get attendance data for each student
    foreach ($students as $student) {
        $attendance_query = "SELECT attendance_date, status, remarks 
                           FROM student_attendance 
                           WHERE student_id = ? 
                           AND attendance_date BETWEEN ? AND ? 
                           ORDER BY attendance_date";
        $attendance_stmt = $pdo->prepare($attendance_query);
        $attendance_stmt->execute([$student['id'], $start_date, $end_date]);
        $student_attendance = $attendance_stmt->fetchAll();
        
        // Calculate statistics for this student
        $total_days = count($student_attendance);
        $present_days = count(array_filter($student_attendance, function($a) { return $a['status'] === 'present'; }));
        $absent_days = count(array_filter($student_attendance, function($a) { return $a['status'] === 'absent'; }));
        $late_days = count(array_filter($student_attendance, function($a) { return $a['status'] === 'late'; }));
        $excused_days = count(array_filter($student_attendance, function($a) { return $a['status'] === 'excused'; }));
        
        $attendance_percentage = $total_days > 0 ? round(($present_days / $total_days) * 100, 2) : 0;
        
        $attendance_data[] = [
            'student' => $student,
            'attendance' => $student_attendance,
            'stats' => [
                'total_days' => $total_days,
                'present_days' => $present_days,
                'absent_days' => $absent_days,
                'late_days' => $late_days,
                'excused_days' => $excused_days,
                'attendance_percentage' => $attendance_percentage
            ]
        ];
    }
    
    // Calculate class summary statistics
    if (!empty($attendance_data)) {
        $total_students = count($attendance_data);
        $total_present = array_sum(array_column(array_column($attendance_data, 'stats'), 'present_days'));
        $total_absent = array_sum(array_column(array_column($attendance_data, 'stats'), 'absent_days'));
        $total_late = array_sum(array_column(array_column($attendance_data, 'stats'), 'late_days'));
        $total_excused = array_sum(array_column(array_column($attendance_data, 'stats'), 'excused_days'));
        $total_days = array_sum(array_column(array_column($attendance_data, 'stats'), 'total_days'));
        
        $summary_stats = [
            'total_students' => $total_students,
            'total_present' => $total_present,
            'total_absent' => $total_absent,
            'total_late' => $total_late,
            'total_excused' => $total_excused,
            'total_days' => $total_days,
            'average_attendance' => $total_days > 0 ? round(($total_present / $total_days) * 100, 2) : 0
        ];
    }
}

$page_title = 'Attendance Reports';
$active = 'reports';
include 'partials/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Attendance Reports</li>
                    </ol>
                </div>
                <h4 class="page-title">Attendance Reports</h4>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Report Filters -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>Report Filters
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <label for="class_id" class="form-label">Class</label>
                    <select class="form-select" id="class_id" name="class_id" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo $class_id == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="report_type" class="form-label">Report Type</label>
                    <select class="form-select" id="report_type" name="report_type">
                        <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>>Summary Report</option>
                        <option value="detailed" <?php echo $report_type == 'detailed' ? 'selected' : ''; ?>>Detailed Report</option>
                        <option value="analytics" <?php echo $report_type == 'analytics' ? 'selected' : ''; ?>>Analytics Report</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-chart-bar me-1"></i>Generate Report
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($attendance_data)): ?>
        <!-- Summary Statistics -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Class Summary Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="h3 text-primary"><?php echo $summary_stats['total_students']; ?></div>
                                    <div class="text-muted">Total Students</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="h3 text-success"><?php echo $summary_stats['total_present']; ?></div>
                                    <div class="text-muted">Present Days</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="h3 text-danger"><?php echo $summary_stats['total_absent']; ?></div>
                                    <div class="text-muted">Absent Days</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="h3 text-warning"><?php echo $summary_stats['total_late']; ?></div>
                                    <div class="text-muted">Late Days</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="h3 text-info"><?php echo $summary_stats['total_excused']; ?></div>
                                    <div class="text-muted">Excused Days</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="h3 text-primary"><?php echo $summary_stats['average_attendance']; ?>%</div>
                                    <div class="text-muted">Avg Attendance</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Report -->
        <?php if ($report_type == 'detailed' || $report_type == 'summary'): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>Student Attendance Details
                            </h5>
                            <div>
                                <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel me-1"></i>Export Excel
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                    <i class="fas fa-print me-1"></i>Print
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="attendanceTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Student Code</th>
                                            <th>Total Days</th>
                                            <th>Present</th>
                                            <th>Absent</th>
                                            <th>Late</th>
                                            <th>Excused</th>
                                            <th>Attendance %</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendance_data as $data): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($data['student']['first_name'] . ' ' . $data['student']['last_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($data['student']['student_code']); ?></td>
                                                <td><?php echo $data['stats']['total_days']; ?></td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo $data['stats']['present_days']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger"><?php echo $data['stats']['absent_days']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning"><?php echo $data['stats']['late_days']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $data['stats']['excused_days']; ?></span>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar <?php echo $data['stats']['attendance_percentage'] >= 80 ? 'bg-success' : ($data['stats']['attendance_percentage'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                             style="width: <?php echo $data['stats']['attendance_percentage']; ?>%">
                                                            <?php echo $data['stats']['attendance_percentage']; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($data['stats']['attendance_percentage'] >= 90): ?>
                                                        <span class="badge bg-success">Excellent</span>
                                                    <?php elseif ($data['stats']['attendance_percentage'] >= 80): ?>
                                                        <span class="badge bg-primary">Good</span>
                                                    <?php elseif ($data['stats']['attendance_percentage'] >= 60): ?>
                                                        <span class="badge bg-warning">Fair</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Poor</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Analytics Report -->
        <?php if ($report_type == 'analytics'): ?>
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Attendance Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="attendanceChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Attendance Trends</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="trendsChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($class_id): ?>
        <!-- No Data Message -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Attendance Data Found</h5>
                        <p class="text-muted">No attendance records found for the selected class and date range.</p>
                        <a href="student_attendance.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Mark Attendance
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Chart.js for Analytics -->
<?php if ($report_type == 'analytics' && !empty($attendance_data)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Attendance Distribution Chart
const ctx1 = document.getElementById('attendanceChart').getContext('2d');
new Chart(ctx1, {
    type: 'doughnut',
    data: {
        labels: ['Present', 'Absent', 'Late', 'Excused'],
        datasets: [{
            data: [
                <?php echo $summary_stats['total_present']; ?>,
                <?php echo $summary_stats['total_absent']; ?>,
                <?php echo $summary_stats['total_late']; ?>,
                <?php echo $summary_stats['total_excused']; ?>
            ],
            backgroundColor: ['#28a745', '#dc3545', '#ffc107', '#17a2b8']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Attendance Trends Chart (simplified - would need more data for proper trends)
const ctx2 = document.getElementById('trendsChart').getContext('2d');
new Chart(ctx2, {
    type: 'line',
    data: {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        datasets: [{
            label: 'Average Attendance %',
            data: [85, 88, 82, 90], // This would be calculated from actual data
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});
</script>
<?php endif; ?>

<script>
// Export to Excel functionality
function exportToExcel() {
    const table = document.getElementById('attendanceTable');
    const ws = XLSX.utils.table_to_sheet(table);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Attendance Report');
    XLSX.writeFile(wb, 'attendance_report_<?php echo date('Y-m-d'); ?>.xlsx');
}

// Auto-submit form when class is selected
document.getElementById('class_id').addEventListener('change', function() {
    if (this.value) {
        this.form.submit();
    }
});
</script>

<!-- Include XLSX library for Excel export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<?php include '../includes/footer.php'; ?>
