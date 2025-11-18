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
    const draggableCells = document.querySelectorAll('.timetable-cell.draggable');
    const allCells = document.querySelectorAll('.timetable-cell');

    // Attach dragstart/end only to draggable cells
    draggableCells.forEach(cell => {
        cell.addEventListener('dragstart', handleDragStart);
        cell.addEventListener('dragend', handleDragEnd);
    });

    // Attach drop-related handlers to all cells so free slots are valid drop targets
    allCells.forEach(cell => {
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
    // Auto-scroll when dragging near top/bottom of viewport
    try {
        const margin = 80; // px from edge to start scrolling
        const speed = 40; // px per event
        const clientY = (e.clientY !== undefined) ? e.clientY : (e.touches && e.touches[0] && e.touches[0].clientY) || 0;
        if (clientY > 0 && clientY < margin) {
            window.scrollBy(0, -speed);
        } else if (clientY > (window.innerHeight - margin)) {
            window.scrollBy(0, speed);
        }
    } catch (err) {
        // ignore scrolling errors
    }

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
        // Build target data from this cell
        const target = {
            streamId: this.dataset.streamId,
            day: this.dataset.day,
            period: this.dataset.period,
            subjectId: this.dataset.subjectId,
            teacherId: this.dataset.teacherId,
            isDouble: this.dataset.isDouble
        };

        // Prevent cross-stream swaps: only allow drops on same stream
        if (draggedData && target.streamId !== draggedData.streamId) {
            alert('You can only swap periods within the same stream.');
            return false;
        }

        // Prevent swapping double periods with single periods
        // draggedData.isDouble will be '1' for double (first half) or '0' for single
        const draggedIsDouble = draggedData && draggedData.isDouble === '1';
        const targetIsDouble = target.isDouble === '1' || target.isDouble === '2';
        
        if (draggedIsDouble !== targetIsDouble) {
            alert('You can only swap double periods with other double periods, and single periods with other single periods.');
            return false;
        }

        // If dragging a double (first half), attempt a double move
        if (draggedData && draggedData.isDouble === '1') {
            // Determine base target period. If user dropped onto the second half (isDouble === '2'),
            // normalize so the base period is the first half.
            let targetPeriod = parseInt(target.period, 10);
            if (target.isDouble === '2') {
                targetPeriod = targetPeriod - 1;
            }
            const targetNextPeriod = targetPeriod + 1;
            const targetNextCell = document.querySelector(`.timetable-cell[data-stream-id="${target.streamId}"][data-day="${target.day}"][data-period="${targetNextPeriod}"]`);

            // Also ensure the base target cell exists
            const targetBaseCell = document.querySelector(`.timetable-cell[data-stream-id="${target.streamId}"][data-day="${target.day}"][data-period="${targetPeriod}"]`);

            if (!targetBaseCell || !targetNextCell || targetBaseCell.classList.contains('period-break') || targetNextCell.classList.contains('period-break')) {
                alert('Cannot move a double period here because there is no consecutive slot available.');
                return false;
            }

            // Build targetData with span=2
            const targetData = Object.assign({}, target, { span: 2, period: String(targetPeriod) });

            // Also build source data with span=2
            const sourceData = Object.assign({}, draggedData, { span: 2 });

            // Check for conflicts across both slots
            checkConflict(sourceData, targetData).then(hasConflict => {
                if (hasConflict) {
                    if (confirm('This swap will cause a conflict. Do you want to proceed anyway?')) {
                        swapPeriods(sourceData, targetData);
                    }
                } else {
                    swapPeriods(sourceData, targetData);
                }
            });

            return false;
        }

        // Normal single-slot swap
        const targetData = target;

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
    const cells = document.querySelectorAll('.timetable-cell');

    cells.forEach(cell => {
        // skip breaks and the dragged element itself
        if (cell === draggedElement || cell.classList.contains('period-break')) return;

        // only same stream
        if (cell.dataset.streamId !== data.streamId) return;

        // If dragging a double, ensure there's a consecutive slot available
        if (data && data.isDouble === '1') {
            const period = parseInt(cell.dataset.period, 10);
            const nextCell = document.querySelector(`.timetable-cell[data-stream-id="${cell.dataset.streamId}"][data-day="${cell.dataset.day}"][data-period="${period + 1}"]`);
            if (!nextCell) return;
            if (nextCell.classList.contains('period-break')) return;
        }

        cell.classList.add('drop-zone');
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
 * Export all streams timetable to Excel
 */
async function exportAllStreamsToExcel() {
    try {
        window.location.href = `exports.php?all_streams=1&format=excel`;
    } catch (error) {
        console.error('Error exporting all streams:', error);
        alert('Error exporting timetable. Please try again.');
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
        window.location.href = `exports.php?stream_id=${streamId}&format=excel`;
    } catch (error) {
        console.error('Error exporting to Excel:', error);
        alert('Error exporting timetable. Please try again.');
    }
}

/**
 * Export timetable to PDF
 */
async function exportToPDF(streamId) {
    try {
        window.location.href = `exports.php?stream_id=${streamId}&format=pdf`;
    } catch (error) {
        console.error('Error exporting to PDF:', error);
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
