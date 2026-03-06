 LebaneseAirline - Travel Booking System

Frontend prototype of a flight booking system built with:

- PHP
- HTML
- CSS
- JavaScript

## Features

- Flight search
- Booking system UI
- User dashboard
- My bookings page
- Profile management
- Authentication pages
- Admin dashboard interface

also if you want doctor download the source of code use xamp and run using yor browser:https://localhost/travel-booking-system/

also since it is only the front-end no back-end(no database) if you want to try the interface of user when loggin how 
header  change and also when an admin loggin how the header of the page change to see it in header of php after
this: 
<?php
include __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
paste this:
$_SESSION['user_id']=1;
$_SESSION['role']='user'; or change it to admin to acess the admin interface or user interface since this is only front-end no database
?>

if you want because there is no refresh method because still without database before switching between user and admin open
this in your browser:http://localhost/travel-booking-system/auth/logout.php
click refresh then go back to the main link so you can switch
