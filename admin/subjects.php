<?php
require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Subjects - Busisi Timetable Generator';
$showNav = true;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $name = sanitize($_POST['name']);
            $code = sanitize($_POST['code'] ?? '');
            $description = sanitize($_POST['description'] ?? '');

            if (createSubject($name, $code, $description)) {
                showAlert('Subject created successfully', 'success');
            } else {
                showAlert('Error creating subject', 'danger');
            }
            break;

        case 'update':
            $id = intval($_POST['id']);
            $name = sanitize($_POST['name']);
            $code = sanitize($_POST['code'] ?? '');
            $description = sanitize($_POST['description'] ?? '');

            if (updateSubject($id, $name, $code, $description)) {
                showAlert('Subject updated successfully', 'success');
            } else {
                showAlert('Error updating subject', 'danger');
            }
            break;

        case 'delete':
            $id = intval($_POST['id']);
            if (deleteSubject($id)) {
                showAlert('Subject deleted successfully', 'success');
            } else {
                showAlert('Error deleting subject', 'danger');
            }
            break;
    }

    header('Location: subjects.php');
    exit;
}

$subjects = getAllSubjects();
?>
<?php require_once '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-book"></i> Subjects Management
        </h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Subjects</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="bi bi-plus-circle"></i> Add Subject
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($subjects)): ?>
                    <p class="text-muted mb-0">No subjects created yet. Click "Add Subject" to get started.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($subject['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($subject['code'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($subject['description'] ?? '-'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($subject['created_at'])); ?></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="editSubject(<?php echo htmlspecialchars(json_encode($subject)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $subject['id']; ?>">
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
                    <h5 class="modal-title">Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="name" class="form-label">Subject Name *</label>
                        <input type="text" class="form-control" id="name" name="name"
                               placeholder="e.g., Mathematics" required>
                    </div>
                    <div class="mb-3">
                        <label for="code" class="form-label">Subject Code</label>
                        <input type="text" class="form-control" id="code" name="code"
                               placeholder="e.g., MATH101">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description"
                                  rows="2" placeholder="Optional description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Subject</button>
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
                    <h5 class="modal-title">Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editId">
                    <div class="mb-3">
                        <label for="editName" class="form-label">Subject Name *</label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCode" class="form-label">Subject Code</label>
                        <input type="text" class="form-control" id="editCode" name="code">
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editSubject(subject) {
    document.getElementById('editId').value = subject.id;
    document.getElementById('editName').value = subject.name;
    document.getElementById('editCode').value = subject.code || '';
    document.getElementById('editDescription').value = subject.description || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
