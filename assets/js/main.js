/**
 * Busisi Timetable Generator - Main JavaScript
 */

// Initialize drag and drop for timetable editing
document.addEventListener('DOMContentLoaded', function() {
    initializeDragAndDrop();
});

/**
 * Initialize drag and drop functionality for timetable cells
 */
function initializeDragAndDrop() {
    const cells = document.querySelectorAll('.timetable-cell.draggable');

    cells.forEach(cell => {
        cell.addEventListener('dragstart', handleDragStart);
        cell.addEventListener('dragend', handleDragEnd);
        cell.addEventListener('dragover', handleDragOver);
        cell.addEventListener('drop', handleDrop);
        cell.addEventListener('dragenter', handleDragEnter);
        cell.addEventListener('dragleave', handleDragLeave);
    });
}

let draggedElement = null;
let draggedData = null;

/**
 * Handle drag start
 */
function handleDragStart(e) {
    draggedElement = this;
    draggedData = {
        streamId: this.dataset.streamId,
        day: this.dataset.day,
        period: this.dataset.period,
        subjectId: this.dataset.subjectId,
        teacherId: this.dataset.teacherId,
        isDouble: this.dataset.isDouble
    };

    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);

    // Highlight valid drop zones
    highlightValidDropZones(draggedData);
}

/**
 * Handle drag end
 */
function handleDragEnd(e) {
    this.classList.remove('dragging');

    // Remove all drop zone highlights
    document.querySelectorAll('.timetable-cell').forEach(cell => {
        cell.classList.remove('drop-zone', 'conflict');
    });
}

/**
 * Handle drag over
 */
function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    return false;
}

/**
 * Handle drag enter
 */
function handleDragEnter(e) {
    if (this !== draggedElement && !this.classList.contains('period-break')) {
        this.classList.add('drop-zone');
    }
}

/**
 * Handle drag leave
 */
function handleDragLeave(e) {
    this.classList.remove('drop-zone');
}

/**
 * Handle drop
 */
function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }

    if (this !== draggedElement && !this.classList.contains('period-break')) {
        const targetData = {
            streamId: this.dataset.streamId,
            day: this.dataset.day,
            period: this.dataset.period,
            subjectId: this.dataset.subjectId,
            teacherId: this.dataset.teacherId,
            isDouble: this.dataset.isDouble
        };

        // Check for conflicts
        checkConflict(draggedData, targetData).then(hasConflict => {
            if (hasConflict) {
                if (confirm('This swap will cause a conflict. Do you want to proceed anyway?')) {
                    swapPeriods(draggedData, targetData);
                }
            } else {
                swapPeriods(draggedData, targetData);
            }
        });
    }

    return false;
}

/**
 * Highlight valid drop zones
 */
function highlightValidDropZones(data) {
    const cells = document.querySelectorAll('.timetable-cell.draggable');

    cells.forEach(cell => {
        if (cell.dataset.streamId === data.streamId &&
            !cell.classList.contains('period-break') &&
            cell !== draggedElement) {
            cell.classList.add('drop-zone');
        }
    });
}

/**
 * Check for conflict when swapping periods
 */
async function checkConflict(source, target) {
    try {
        const response = await fetch('ajax/check_conflict.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                source: source,
                target: target
            })
        });

        const result = await response.json();
        return result.hasConflict;
    } catch (error) {
        console.error('Error checking conflict:', error);
        return false;
    }
}

/**
 * Swap two periods
 */
async function swapPeriods(source, target) {
    try {
        const response = await fetch('ajax/swap_periods.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                source: source,
                target: target
            })
        });

        const result = await response.json();

        if (result.success) {
            // Reload the page to show updated timetable
            location.reload();
        } else {
            alert('Error swapping periods: ' + result.message);
        }
    } catch (error) {
        console.error('Error swapping periods:', error);
        alert('Error swapping periods. Please try again.');
    }
}

/**
 * Confirm delete action
 */
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

/**
 * Auto-dismiss alerts after 5 seconds
 */
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

/**
 * Form validation helper
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return false;
    }

    return true;
}

/**
 * Export timetable to Excel
 */
async function exportToExcel(streamId) {
    try {
        window.location.href = `export.php?stream_id=${streamId}&format=excel`;
    } catch (error) {
        console.error('Error exporting to Excel:', error);
        alert('Error exporting timetable. Please try again.');
    }
}

/**
 * Print timetable
 */
function printTimetable(streamId) {
    window.print();
}

/**
 * Generate timetable
 */
async function generateTimetable() {
    if (!confirm('This will generate new timetables for all streams. Any existing timetables will be replaced. Continue?')) {
        return;
    }

    const btn = document.getElementById('generateBtn');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';

    try {
        const response = await fetch('ajax/generate_timetable.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        });

        const result = await response.json();

        if (result.success) {
            alert('Timetables generated successfully!');
            window.location.href = 'view.php';
        } else {
            alert('Error generating timetables: ' + result.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Error generating timetables:', error);
        alert('Error generating timetables. Please try again.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
