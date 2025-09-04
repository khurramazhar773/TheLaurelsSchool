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

if (!$student_id) {
    redirect('students.php');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        redirect('students.php');
    }
} catch (Exception $e) {
    redirect('students.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student - The Laurels School LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <!-- Header with Back Button -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>Student Information</h1>
                    <a href="students.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Go Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Student Details -->
        <div class="card">
            <div class="card-header">
                <h5>Student #<?php echo $student['id']; ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Personal Information</h6>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                        <p><strong>Sex:</strong> <?php echo htmlspecialchars($student['sex']); ?></p>
                        <p><strong>Date of Birth:</strong> <?php echo $student['date_of_birth']; ?></p>
                        <p><strong>Status:</strong> <span class="badge bg-primary"><?php echo ucfirst($student['status']); ?></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Contact Information</h6>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($student['address']); ?></p>
                        <p><strong>City:</strong> <?php echo htmlspecialchars($student['city']); ?></p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6>Father's Information</h6>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($student['father_name']); ?></p>
                        <p><strong>Occupation:</strong> <?php echo htmlspecialchars($student['father_occupation'] ?? 'N/A'); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['father_cell'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Mother's Information</h6>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($student['mother_name']); ?></p>
                        <p><strong>Occupation:</strong> <?php echo htmlspecialchars($student['mother_occupation'] ?? 'N/A'); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['mother_cell'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Emergency Contact</h6>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($student['emergency_contact_name']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['emergency_contact_phone']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit me-2"></i>Edit Student
                </a>
                <a href="students.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Students
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 