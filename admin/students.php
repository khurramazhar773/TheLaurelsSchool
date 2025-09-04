<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';
$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                if (isset($_POST['student_id'])) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
                        $stmt->execute([$_POST['student_id']]);
                        $message = 'Student deleted successfully.';
                        $action = 'list';
                    } catch (Exception $e) {
                        $message = 'Error deleting student.';
                    }
                }
                break;
        }
    }
}

// Handle GET actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'add':
            // Redirect to add student page
            redirect('add_student.php');
            break;
        case 'edit':
            // Redirect to edit student page
            if (isset($_GET['id'])) {
                redirect('edit_student.php?id=' . $_GET['id']);
            }
            break;
        case 'view':
            // Redirect to view student page
            if (isset($_GET['id'])) {
                redirect('view_student.php?id=' . $_GET['id']);
            }
            break;
    }
}

// Get students list
$students = [];
if ($action === 'list') {
    try {
        $status_filter = $_GET['status'] ?? '';
        $search = trim((string)($_GET['search'] ?? ''));
        $isNumeric = ctype_digit($search);
        $isPhone11 = $isNumeric && strlen($search) === 11;
        $isIdNumeric = $isNumeric && strlen($search) > 0 && strlen($search) < 11;
        
        $sql = "SELECT * FROM students WHERE 1=1";
        $params = [];
        
        if ($status_filter) {
            $sql .= " AND status = ?";
            $params[] = $status_filter;
        }
        
        if ($search !== '') {
            if ($isPhone11) {
                // 11-digit numeric: search by phone exactly only
                $sql .= " AND phone = ?";
                $params[] = $search;
            } elseif ($isIdNumeric) {
                // Numeric but not 11 digits: search by ID only
                $sql .= " AND id = ?";
                $params[] = (int)$search;
            } else {
                // Generic text search across multiple fields
                $sql .= " AND (first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR city LIKE ? OR father_name LIKE ? OR mother_name LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll();
    } catch (Exception $e) {
        $message = 'Error loading students.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
    <?php $active='students'; include __DIR__.'/partials/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Students Management</h1>
                    <a href="add_student.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New Student
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                                       placeholder="Search by 11-digit phone (exact), ID (number), name, city, parents...">
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="active" <?php echo ($_GET['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="withdrawn" <?php echo ($_GET['status'] ?? '') === 'withdrawn' ? 'selected' : ''; ?>>Withdrawn</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-search me-1"></i>Filter
                                    </button>
                                    <a href="students.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive rounded-lg">
                            <table class="table table-striped table-hover" id="studentsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Application Date</th>
                                        <th>Phone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo $student['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                            <?php if ($student['middle_name']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($student['middle_name']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch ($student['status']) {
                                                case 'pending': $statusClass = 'badge bg-warning'; break;
                                                case 'active': $statusClass = 'badge bg-success'; break;
                                                case 'inactive': $statusClass = 'badge bg-secondary'; break;
                                                case 'withdrawn': $statusClass = 'badge bg-danger'; break;
                                                case 'completed': $statusClass = 'badge bg-info'; break;
                                                case 'suspended': $statusClass = 'badge bg-warning'; break;
                                                case 'expelled': $statusClass = 'badge bg-danger'; break;
                                                case 'transferred': $statusClass = 'badge bg-secondary'; break;
                                                case 'graduated': $statusClass = 'badge bg-success'; break;
                                                case 'on_leave': $statusClass = 'badge bg-info'; break;
                                                default: $statusClass = 'badge bg-secondary';
                                            }
                                            ?>
                                            <span class="<?php echo $statusClass; ?>"><?php echo ucfirst($student['status']); ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" 
                                                        onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
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
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete student: <strong id="studentName"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="student_id" id="studentId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteStudent(id, name) {
            document.getElementById('studentId').value = id;
            document.getElementById('studentName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html> 