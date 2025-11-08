-- Initial Data for Busisi Timetable Generator
-- Default admin user: admin / admin123

USE `busisi`;

-- Insert default admin (password: admin123)
INSERT INTO `admins` (`username`, `password`, `email`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@busisi.com');

-- Insert default school settings
INSERT INTO `school_settings` (`setting_key`, `setting_value`) VALUES
('school_name', 'Busisi Secondary School'),
('school_address', ''),
('school_phone', ''),
('school_email', ''),
('school_days', '5'),
('periods_per_day', '8'),
('period_duration', '40'),
('school_start_time', '08:00'),
('academic_year', '2025'),
('setup_completed', '0');

-- Insert default break periods
INSERT INTO `break_periods` (`name`, `period_number`, `duration_minutes`) VALUES
('Short Break', 3, 15),
('Lunch Break', 5, 30);
