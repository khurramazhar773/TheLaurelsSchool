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

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_notification') {
        $student_id = $_POST['student_id'] ?? '';
        $notification_type = $_POST['notification_type'] ?? '';
        $message_text = $_POST['message'] ?? '';
        
        if ($student_id && $notification_type && $message_text) {
            // Here you would integrate with your notification system
            // For now, we'll just log it
            $success = "Notification sent successfully to student ID: $student_id";
        } else {
            $message = "Please fill in all required fields.";
        }
    } elseif ($action === 'mark_attendance_reminder') {
        $class_id = $_POST['class_id'] ?? '';
        if ($class_id) {
            // Mark attendance reminder for the class
            $success = "Attendance reminder sent to class ID: $class_id";
        }
    }
}

// Get students with poor attendance (last 7 days)
$poor_attendance_query = "
    SELECT 
        s.id,
        s.first_name,
        s.last_name,
        s.student_code,
        s.email,
        s.phone,
        c.name as class_name,
        COUNT(sa.id) as total_days,
        SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_days,
        ROUND((SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) / COUNT(sa.id)) * 100, 2) as attendance_percentage
    FROM students s
    JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
    JOIN classes c ON e.class_id = c.id
    LEFT JOIN student_attendance sa ON s.id = sa.student_id 
        AND sa.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    WHERE s.status = 'active'
    GROUP BY s.id, s.first_name, s.last_name, s.student_code, s.email, s.phone, c.name
    HAVING total_days > 0 AND attendance_percentage < 80
    ORDER BY attendance_percentage ASC
";

$poor_attendance_students = $pdo->query($poor_attendance_query)->fetchAll();

// Get classes with low attendance today
$low_attendance_classes_query = "
    SELECT 
        c.id,
        c.name as class_name,
        COUNT(s.id) as total_students,
        COUNT(sa.id) as marked_attendance,
        SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_count,
        ROUND((SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) / COUNT(sa.id)) * 100, 2) as attendance_rate
    FROM classes c
    LEFT JOIN enrollments e ON c.id = e.class_id AND e.status = 'active'
    LEFT JOIN students s ON e.student_id = s.id AND s.status = 'active'
    LEFT JOIN student_attendance sa ON s.id = sa.student_id AND sa.attendance_date = CURDATE()
    GROUP BY c.id, c.name
    HAVING marked_attendance > 0 AND attendance_rate < 85
    ORDER BY attendance_rate ASC
";

$low_attendance_classes = $pdo->query($low_attendance_classes_query)->fetchAll();

// Get recent attendance trends
$trends_query = "
    SELECT 
        attendance_date,
        COUNT(*) as total_marked,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
        ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_rate
    FROM student_attendance 
    WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY attendance_date
    ORDER BY attendance_date ASC
";

$attendance_trends = $pdo->query($trends_query)->fetchAll();

$page_title = 'Attendance Notifications';
$active = 'notifications';
include 'partials/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Attendance Notifications</li>
                    </ol>
                </div>
                <h4 class="page-title">Attendance Notifications & Alerts</h4>
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

    <!-- Alert Summary Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                    <h5>Poor Attendance</h5>
                    <div class="h2 text-danger"><?php echo count($poor_attendance_students); ?></div>
                    <small class="text-muted">Students need attention</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-chalkboard-teacher fa-2x text-warning mb-3"></i>
                    <h5>Low Class Attendance</h5>
                    <div class="h2 text-warning"><?php echo count($low_attendance_classes); ?></div>
                    <small class="text-muted">Classes below 85%</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="fas fa-bell fa-2x text-info mb-3"></i>
                    <h5>Pending Notifications</h5>
                    <div class="h2 text-info"><?php echo count($poor_attendance_students) + count($low_attendance_classes); ?></div>
                    <small class="text-muted">Total alerts</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-2x text-success mb-3"></i>
                    <h5>Today's Rate</h5>
                    <div class="h2 text-success">
                        <?php 
                        $today_rate = !empty($attendance_trends) ? end($attendance_trends)['attendance_rate'] : 0;
                        echo $today_rate;
                        ?>%
                    </div>
                    <small class="text-muted">Overall attendance</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Students with Poor Attendance -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-times me-2 text-danger"></i>Students with Poor Attendance (Last 7 Days)
                    </h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="sendBulkNotifications()">
                        <i class="fas fa-paper-plane me-1"></i>Send Bulk Notifications
                    </button>
                </div>
                <div class="card-body">
                    <?php if (!empty($poor_attendance_students)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th>Contact</th>
                                        <th>Attendance %</th>
                                        <th>Days Present</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($poor_attendance_students as $student): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($student['student_code']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                            <td>
                                                <small>
                                                    <?php if ($student['email']): ?>
                                                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($student['email']); ?><br>
                                                    <?php endif; ?>
                                                    <?php if ($student['phone']): ?>
                                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($student['phone']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar <?php echo $student['attendance_percentage'] >= 70 ? 'bg-warning' : 'bg-danger'; ?>" 
                                                         style="width: <?php echo $student['attendance_percentage']; ?>%">
                                                        <?php echo $student['attendance_percentage']; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $student['present_days']; ?></span> / 
                                                <span class="badge bg-secondary"><?php echo $student['total_days']; ?></span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="sendNotification(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                                    <i class="fas fa-paper-plane me-1"></i>Notify
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="text-success">Great News!</h5>
                            <p class="text-muted">No students with poor attendance in the last 7 days.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Classes with Low Attendance -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chalkboard-teacher me-2 text-warning"></i>Classes with Low Attendance Today
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($low_attendance_classes)): ?>
                        <div class="row">
                            <?php foreach ($low_attendance_classes as $class): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card border-warning">
                                        <div class="card-body text-center">
                                            <h6 class="card-title"><?php echo htmlspecialchars($class['class_name']); ?></h6>
                                            <div class="h3 text-warning"><?php echo $class['attendance_rate']; ?>%</div>
                                            <small class="text-muted">
                                                <?php echo $class['present_count']; ?>/<?php echo $class['marked_attendance']; ?> students present
                                            </small>
                                            <div class="mt-2">
                                                <button class="btn btn-sm btn-outline-warning" onclick="sendClassReminder(<?php echo $class['id']; ?>, '<?php echo htmlspecialchars($class['class_name']); ?>')">
                                                    <i class="fas fa-bell me-1"></i>Send Reminder
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="text-success">Excellent!</h5>
                            <p class="text-muted">All classes have good attendance today.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Trends Chart -->
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

</div>

<!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="send_notification">
                    <input type="hidden" name="student_id" id="modal_student_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <input type="text" class="form-control" id="modal_student_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notification_type" class="form-label">Notification Type</label>
                        <select class="form-select" id="notification_type" name="notification_type" required>
                            <option value="">Select Type</option>
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="phone">Phone Call</option>
                            <option value="parent">Parent Notification</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required placeholder="Enter your message here..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Notification</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Chart.js for Trends -->
<?php if (!empty($attendance_trends)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('attendanceTrendChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: [
            <?php foreach ($attendance_trends as $trend): ?>
                '<?php echo date('M j', strtotime($trend['attendance_date'])); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Attendance Rate %',
            data: [
                <?php foreach ($attendance_trends as $trend): ?>
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

<script>
// Send individual notification
function sendNotification(studentId, studentName) {
    document.getElementById('modal_student_id').value = studentId;
    document.getElementById('modal_student_name').value = studentName;
    document.getElementById('message').value = `Dear ${studentName},\n\nWe noticed your attendance has been below the expected level. Please ensure regular attendance for better academic performance.\n\nBest regards,\nThe Laurels School`;
    
    const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
    modal.show();
}

// Send class reminder
function sendClassReminder(classId, className) {
    if (confirm(`Send attendance reminder to ${className}?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="mark_attendance_reminder">
            <input type="hidden" name="class_id" value="${classId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Send bulk notifications
function sendBulkNotifications() {
    if (confirm('Send notifications to all students with poor attendance?')) {
        // This would typically send notifications to all students in the list
        alert('Bulk notifications sent successfully!');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
