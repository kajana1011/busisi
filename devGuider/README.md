# Automated Secondary School Timetable Generator

A comprehensive web-based system built with **PHP**, **MySQL**, and **Bootstrap** to automatically generate conflict-free timetables for secondary schools.

## 🎯 Features

- **Complete School Management**
  - Registering a school 
  - Define academic forms (Form 1-4)
  - Manage class streams under each form
  - Add and organize subjects
  - Manage teaching staff information

- **Smart Timetable Generation**
  - Automatic conflict-free scheduling
  - No teacher double-booking
  - Configurable school days and periods, as well as special and common periods like debate,  religion or sports and game sessions
  - Support for break periods
  - Single and double period subjects
  - Randomized generation for variety
  - Previewing and editing the generated timetable before being saved

- **Flexible Configuration**
  - Customizable school days (5-7 days per week)
  - Adjustable periods per day (6-12 periods)
  - Configurable period duration (30-60 minutes)
  - Custom break time slots
  - School-specific settings

- **Export & Sharing**
  - Export all timetables in bulk excell document 
  - Print-ready formats
  - Professional layout design

- **Responsive Design**
  - Works on desktop, tablet, and mobile devices
  - Bootstrap-based UI
  - Modern and intuitive interface

## 🛠️ Technical Stack

- **Backend**: PHP 8.0+ (Core PHP, no framework)
- **Database**: MySQL 5.7+ or MariaDB
- **Frontend**: Bootstrap 5.3, HTML5, CSS3, JavaScript
- **Icons**: Bootstrap Icons
- **Architecture**: MVC-inspired structure

## 📋 Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Web Server**: Apache
- **Extensions**: PDO, PDO_MySQL
- **Browser**: Modern browser with JavaScript enabled

## 🚀 Installation

### Prerequisites

1. **Development Environment**
   - Install XAMPP (version 8.0 or higher) from [Apache Friends](https://www.apachefriends.org/)
   - Ensure PHP 8.0+ is selected during installation
   - Make sure MySQL/MariaDB is included in the installation

2. **Required Tools**
   - Git (for version control)
   - Web browser (Chrome, Firefox, or Edge recommended)
   - Text editor (VS Code recommended)

### Option 1: Quick Setup (Recommended)

1. **Set up XAMPP**
   ```powershell
   # Start XAMPP Control Panel and enable:
   # - Apache
   # - MySQL
   ```

2. **Clone the project**
   ```powershell
   cd c:\xampp\htdocs
   git clone https://github.com/kajana1011/busisi.git
   cd busisi
   ```

3. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database:
   ```sql
   CREATE DATABASE busisi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. **Configure the Application**
   - Copy `config/database.example.php` to `config/database.php`
   - Update database settings in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'busisi');
   define('DB_USER', 'root'); // default XAMPP username
   define('DB_PASS', '');     // default XAMPP password
   ```

5. **Set Permissions**
   ```powershell
   # Ensure the config directory is writable
   icacls "c:\xampp\htdocs\busisi\config" /grant "Users":(OI)(CI)F
   ```

6. **Initialize Application**
   - Open your browser and navigate to `http://localhost/busisi/setup.php`
   - Follow the setup wizard
   - Default admin credentials: 
     - Username: `admin`
     - Password: `admin123`

### Option 2: Manual Setup

1. **Database Creation**
   ```sql
   CREATE DATABASE busisi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE busisi;
   
   -- Run the following SQL files in order:
   -- 1. schema.sql
   -- 2. initial_data.sql
   ```

2. **Manual Configuration**
   - Copy and edit configuration files:
   ```powershell
   cd c:\xampp\htdocs\busisi
   copy config\database.example.php config\database.php
   # Edit config\database.php with your database settings
   ```

3. **Create Admin Account**
   ```sql
   INSERT INTO admins (username, password, email, created_at)
   VALUES ('admin', PASSWORD('admin123'), 'admin@example.com', NOW());
   ```

## 🔧 Configuration

### Development Environment Setup

1. **XAMPP Configuration**
   - Open XAMPP Control Panel
   - Apache Configuration (httpd.conf):
     ```apache
     DocumentRoot "C:/xampp/htdocs"
     <Directory "C:/xampp/htdocs">
         Options Indexes FollowSymLinks MultiViews
         AllowOverride All
         Require all granted
     </Directory>
     ```
   - MySQL Configuration (my.ini):
     ```ini
     [mysqld]
     max_allowed_packet=16M
     ```

2. **PHP Configuration**
   - Edit php.ini:
     ```ini
     max_execution_time = 300
     memory_limit = 256M
     post_max_size = 20M
     upload_max_filesize = 20M
     ```
   - Required Extensions:
     - pdo_mysql
     - mysqli
     - json
     - session

### Database Settings
1. **Create Configuration File**
   ```powershell
   copy config\database.example.php config\database.php
   ```

2. **Edit Database Configuration**
   Edit `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');     // Database host
   define('DB_NAME', 'busisi');        // Database name
   define('DB_USER', 'root');          // Database username
   define('DB_PASS', '');              // Database password
   define('DB_CHARSET', 'utf8mb4');    // Database charset
   ```

### Application Settings
1. **Initial Setup**
   - Log in as admin at `http://localhost/busisi/admin`
   - Navigate to Settings → School Configuration

2. **Configure School Parameters**
   - School Information:
     - School name
     - Address
     - Contact details
   - Academic Settings:
     - Number of school days (5-7 days)
     - Periods per day (6-12)
     - Period duration (30-60 minutes)
   - Schedule Configuration:
     - School start time
     - Break periods
     - Special periods (assembly, clubs)
     - Common periods (sports, religion)

## 📖 Usage Guide

### 1. Initial Setup
1. **Login** with admin credentials
2. **Add Forms**: Create academic forms (Form 1, Form 2, etc.)
3. **Add Streams**: Create class streams under each form (A, B, C, Science, Arts, etc.)
4. **Add Subjects**: Define all subjects taught in the school
5. **Add Teachers**: Add teaching staff information
6. **Configure Settings**: Set school-specific parameters

### 2. Subject Assignments
1. Go to **Subjects → Teacher Assignments** tab
2. Assign teachers to subjects in specific streams
3. Set periods per week for each subject


### 3. Generate Timetables
1. Go to **Generate** page
2. Verify system readiness
3. Click **Generate Timetables**
4. System automatically creates conflict-free schedules
5. If the subject has even periods per week (like 6 or four period) they should be doubled, and if odd like 5 periods there remaining one should be single,

### 4. View and Export
1. **View Timetables**: Review generated schedules
2. **Make Adjustments**: Edit if needed (teachers may request to interchange periods)
3. **Export**: Download in various formats 
4. **Print**: Use print-ready layouts (for future).



## 🔍 Database Schema

### Core Tables
- `admins` - System administrators
- `forms` - Academic forms (Form 1, 2, 3, 4)
- `streams` - Class streams under forms
- `subjects` - School subjects
- `teachers` - Teaching staff
- `subject_assignments` - Teacher-subject-stream assignments
- `school_settings` - System configuration
- `timetables` - Generated timetable data

## 🎨 Customization

### Styling
- Modify CSS in `includes/header.php`
- Customize Bootstrap theme
- Add custom styles for school branding

### Functionality
- Extend `includes/functions.php` for new features
- Add new pages following existing structure
- Customize export formats

### Algorithm
- Modify timetable generation logic in `generateTimetable()` function
- Add constraints and rules as needed
- Implement optimization algorithms

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check database credentials in `config/database.php`
   - Ensure MySQL server is running
   - Verify database exists and user has proper permissions

2. **Setup Wizard Issues**
   - Ensure `config` directory is writable
   - Check PHP has PDO_MySQL extension
   - Verify database user has CREATE privileges

3. **Timetable Generation Problems**
   - Ensure all prerequisites are complete (forms, streams, subjects, teachers, assignments)
   - Check for conflicting assignments
   - Verify school settings are properly configured

4. **Export Not Working**
   - Check file permissions
   - Ensure browser allows downloads
   - For Excel export, install PhpSpreadsheet library

### Performance Optimization

1. **Database Indexing**
   - Indexes are automatically created on foreign keys
   - Add custom indexes for frequently queried columns

2. **Large Schools**
   - Consider pagination for large data sets
   - Optimize queries for better performance
   - Use database views for complex reports

## 🔒 Security

### Built-in Security Features
- Password hashing with PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Session-based authentication
- CSRF protection on forms

### Recommended Security Measures
- Use HTTPS in production
- Regular database backups
- Keep PHP and MySQL updated
- Implement proper file permissions
- Use strong admin passwords

## 🚀 Future Enhancements

### Planned Features
- **Excel Export**: Native Excel format support
- **PDF Export**: Professional PDF generation
- **Email Integration**: Send timetables via email
- **Multi-school Support**: Manage multiple schools
- **API Endpoints**: REST API for mobile apps
- **Advanced Scheduling**: AI-based optimization
- **Student Portal**: Student view of timetables
- **Conflict Resolution**: Manual conflict resolution tools

### Contributing
1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 👥 Support

### Getting Help
1. Check this README for common solutions
2. Review the troubleshooting section
3. Search existing issues in the repository
4. Create a new issue with detailed information

### Bug Reports
When reporting bugs, please include:
- PHP version and server environment
- Browser and version
- Steps to reproduce the issue
- Error messages (if any)
- Screenshots (if applicable)

### Feature Requests
We welcome feature requests! Please:
- Check if the feature already exists
- Describe the use case clearly
- Explain why it would be beneficial
- Consider contributing the implementation

## 🙏 Acknowledgments

- **Bootstrap** - For the responsive UI framework
- **Bootstrap Icons** - For the comprehensive icon set
- **PHP Community** - For excellent documentation and resources
- **MySQL** - For the reliable database system

---

**Made with ❤️ for secondary schools, dedicated to Busisi Secondary School**


<!-- INSERT INTO timetable(stream_id, day_of_week, period_number, subject_id, teacher_id, is_break, is_special) -->
