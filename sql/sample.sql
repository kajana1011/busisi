-- Sample SQL commands to truncate forms and streams tables
-- Note: This will delete all data in forms and streams tables and related data due to foreign key constraints

USE `busisi`;

-- Disable foreign key checks to allow truncation
SET FOREIGN_KEY_CHECKS = 0;

-- Truncate tables in order (child tables first due to foreign key constraints)
TRUNCATE TABLE `timetables`;
TRUNCATE TABLE `subject_assignments`;
TRUNCATE TABLE `streams`;
TRUNCATE TABLE `forms`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Optional: Reset auto-increment counters
ALTER TABLE `forms` AUTO_INCREMENT = 1;
ALTER TABLE `streams` AUTO_INCREMENT = 1;
ALTER TABLE `subject_assignments` AUTO_INCREMENT = 1;
ALTER TABLE `timetables` AUTO_INCREMENT = 1;
