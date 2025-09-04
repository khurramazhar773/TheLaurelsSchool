<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$pdo = getDBConnection();
$student_id = $_GET['id'] ?? 0;
$message = '';

if (!$student_id) {
    redirect('students.php');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        $message = 'Student not found.';
        redirect('students.php');
    }
} catch (Exception $e) {
    $message = 'Error loading student information.';
    redirect('students.php');
}

// Format date for display
function formatDisplayDate($date) {
    if (!$date) return 'N/A';
    return date('F d, Y', strtotime($date));
}

// Format boolean values
function formatBoolean($value) {
    return $value ? 'Yes' : 'No';
}

// Get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'badge bg-warning';
        case 'active': return 'badge bg-success';
        case 'inactive': return 'badge bg-secondary';
        case 'withdrawn': return 'badge bg-danger';
        case 'completed': return 'badge bg-info';
        case 'suspended': return 'badge bg-warning';
        case 'expelled': return 'badge bg-danger';
        case 'transferred': return 'badge bg-secondary';
        case 'graduated': return 'badge bg-success';
        case 'on_leave': return 'badge bg-info';
        default: return 'badge bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
    <?php $active='students'; include __DIR__.'/partials/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Header with Back Button -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3">Student Information</h1>
            <div>
                <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit me-2"></i>Edit Student
                </a>
                <a href="students.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Students
                </a>
            </div>
        </div>

        <!-- Student Basic Info Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-user me-2"></i>Basic Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']); ?></p>
                        <p><strong>Sex:</strong> <?php echo htmlspecialchars($student['sex']); ?></p>
                        <p><strong>Date of Birth:</strong> <?php echo formatDisplayDate($student['date_of_birth']); ?></p>
                        <p><strong>Birth Place:</strong> <?php echo htmlspecialchars($student['birth_place']); ?></p>
                        <p><strong>Religion:</strong> <?php echo htmlspecialchars($student['religion'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> <span class="<?php echo getStatusBadgeClass($student['status']); ?>"><?php echo ucfirst($student['status']); ?></span></p>
                        <p><strong>Application Date:</strong> <?php echo formatDisplayDate($student['application_date']); ?></p>
                        <p><strong>Student ID:</strong> #<?php echo $student['id']; ?></p>
                        <p><strong>Created:</strong> <?php echo formatDisplayDate($student['created_at']); ?></p>
                        <p><strong>Last Updated:</strong> <?php echo formatDisplayDate($student['updated_at']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-map-marker-alt me-2"></i>Address & Contact</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($student['address'])); ?></p>
                        <p><strong>City:</strong> <?php echo htmlspecialchars($student['city']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-phone me-2"></i>Emergency Contact</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($student['emergency_contact_name']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['emergency_contact_phone']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Parent Information -->
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-male me-2"></i>Father's Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($student['father_name']); ?></p>
                        <p><strong>Occupation:</strong> <?php echo htmlspecialchars($student['father_occupation'] ?? 'N/A'); ?></p>
                        <p><strong>Position:</strong> <?php echo htmlspecialchars($student['father_position'] ?? 'N/A'); ?></p>
                        <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($student['father_address'] ?? 'N/A')); ?></p>
                        <p><strong>Work Phone:</strong> <?php echo htmlspecialchars($student['father_work_phone'] ?? 'N/A'); ?></p>
                        <p><strong>Cell:</strong> <?php echo htmlspecialchars($student['father_cell'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-female me-2"></i>Mother's Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($student['mother_name']); ?></p>
                        <p><strong>Occupation:</strong> <?php echo htmlspecialchars($student['mother_occupation'] ?? 'N/A'); ?></p>
                        <p><strong>Position:</strong> <?php echo htmlspecialchars($student['mother_position'] ?? 'N/A'); ?></p>
                        <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($student['mother_address'] ?? 'N/A')); ?></p>
                        <p><strong>Work Phone:</strong> <?php echo htmlspecialchars($student['mother_work_phone'] ?? 'N/A'); ?></p>
                        <p><strong>Cell:</strong> <?php echo htmlspecialchars($student['mother_cell'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Guardian Information (if applicable) -->
        <?php if ($student['guardian_name']): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-user-shield me-2"></i>Guardian Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($student['guardian_name']); ?></p>
                        <p><strong>Relation:</strong> <?php echo htmlspecialchars($student['guardian_relation']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['guardian_phone'] ?? 'N/A'); ?></p>
                        <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($student['guardian_address'] ?? 'N/A')); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Previous School Information -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-school me-2"></i>Previous School Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <p><strong>School Name:</strong> <?php echo htmlspecialchars($student['last_school_attended'] ?? 'N/A'); ?></p>
                        <p><strong>Year:</strong> <?php echo htmlspecialchars($student['last_school_year'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Grade:</strong> <?php echo htmlspecialchars($student['last_school_grade'] ?? 'N/A'); ?></p>
                        <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($student['last_school_address'] ?? 'N/A')); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medical History -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-heartbeat me-2"></i>Medical History</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <ul class="list-unstyled mb-0">
                            <li>Asthma: <?php echo formatBoolean($student['has_asthma']); ?></li>
                            <li>Allergies: <?php echo formatBoolean($student['has_allergies']); ?></li>
                            <li>Heart Disease: <?php echo formatBoolean($student['has_heart_disease']); ?></li>
                            <li>Convulsions: <?php echo formatBoolean($student['has_convulsions']); ?></li>
                            <li>Diabetes: <?php echo formatBoolean($student['has_diabetes']); ?></li>
                            <li>Cancer: <?php echo formatBoolean($student['has_cancer']); ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled mb-0">
                            <li>Tuberculosis: <?php echo formatBoolean($student['has_tuberculosis']); ?></li>
                            <li>Epilepsy: <?php echo formatBoolean($student['has_epilepsy']); ?></li>
                            <li>Hearing Problems: <?php echo formatBoolean($student['has_hearing_problems']); ?></li>
                            <li>Speech Problems: <?php echo formatBoolean($student['has_speech_problems']); ?></li>
                            <li>Orthopedic Problems: <?php echo formatBoolean($student['has_orthopedic_problems']); ?></li>
                            <li>Other Problems: <?php echo formatBoolean($student['has_other_problems']); ?></li>
                        </ul>
                    </div>
                </div>
                
                <?php if ($student['other_problems_description']): ?>
                <div class="mt-3">
                    <p class="mb-1"><strong>Other Problems Description:</strong></p>
                    <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($student['other_problems_description'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($student['major_operations_injuries']): ?>
                <div class="mt-3">
                    <p class="mb-1"><strong>Major Operations/Injuries:</strong></p>
                    <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($student['major_operations_injuries'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($student['regular_medication']): ?>
                <div class="mt-3">
                    <p class="mb-1"><strong>Regular Medication:</strong></p>
                    <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($student['regular_medication'])); ?></p>
                </div>
                <?php endif; ?>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <p><strong>Family Physician:</strong> <?php echo htmlspecialchars($student['family_physician_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Physician Phone:</strong> <?php echo htmlspecialchars($student['family_physician_phone'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- How They Heard About The School -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>How They Heard About The Laurels School</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <ul class="list-unstyled mb-0">
                            <li>Newspapers: <?php echo formatBoolean($student['heard_through_newspapers']); ?></li>
                            <li>Advertisements: <?php echo formatBoolean($student['heard_through_advertisements']); ?></li>
                            <li>Friends: <?php echo formatBoolean($student['heard_through_friends']); ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled mb-0">
                            <li>Relatives: <?php echo formatBoolean($student['heard_through_relatives']); ?></li>
                            <li>Other: <?php echo formatBoolean($student['heard_through_other']); ?></li>
                        </ul>
                    </div>
                </div>
                
                <?php if ($student['heard_through_other_description']): ?>
                <div class="mt-3">
                    <p class="mb-1"><strong>Other Source Description:</strong></p>
                    <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($student['heard_through_other_description'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Documents and Verification -->
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-file-alt me-2"></i>Required Documents</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li>Passport Photo: <?php echo $student['passport_photo'] ? 'Uploaded' : 'Not Uploaded'; ?></li>
                            <li>School Leaving Certificate: <?php echo $student['school_leaving_certificate'] ? 'Uploaded' : 'Not Uploaded'; ?></li>
                            <li>Recent Exam Results: <?php echo $student['recent_exam_results'] ? 'Uploaded' : 'Not Uploaded'; ?></li>
                            <li>Father's NIC: <?php echo $student['father_nic_copy'] ? 'Uploaded' : 'Not Uploaded'; ?></li>
                            <li>Mother's NIC: <?php echo $student['mother_nic_copy'] ? 'Uploaded' : 'Not Uploaded'; ?></li>
                            <li>Guardian's NIC: <?php echo $student['guardian_nic_copy'] ? 'Uploaded' : 'Not Uploaded'; ?></li>
                            <li>Birth Certificate: <?php echo $student['birth_certificate'] ? 'Uploaded' : 'Not Uploaded'; ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-check-circle me-2"></i>Verification Status</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Verified:</strong>
                            <span class="<?php echo $student['is_verified'] ? 'badge bg-success' : 'badge bg-warning'; ?>"><?php echo $student['is_verified'] ? 'Yes' : 'No'; ?></span>
                        </p>
                        <?php if ($student['verified_by']): ?>
                        <p><strong>Verified By:</strong> User ID #<?php echo $student['verified_by']; ?></p>
                        <?php endif; ?>
                        <?php if ($student['verified_at']): ?>
                        <p><strong>Verified At:</strong> <?php echo formatDisplayDate($student['verified_at']); ?></p>
                        <?php endif; ?>
                        <?php if ($student['verification_notes']): ?>
                        <p class="mb-1"><strong>Verification Notes:</strong></p>
                        <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($student['verification_notes'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="text-center mb-4">
            <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-warning btn-lg me-3">
                <i class="fas fa-edit me-2"></i>Edit Student
            </a>
            <a href="students.php" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-left me-2"></i>Back to Students List
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 