LEARNHUB — PHP + MySQL EDUCATIONAL PLATFORM
===========================================

FEATURES
- English interface.
- One administrator dashboard manages courses, lessons/videos, and student payment receipts.
- No categories.
- Admin creates courses and chooses draft/published status.
- Admin uploads MP4, WebM, or OGG lesson videos for each course.
- Students register using public registration (student role only).
- Students can enroll in free courses immediately.
- Students submit a payment receipt for paid courses (JPG, PNG, WEBP, PDF).
- Admin approves/rejects payment submissions.
- Only approved students can watch course videos.
- PDO prepared statements, password hashing, CSRF checks, upload MIME validation.

INSTALLATION ON XAMPP (WINDOWS)
1. Extract the folder named edu-platform into C:\xampp\htdocs\
2. Open XAMPP Control Panel and start Apache and MySQL.
3. Open http://localhost/phpmyadmin
4. Import database.sql (it creates the learnhub_platform database and tables).
5. Open config/config.php and verify DB credentials. Default XAMPP settings are:
   host=localhost, database=learnhub_platform, username=root, password=(empty)
6. Open http://localhost/edu-platform/

ADMIN LOGIN
Email: admin@example.com
Password: Admin@12345
Change this password immediately after the first login. Do not share the admin account.

VIDEO UPLOAD CONFIGURATION
The app-level maximum video size is 500 MB. PHP also limits uploads. To permit larger videos:
1. Open C:\xampp\php\php.ini
2. Set (example):
   upload_max_filesize = 500M
   post_max_size = 520M
   max_execution_time = 300
   max_input_time = 300
3. Save php.ini and restart Apache.
4. Confirm available disk space. Large video files can fill your drive.

RECOMMENDED TEST FLOW
1. Log in as admin.
2. Create a course, publish it, then open Manage videos.
3. Upload a lesson video.
4. Register a separate student account.
5. For a paid course, submit a receipt from the course page.
6. Return to the admin dashboard and approve/reject the receipt.
7. Student logs in and opens the course from their dashboard after approval.

IMPORTANT
- This project is a starter platform and has not been independently security-audited.
- For real payments, verify transfers manually before approving receipts.
- Use HTTPS and proper backups before public deployment.
- BASE_URL in config/config.php is set to /edu-platform. Keep the extracted folder name exactly edu-platform, or update BASE_URL.
- If your PHP version does not support the syntax used here, use PHP 8.1 or newer (recommended).
