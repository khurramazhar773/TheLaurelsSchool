<?php
/**
 * Attendance Summary Widget
 * This file provides a quick overview of attendance statistics
 */

// Session is already started, so we don't need to start it again
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$pdo = getDBConnection();

// Get today's attendance summary
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Today's stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_marked,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused
    FROM student_attendance 
    WHERE attendance_date = ?
");
$stmt->execute([$today]);
$today_stats = $stmt->fetch();

// Yesterday's stats for comparison
$stmt->execute([$yesterday]);
$yesterday_stats = $stmt->fetch();

// Get total active students
$stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
$total_students = $stmt->fetch()['total'];

// Calculate percentages
$today_attendance_rate = $total_students > 0 ? round(($today_stats['present'] / $total_students) * 100, 1) : 0;
$yesterday_attendance_rate = $total_students > 0 ? round(($yesterday_stats['present'] / $total_students) * 100, 1) : 0;

// Calculate change from yesterday
$attendance_change = $today_attendance_rate - $yesterday_attendance_rate;
$change_class = $attendance_change > 0 ? 'text-success' : ($attendance_change < 0 ? 'text-danger' : 'text-muted');
$change_icon = $attendance_change > 0 ? 'fa-arrow-up' : ($attendance_change < 0 ? 'fa-arrow-down' : 'fa-minus');

// Get classes with lowest attendance today
$stmt = $pdo->prepare("
    SELECT c.name as class_name, 
           COUNT(sa.id) as marked,
           SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present,
           COUNT(s.id) as total_students
    FROM classes c
    LEFT JOIN enrollments e ON c.id = e.class_id AND e.status = 'active'
    LEFT JOIN students s ON e.student_id = s.id AND s.status = 'active'
    LEFT JOIN student_attendance sa ON s.id = sa.student_id AND sa.attendance_date = ?
    GROUP BY c.id, c.name
    HAVING marked > 0
    ORDER BY (present / NULLIF(marked, 0)) ASC
    LIMIT 3
");
$stmt->execute([$today]);
$lowest_attendance_classes = $stmt->fetchAll();

// Get recent attendance trends (last 7 days)
$stmt = $pdo->prepare("
    SELECT 
        attendance_date,
        COUNT(*) as total_marked,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
    FROM student_attendance 
    WHERE attendance_date >= DATE_SUB(?, INTERVAL 6 DAY)
    GROUP BY attendance_date
    ORDER BY attendance_date ASC
");
$stmt->execute([$today]);
$trends = $stmt->fetchAll();
?>

<div class="row">
    <!-- Today's Attendance Overview -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-day me-2"></i>Today's Attendance Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h2 text-primary"><?php echo $today_attendance_rate; ?>%</div>
                            <div class="text-muted">Attendance Rate</div>
                            <small class="<?php echo $change_class; ?>">
                                <i class="fas <?php echo $change_icon; ?> me-1"></i>
                                <?php echo abs($attendance_change); ?>% from yesterday
                            </small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <div class="h4 text-success"><?php echo $today_stats['present']; ?></div>
                            <div class="text-muted">Present</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <div class="h4 text-danger"><?php echo $today_stats['absent']; ?></div>
                            <div class="text-muted">Absent</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <div class="h4 text-warning"><?php echo $today_stats['late']; ?></div>
                            <div class="text-muted">Late</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <div class="h4 text-info"><?php echo $today_stats['excused']; ?></div>
                            <div class="text-muted">Excused</div>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="text-center">
                            <div class="h4 text-muted"><?php echo $total_students - $today_stats['total_marked']; ?></div>
                            <div class="text-muted">Unmarked</div>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="mt-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Overall Attendance</span>
                        <span><?php echo $today_attendance_rate; ?>%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar <?php echo $today_attendance_rate >= 90 ? 'bg-success' : ($today_attendance_rate >= 70 ? 'bg-warning' : 'bg-danger'); ?>" 
                             style="width: <?php echo $today_attendance_rate; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="student_attendance.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Mark Today's Attendance
                    </a>
                    <a href="attendance_reports.php" class="btn btn-outline-primary">
                        <i class="fas fa-chart-bar me-2"></i>View Reports
                    </a>
                    <a href="attendance_settings.php" class="btn btn-outline-secondary">
                        <i class="fas fa-cog me-2"></i>Attendance Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Classes with Low Attendance -->
<?php if (!empty($lowest_attendance_classes)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2 text-warning"></i>Classes Needing Attention
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($lowest_attendance_classes as $class): ?>
                        <?php 
                        $class_attendance_rate = $class['marked'] > 0 ? round(($class['present'] / $class['marked']) * 100, 1) : 0;
                        ?>
                        <div class="col-md-4">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <h6 class="card-title"><?php echo htmlspecialchars($class['class_name']); ?></h6>
                                    <div class="h4 <?php echo $class_attendance_rate >= 80 ? 'text-success' : ($class_attendance_rate >= 60 ? 'text-warning' : 'text-danger'); ?>">
                                        <?php echo $class_attendance_rate; ?>%
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $class['present']; ?>/<?php echo $class['marked']; ?> students present
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Attendance Trends Chart -->
<?php if (!empty($trends)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>7-Day Attendance Trend
                </h5>
            </div>
            <div class="card-body">
                <canvas id="attendanceTrendChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('attendanceTrendChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: [
            <?php foreach ($trends as $trend): ?>
                '<?php echo date('M j', strtotime($trend['attendance_date'])); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Attendance Rate %',
            data: [
                <?php foreach ($trends as $trend): ?>
                    <?php echo $total_students > 0 ? round(($trend['present'] / $total_students) * 100, 1) : 0; ?>,
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
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>
<?php endif; ?>
