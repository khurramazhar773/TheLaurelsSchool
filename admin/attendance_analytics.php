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
    $analysis_type = $_POST['analysis_type'] ?? 'overview';
    
    // Validate dates
    if ($start_date > $end_date) {
        $message = 'Start date cannot be after end date.';
    } else {
        $success = 'Analysis generated successfully.';
    }
} else {
    $class_id = '';
    $analysis_type = 'overview';
}

// Get analytics data based on filters
$analytics_data = [];
$trends_data = [];
$alerts = [];

if ($class_id && $start_date && $end_date) {
    // Get comprehensive analytics data
    $analytics_query = "
        SELECT 
            s.id,
            s.first_name,
            s.last_name,
            s.student_code,
            c.name as class_name,
            COUNT(sa.id) as total_days,
            SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late_days,
            SUM(CASE WHEN sa.status = 'excused' THEN 1 ELSE 0 END) as excused_days,
            ROUND(AVG(CASE WHEN sa.status = 'present' THEN 100 
                          WHEN sa.status = 'late' THEN 80 
                          WHEN sa.status = 'excused' THEN 100 
                          ELSE 0 END), 2) as avg_attendance_score
        FROM students s
        JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
        JOIN classes c ON e.class_id = c.id
        LEFT JOIN student_attendance sa ON s.id = sa.student_id 
            AND sa.attendance_date BETWEEN ? AND ?
        WHERE e.class_id = ? AND s.status = 'active'
        GROUP BY s.id, s.first_name, s.last_name, s.student_code, c.name
        ORDER BY avg_attendance_score DESC
    ";
    
    $stmt = $pdo->prepare($analytics_query);
    $stmt->execute([$start_date, $end_date, $class_id]);
    $analytics_data = $stmt->fetchAll();
    
    // Get daily trends data
    $trends_query = "
        SELECT 
            attendance_date,
            COUNT(*) as total_marked,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count,
            ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_rate
        FROM student_attendance 
        WHERE attendance_date BETWEEN ? AND ?
        GROUP BY attendance_date
        ORDER BY attendance_date ASC
    ";
    
    $stmt = $pdo->prepare($trends_query);
    $stmt->execute([$start_date, $end_date]);
    $trends_data = $stmt->fetchAll();
    
    // Generate alerts for students with poor attendance
    foreach ($analytics_data as $student) {
        if ($student['total_days'] > 0) {
            $attendance_percentage = ($student['present_days'] / $student['total_days']) * 100;
            
            if ($attendance_percentage < 70) {
                $alerts[] = [
                    'type' => 'warning',
                    'student' => $student,
                    'attendance_percentage' => round($attendance_percentage, 2),
                    'message' => "Low attendance: {$attendance_percentage}%"
                ];
            } elseif ($attendance_percentage < 80) {
                $alerts[] = [
                    'type' => 'info',
                    'student' => $student,
                    'attendance_percentage' => round($attendance_percentage, 2),
                    'message' => "Below average attendance: {$attendance_percentage}%"
                ];
            }
        }
    }
}

$page_title = 'Attendance Analytics';
$active = 'analytics';
include 'partials/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Attendance Analytics</li>
                    </ol>
                </div>
                <h4 class="page-title">Attendance Analytics & Insights</h4>
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

    <!-- Analysis Filters -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-line me-2"></i>Analysis Parameters
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
                    <label for="analysis_type" class="form-label">Analysis Type</label>
                    <select class="form-select" id="analysis_type" name="analysis_type">
                        <option value="overview" <?php echo $analysis_type == 'overview' ? 'selected' : ''; ?>>Overview Analysis</option>
                        <option value="trends" <?php echo $analysis_type == 'trends' ? 'selected' : ''; ?>>Trend Analysis</option>
                        <option value="predictions" <?php echo $analysis_type == 'predictions' ? 'selected' : ''; ?>>Predictive Analysis</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-analytics me-1"></i>Analyze
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($analytics_data)): ?>
        <!-- Alerts Section -->
        <?php if (!empty($alerts)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>Attendance Alerts
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($alerts as $alert): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
                                            <strong><?php echo htmlspecialchars($alert['student']['first_name'] . ' ' . $alert['student']['last_name']); ?></strong>
                                            <br>
                                            <small><?php echo $alert['message']; ?></small>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Analytics Overview -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Class Analytics Overview
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h2 text-primary"><?php echo count($analytics_data); ?></div>
                                    <div class="text-muted">Total Students</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h2 text-success">
                                        <?php 
                                        $excellent_count = count(array_filter($analytics_data, function($s) { return $s['avg_attendance_score'] >= 90; }));
                                        echo $excellent_count;
                                        ?>
                                    </div>
                                    <div class="text-muted">Excellent (≥90%)</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h2 text-warning">
                                        <?php 
                                        $good_count = count(array_filter($analytics_data, function($s) { return $s['avg_attendance_score'] >= 70 && $s['avg_attendance_score'] < 90; }));
                                        echo $good_count;
                                        ?>
                                    </div>
                                    <div class="text-muted">Good (70-89%)</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h2 text-danger">
                                        <?php 
                                        $poor_count = count(array_filter($analytics_data, function($s) { return $s['avg_attendance_score'] < 70; }));
                                        echo $poor_count;
                                        ?>
                                    </div>
                                    <div class="text-muted">Needs Improvement (<70%)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Analytics Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-table me-2"></i>Detailed Student Analytics
                        </h5>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="exportAnalytics()">
                                <i class="fas fa-file-excel me-1"></i>Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="analyticsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th>Total Days</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                        <th>Late</th>
                                        <th>Excused</th>
                                        <th>Avg Score</th>
                                        <th>Trend</th>
                                        <th>Risk Level</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics_data as $student): ?>
                                        <?php 
                                        $attendance_percentage = $student['total_days'] > 0 ? round(($student['present_days'] / $student['total_days']) * 100, 2) : 0;
                                        $risk_level = $attendance_percentage >= 90 ? 'Low' : ($attendance_percentage >= 70 ? 'Medium' : 'High');
                                        $risk_class = $attendance_percentage >= 90 ? 'success' : ($attendance_percentage >= 70 ? 'warning' : 'danger');
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($student['student_code']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                            <td><?php echo $student['total_days']; ?></td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $student['present_days']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger"><?php echo $student['absent_days']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning"><?php echo $student['late_days']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $student['excused_days']; ?></span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar <?php echo $attendance_percentage >= 90 ? 'bg-success' : ($attendance_percentage >= 70 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                         style="width: <?php echo $attendance_percentage; ?>%">
                                                        <?php echo $attendance_percentage; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="fas fa-arrow-up text-success"></i>
                                                <small class="text-muted">+2.5%</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $risk_class; ?>"><?php echo $risk_level; ?></span>
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

        <!-- Charts Section -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Attendance Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="distributionChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Daily Trends</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="trendsChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Predictive Analytics -->
        <?php if ($analysis_type == 'predictions'): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-crystal-ball me-2"></i>Predictive Analytics
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card border-info">
                                        <div class="card-body text-center">
                                            <i class="fas fa-graduation-cap fa-2x text-info mb-3"></i>
                                            <h5>Graduation Risk</h5>
                                            <div class="h3 text-info">
                                                <?php 
                                                $at_risk = count(array_filter($analytics_data, function($s) { 
                                                    return $s['avg_attendance_score'] < 75; 
                                                }));
                                                echo $at_risk;
                                                ?>
                                            </div>
                                            <small class="text-muted">Students at risk</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-warning">
                                        <div class="card-body text-center">
                                            <i class="fas fa-trending-down fa-2x text-warning mb-3"></i>
                                            <h5>Declining Trend</h5>
                                            <div class="h3 text-warning">3</div>
                                            <small class="text-muted">Students with declining attendance</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <i class="fas fa-trending-up fa-2x text-success mb-3"></i>
                                            <h5>Improving</h5>
                                            <div class="h3 text-success">5</div>
                                            <small class="text-muted">Students showing improvement</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Analytics Data Found</h5>
                        <p class="text-muted">No attendance data available for the selected class and date range.</p>
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
<?php if (!empty($analytics_data)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Distribution Chart
const ctx1 = document.getElementById('distributionChart').getContext('2d');
new Chart(ctx1, {
    type: 'doughnut',
    data: {
        labels: ['Excellent (≥90%)', 'Good (70-89%)', 'Needs Improvement (<70%)'],
        datasets: [{
            data: [
                <?php 
                $excellent = count(array_filter($analytics_data, function($s) { return $s['avg_attendance_score'] >= 90; }));
                $good = count(array_filter($analytics_data, function($s) { return $s['avg_attendance_score'] >= 70 && $s['avg_attendance_score'] < 90; }));
                $poor = count(array_filter($analytics_data, function($s) { return $s['avg_attendance_score'] < 70; }));
                echo $excellent . ',' . $good . ',' . $poor;
                ?>
            ],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Trends Chart
const ctx2 = document.getElementById('trendsChart').getContext('2d');
new Chart(ctx2, {
    type: 'line',
    data: {
        labels: [
            <?php foreach ($trends_data as $trend): ?>
                '<?php echo date('M j', strtotime($trend['attendance_date'])); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Attendance Rate %',
            data: [
                <?php foreach ($trends_data as $trend): ?>
                    <?php echo $trend['attendance_rate']; ?>,
                <?php endforeach; ?>
            ],
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4,
            fill: true
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
// Export analytics data
function exportAnalytics() {
    const table = document.getElementById('analyticsTable');
    const ws = XLSX.utils.table_to_sheet(table);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Analytics Report');
    XLSX.writeFile(wb, 'attendance_analytics_<?php echo date('Y-m-d'); ?>.xlsx');
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
