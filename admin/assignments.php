<?php
require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Subject Assignments - Busisi Timetable Generator';
$showNav = true;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $streamId = intval($_POST['stream_id']);
            $subjectId = intval($_POST['subject_id']);
            $teacherId = intval($_POST['teacher_id']);
            $periodsPerWeek = intval($_POST['periods_per_week']);

            if (createAssignment($streamId, $subjectId, $teacherId, $periodsPerWeek)) {
                showAlert('Assignment created successfully', 'success');
            } else {
                showAlert('Error creating assignment. This subject may already be assigned to this stream.', 'danger');
            }
            break;

        case 'update':
            $id = intval($_POST['id']);
            $streamId = intval($_POST['stream_id']);
            $subjectId = intval($_POST['subject_id']);
            $teacherId = intval($_POST['teacher_id']);
            $periodsPerWeek = intval($_POST['periods_per_week']);

            if (updateAssignment($id, $streamId, $subjectId, $teacherId, $periodsPerWeek)) {
                showAlert('Assignment updated successfully', 'success');
            } else {
                showAlert('Error updating assignment', 'danger');
            }
            break;

        case 'delete':
            $id = intval($_POST['id']);
            if (deleteAssignment($id)) {
                showAlert('Assignment deleted successfully', 'success');
            } else {
                showAlert('Error deleting assignment', 'danger');
            }
            break;
    }

    header('Location: assignments.php');
    exit;
}

$assignments = getAllAssignments();
$streams = getAllStreams();
$subjects = getAllSubjects();
$teachers = getAllTeachers();
?>
<?php require_once '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-diagram-3"></i> Subject Assignments
        </h2>
        <p class="text-muted mb-4">
            Assign teachers to subjects for each stream and set the number of periods per week.
        </p>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Assignments</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"
                        <?php echo empty($streams) || empty($subjects) || empty($teachers) ? 'disabled' : ''; ?>>
                    <i class="bi bi-plus-circle"></i> Create Assignment
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($streams) || empty($subjects) || empty($teachers)): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        You need to create forms/streams, subjects, and add teachers before creating assignments.
                    </div>
                <?php elseif (empty($assignments)): ?>
                    <p class="text-muted mb-0">No assignments created yet. Click "Create Assignment" to get started.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Form</th>
                                    <th>Stream</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Periods/Week</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($assignment['form_name']); ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($assignment['stream_name']); ?></strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($assignment['subject_name']); ?>
                                            <?php if ($assignment['subject_code']): ?>
                                                <small class="text-muted">(<?php echo htmlspecialchars($assignment['subject_code']); ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $assignment['periods_per_week']; ?> periods</span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($assignment['created_at'])); ?></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="editAssignment(<?php echo htmlspecialchars(json_encode($assignment)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $assignment['id']; ?>">
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
                    <h5 class="modal-title">Create Subject Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="streamId" class="form-label">Stream *</label>
                        <select class="form-select" id="streamId" name="stream_id" required>
                            <option value="">Select Stream</option>
                            <?php foreach ($streams as $stream): ?>
                                <option value="<?php echo $stream['id']; ?>">
                                    <?php echo htmlspecialchars($stream['form_name'] . ' - ' . $stream['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="subjectId" class="form-label">Subject *</label>
                        <select class="form-select" id="subjectId" name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>">
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                    <?php echo $subject['code'] ? ' (' . htmlspecialchars($subject['code']) . ')' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="teacherId" class="form-label">Teacher *</label>
                        <select class="form-select" id="teacherId" name="teacher_id" required>
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="periodsPerWeek" class="form-label">Periods Per Week *</label>
                        <input type="number" class="form-control" id="periodsPerWeek" name="periods_per_week"
                               min="1" max="20" value="5" required>
                        <small class="text-muted">
                            Number of periods per week for this subject
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Assignment</button>
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
                    <h5 class="modal-title">Edit Subject Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editId">
                    <div class="mb-3">
                        <label for="editStreamId" class="form-label">Stream *</label>
                        <select class="form-select" id="editStreamId" name="stream_id" required>
                            <?php foreach ($streams as $stream): ?>
                                <option value="<?php echo $stream['id']; ?>">
                                    <?php echo htmlspecialchars($stream['form_name'] . ' - ' . $stream['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editSubjectId" class="form-label">Subject *</label>
                        <select class="form-select" id="editSubjectId" name="subject_id" required>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>">
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                    <?php echo $subject['code'] ? ' (' . htmlspecialchars($subject['code']) . ')' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editTeacherId" class="form-label">Teacher *</label>
                        <select class="form-select" id="editTeacherId" name="teacher_id" required>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editPeriodsPerWeek" class="form-label">Periods Per Week *</label>
                        <input type="number" class="form-control" id="editPeriodsPerWeek" name="periods_per_week"
                               min="1" max="20" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editAssignment(assignment) {
    document.getElementById('editId').value = assignment.id;
    document.getElementById('editStreamId').value = assignment.stream_id;
    document.getElementById('editSubjectId').value = assignment.subject_id;
    document.getElementById('editTeacherId').value = assignment.teacher_id;
    document.getElementById('editPeriodsPerWeek').value = assignment.periods_per_week;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
