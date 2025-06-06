## Prerequisites

Before you begin, ensure you have the following installed:
- PHP 7.4 or higher
- MySQL/MariaDB
- Composer (PHP package manager)
- XAMPP (or similar local development environment)
- Git

## Installation Steps

1. **Clone the Repository**

2. **Set Up XAMPP**
   - Start XAMPP Control Panel
   - Start Apache and MySQL services
   - Ensure Apache is running on port 80 (default)
   - Ensure MySQL is running on port 3306 (default)

3. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `project_ptyxiakh`
   - Import the database schema from the `database` directory

4. **Install Dependencies**
   ```bash
   composer install
   ```

5. **Configure Environment**
   - Update the database credentials in your configuration file:
     - Database name: `project_ptyxiakh`
     - Username: `root` (default XAMPP username)
     - Password: `` (default XAMPP password is empty)
     - Host: `localhost`

6. **Project Structure**
   - `public/` - Publicly accessible files
   - `includes/` - PHP includes and core functionality
   - `database/` - Database schemas and migrations
   - `assets/` - Static assets (CSS, JS, images)
   - `vendor/` - Composer dependencies

7. **Running the Project**
   - Place the project in your XAMPP htdocs directory
   - Access the project and right click on the home.php file and select the "PHP SERVER: Serve Project" option.

## Development


### Automated Tasks
- The project includes a reminder system that can be run using `send_reminders.bat`

## Troubleshooting

1. **Apache Not Starting**
   - Check if port 80 is not in use by another application
   - Verify XAMPP installation
   - Check Apache error logs in XAMPP

2. **Database Connection Issues**
   - Verify MySQL service is running
   - Check database credentials in configuration
   - Ensure database exists and is properly imported

3. **Composer Issues**
   - Run `composer update` to update dependencies
   - Check PHP version compatibility
   - Clear composer cache if needed: `composer clear-cache`
