# Travel Booking System

This is a PHP and MySQL travel booking system built to run locally using XAMPP.  
The project allows users to browse flights, make bookings, manage their profile, and reset their password using email. It also includes an admin area for managing flights and user bookings.

## Main Features

### User Features
- User registration and login
- User dashboard
- View available flights
- Make travel bookings
- View personal bookings
- Update user profile
- Reset password through email link

### Admin Features
- Admin dashboard
- Manage flights
- Add, edit, and delete flight records
- Manage user bookings
- View booking details
- Update booking information

### System Features
- PHP backend
- MySQL database connection
- PHPMailer support for email sending
- Organised project structure with separate folders for admin, user, authentication, database, includes, and assets

## Project Structure

```text
travel-booking-system/
│
├── admin/          # Admin pages and management features
├── assets/         # CSS, images, and other front-end assets
├── auth/           # Login, registration, logout, and password reset pages
├── data/           # Old/local data files kept for compatibility
├── database/       # Database file or SQL setup files
├── includes/       # Shared PHP files such as database connection, header, footer, and functions
├── user/           # User dashboard, booking pages, and profile pages
├── vendor/         # Composer dependencies such as PHPMailer
│
├── composer.json   # Composer package configuration
├── composer.lock   # Composer dependency lock file
├── index.php       # Main homepage
├── readme.md       # Project description
└── smtp-test.php   # Email testing file