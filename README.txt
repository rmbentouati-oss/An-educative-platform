# LearnHub – Online Learning Management System

**LearnHub** is a web-based educational platform designed to simplify online course management and provide students with an organized learning experience.

The platform enables administrators to manage courses, upload educational videos, review student payment submissions, and control access to learning materials through a centralized dashboard.

## Key Features

### Administrative Dashboard
- Create, edit, publish, and manage courses.
- Upload and organize course lessons and videos.
- Review student payment receipts.
- Approve or reject enrollment requests.
- Manage course availability and publication status.

### Student Features
- Create student accounts and log in securely.
- Browse available courses.
- Enroll in free courses.
- Submit payment receipts for paid courses.
- Access course videos after enrollment approval.
- Follow their enrolled courses.

### Security & Data Management
- Secure password hashing.
- PDO prepared statements.
- CSRF protection.
- File type and MIME validation.
- Role-based access control.
- Controlled access to educational content.

## Technology Stack

| Technology | Purpose |
|---|---|
| PHP 8.1+ | Backend development |
| MySQL | Database management |
| HTML5 | Page structure |
| CSS3 | User interface styling |
| JavaScript | Frontend interactions |
| PDO | Secure database connectivity |
| XAMPP | Local development environment |

## Project Structure


An-educative-platform/
│
├── admin/          # Administrative dashboard
├── assets/
│   └── css/        # Stylesheets
├── auth/           # Authentication system
├── config/         # Database configuration
├── includes/       # Shared functions and components
├── student/        # Student dashboard and features
├── uploads/        # Uploaded files and learning materials
│
├── course.php      # Course details and enrollment
├── index.php       # Main landing page
├── database.sql    # Database schema
└── README.md       # Project documentation


## Installation & Setup

### Requirements
- PHP 8.1 or newer
- MySQL
- Apache Server
- XAMPP or an equivalent local server environment

### Installation Steps

1. Clone the repository:

   
   git clone https://github.com/rmbentouati-oss/An-educative-platform.git
   

2. Move the project folder into:


   C:\xampp\htdocs\
   

3. Start Apache and MySQL from XAMPP Control Panel.

4. Open phpMyAdmin and import `database.sql`.

5. Configure your database credentials in:


   config/config.php

6. Access the application:

   
   http://localhost/An-educative-platform/
   

**Note:** If the application uses a different `BASE_URL`, update it in the configuration file accordingly.

## Default Administrator Account

For local testing:

- **Email:** admin@example.com
- **Password:** Admin@12345

Change the default password immediately after your first login.

## Application Workflow

1. The administrator creates and publishes courses.
2. Educational videos are uploaded and organized into lessons.
3. Students register and explore available courses.
4. Students enroll in free courses or submit payment receipts for paid courses.
5. The administrator reviews submitted receipts.
6. Once approved, students gain access to the corresponding course materials.

## Future Improvements

Potential enhancements include:

- Online payment gateway integration.
- Course progress tracking.
- Student profile management.
- Course search and filtering.
- Improved video management.
- Notifications for enrollment updates.
- Responsive interface improvements.

## Project Status

**Version 1.0 – Initial Release**

This project is an educational web application developed to demonstrate practical skills in backend development, database management, authentication, and web application architecture.

## Developer

**BENTOUATI Ryma**

Computer Science Student | Web Developer

GitHub: [@rmbentouati-oss](https://github.com/rmbentouati-oss)

---

*Built with PHP, MySQL, and a passion for web development.*
