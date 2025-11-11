-- Initial Data for Busisi Timetable Generator
-- Default admin user: admin / admin123

USE `busisi`;

-- Insert default school settings
INSERT INTO `school_settings` (`setting_key`, `setting_value`) VALUES
('school_name', 'Busisi Secondary School'),
('school_address', 'P.O. Box 123, Busisi, Kenya'),
('school_phone', '+254 700 123 456'),
('school_email', 'info@busisi.edu.ke'),
('school_days', '5'),
('periods_per_day', '8'),
('period_duration', '40'),
('school_start_time', '08:00'),
('academic_year', '2025'),
('setup_completed', '1');

-- Insert default break periods
INSERT INTO `break_periods` (`name`, `period_number`, `duration_minutes`) VALUES
('Short Break', 3, 15),
('Lunch Break', 5, 30);

-- Insert forms
INSERT INTO `forms` (`name`, `description`, `display_order`) VALUES
('Form 1', 'First year secondary school', 1),
('Form 2', 'Second year secondary school', 2),
('Form 3', 'Third year secondary school', 3),
('Form 4', 'Fourth year secondary school', 4);

-- Insert streams
INSERT INTO `streams` (`form_id`, `name`, `description`) VALUES
(1, 'A', 'Form 1 Stream A'),
(1, 'B', 'Form 1 Stream B'),
(2, 'A', 'Form 2 Stream A'),
(2, 'B', 'Form 2 Stream B'),
(3, 'Science', 'Form 3 Science Stream'),
(3, 'Arts', 'Form 3 Arts Stream'),
(4, 'Science', 'Form 4 Science Stream'),
(4, 'Arts', 'Form 4 Arts Stream');

-- Insert subjects
INSERT INTO `subjects` (`name`, `code`, `description`) VALUES
('Mathematics', 'MATH', 'Mathematics subject'),
('English', 'ENG', 'English language and literature'),
('Kiswahili', 'KISW', 'Kiswahili language'),
('Physics', 'PHYS', 'Physics science'),
('Chemistry', 'CHEM', 'Chemistry science'),
('Biology', 'BIO', 'Biology science'),
('Geography', 'GEO', 'Geography studies'),
('History', 'HIST', 'History studies'),
('CRE', 'CRE', 'Christian Religious Education'),
('Business Studies', 'BS', 'Business studies'),
('Computer Studies', 'COMP', 'Computer science and ICT'),
('Agriculture', 'AGRI', 'Agriculture studies'),
('Home Science', 'HOME', 'Home science'),
('Art and Design', 'ART', 'Art and design'),
('Music', 'MUSIC', 'Music studies');

-- Insert teachers
INSERT INTO `teachers` (`first_name`, `last_name`, `email`, `phone`, `employee_id`) VALUES
('John', 'Doe', 'john.doe@busisi.edu.ke', '+254 700 111 111', 'T001'),
('Jane', 'Smith', 'jane.smith@busisi.edu.ke', '+254 700 222 222', 'T002'),
('Michael', 'Johnson', 'michael.johnson@busisi.edu.ke', '+254 700 333 333', 'T003'),
('Sarah', 'Williams', 'sarah.williams@busisi.edu.ke', '+254 700 444 444', 'T004'),
('David', 'Brown', 'david.brown@busisi.edu.ke', '+254 700 555 555', 'T005'),
('Emma', 'Davis', 'emma.davis@busisi.edu.ke', '+254 700 666 666', 'T006'),
('Robert', 'Miller', 'robert.miller@busisi.edu.ke', '+254 700 777 777', 'T007'),
('Lisa', 'Wilson', 'lisa.wilson@busisi.edu.ke', '+254 700 888 888', 'T008'),
('James', 'Moore', 'james.moore@busisi.edu.ke', '+254 700 999 999', 'T009'),
('Maria', 'Taylor', 'maria.taylor@busisi.edu.ke', '+254 700 101 010', 'T010');

-- Insert subject assignments
INSERT INTO `subject_assignments` (`stream_id`, `subject_id`, `teacher_id`, `periods_per_week`) VALUES
-- Form 1 Stream A
(1, 1, 1, 5), -- Mathematics
(1, 2, 2, 4), -- English
(1, 3, 3, 4), -- Kiswahili
(1, 4, 4, 3), -- Physics
(1, 5, 5, 3), -- Chemistry
(1, 6, 6, 3), -- Biology
(1, 7, 7, 2), -- Geography
(1, 8, 8, 2), -- History
(1, 9, 9, 2), -- CRE
(1, 10, 10, 2), -- Business Studies

-- Form 1 Stream B
(2, 1, 1, 5), -- Mathematics
(2, 2, 2, 4), -- English
(2, 3, 3, 4), -- Kiswahili
(2, 4, 4, 3), -- Physics
(2, 5, 5, 3), -- Chemistry
(2, 6, 6, 3), -- Biology
(2, 7, 7, 2), -- Geography
(2, 8, 8, 2), -- History
(2, 9, 9, 2), -- CRE
(2, 11, 10, 2), -- Computer Studies

-- Form 2 Stream A
(3, 1, 1, 5), -- Mathematics
(3, 2, 2, 4), -- English
(3, 3, 3, 4), -- Kiswahili
(3, 4, 4, 3), -- Physics
(3, 5, 5, 3), -- Chemistry
(3, 6, 6, 3), -- Biology
(3, 7, 7, 2), -- Geography
(3, 8, 8, 2), -- History
(3, 9, 9, 2), -- CRE
(3, 10, 10, 2), -- Business Studies

-- Form 2 Stream B
(4, 1, 1, 5), -- Mathematics
(4, 2, 2, 4), -- English
(4, 3, 3, 4), -- Kiswahili
(4, 4, 4, 3), -- Physics
(4, 5, 5, 3), -- Chemistry
(4, 6, 6, 3), -- Biology
(4, 7, 7, 2), -- Geography
(4, 8, 8, 2), -- History
(4, 9, 9, 2), -- CRE
(4, 11, 10, 2), -- Computer Studies

-- Form 3 Science
(5, 1, 1, 5), -- Mathematics
(5, 2, 2, 4), -- English
(5, 3, 3, 4), -- Kiswahili
(5, 4, 4, 4), -- Physics
(5, 5, 5, 4), -- Chemistry
(5, 6, 6, 4), -- Biology
(5, 7, 7, 2), -- Geography
(5, 8, 8, 2), -- History
(5, 9, 9, 2), -- CRE
(5, 11, 10, 3), -- Computer Studies

-- Form 3 Arts
(6, 1, 1, 5), -- Mathematics
(6, 2, 2, 4), -- English
(6, 3, 3, 4), -- Kiswahili
(6, 7, 7, 3), -- Geography
(6, 8, 8, 3), -- History
(6, 9, 9, 3), -- CRE
(6, 10, 10, 3), -- Business Studies
(6, 12, 4, 2), -- Agriculture
(6, 13, 5, 2), -- Home Science
(6, 14, 6, 2), -- Art and Design

-- Form 4 Science
(7, 1, 1, 5), -- Mathematics
(7, 2, 2, 4), -- English
(7, 3, 3, 4), -- Kiswahili
(7, 4, 4, 4), -- Physics
(7, 5, 5, 4), -- Chemistry
(7, 6, 6, 4), -- Biology
(7, 7, 7, 2), -- Geography
(7, 8, 8, 2), -- History
(7, 9, 9, 2), -- CRE
(7, 11, 10, 3), -- Computer Studies

-- Form 4 Arts
(8, 1, 1, 5), -- Mathematics
(8, 2, 2, 4), -- English
(8, 3, 3, 4), -- Kiswahili
(8, 7, 7, 3), -- Geography
(8, 8, 8, 3), -- History
(8, 9, 9, 3), -- CRE
(8, 10, 10, 3), -- Business Studies
(8, 12, 4, 2), -- Agriculture
(8, 13, 5, 2), -- Home Science
(8, 14, 6, 2); -- Art and Design

-- Insert special periods
INSERT INTO `special_periods` (`name`, `day_of_week`, `start_period`, `end_period`, `is_active`) VALUES
('Assembly', 1, 1, 1, 1), -- Monday, period 1
('Sports', 3, 8, 8, 1), -- Wednesday, period 8
('Guidance and Counseling', 5, 7, 7, 1); -- Friday, period 7

-- Insert sample generation history
-- INSERT INTO `generation_history` (`generated_at`, `generated_by`, `status`, `notes`) VALUES
-- (NOW(), 1, 'success', 'Initial timetable generation');

-- NOTE: The admin password must be a bcrypt hash. You can either
-- 1) run `php adminseed.php` from the project root to create the default admin (recommended),
-- 2) or replace the placeholder hash below with a bcrypt hash generated on your machine.
-- Example default credentials: username: admin | password: admin123

-- Insert default admin (replace the password hash with a valid bcrypt hash or run adminseed.php)
INSERT INTO `admins` (`username`, `password`, `email`) VALUES
('admin', '$2y$10$PLACEHOLDER_BCRYPT_HASH_REPLACE_ME_BY_RUNNING_adminseed.php', 'admin@busisi.com');

-- Insert some sample timetable entries for a couple of streams (simple example)
INSERT INTO `timetables` (`stream_id`, `day_of_week`, `period_number`, `subject_id`, `teacher_id`, `is_break`, `is_special`, `is_double_period`, `special_period_id`) VALUES
(1, 1, 1, 1, 1, 0, 0, 0, NULL), -- Form1 A - Period 1 - Mathematics
(1, 1, 2, 2, 2, 0, 0, 0, NULL), -- Form1 A - Period 2 - English
(1, 1, 3, NULL, NULL, 1, 0, 0, NULL), -- Form1 A - Period 3 - Short Break
(1, 1, 4, 3, 3, 0, 0, 0, NULL), -- Form1 A - Period 4 - Kiswahili
(2, 1, 1, 1, 1, 0, 0, 0, NULL), -- Form1 B - Period 1 - Mathematics
(2, 1, 2, 2, 2, 0, 0, 0, NULL), -- Form1 B - Period 2 - English
(2, 1, 3, NULL, NULL, 1, 0, 0, NULL); -- Form1 B - Period 3 - Short Break

-- Insert a sample generation history entry (generated_by left NULL until admin exists)
INSERT INTO `generation_history` (`generated_at`, `generated_by`, `status`, `notes`) VALUES
(NOW(), NULL, 'success', 'Initial seed - sample generation history');

-- ------------------------------------------------------------------
-- Additional sample data for testing
-- 1) Add more teachers (T011-T020)
-- 2) Populate timetables for streams 1-4 (full week) and streams 5-8 (partial)
-- 3) Add a couple of generation_history entries referencing admin id 1
-- ------------------------------------------------------------------

-- Additional teachers
INSERT INTO `teachers` (`first_name`, `last_name`, `email`, `phone`, `employee_id`) VALUES
('Grace', 'Lee', 'grace.lee@busisi.edu.ke', '+254 700 111 212', 'T011'),
('Samuel', 'Njoroge', 'samuel.njoroge@busisi.edu.ke', '+254 700 111 313', 'T012'),
('Anne', 'Karanja', 'anne.karanja@busisi.edu.ke', '+254 700 111 414', 'T013'),
('Paul', 'Otieno', 'paul.otieno@busisi.edu.ke', '+254 700 111 515', 'T014'),
('Cynthia', 'Wambui', 'cynthia.wambui@busisi.edu.ke', '+254 700 111 616', 'T015'),
('Peter', 'Mumo', 'peter.mumo@busisi.edu.ke', '+254 700 111 717', 'T016'),
('Linda', 'Kibet', 'linda.kibet@busisi.edu.ke', '+254 700 111 818', 'T017'),
('Kevin', 'Mbae', 'kevin.mbae@busisi.edu.ke', '+254 700 111 919', 'T018'),
('Nancy', 'Maina', 'nancy.maina@busisi.edu.ke', '+254 700 112 020', 'T019'),
('Frank', 'Wangari', 'frank.wangari@busisi.edu.ke', '+254 700 112 121', 'T020');

-- Programmatic timetable population.
-- Streams 1-4: full week (Monday-Friday) with 8 periods/day.
-- Streams 5-8: sample for Monday and Wednesday only.
-- Rules used:
--  - Breaks at period 3 and 5 => subject_id, teacher_id = NULL and is_break=1
--  - Special periods: Assembly (day1,period1)->special_period_id=1,
--                     Sports (day3,period8)->special_period_id=2,
--                     Guidance (day5,period7)->special_period_id=3
--  - For non-break periods subject_id is derived so it references existing subjects (1..15)
--  - teacher_id derived from subject_id to reference existing teachers (1..20)

INSERT INTO `timetables` (
	`stream_id`, `day_of_week`, `period_number`, `subject_id`, `teacher_id`, `is_break`, `is_special`, `is_double_period`, `special_period_id`
)
SELECT s.stream_id, d.day, p.period,
	CASE WHEN p.period IN (3,5) THEN NULL ELSE ((s.stream_id + d.day + p.period) % 15) + 1 END AS subject_id,
	CASE WHEN p.period IN (3,5) THEN NULL ELSE ((((s.stream_id + d.day + p.period) % 15) + 1 - 1) % 20) + 1 END AS teacher_id,
	IF(p.period IN (3,5), 1, 0) AS is_break,
	CASE WHEN (d.day = 1 AND p.period = 1) OR (d.day = 3 AND p.period = 8) OR (d.day = 5 AND p.period = 7) THEN 1 ELSE 0 END AS is_special,
	0 AS is_double_period,
	CASE WHEN (d.day = 1 AND p.period = 1) THEN 1 WHEN (d.day = 3 AND p.period = 8) THEN 2 WHEN (d.day = 5 AND p.period = 7) THEN 3 ELSE NULL END AS special_period_id
FROM (SELECT 1 AS stream_id UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) s
CROSS JOIN (SELECT 1 AS day UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5) d
CROSS JOIN (SELECT 1 AS period UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8) p;

-- Partial timetable for streams 5-8 (Monday and Wednesday only)
INSERT INTO `timetables` (
	`stream_id`, `day_of_week`, `period_number`, `subject_id`, `teacher_id`, `is_break`, `is_special`, `is_double_period`, `special_period_id`
)
SELECT s.stream_id, d.day, p.period,
	CASE WHEN p.period IN (3,5) THEN NULL ELSE ((s.stream_id + d.day + p.period) % 15) + 1 END AS subject_id,
	CASE WHEN p.period IN (3,5) THEN NULL ELSE ((((s.stream_id + d.day + p.period) % 15) + 1 - 1) % 20) + 1 END AS teacher_id,
	IF(p.period IN (3,5), 1, 0) AS is_break,
	CASE WHEN (d.day = 1 AND p.period = 1) OR (d.day = 3 AND p.period = 8) OR (d.day = 5 AND p.period = 7) THEN 1 ELSE 0 END AS is_special,
	0 AS is_double_period,
	CASE WHEN (d.day = 1 AND p.period = 1) THEN 1 WHEN (d.day = 3 AND p.period = 8) THEN 2 WHEN (d.day = 5 AND p.period = 7) THEN 3 ELSE NULL END AS special_period_id
FROM (SELECT 5 AS stream_id UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8) s
CROSS JOIN (SELECT 1 AS day UNION ALL SELECT 3) d
CROSS JOIN (SELECT 1 AS period UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8) p;

-- Add a couple of generation history rows (assumes admin with id=1 exists)
INSERT INTO `generation_history` (`generated_at`, `generated_by`, `status`, `notes`) VALUES
(NOW(), 1, 'success', 'Auto-generated sample timetables (streams 1-4 full week)'),
(NOW(), 1, 'success', 'Auto-generated sample timetables (streams 5-8 partial)');

-- ------------------------------------------------------------------
-- Extra subject_assignments to increase dataset density
-- Use INSERT IGNORE so re-running the seed won't fail on duplicates
-- Assign subjects 11-15 (Computer Studies, Agriculture, Home Science, Art, Music)
-- to streams 1-8 using teachers T011-T020 with reasonable periods_per_week
-- ------------------------------------------------------------------

INSERT IGNORE INTO `subject_assignments` (`stream_id`, `subject_id`, `teacher_id`, `periods_per_week`) VALUES
-- For Form 1 streams (1,2)
(1, 11, 11, 2),
(1, 12, 12, 1),
(1, 13, 13, 1),
(1, 14, 14, 1),
(1, 15, 15, 1),
(2, 11, 11, 2),
(2, 12, 12, 1),
(2, 13, 13, 1),
(2, 14, 14, 1),
(2, 15, 15, 1),

-- For Form 2 streams (3,4)
(3, 11, 16, 2),
(3, 12, 17, 1),
(3, 13, 18, 1),
(3, 14, 19, 1),
(3, 15, 20, 1),
(4, 11, 16, 2),
(4, 12, 17, 1),
(4, 13, 18, 1),
(4, 14, 19, 1),
(4, 15, 20, 1),

-- For Form 3 streams (5,6)
(5, 11, 11, 3),
(5, 12, 12, 2),
(5, 13, 13, 2),
(5, 14, 14, 2),
(5, 15, 15, 1),
(6, 11, 16, 2),
(6, 12, 17, 2),
(6, 13, 18, 2),
(6, 14, 19, 2),
(6, 15, 20, 1),

-- For Form 4 streams (7,8)
(7, 11, 11, 3),
(7, 12, 12, 2),
(7, 13, 13, 2),
(7, 14, 14, 2),
(7, 15, 15, 1),
(8, 11, 16, 3),
(8, 12, 17, 2),
(8, 13, 18, 2),
(8, 14, 19, 2),
(8, 15, 20, 1);

-- Add one more special period for variety
INSERT IGNORE INTO `special_periods` (`name`, `day_of_week`, `start_period`, `end_period`, `is_active`) VALUES
('Staff Meeting', 4, 2, 2, 1); -- Thursday, period 2

-- Extra generation history rows for testing
INSERT INTO `generation_history` (`generated_at`, `generated_by`, `status`, `notes`) VALUES
(NOW() - INTERVAL 10 DAY, 1, 'success', 'Sample generation 10 days ago'),
(NOW() - INTERVAL 7 DAY, 1, 'success', 'Sample generation 7 days ago'),
(NOW() - INTERVAL 3 DAY, 1, 'failed', 'Sample failed generation 3 days ago'),
(NOW() - INTERVAL 2 DAY, 1, 'partial', 'Sample partial generation 2 days ago'),
(NOW() - INTERVAL 1 DAY, 1, 'success', 'Sample generation yesterday');


