# Busisi Timetable Generator - TODO

## Phase 1: Database & Configuration
- [ ] Create database schema
- [ ] Set up database connection
- [ ] Create configuration files
- [ ] Build setup wizard

## Phase 2: Core Backend
- [ ] Authentication system
- [ ] CRUD operations for forms, streams, subjects, teachers
- [ ] Subject assignments module
- [ ] School settings management

## Phase 3: Timetable Generation Algorithm
- [ ] Core generation logic with conflict detection
- [ ] Even distribution of periods
- [ ] Single and double period handling
- [ ] Special periods integration
- [ ] Break periods handling

## Phase 4: Preview & Editing
- [ ] Timetable preview interface
- [ ] Drag-and-drop functionality
- [ ] Conflict detection on period swap
- [ ] Save edited timetables

## Phase 5: Export & Finalization
- [ ] Excel export functionality
- [ ] Print-ready formats
- [ ] Testing and optimization

## Local reminders (from assistant)
- [ ] Implement assignment-accurate timetable generator (scripts/generate_timetable.php) — create PHP CLI that reads `subject_assignments` and writes conflict-aware `timetables` (not-started)
- [ ] Add integrity check script (scripts/check_integrity.php) — verify foreign keys and detect teacher double-bookings (not-started)
- [ ] Remind user about timetable generator — user asked to be reminded later; ask when to remind or schedule (not-started)

> Notes:
> - The repo already contains a synthetic seed in `sql/initial_data.sql` which fills many tables for UI/testing.
> - The generator would produce more realistic timetables by respecting `periods_per_week` from `subject_assignments` and avoiding teacher conflicts.
> - If you want any of these items done now, tell me which one and whether you want the script to write directly to the DB or output SQL.
