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


