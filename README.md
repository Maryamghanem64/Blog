# Professional Blog Platform

A modern, responsive blog platform built with PHP, MySQL, HTML, CSS, JavaScript, and Bootstrap 5.

## Features
- User registration and login (with validation and security)
- Dark mode and light mode support
- Create, edit, and delete blog posts with image uploads
- Category management for posts
- Commenting system for posts
- User dashboard and admin panel
- Responsive design for all devices
- Clean, professional UI with Bootstrap 5

## Technologies Used
- PHP (server-side logic)
- MySQL (database)
- HTML5 & CSS3 (structure and styling)
- JavaScript (interactivity)
- Bootstrap 5 (responsive design)

## Setup Instructions
1. **Clone or copy this repository to your web server directory.**
2. **Database Setup:**
   - Import `database_setup.sql` into your MySQL server to create the required database and tables.
   - Update `config/database.php` with your MySQL credentials if needed.
3. **File Permissions:**
   - Ensure the `uploads/` directory is writable by the web server for image uploads.
4. **Configuration:**
   - Review and adjust settings in `config/config.php` as needed (site name, email, etc).
5. **Access the App:**
   - Open your browser and go to `http://localhost/blog` (or your server's URL).
   - Register a new user or log in with the default admin (see `database_setup.sql`).

## Usage Notes
- **Admin User:** Default admin credentials are set in `database_setup.sql`.
- **Image Uploads:** Supported formats: JPG, JPEG, PNG, GIF. Max size: 5MB.
- **Dark Mode:** Toggle dark mode using the provided button in the UI.
- **Security:** CSRF protection, input sanitization, and password hashing are implemented.

## Folder Structure
- `assets/` - CSS and JS assets
- `config/` - Configuration files
- `controllers/` - PHP controllers (auth, etc)
- `models/` - Data models (User, Post, etc)
- `uploads/` - Uploaded images
- Main PHP files for each page (home, post, register, login, etc)

## License
This project is for educational and personal use. Feel free to modify and extend it for your needs. 