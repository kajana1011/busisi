# Busisi Secondary School Timetable Generator

A comprehensive web-based system built with **PHP**, **MySQL**, and **Bootstrap** to automatically generate conflict-free timetables for secondary schools.

## Features

### Complete School Management
- Register and manage school information
- Define academic forms (Form 1-4)
- Manage class streams under each form
- Add and organize subjects
- Manage teaching staff information

### Smart Timetable Generation
- **Automatic conflict-free scheduling** - No teacher double-booking
- **Even distribution** - Periods evenly distributed throughout the week
- **Single and double periods** - Intelligent handling of odd/even period counts
- **Special periods** - Support for sports, debate, religion sessions (common to all streams)
- **Break periods** - Configurable break times
- **Randomized generation** - Variety in timetable layouts

### Flexible Configuration
- Customizable school days (5-7 days per week)
- Adjustable periods per day (6-12 periods)
- Configurable period duration (30-60 minutes)
- Custom break time slots
- School-specific settings

### Timetable Preview & Editing
- **Drag-and-drop editing** - Swap periods by dragging
- **Real-time conflict detection** - System warns about conflicts when swapping
- **Visual indicators** - Color-coded breaks, special periods, and double periods

### Export & Sharing
- Export timetables to CSV format
- Print-ready layouts
- Professional design

## Installation

### Requirements
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB
- Apache web server
- PDO and PDO_MySQL extensions

### Setup Instructions

1. **Clone or download** this project to your web server directory (e.g., `htdocs` for XAMPP)

2. **Configure database settings**
   - Copy `config/database.example.php` to `config/database.php`
   - Edit `config/database.php` with your MySQL credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'busisi');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. **Run the setup wizard**
   - Open your browser and navigate to: `http://localhost/busisi/setup.php`
   - Follow the setup wizard to create the database and tables

4. **Login to the admin panel**
   - Navigate to: `http://localhost/busisi/admin/login.php`
   - Default credentials:
     - Username: `admin`
     - Password: `admin123`
   - **Important:** Change the password after first login!

## Usage Guide

### 1. Initial Configuration

1. **Settings** → Configure school information
   - Set school name, days per week, periods per day
   - Add break periods
   - Add special periods (Sports, Debate, Religion)

2. **Forms & Streams** → Create academic structure
   - Add forms (e.g., Form 1, Form 2, Form 3, Form 4)
   - Add streams under each form (e.g., A, B, Science, Arts)

3. **Subjects** → Add all subjects taught

4. **Teachers** → Add teaching staff

5. **Assignments** → Link teachers to subjects in specific streams
   - Set periods per week for each subject

### 2. Generate Timetables

1. Go to **Generate** page
2. Check system readiness
3. Click **Generate Timetables**
4. System automatically creates schedules for all streams

### 3. View and Edit Timetables

1. Go to **View Timetables**
2. Select a stream from dropdown
3. **Drag and drop** periods to swap them
4. System will warn if swap causes conflicts
5. Export or print as needed

## How the Algorithm Works

### Period Distribution
- Subjects with **even periods** (e.g., 6 periods): Distributed as 3 double periods
- Subjects with **odd periods** (e.g., 5 periods): Distributed as 2 double periods + 1 single period
- Double periods are prioritized during allocation
- Maximum 2 periods of same subject per day for even distribution

### Conflict Prevention
- **Teacher scheduling**: System tracks teacher availability across all streams
- **No double-booking**: Teachers cannot be assigned to multiple classes at same time
- **Special period blocking**: Slots reserved for special periods are unavailable

### Smart Allocation
- Random assignment with constraints
- Even distribution across days
- Respect for break periods and special sessions
- Multiple attempts for optimal placement

## File Structure

```
busisi/
├── admin/                  # Admin panel pages
│   ├── ajax/              # AJAX endpoints
│   ├── index.php          # Dashboard
│   ├── forms.php          # Forms & Streams management
│   ├── subjects.php       # Subjects management
│   ├── teachers.php       # Teachers management
│   ├── assignments.php    # Subject assignments
│   ├── settings.php       # School settings
│   ├── generate.php       # Timetable generation
│   ├── view.php           # View & edit timetables
│   └── export.php         # Export functionality
├── assets/
│   ├── css/               # Stylesheets
│   └── js/
│       └── main.js        # Drag-and-drop functionality
├── config/
│   ├── database.php       # Database configuration
│   └── database.example.php
├── includes/
│   ├── db.php             # Database connection
│   ├── functions.php      # Helper functions
│   ├── timetable_generator.php  # Generation algorithm
│   ├── header.php         # Common header
│   └── footer.php         # Common footer
├── sql/
│   ├── schema.sql         # Database schema
│   └── initial_data.sql   # Default data
├── index.php              # Main entry point
├── setup.php              # Setup wizard
└── README.md              # This file
```

## Database Schema

### Core Tables
- `admins` - System administrators
- `forms` - Academic forms
- `streams` - Class streams
- `subjects` - School subjects
- `teachers` - Teaching staff
- `subject_assignments` - Teacher-subject-stream links
- `school_settings` - Configuration
- `break_periods` - Break time slots
- `special_periods` - Special sessions
- `timetables` - Generated schedules
- `generation_history` - Generation logs

## Troubleshooting

### Database Connection Issues
- Check MySQL server is running
- Verify credentials in `config/database.php`
- Ensure database exists or run setup wizard

### Generation Fails
- Verify all prerequisites (forms, streams, subjects, teachers, assignments)
- Check for sufficient periods to accommodate all assignments
- Review error in generation history

### Drag-and-Drop Not Working
- Ensure JavaScript is enabled
- Check browser console for errors
- Try a modern browser (Chrome, Firefox, Edge)

## Security Features

- Password hashing with PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Session-based authentication
- CSRF protection on forms

## Future Enhancements

- Excel export with PhpSpreadsheet
- PDF generation
- Email notifications
- Multi-school support
- REST API
- Mobile app
- Advanced AI optimization
- Student portal

## License

This project is open source and available for educational purposes.

## Credits

Built with:
- PHP 8.0+
- MySQL/MariaDB
- Bootstrap 5.3
- Bootstrap Icons

**Made with dedication for Busisi Secondary School**

## Support

For issues or questions:
1. Check this README
2. Review troubleshooting section
3. Check database and configuration
4. Verify all prerequisites are met

---

**Version:** 1.0.0
**Last Updated:** 2025
