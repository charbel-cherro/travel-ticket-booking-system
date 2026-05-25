<?php
// MySQL connection used by the whole project.
// For XAMPP, keep username root and empty password.

function getPDOConnection(): PDO
{
    $host = '127.0.0.1';
    $dbname = 'travel_booking_db';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die('Database connection failed. Import database/travel_booking_db.sql in phpMyAdmin first. Error: ' . htmlspecialchars($e->getMessage()));
    }
}
