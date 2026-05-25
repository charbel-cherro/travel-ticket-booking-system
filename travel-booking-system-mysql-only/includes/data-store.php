<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/db.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = getPDOConnection();
    }
    return $pdo;
}

function app_default_users(): array {
    return [
        [
            'id' => 1,
            'name' => 'Admin',
            'email' => 'admin@lebaneseairline.com',
            'password' => password_hash('Admin123!', PASSWORD_DEFAULT),
            'role' => 'admin',
            'created_at' => date('c')
        ]
    ];
}

function default_destinations(): array {
    return [
        ['id' => 1, 'city' => 'Beirut', 'country' => 'Lebanon', 'airport_code' => 'BEY'],
        ['id' => 2, 'city' => 'Paris', 'country' => 'France', 'airport_code' => 'CDG'],
        ['id' => 3, 'city' => 'London', 'country' => 'United Kingdom', 'airport_code' => 'LHR'],
        ['id' => 4, 'city' => 'Dubai', 'country' => 'United Arab Emirates', 'airport_code' => 'DXB'],
        ['id' => 5, 'city' => 'Tokyo', 'country' => 'Japan', 'airport_code' => 'HND'],
        ['id' => 6, 'city' => 'Rome', 'country' => 'Italy', 'airport_code' => 'FCO'],
        ['id' => 7, 'city' => 'Istanbul', 'country' => 'Turkey', 'airport_code' => 'IST'],
    ];
}

function default_insurance_options(): array {
    return [
        ['id' => 1, 'name' => 'None', 'price' => 0, 'description' => 'No additional travel cover.'],
        ['id' => 2, 'name' => 'Basic', 'price' => 15, 'description' => 'Trip delay and basic baggage support.'],
        ['id' => 3, 'name' => 'Premium', 'price' => 30, 'description' => 'Medical support, baggage cover, and flexible assistance.'],
    ];
}

function get_all_users(): array {
    $stmt = db()->query("SELECT id, name, email, password, role, created_at FROM users ORDER BY id ASC");
    return $stmt->fetchAll();
}

function save_all_users(array $users): void {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password, role, created_at)
            VALUES (:id, :name, :email, :password, :role, COALESCE(:created_at, NOW()))
            ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email), password = VALUES(password), role = VALUES(role)");
        foreach ($users as $user) {
            $stmt->execute([
                ':id' => (int)($user['id'] ?? 0) ?: null,
                ':name' => trim($user['name'] ?? ''),
                ':email' => strtolower(trim($user['email'] ?? '')),
                ':password' => $user['password'] ?? '',
                ':role' => $user['role'] ?? 'user',
                ':created_at' => isset($user['created_at']) ? date('Y-m-d H:i:s', strtotime($user['created_at'])) : null,
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function find_user_by_email(string $email): ?array {
    $stmt = db()->prepare("SELECT id, name, email, password, role, created_at FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
    $stmt->execute([':email' => trim($email)]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function next_user_id(): int {
    $stmt = db()->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM users");
    return (int)$stmt->fetchColumn();
}

function create_user(string $name, string $email, string $password, string $role = 'user'): array {
    $stmt = db()->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
    $stmt->execute([
        ':name' => trim($name),
        ':email' => strtolower(trim($email)),
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => $role,
    ]);
    return find_user_by_email($email) ?? [];
}

function booking_row_to_array(array $row): array {
    $stmt = db()->prepare("SELECT passenger_name FROM booking_passengers WHERE booking_id = :id ORDER BY id ASC");
    $stmt->execute([':id' => (int)$row['id']]);
    $passengers = array_column($stmt->fetchAll(), 'passenger_name');

    return [
        'id' => (int)$row['id'],
        'user_id' => (int)($row['user_id'] ?? 0),
        'user_name' => $row['user_name'],
        'user_email' => $row['user_email'],
        'route' => $row['route'],
        'flight_name' => $row['flight_name'],
        'flight_code' => $row['flight_code'],
        'flight_time' => $row['flight_time'],
        'date' => $row['flight_date'],
        'trip_type' => $row['trip_type'],
        'class' => $row['class_type'],
        'passengers' => (int)$row['passengers'],
        'passenger_names' => $passengers,
        'seat_number' => $row['seat_number'],
        'return_seat_number' => $row['return_seat_number'] ?? '',
        'insurance' => (float)$row['insurance_price'],
        'insurance_name' => $row['insurance_name'],
        'hand_bags' => (int)$row['hand_bags'],
        'checked_bags' => (int)$row['checked_bags'],
        'bag_fee' => (float)$row['bag_fee'],
        'payment' => $row['payment'],
        'status' => $row['status'],
        'total' => (float)$row['total'],
        'created_at' => $row['created_at'],
    ];
}

function get_all_bookings(): array {
    $stmt = db()->query("SELECT * FROM bookings ORDER BY created_at DESC, id DESC");
    return array_map('booking_row_to_array', $stmt->fetchAll());
}

function save_all_bookings(array $bookings): void {
    // Kept for compatibility with the original project. New bookings are inserted through add_booking().
    foreach ($bookings as $booking) {
        if (!empty($booking['id'])) {
            $stmt = db()->prepare("UPDATE bookings SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $booking['status'] ?? 'Confirmed', ':id' => (int)$booking['id']]);
        }
    }
}

function next_booking_id(): int {
    $stmt = db()->query("SELECT COALESCE(MAX(id), 1000) + 1 AS next_id FROM bookings");
    return (int)$stmt->fetchColumn();
}

function split_seats(string $seats): array {
    return array_values(array_filter(array_map('trim', explode(',', $seats)), fn($seat) => $seat !== ''));
}

function add_booking(array $booking): array {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $flightId = isset($booking['flight_id']) && (int)$booking['flight_id'] > 0 ? (int)$booking['flight_id'] : null;
        $insuranceId = isset($booking['insurance_id']) && (int)$booking['insurance_id'] > 0 ? (int)$booking['insurance_id'] : null;
        $flightDate = !empty($booking['date']) ? $booking['date'] : null;

        $stmt = $pdo->prepare("INSERT INTO bookings
            (user_id, user_name, user_email, flight_id, route, flight_name, flight_code, flight_time, flight_date,
             trip_type, class_type, passengers, seat_number, return_seat_number, insurance_id, insurance_name,
             insurance_price, hand_bags, checked_bags, bag_fee, payment, status, total)
            VALUES
            (:user_id, :user_name, :user_email, :flight_id, :route, :flight_name, :flight_code, :flight_time, :flight_date,
             :trip_type, :class_type, :passengers, :seat_number, :return_seat_number, :insurance_id, :insurance_name,
             :insurance_price, :hand_bags, :checked_bags, :bag_fee, :payment, :status, :total)");
        $stmt->execute([
            ':user_id' => (int)($booking['user_id'] ?? 0) ?: null,
            ':user_name' => $booking['user_name'] ?? 'Traveler',
            ':user_email' => $booking['user_email'] ?? '',
            ':flight_id' => $flightId,
            ':route' => $booking['route'] ?? '',
            ':flight_name' => $booking['flight_name'] ?? '',
            ':flight_code' => $booking['flight_code'] ?? '',
            ':flight_time' => $booking['flight_time'] ?? '',
            ':flight_date' => $flightDate,
            ':trip_type' => $booking['trip_type'] ?? 'oneway',
            ':class_type' => $booking['class'] ?? 'economy',
            ':passengers' => (int)($booking['passengers'] ?? 1),
            ':seat_number' => $booking['seat_number'] ?? '',
            ':return_seat_number' => $booking['return_seat_number'] ?? '',
            ':insurance_id' => $insuranceId,
            ':insurance_name' => $booking['insurance_name'] ?? 'None',
            ':insurance_price' => (float)($booking['insurance'] ?? 0),
            ':hand_bags' => (int)($booking['hand_bags'] ?? 0),
            ':checked_bags' => (int)($booking['checked_bags'] ?? 0),
            ':bag_fee' => (float)($booking['bag_fee'] ?? 0),
            ':payment' => $booking['payment'] ?? 'Credit Card',
            ':status' => $booking['status'] ?? 'Confirmed',
            ':total' => (float)($booking['total'] ?? 0),
        ]);
        $bookingId = (int)$pdo->lastInsertId();

        $passengerStmt = $pdo->prepare("INSERT INTO booking_passengers (booking_id, passenger_name) VALUES (:booking_id, :passenger_name)");
        foreach (($booking['passenger_names'] ?? []) as $name) {
            $name = trim((string)$name);
            if ($name !== '') {
                $passengerStmt->execute([':booking_id' => $bookingId, ':passenger_name' => $name]);
            }
        }

        $seatStmt = $pdo->prepare("INSERT INTO booking_seats (booking_id, flight_id, class_type, seat_number, leg_type)
            VALUES (:booking_id, :flight_id, :class_type, :seat_number, :leg_type)");
        foreach (split_seats($booking['seat_number'] ?? '') as $seat) {
            $seatStmt->execute([
                ':booking_id' => $bookingId,
                ':flight_id' => $flightId,
                ':class_type' => $booking['class'] ?? 'economy',
                ':seat_number' => $seat,
                ':leg_type' => 'outbound',
            ]);
        }
        foreach (split_seats($booking['return_seat_number'] ?? '') as $seat) {
            $seatStmt->execute([
                ':booking_id' => $bookingId,
                ':flight_id' => $flightId,
                ':class_type' => $booking['class'] ?? 'economy',
                ':seat_number' => $seat,
                ':leg_type' => 'return',
            ]);
        }

        if (!empty($booking['multi_segments']) && is_array($booking['multi_segments'])) {
            $segmentStmt = $pdo->prepare("INSERT INTO booking_segments (booking_id, segment_order, from_city, to_city, segment_date)
                VALUES (:booking_id, :segment_order, :from_city, :to_city, :segment_date)");
            foreach ($booking['multi_segments'] as $index => $segment) {
                $from = trim($segment['from'] ?? '');
                $to = trim($segment['to'] ?? '');
                if ($from !== '' || $to !== '') {
                    $segmentStmt->execute([
                        ':booking_id' => $bookingId,
                        ':segment_order' => $index + 1,
                        ':from_city' => $from,
                        ':to_city' => $to,
                        ':segment_date' => $segment['date'] ?: null,
                    ]);
                }
            }
        }

        $paymentStmt = $pdo->prepare("INSERT INTO payments (booking_id, payment_method, payment_status)
            VALUES (:booking_id, :payment_method, 'Paid')");
        $paymentStmt->execute([':booking_id' => $bookingId, ':payment_method' => $booking['payment'] ?? 'Credit Card']);

        $pdo->commit();
        $booking['id'] = $bookingId;
        $booking['created_at'] = date('Y-m-d H:i:s');
        return $booking;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function get_bookings_for_user(int $userId): array {
    $stmt = db()->prepare("SELECT * FROM bookings WHERE user_id = :user_id ORDER BY created_at DESC, id DESC");
    $stmt->execute([':user_id' => $userId]);
    return array_map('booking_row_to_array', $stmt->fetchAll());
}

function get_all_destinations(): array {
    $stmt = db()->query("SELECT id, city, country, airport_code FROM destinations ORDER BY city ASC");
    return $stmt->fetchAll();
}

function save_all_destinations(array $destinations): void {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $ids = array_values(array_filter(array_map(fn($d) => (int)($d['id'] ?? 0), $destinations)));
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM destinations WHERE id NOT IN ($placeholders)")->execute($ids);
        } else {
            $pdo->exec("DELETE FROM destinations");
        }

        $stmt = $pdo->prepare("INSERT INTO destinations (id, city, country, airport_code)
            VALUES (:id, :city, :country, :airport_code)
            ON DUPLICATE KEY UPDATE city = VALUES(city), country = VALUES(country), airport_code = VALUES(airport_code)");
        foreach ($destinations as $destination) {
            $stmt->execute([
                ':id' => (int)($destination['id'] ?? 0) ?: null,
                ':city' => trim($destination['city'] ?? ''),
                ':country' => trim($destination['country'] ?? ''),
                ':airport_code' => strtoupper(trim($destination['airport_code'] ?? '')),
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function next_destination_id(): int {
    $stmt = db()->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM destinations");
    return (int)$stmt->fetchColumn();
}

function get_all_insurance_options(): array {
    $stmt = db()->query("SELECT id, name, price, description FROM insurance_options ORDER BY id ASC");
    return $stmt->fetchAll();
}

function save_all_insurance_options(array $options): void {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $ids = array_values(array_filter(array_map(fn($o) => (int)($o['id'] ?? 0), $options)));
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM insurance_options WHERE id NOT IN ($placeholders)")->execute($ids);
            $pdo->prepare("DELETE FROM insurance WHERE insurance_id NOT IN ($placeholders)")->execute($ids);
        } else {
            $pdo->exec("DELETE FROM insurance_options");
            $pdo->exec("DELETE FROM insurance");
        }

        $stmt = $pdo->prepare("INSERT INTO insurance_options (id, name, price, description)
            VALUES (:id, :name, :price, :description)
            ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price), description = VALUES(description)");
        $compatStmt = $pdo->prepare("INSERT INTO insurance (insurance_id, insurance_type, insurance_price)
            VALUES (:id, :name, :price)
            ON DUPLICATE KEY UPDATE insurance_type = VALUES(insurance_type), insurance_price = VALUES(insurance_price)");
        foreach ($options as $option) {
            $data = [
                ':id' => (int)($option['id'] ?? 0) ?: null,
                ':name' => trim($option['name'] ?? ''),
                ':price' => (float)($option['price'] ?? 0),
                ':description' => trim($option['description'] ?? ''),
            ];
            $stmt->execute($data);
            $compatStmt->execute([':id' => $data[':id'], ':name' => $data[':name'], ':price' => $data[':price']]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function next_insurance_id(): int {
    $stmt = db()->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM insurance_options");
    return (int)$stmt->fetchColumn();
}
