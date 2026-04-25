<?php
$pageTitle = 'Patients';
$pageSubtitle = 'Manage patient records';
require_once __DIR__ . '/auth.php';
requireRole(roles: ['admin', 'technician']);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $patientId = generatePatientId();
        $fullName = sanitize(data: $_POST['full_name']);
        $dob = sanitize(data: $_POST['date_of_birth']);
        $age = intval(value: $_POST['age']);
        $gender = sanitize(data: $_POST['gender']);
        $bloodGroup = sanitize(data: $_POST['blood_group'] ?? '');
        $phone = sanitize(data: $_POST['phone']);
        $email = sanitize(data: $_POST['email']);
        $address = sanitize(data: $_POST['address']);

        try {
            $stmt = $pdo->prepare(query: "INSERT INTO patients (patient_id, full_name, date_of_birth, age, gender, blood_group, phone, email, address, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute(params: [$patientId, $fullName, $dob ?: null, $age, $gender, $bloodGroup ?: null, $phone, $email, $address, getUserId()]);
            $success = "Patient registered successfully! ID: $patientId";
            broadcastNotification(pdo: $pdo, roles: ['admin', 'technician'], type: 'patient', title: 'New Patient Registered', message: "$fullName has been registered (ID: $patientId)", link: 'patients.php');
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    if ($_POST['action'] === 'edit') {
        $id = intval(value: $_POST['id']);
        $fullName = sanitize(data: $_POST['full_name']);
        $dob = sanitize(data: $_POST['date_of_birth']);
        $age = intval(value: $_POST['age']);
        $gender = sanitize(data: $_POST['gender']);
        $bloodGroup = sanitize(data: $_POST['blood_group'] ?? '');
        $phone = sanitize(data: $_POST['phone']);
        $email = sanitize(data: $_POST['email']);
        $address = sanitize(data: $_POST['address']);

        try {
            $stmt = $pdo->prepare(query: "UPDATE patients SET full_name=?, date_of_birth=?, age=?, gender=?, blood_group=?, phone=?, email=?, address=? WHERE id=?");
            $stmt->execute(params: [$fullName, $dob ?: null, $age, $gender, $bloodGroup ?: null, $phone, $email, $address, $id]);
            $success = "Patient updated successfully!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = intval(value: $_POST['id']);
        try {
            $pdo->prepare(query: "DELETE FROM patients WHERE id = ?")->execute(params: [$id]);
            $success = "Patient deleted.";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$search = sanitize(data: $_GET['search'] ?? '');
$query = "SELECT * FROM patients";
$params = [];
if ($search) {
    $query .= " WHERE full_name LIKE ? OR patient_id LIKE ?";
    $params = ["%$search%", "%$search%"];
}
$query .= " ORDER BY created_at DESC";
$patients = $pdo->prepare(query: $query);
$patients->execute(params: $params);
$patients = $patients->fetchAll();

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
        <form method="GET" style="display:flex;gap:8px;">
            <input type="text" name="search" class="form-control" data-translate="Search patient name or ID..." placeholder="Search patient name or ID..."
                value="<?php echo htmlspecialchars(string: $search); ?>" style="width:280px;">
            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="toolbar-right">
        <button class="btn btn-primary" onclick="openModal('addPatientModal')">
            <i class="fas fa-plus"></i> <span data-translate="Register Patient">Register Patient</span>
        </button>
    </div>
</div>

<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th data-translate="Patient ID">Patient ID</th>
                    <th data-translate="Full Name">Full Name</th>
                    <th data-translate="Age">Age</th>
                    <th data-translate="Gender">Gender</th>
                    <th data-translate="Blood Group">Blood Group</th>
                    <th data-translate="Phone">Phone</th>
                    <th data-translate="Email">Email</th>
                    <th data-translate="Registered">Registered</th>
                    <th data-translate="Actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($patients)): ?>
                    <tr>
                        <td colspan="9" class="empty-state"><i class="fas fa-user-slash"></i><br><span data-translate="No patients found">No patients found</span></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($patients as $p): ?>
                        <tr>
                            <td><strong style="color:var(--accent-primary);"><?php echo $p['patient_id']; ?></strong></td>
                            <td style="color:var(--text-primary);font-weight:500;">
                                <?php echo htmlspecialchars($p['full_name']); ?></td>
                            <td><?php echo $p['age']; ?></td>
                            <td><?php echo $p['gender']; ?></td>
                            <td><?php if (!empty($p['blood_group'])): ?><span
                                        style="font-weight:700;color:#ef4444;background:rgba(239,68,68,0.08);padding:2px 8px;border-radius:6px;font-size:12px;"><?php echo htmlspecialchars($p['blood_group']); ?></span><?php else: ?><span
                                        style="color:var(--text-muted);">—</span><?php endif; ?></td>
                            <td><?php echo htmlspecialchars($p['phone']); ?></td>
                            <td><?php echo htmlspecialchars($p['email']); ?></td>
                            <td style="font-size:13px;color:var(--text-muted);"><?php echo formatDate($p['created_at']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-secondary"
                                        onclick="editPatient(<?php echo htmlspecialchars(json_encode($p)); ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="tests.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary"
                                        title="Assign Tests">
                                        <i class="fas fa-flask"></i>
                                    </a>
                                    <form method="POST" style="display:inline;"
                                        onsubmit="return confirm('Delete this patient?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Patient Modal -->
<div class="modal-overlay" id="addPatientModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus" style="color:var(--accent-primary);margin-right:8px;"></i><span data-translate="Register New Patient">Register New Patient</span></h3>
            <button class="modal-close" onclick="closeModal('addPatientModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label><span data-translate="Full Name">Full Name</span> <span class="required">*</span></label>
                        <input type="text" name="full_name" class="form-control" required data-translate="Enter full name" placeholder="Enter full name">
                    </div>
                    <div class="form-group">
                        <label data-translate="Date of Birth">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="add_dob" class="form-control" onchange="calcAge('add_dob','add_age')">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><span data-translate="Age">Age</span> <span class="required">*</span></label>
                        <input type="number" name="age" id="add_age" class="form-control" required min="0" max="150"
                            placeholder="Auto-filled from DOB" readonly style="background:rgba(6,182,212,0.05);cursor:default;">
                    </div>
                    <div class="form-group">
                        <label><span data-translate="Gender">Gender</span> <span class="required">*</span></label>
                        <select name="gender" class="form-control" required>
                            <option value="" data-translate="Select Gender">Select Gender</option>
                            <option value="Male" data-translate="Male">Male</option>
                            <option value="Female" data-translate="Female">Female</option>
                            <option value="Other" data-translate="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label data-translate="Blood Group">Blood Group</label>
                    <select name="blood_group" class="form-control">
                        <option value="">Select Blood Group</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label data-translate="Phone">Phone</label>
                        <input type="tel" name="phone" class="form-control" data-translate="Phone number" placeholder="Phone number">
                    </div>
                    <div class="form-group">
                        <label data-translate="Email">Email</label>
                        <input type="email" name="email" class="form-control" data-translate="Email address" placeholder="Email address">
                    </div>
                </div>
                <div class="form-group">
                    <label data-translate="Address">Address</label>
                    <textarea name="address" class="form-control" data-translate="Patient address" placeholder="Patient address"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addPatientModal')" data-translate="Cancel">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <span data-translate="Register Patient">Register Patient</span></button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Patient Modal -->
<div class="modal-overlay" id="editPatientModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-edit" style="color:var(--warning);margin-right:8px;"></i><span data-translate="Edit Patient">Edit Patient</span></h3>
            <button class="modal-close" onclick="closeModal('editPatientModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" id="edit_dob" class="form-control" onchange="calcAge('edit_dob','edit_age')">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Age <span class="required">*</span></label>
                        <input type="number" name="age" id="edit_age" class="form-control" required min="0" max="150"
                            placeholder="Auto-filled from DOB" readonly style="background:rgba(6,182,212,0.05);cursor:default;">
                    </div>
                    <div class="form-group">
                        <label>Gender <span class="required">*</span></label>
                        <select name="gender" id="edit_gender" class="form-control" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Blood Group</label>
                    <select name="blood_group" id="edit_blood_group" class="form-control">
                        <option value="">Select Blood Group</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" id="edit_phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" id="edit_address" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editPatientModal')" data-translate="Cancel">Cancel</button>
                <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <span data-translate="Update Patient">Update Patient</span></button>
            </div>
        </form>
    </div>
</div>

<script>
    // Calculate age from a date-of-birth input and put result in age input
    function calcAge(dobId, ageId) {
        const dob = document.getElementById(dobId).value;
        const ageField = document.getElementById(ageId);
        if (!dob) { ageField.value = ''; return; }
        const today = new Date();
        const birth = new Date(dob);
        let age = today.getFullYear() - birth.getFullYear();
        const m = today.getMonth() - birth.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
        ageField.value = age >= 0 ? age : '';
    }

    function editPatient(p) {
        document.getElementById('edit_id').value = p.id;
        document.getElementById('edit_full_name').value = p.full_name;
        document.getElementById('edit_dob').value = p.date_of_birth || '';
        // Recalculate age from DOB to keep it accurate
        if (p.date_of_birth) {
            calcAge('edit_dob', 'edit_age');
        } else {
            document.getElementById('edit_age').value = p.age;
        }
        document.getElementById('edit_gender').value = p.gender;
        document.getElementById('edit_blood_group').value = p.blood_group || '';
        document.getElementById('edit_phone').value = p.phone || '';
        document.getElementById('edit_email').value = p.email || '';
        document.getElementById('edit_address').value = p.address || '';
        openModal('editPatientModal');
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>