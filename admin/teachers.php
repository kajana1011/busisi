<?php
require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Teachers - Busisi Timetable Generator';
$showNav = true;
$isAdmin = true;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $firstName = sanitize($_POST['first_name']);
            $lastName = sanitize($_POST['last_name']);
            $email = sanitize($_POST['email'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');

            if (createTeacher($firstName, $lastName, $email, $phone)) {
                showAlert('Teacher created successfully', 'success');
            } else {
                showAlert('Error creating teacher', 'danger');
            }
            break;

        case 'update':
            $id = intval($_POST['id']);
            $firstName = sanitize($_POST['first_name']);
            $lastName = sanitize($_POST['last_name']);
            $email = sanitize($_POST['email'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');

            if (updateTeacher($id, $firstName, $lastName, $email, $phone)) {
                showAlert('Teacher updated successfully', 'success');
            } else {
                showAlert('Error updating teacher', 'danger');
            }
            break;

        case 'delete':
            $id = intval($_POST['id']);
            if (deleteTeacher($id)) {
                showAlert('Teacher deleted successfully', 'success');
            } else {
                showAlert('Error deleting teacher', 'danger');
            }
            break;
    }

    header('Location: teachers.php');
    exit;
}

$teachers = getAllTeachers();
?>
<?php require_once '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-person-badge"></i> Teachers Management
        </h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Teachers</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="bi bi-plus-circle"></i> Add Teacher
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($teachers)): ?>
                    <p class="text-muted mb-0">No teachers added yet. Click "Add Teacher" to get started.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 200px;">Name</th>
                                    <th>Subjects & Streams</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teachers as $teacher): ?>
                                    <?php $assignments = getTeacherAssignments($teacher['id']); ?>
                                    <?php
                                    $totalPeriods = 0;
                                    foreach ($assignments as $assignment) {
                                        $totalPeriods += $assignment['periods_per_week'];
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></strong>
                                            <?php if ($totalPeriods > 0): ?>
                                                <small class="text-muted">(<?php echo $totalPeriods; ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($assignments)): ?>
                                                <div class="small">
                                                    <?php
                                                    $subjectStreams = [];
                                                    foreach ($assignments as $assignment) {
                                                        $formNumber = preg_replace('/[^0-9]/', '', $assignment['form_name']);
                                                        $streamCode = $formNumber . $assignment['stream_name'];
                                                        $subjectName = $assignment['subject_name'] . ($assignment['subject_code'] ? ' (' . $assignment['subject_code'] . ')' : '');
                                                        $subjectStreams[] = $subjectName . ' - ' . $streamCode;
                                                    }
                                                    echo implode('<br>', $subjectStreams);
                                                    ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">No assignments</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="editTeacher(<?php echo htmlspecialchars(json_encode($teacher)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $teacher['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="firstName" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="firstName" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lastName" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="lastName" name="last_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="teacher@example.com">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone"
                               placeholder="+255...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editFirstName" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="editFirstName" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editLastName" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="editLastName" name="last_name" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editEmail" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="editPhone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="editPhone" name="phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editTeacher(teacher) {
    document.getElementById('editId').value = teacher.id;
    document.getElementById('editFirstName').value = teacher.first_name;
    document.getElementById('editLastName').value = teacher.last_name;

    document.getElementById('editEmail').value = teacher.email || '';
    document.getElementById('editPhone').value = teacher.phone || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
