
Built by https://www.blackbox.ai

---

```markdown
# DYCI Organization Management System

## Project Overview
The DYCI Organization Management System is a web application designed for efficiently managing users and organizations within a school environment. It supports different user roles including admin, organization officers, and students, providing tailored functionalities for each role. Users can log in to view dashboards, manage users, track activities, generate reports, and oversee attendance.

## Installation
To set up your local instance of the DYCI Organization Management System, follow these steps:

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/your-repository/dyci-organization-management.git
   cd dyci-organization-management
   ```

2. **Set Up the Database:**
   - Create a MySQL database (e.g., `dyci_school`).
   - Import the SQL schema if provided in the repository to set up necessary tables.

3. **Update Configuration:**
   - Modify the `db-connect.php` file to reflect your database credentials.
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'dyci_school');
   ```

4. **Install PHP and Required Extensions:**
   Make sure you have PHP (7.4 or higher) and required extensions installed.

5. **Start Your Web Server:**
   You can use Apache, Nginx or PHP's built-in server. For the built-in server:
   ```bash
   php -S localhost:8000
   ```

6. **Access the application:**
   Open your web browser and navigate to `http://localhost:8000/login.php`.

## Usage
After installation, you can access the application by navigating to the login page. Users can log in using their credentials. Depending on their role, they will have access to features like:

- **Admin Features:**
  - User management
  - Organization management
  - Reporting
  - Security log access

- **Organization Officer Features:**
  - Managing members of their organization
  - Scheduling and managing activities
  - Viewing attendance

- **Student Features:**
  - Viewing student-specific details and activities

## Features
- User authentication & role-based access control
- User management: Create, update, delete user accounts
- Organization management: Manage organizations and their details
- Activity management: Schedule and manage activities, view attendance
- Security logging for tracking user actions
- Responsive UI built using Tailwind CSS

## Dependencies
The project does not have external dependencies defined in `package.json`. However, make sure to have the following installed:
- PHP (>= 7.4)
- MySQL
- Web server (Apache or Nginx)

Note: If you need to extend additional functionalities or integrate other packages, you may create a `package.json` if needed.

## Project Structure
```
dyci-organization-management/
├── db-connect.php          # Database connection and utility functions
├── login.php               # Login page with authentication
├── logout.php              # Logout logic
├── admin-dashboard.php      # Admin dashboard UI
├── org-officer-dashboard.php # Organization officer dashboard UI
├── user-management.php      # Interface for managing users
├── org-management.php       # Interface for managing organizations
├── org-members.php          # Management of organization members
├── org-activities.php       # Management of the organization's activities
├── activity-scheduler.php   # Scheduling new activities
├── attendance-handler.php    # Logic for handling attendance actions
├── export-attendance.php     # Logic for exporting attendance records
├── unauthorized.php          # Unauthorized access handler
└── org-details.php          # Details view for an organization
```

## Contributing
If you would like to contribute to this project, please feel free to open issues or submit pull requests. Contributions are welcome!

## License
This project is licensed under the [MIT License](LICENSE).

## Contact
For any inquiries you may have, please feel free to reach out to the project maintainer.
```