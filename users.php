<?php
$pageTitle = 'User Management';
$pageSubtitle = 'Manage system users and roles';
require_once __DIR__ . '/auth.php';
requireRole('admin');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $username = strtolower(sanitize($_POST['username']));
        if (preg_match('/[A-Z]/', $_POST['username'])) {
            $error = "Username must contain only lowercase letters, numbers, and underscores.";
        } else if (!preg_match('/^[a-z0-9_]+$/', $username)) {
            $error = "Username can only contain lowercase letters, numbers, and underscores.";
        }
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $fullName = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $role = sanitize($_POST['role']);

        if (empty($error)) try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $fullName, $email, $phone, $role]);
            $success = "User '$username' created successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Username '$username' already exists.";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }

    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $fullName = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $role = sanitize($_POST['role']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        try {
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, is_active=?, password=? WHERE id=?");
                $stmt->execute([$fullName, $email, $phone, $role, $isActive, $password, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, is_active=? WHERE id=?");
                $stmt->execute([$fullName, $email, $phone, $role, $isActive, $id]);
            }
            $success = "User updated successfully!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        if ($id == getUserId()) {
            $error = "You cannot delete your own account.";
        } else {
            try {
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
                $success = "User deleted.";
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?>
    <div class="toast toast-success" id="toast"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="toast toast-error" id="toast"><i class="fas fa-times-circle"></i> <?php echo $error; ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-left">
        <span style="color:var(--text-muted);font-size:14px;"><?php echo count($users); ?> users in system</span>
    </div>
    <div class="toolbar-right">
        <button class="btn btn-primary" onclick="openModal('addUserModal')">
            <i class="fas fa-user-plus"></i> <span data-translate="Add User">Add User</span>
        </button>
    </div>
</div>

<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th data-translate="User">User</th>
                    <th data-translate="Username">Username</th>
                    <th data-translate="Role">Role</th>
                    <th data-translate="Email">Email</th>
                    <th data-translate="Phone">Phone</th>
                    <th data-translate="Status">Status</th>
                    <th data-translate="Created">Created</th>
                    <th data-translate="Actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="user-avatar <?php echo $u['role']; ?>-avatar"
                                    style="width:34px;height:34px;font-size:13px;border-radius:8px;">
                                    <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                                </div>
                                <strong
                                    style="color:var(--text-primary);"><?php echo htmlspecialchars($u['full_name']); ?></strong>
                            </div>
                        </td>
                        <td style="color:var(--accent-primary);font-weight:500;"><?php echo $u['username']; ?></td>
                        <td><span class="badge badge-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span>
                        </td>
                        <td style="font-size:13px;"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td style="font-size:13px;"><?php echo htmlspecialchars($u['phone']); ?></td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge badge-completed">Active</span>
                            <?php else: ?>
                                <span class="badge badge-pending">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:13px;color:var(--text-muted);"><?php echo formatDate($u['created_at']); ?></td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-secondary"
                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($u)); ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($u['id'] != getUserId()): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus" style="color:var(--accent-primary);margin-right:8px;"></i><span data-translate="Add New User">Add New User</span></h3>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label><span data-translate="Full Name">Full Name</span> <span class="required">*</span></label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><span data-translate="Username">Username</span> <span class="required">*</span></label>
                        <input type="text" name="username" id="add_username" class="form-control" required
                            pattern="[a-z0-9_]+" title="Lowercase letters, numbers and underscores only"
                            oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '')">
                        <small style="color:var(--text-muted);font-size:11px;margin-top:4px;display:block;">Lowercase letters, numbers &amp; underscores only</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><span data-translate="Password">Password</span> <span class="required">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label><span data-translate="Role">Role</span> <span class="required">*</span></label>
                        <select name="role" class="form-control" required>
                            <option value="technician">Technician</option>
                            <option value="doctor">Doctor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')" data-translate="Cancel">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <span data-translate="Create User">Create User</span></button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-edit" style="color:var(--warning);margin-right:8px;"></i><span data-translate="Edit User">Edit User</span></h3>
            <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="eu_id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" id="eu_full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>New Password <small>(leave blank to keep current)</small></label>
                        <input type="password" name="password" class="form-control" minlength="6">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Role <span class="required">*</span></label>
                        <select name="role" id="eu_role" class="form-control" required>
                            <option value="technician">Technician</option>
                            <option value="doctor">Doctor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="is_active" id="eu_active"
                                style="accent-color:var(--success);width:18px;height:18px;">
                            Active
                        </label>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="eu_email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" id="eu_phone" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')" data-translate="Cancel">Cancel</button>
                <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <span data-translate="Update User">Update User</span></button>
            </div>
        </form>
    </div>
</div>

<script>
    function editUser(u) {
        document.getElementById('eu_id').value = u.id;
        document.getElementById('eu_full_name').value = u.full_name;
        document.getElementById('eu_role').value = u.role;
        document.getElementById('eu_email').value = u.email || '';
        document.getElementById('eu_phone').value = u.phone || '';
        document.getElementById('eu_active').checked = u.is_active == 1;
        openModal('editUserModal');
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>