# Travel Booking System - MySQL Version

This version keeps the original project layout/design, but removes JSON storage and uses MySQL with PDO.

## Setup in XAMPP

1. Copy the folder `travel-booking-system-mysql-only` into `htdocs`.
2. Open phpMyAdmin.
3. Import `database/travel_booking_db.sql`.
4. Open the project in the browser:
   `http://localhost/travel-booking-system-mysql-only/`

## Default admin login

Email: `admin@lebaneseairline.com`  
Password: `Admin123!`

## What changed

- Removed the old `data/*.json` files.
- Added MySQL tables for users, destinations, flights, insurance, bookings, passengers, seats, segments, and payments.
- Rebuilt the original data functions to use PDO prepared statements.
- Kept the original CSS, HTML structure, and visual design.
