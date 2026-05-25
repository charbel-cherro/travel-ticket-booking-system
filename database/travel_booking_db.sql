-- Travel Booking System - MySQL database
-- Import this file in phpMyAdmin before running the project.
CREATE DATABASE IF NOT EXISTS travel_booking_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE travel_booking_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS booking_segments;
DROP TABLE IF EXISTS booking_passengers;
DROP TABLE IF EXISTS booking_seats;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS flights;
DROP TABLE IF EXISTS insurance_options;
DROP TABLE IF EXISTS insurance;
DROP TABLE IF EXISTS destinations;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    airport_code VARCHAR(10) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE insurance_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    description VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Compatibility table in case your teacher expects a table named insurance.
CREATE TABLE insurance (
    insurance_id INT AUTO_INCREMENT PRIMARY KEY,
    insurance_type VARCHAR(100) NOT NULL,
    insurance_price DECIMAL(10,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE flights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    from_city VARCHAR(100) NOT NULL,
    to_city VARCHAR(100) NOT NULL,
    flight_date DATE NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    stops INT NOT NULL DEFAULT 0,
    economy_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    business_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    first_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('active', 'cancelled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    user_name VARCHAR(120) NOT NULL,
    user_email VARCHAR(150) NOT NULL,
    flight_id INT NULL,
    route VARCHAR(255) NOT NULL,
    flight_name VARCHAR(120) NOT NULL,
    flight_code VARCHAR(20) NOT NULL,
    flight_time VARCHAR(50) NOT NULL,
    flight_date DATE NULL,
    trip_type VARCHAR(50) NOT NULL,
    class_type VARCHAR(50) NOT NULL,
    passengers INT NOT NULL DEFAULT 1,
    seat_number VARCHAR(255) NOT NULL,
    return_seat_number VARCHAR(255) DEFAULT '',
    insurance_id INT NULL,
    insurance_name VARCHAR(100) NOT NULL DEFAULT 'None',
    insurance_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    hand_bags INT NOT NULL DEFAULT 0,
    checked_bags INT NOT NULL DEFAULT 0,
    bag_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Confirmed',
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_bookings_flight FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE SET NULL,
    CONSTRAINT fk_bookings_insurance FOREIGN KEY (insurance_id) REFERENCES insurance_options(id) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1001;

CREATE TABLE booking_passengers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    passenger_name VARCHAR(120) NOT NULL,
    CONSTRAINT fk_passengers_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE booking_seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    flight_id INT NULL,
    class_type VARCHAR(50) NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    leg_type ENUM('outbound','return') NOT NULL DEFAULT 'outbound',
    CONSTRAINT fk_seats_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_seats_flight FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE booking_segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    segment_order INT NOT NULL,
    from_city VARCHAR(100) NOT NULL,
    to_city VARCHAR(100) NOT NULL,
    segment_date DATE NULL,
    CONSTRAINT fk_segments_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(50) NOT NULL DEFAULT 'Paid',
    payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (id, name, email, password, role) VALUES
(1, 'Admin', 'admin@lebaneseairline.com', '$2y$12$d43FQY.8lLgWKhXf2pPDheFtYlOMb.bOKLRmJ8yFkaw3qFs7hCCkW', 'admin');

INSERT INTO destinations (id, city, country, airport_code) VALUES
(1, 'Beirut', 'Lebanon', 'BEY'),
(2, 'Paris', 'France', 'CDG'),
(3, 'London', 'United Kingdom', 'LHR'),
(4, 'Dubai', 'United Arab Emirates', 'DXB'),
(5, 'Tokyo', 'Japan', 'HND'),
(6, 'Rome', 'Italy', 'FCO'),
(7, 'Istanbul', 'Turkey', 'IST');

INSERT INTO insurance_options (id, name, price, description) VALUES
(1, 'None', 0.00, 'No additional travel cover.'),
(2, 'Basic', 15.00, 'Trip delay and basic baggage support.'),
(3, 'Premium', 30.00, 'Medical support, baggage cover, and flexible assistance.');

INSERT INTO insurance (insurance_id, insurance_type, insurance_price) VALUES
(1, 'None', 0.00),
(2, 'Basic', 15.00),
(3, 'Premium', 30.00);

INSERT INTO flights (id, name, code, from_city, to_city, flight_date, departure_time, arrival_time, stops, economy_price, business_price, first_price, status) VALUES
(1, 'Beirut–Paris', 'LA203', 'Beirut', 'Paris', '2026-04-10', '08:00:00', '12:30:00', 0, 420.00, 690.00, 980.00, 'active'),
(2, 'Beirut–London', 'LA118', 'Beirut', 'London', '2026-04-12', '09:30:00', '14:30:00', 1, 350.00, 620.00, 910.00, 'active'),
(3, 'Dubai–Tokyo', 'LA450', 'Dubai', 'Tokyo', '2026-04-15', '22:00:00', '10:00:00', 1, 490.00, 830.00, 1180.00, 'active'),
(4, 'London–Rome', 'LA319', 'London', 'Rome', '2026-04-18', '11:15:00', '14:05:00', 0, 310.00, 520.00, 780.00, 'active'),
(5, 'Beirut–Istanbul', 'LA271', 'Beirut', 'Istanbul', '2026-04-20', '07:20:00', '09:15:00', 0, 260.00, 430.00, 660.00, 'active');
