<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Only admins
if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';
$message = '';

// Handle add user POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    try {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'teacher';
        $status = $_POST['status'] ?? 'active';
        if (!$first_name || !$last_name || !$email || !$password) { throw new Exception('Please fill all required fields.'); }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new Exception('Invalid email address.'); }
        // Ensure unique email
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) { throw new Exception('Email already exists.'); }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (first_name,last_name,email,password,role,status,created_at,updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([$first_name,$last_name,$email,$hash,$role,$status]);
        header('Location: users.php?success=1');
        exit;
    } catch (Exception $e) {
        $message = $e->getMessage();
        $action = 'add';
    }
}

// Handle delete user POST (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    try {
        $userIdToDelete = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($userIdToDelete <= 0) { throw new Exception('Invalid user.'); }
        // Prevent deleting yourself
        if ($userIdToDelete === (int)($_SESSION['user_id'] ?? 0)) { throw new Exception('You cannot delete your own account.'); }
        // Optional: prevent deleting the last admin
        $roleStmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
        $roleStmt->execute([$userIdToDelete]);
        $target = $roleStmt->fetch();
        if (!$target) { throw new Exception('User not found.'); }
        if ($target['role'] === 'admin') {
            $adminCount = (int)$pdo->query("SELECT COUNT(*) as c FROM users WHERE role='admin' AND status='active'")->fetch()['c'];
            if ($adminCount <= 1) { throw new Exception('Cannot delete the last active admin.'); }
        }
        $del = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $del->execute([$userIdToDelete]);
        header('Location: users.php?deleted=1');
        exit;
    } catch (Exception $e) {
        $message = $e->getMessage();
        $action = 'list';
    }
}

// Load users for list
$users = [];
if ($action === 'list') {
    $stmt = $pdo->query('SELECT id,first_name,last_name,email,role,status,created_at FROM users ORDER BY created_at DESC');
    $users = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
<?php $active='users'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <?php if (isset($_GET['success'])): ?><div class="alert alert-success">User saved successfully.</div><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">User deleted successfully.</div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

    <?php if ($action === 'add'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4">Add New User</h1>
            <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
        </div>
        <div class="card">
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action" value="add">
                    <div class="col-md-6">
                        <label class="form-label">First Name *</label>
                        <input name="first_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name *</label>
                        <input name="last_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-select">
                            <option value="admin" <?php echo (($_POST['role'] ?? '')==='admin')?'selected':''; ?>>Admin</option>
                            <option value="teacher" <?php echo (($_POST['role'] ?? '')==='teacher' || !isset($_POST['role']))?'selected':''; ?>>Teacher</option>
                            <option value="student" <?php echo (($_POST['role'] ?? '')==='student')?'selected':''; ?>>Student</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo (($_POST['status'] ?? 'active')==='active')?'selected':''; ?>>Active</option>
                            <option value="inactive" <?php echo (($_POST['status'] ?? '')==='inactive')?'selected':''; ?>>Inactive</option>
                            <option value="suspended" <?php echo (($_POST['status'] ?? '')==='suspended')?'selected':''; ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <a href="users.php" class="btn btn-secondary me-2">Cancel</a>
                        <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Save User</button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4">Users</h1>
            <a href="users.php?action=add" class="btn btn-primary"><i class="fas fa-user-plus me-2"></i>Add New User</a>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive rounded-lg">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td><?php echo htmlspecialchars($u['first_name'].' '.$u['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($u['role']); ?></span></td>
                                    <td>
                                        <?php
                                            $cls = $u['status']==='active'?'bg-success':($u['status']==='inactive'?'bg-secondary':'bg-warning');
                                        ?>
                                        <span class="badge <?php echo $cls; ?>"><?php echo htmlspecialchars($u['status']); ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['first_name'].' '.$u['last_name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete user: <strong id="delUserName"></strong>?</p>
                <p class="text-danger mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="delUserId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(id, name) {
    document.getElementById('delUserId').value = id;
    document.getElementById('delUserName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
</body>
</html> 