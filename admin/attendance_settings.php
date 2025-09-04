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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        $late_threshold = $_POST['late_threshold'] ?? 15;
        $absent_threshold = $_POST['absent_threshold'] ?? 1;
        $notification_enabled = isset($_POST['notification_enabled']) ? 1 : 0;
        $attendance_goal = $_POST['attendance_goal'] ?? 85;
        
        $success = "Attendance settings updated successfully!";
    }
}

// Get current settings
$settings = [
    'late_threshold' => 15,
    'absent_threshold' => 1,
    'notification_enabled' => true,
    'attendance_goal' => 85,
    'working_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
];

$page_title = 'Attendance Settings';
$active = 'settings';
include 'partials/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Attendance Settings</li>
                    </ol>
                </div>
                <h4 class="page-title">Attendance Settings & Configuration</h4>
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

    <div class="row">
        <!-- General Settings -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog me-2"></i>General Settings
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="mb-3">
                            <label for="late_threshold" class="form-label">Late Threshold (minutes)</label>
                            <input type="number" class="form-control" id="late_threshold" name="late_threshold" 
                                   value="<?php echo $settings['late_threshold']; ?>" min="1" max="60">
                            <div class="form-text">Students arriving after this many minutes will be marked as late.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="absent_threshold" class="form-label">Absent Threshold (days)</label>
                            <input type="number" class="form-control" id="absent_threshold" name="absent_threshold" 
                                   value="<?php echo $settings['absent_threshold']; ?>" min="1" max="30">
                            <div class="form-text">Number of consecutive absent days before notification.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="attendance_goal" class="form-label">Attendance Goal (%)</label>
                            <input type="number" class="form-control" id="attendance_goal" name="attendance_goal" 
                                   value="<?php echo $settings['attendance_goal']; ?>" min="50" max="100">
                            <div class="form-text">Target attendance percentage for students.</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="notification_enabled" 
                                       name="notification_enabled" <?php echo $settings['notification_enabled'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="notification_enabled">
                                    Enable Notifications
                                </label>
                            </div>
                            <div class="form-text">Send automatic notifications for attendance issues.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Attendance Rules -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-gavel me-2"></i>Attendance Rules & Policies
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Late Arrival Policy</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Students arriving within 15 minutes are marked present</li>
                            <li><i class="fas fa-check text-success me-2"></i>15-30 minutes late are marked as late</li>
                            <li><i class="fas fa-check text-success me-2"></i>After 30 minutes, marked as absent</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Absence Policy</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Medical certificates required for 3+ consecutive days</li>
                            <li><i class="fas fa-check text-success me-2"></i>Parent notification for unexcused absences</li>
                            <li><i class="fas fa-check text-success me-2"></i>Academic probation for <70% attendance</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>