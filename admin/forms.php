<?php
require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Forms & Streams - Busisi Timetable Generator';
$showNav = true;
$isAdmin = true;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_form':
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description'] ?? '');
            $order = intval($_POST['display_order'] ?? 0);

            if (createForm($name, $description, $order)) {
                showAlert('Form created successfully', 'success');
            } else {
                showAlert('Error creating form', 'danger');
            }
            break;

        case 'update_form':
            $id = intval($_POST['id']);
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description'] ?? '');
            $order = intval($_POST['display_order'] ?? 0);

            if (updateForm($id, $name, $description, $order)) {
                showAlert('Form updated successfully', 'success');
            } else {
                showAlert('Error updating form', 'danger');
            }
            break;

        case 'delete_form':
            $id = intval($_POST['id']);
            if (deleteForm($id)) {
                showAlert('Form deleted successfully', 'success');
            } else {
                showAlert('Error deleting form', 'danger');
            }
            break;

        case 'create_stream':
            $formId = intval($_POST['form_id']);
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description'] ?? '');

            if (createStream($formId, $name, $description)) {
                showAlert('Stream created successfully', 'success');
            } else {
                showAlert('Error creating stream', 'danger');
            }
            break;

        case 'update_stream':
            $id = intval($_POST['id']);
            $formId = intval($_POST['form_id']);
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description'] ?? '');

            if (updateStream($id, $formId, $name, $description)) {
                showAlert('Stream updated successfully', 'success');
            } else {
                showAlert('Error updating stream', 'danger');
            }
            break;

        case 'delete_stream':
            $id = intval($_POST['id']);
            if (deleteStream($id)) {
                showAlert('Stream deleted successfully', 'success');
            } else {
                showAlert('Error deleting stream', 'danger');
            }
            break;
    }

    header('Location: forms.php');
    exit;
}

$forms = getAllForms();
$streams = getAllStreams();
?>
<?php require_once '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-collection"></i> Forms & Streams Management
        </h2>
    </div>
</div>

<div class="row">
    <!-- Forms Section -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Forms</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createFormModal">
                    <i class="bi bi-plus-circle"></i> Add Form
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($forms)): ?>
                    <p class="text-muted mb-0">No forms created yet. Click "Add Form" to get started.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Order</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($forms as $form): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($form['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($form['description'] ?? '-'); ?></td>
                                        <td><?php echo $form['display_order']; ?></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="editForm(<?php echo htmlspecialchars(json_encode($form)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirmDelete('Delete this form and all its streams?')">
                                                <input type="hidden" name="action" value="delete_form">
                                                <input type="hidden" name="id" value="<?php echo $form['id']; ?>">
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

    <!-- Streams Section -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Streams</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createStreamModal"
                        <?php echo empty($forms) ? 'disabled' : ''; ?>>
                    <i class="bi bi-plus-circle"></i> Add Stream
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($streams)): ?>
                    <p class="text-muted mb-0">
                        <?php echo empty($forms) ? 'Create a form first before adding streams.' : 'No streams created yet. Click "Add Stream" to get started.'; ?>
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Form</th>
                                    <th>Stream</th>
                                    <th>Description</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($streams as $stream): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($stream['form_name']); ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($stream['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($stream['description'] ?? '-'); ?></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="editStream(<?php echo htmlspecialchars(json_encode($stream)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirmDelete('Delete this stream?')">
                                                <input type="hidden" name="action" value="delete_stream">
                                                <input type="hidden" name="id" value="<?php echo $stream['id']; ?>">
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

<!-- Create Form Modal -->
<div class="modal fade" id="createFormModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_form">
                    <div class="mb-3">
                        <label for="formName" class="form-label">Form Name *</label>
                        <input type="text" class="form-control" id="formName" name="name"
                               placeholder="e.g., Form 1" required>
                    </div>
                    <div class="mb-3">
                        <label for="formDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="formDescription" name="description"
                                  rows="2" placeholder="Optional description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="formOrder" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="formOrder" name="display_order"
                               value="0" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Form</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Form Modal -->
<div class="modal fade" id="editFormModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_form">
                    <input type="hidden" name="id" id="editFormId">
                    <div class="mb-3">
                        <label for="editFormName" class="form-label">Form Name *</label>
                        <input type="text" class="form-control" id="editFormName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editFormDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editFormDescription" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editFormOrder" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="editFormOrder" name="display_order" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Form</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Stream Modal -->
<div class="modal fade" id="createStreamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Stream</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_stream">
                    <div class="mb-3">
                        <label for="streamForm" class="form-label">Form *</label>
                        <select class="form-select" id="streamForm" name="form_id" required>
                            <option value="">Select Form</option>
                            <?php foreach ($forms as $form): ?>
                                <option value="<?php echo $form['id']; ?>">
                                    <?php echo htmlspecialchars($form['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="streamName" class="form-label">Stream Name *</label>
                        <input type="text" class="form-control" id="streamName" name="name"
                               placeholder="e.g., A, B, Science, Arts" required>
                    </div>
                    <div class="mb-3">
                        <label for="streamDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="streamDescription" name="description"
                                  rows="2" placeholder="Optional description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Stream</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Stream Modal -->
<div class="modal fade" id="editStreamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Stream</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_stream">
                    <input type="hidden" name="id" id="editStreamId">
                    <div class="mb-3">
                        <label for="editStreamForm" class="form-label">Form *</label>
                        <select class="form-select" id="editStreamForm" name="form_id" required>
                            <?php foreach ($forms as $form): ?>
                                <option value="<?php echo $form['id']; ?>">
                                    <?php echo htmlspecialchars($form['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editStreamName" class="form-label">Stream Name *</label>
                        <input type="text" class="form-control" id="editStreamName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editStreamDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editStreamDescription" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Stream</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editForm(form) {
    document.getElementById('editFormId').value = form.id;
    document.getElementById('editFormName').value = form.name;
    document.getElementById('editFormDescription').value = form.description || '';
    document.getElementById('editFormOrder').value = form.display_order;
    new bootstrap.Modal(document.getElementById('editFormModal')).show();
}

function editStream(stream) {
    document.getElementById('editStreamId').value = stream.id;
    document.getElementById('editStreamForm').value = stream.form_id;
    document.getElementById('editStreamName').value = stream.name;
    document.getElementById('editStreamDescription').value = stream.description || '';
    new bootstrap.Modal(document.getElementById('editStreamModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
