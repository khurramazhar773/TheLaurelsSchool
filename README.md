# The Laurels School Learning Management System (LMS)

A comprehensive Learning Management System built for The Laurels School using PHP, MySQL, HTML, CSS, and JavaScript.

## 🎓 Features

### For Administrators
- User management (students, teachers, admins)
- Class and subject management
- System-wide announcements
- Activity monitoring and logs
- Database management

### For Teachers
- Create and manage lessons
- Assign and grade assignments
- Track student attendance
- Manage class materials
- View student progress

### For Students
- Access course materials
- Submit assignments
- View grades and feedback
- Track attendance
- Access announcements

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Server**: Apache/Nginx
- **Additional**: Font Awesome Icons

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser

## 🚀 Installation

### 1. Clone or Download the Project
```bash
# If using git
git clone [repository-url]
cd "The Laurels School LMS"

# Or download and extract the ZIP file
```

### 2. Database Setup
1. Create a MySQL database named `laurels_school_lms`
2. Import the database schema:
   ```bash
   mysql -u root -p laurels_school_lms < database/schema.sql
   ```
   Or use phpMyAdmin to import the `database/schema.sql` file.

### 3. Configuration
1. Open `config/database.php`
2. Update the database connection settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'laurels_school_lms');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

3. Update the site URL in `config/config.php`:
   ```php
   define('SITE_URL', 'http://your-domain.com/The%20Laurels%20School%20LMS');
   ```

### 4. File Permissions
Ensure the following directories are writable:
```bash
chmod 755 assets/uploads/
chmod 755 assets/images/
```

### 5. Web Server Configuration
Place the project in your web server's document root or configure a virtual host.

## 🔐 Default Login Credentials

After installation, you can log in with the default admin account:

- **Email**: admin@laurelsschool.com
- **Password**: admin123

**⚠️ Important**: Change the default password immediately after first login!

## 📁 Project Structure

```
The Laurels School LMS/
├── admin/                 # Admin panel files
├── student/              # Student portal files
├── teacher/              # Teacher portal files
├── assets/               # Static assets
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   ├── images/          # Images
│   └── uploads/         # User uploaded files
├── config/              # Configuration files
├── database/            # Database files
├── includes/            # PHP includes and functions
├── index.php           # Main entry point
└── README.md           # This file
```

## 🗄️ Database Schema

The system includes the following main tables:

- **users** - User accounts and profiles
- **classes** - Class information
- **subjects** - Subject/course information
- **enrollments** - Student class enrollments
- **lessons** - Lesson content and materials
- **assignments** - Assignment details
- **assignment_submissions** - Student submissions
- **attendance** - Student attendance records
- **grades** - Student grades and assessments
- **announcements** - System announcements
- **files** - File uploads and attachments
- **activity_logs** - System activity tracking

## 🎨 Customization

### Styling
- Main stylesheet: `assets/css/style.css`
- Color scheme can be modified in the CSS variables
- Responsive design included

### Functionality
- Core functions: `includes/functions.php`
- Database operations: `config/database.php`
- System configuration: `config/config.php`

## 🔒 Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Session management and timeout
- File upload validation
- Activity logging

## 📱 Responsive Design

The system is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones

## 🚨 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **File Upload Issues**
   - Check file permissions on `assets/uploads/`
   - Verify PHP upload settings in `php.ini`
   - Check file size limits

3. **Login Problems**
   - Ensure database is properly imported
   - Check if default admin account exists
   - Verify session configuration

4. **Page Not Found Errors**
   - Check web server configuration
   - Verify file paths and permissions
   - Ensure URL rewriting is properly configured

### Debug Mode

Enable debug mode in `config/config.php`:
```php
define('DEBUG_MODE', true);
```

## 📞 Support

For technical support or questions:
- Check the troubleshooting section above
- Review the code comments for guidance
- Contact your system administrator

## 🔄 Updates

To update the system:
1. Backup your database and files
2. Download the latest version
3. Replace files (except `config/` and `assets/uploads/`)
4. Run any database migration scripts
5. Test the system thoroughly

## 📄 License

This project is developed for The Laurels School. Please ensure compliance with your organization's policies.

## 🤝 Contributing

If you're contributing to this project:
1. Follow the existing code style
2. Test your changes thoroughly
3. Update documentation as needed
4. Ensure security best practices

---

**Version**: 1.0.0  
**Last Updated**: September 2025  
**Developed for**: The Laurels School 