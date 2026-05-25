<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/db.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = getPDOConnection();
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

/*
|--------------------------------------------------------------------------
| Small database helpers
|--------------------------------------------------------------------------
| These helpers make the project safer when the SQL file and PHP file are not
| perfectly matched. They check which columns actually exist before using them.
*/

function safe_identifier(string $name): string {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new InvalidArgumentException("Invalid SQL identifier: " . $name);
    }
    return "`" . $name . "`";
}

function table_columns(string $table): array {
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    try {
        $stmt = db()->query("SHOW COLUMNS FROM " . safe_identifier($table));
        $cache[$table] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    } catch (Throwable $e) {
        $cache[$table] = [];
    }

    return $cache[$table];
}

function table_exists(string $table): bool {
    return count(table_columns($table)) > 0;
}

function column_exists(string $table, string $column): bool {
    return in_array($column, table_columns($table), true);
}

function first_existing_column(string $table, array $possibleColumns): ?string {
    foreach ($possibleColumns as $column) {
        if (column_exists($table, $column)) {
            return $column;
        }
    }
    return null;
}

function insert_dynamic(string $table, array $data): int {
    $columns = table_columns($table);
    $insertData = [];

    foreach ($data as $column => $value) {
        if (in_array($column, $columns, true)) {
            $insertData[$column] = $value;
        }
    }

    if (empty($insertData)) {
        throw new RuntimeException("No matching columns found for table: " . $table);
    }

    $columnSql = implode(', ', array_map('safe_identifier', array_keys($insertData)));
    $placeholders = implode(', ', array_map(fn($c) => ':' . $c, array_keys($insertData)));

    $stmt = db()->prepare("INSERT INTO " . safe_identifier($table) . " ($columnSql) VALUES ($placeholders)");

    $params = [];
    foreach ($insertData as $column => $value) {
        $params[':' . $column] = $value;
    }

    $stmt->execute($params);
    return (int)db()->lastInsertId();
}

function update_dynamic(string $table, array $data, string $whereColumn, mixed $whereValue): void {
    $columns = table_columns($table);
    $updateData = [];

    foreach ($data as $column => $value) {
        if (in_array($column, $columns, true)) {
            $updateData[$column] = $value;
        }
    }

    if (empty($updateData) || !in_array($whereColumn, $columns, true)) {
        return;
    }

    $setSql = implode(', ', array_map(fn($c) => safe_identifier($c) . " = :" . $c, array_keys($updateData)));
    $stmt = db()->prepare("UPDATE " . safe_identifier($table) . " SET $setSql WHERE " . safe_identifier($whereColumn) . " = :where_value");

    $params = [':where_value' => $whereValue];
    foreach ($updateData as $column => $value) {
        $params[':' . $column] = $value;
    }

    $stmt->execute($params);
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

/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
*/

function get_all_users(): array {
    $stmt = db()->query("SELECT * FROM users ORDER BY id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function save_all_users(array $users): void {
    $pdo = db();
    $pdo->beginTransaction();

    try {
        foreach ($users as $user) {
            if (!empty($user['id']) && column_exists('users', 'id')) {
                update_dynamic('users', [
                    'name' => trim($user['name'] ?? ''),
                    'email' => strtolower(trim($user['email'] ?? '')),
                    'password' => $user['password'] ?? '',
                    'role' => $user['role'] ?? 'user',
                    'created_at' => isset($user['created_at']) ? date('Y-m-d H:i:s', strtotime($user['created_at'])) : date('Y-m-d H:i:s'),
                ], 'id', (int)$user['id']);
            } else {
                insert_dynamic('users', [
                    'name' => trim($user['name'] ?? ''),
                    'email' => strtolower(trim($user['email'] ?? '')),
                    'password' => $user['password'] ?? '',
                    'role' => $user['role'] ?? 'user',
                    'created_at' => isset($user['created_at']) ? date('Y-m-d H:i:s', strtotime($user['created_at'])) : date('Y-m-d H:i:s'),
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function find_user_by_email(string $email): ?array {
    $stmt = db()->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
    $stmt->execute([':email' => trim($email)]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function find_user_by_id(int $id): ?array {
    if ($id <= 0 || !table_exists('users')) {
        return null;
    }

    $stmt = db()->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function next_user_id(): int {
    $stmt = db()->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM users");
    return (int)$stmt->fetchColumn();
}

function create_user(string $name, string $email, string $password, string $role = 'user'): array {
    insert_dynamic('users', [
        'name' => trim($name),
        'email' => strtolower(trim($email)),
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    return find_user_by_email($email) ?? [];
}

/*
|--------------------------------------------------------------------------
| Bookings
|--------------------------------------------------------------------------
*/

function split_seats(string $seats): array {
    return array_values(array_filter(array_map('trim', explode(',', $seats)), fn($seat) => $seat !== ''));
}

function get_passenger_names_for_booking(int $bookingId): array {
    if (!table_exists('booking_passengers')) {
        return [];
    }

    $nameColumn = first_existing_column('booking_passengers', ['passenger_name', 'full_name', 'name']);
    if ($nameColumn === null) {
        return [];
    }

    $stmt = db()->prepare(
        "SELECT " . safe_identifier($nameColumn) . " AS passenger_name
         FROM booking_passengers
         WHERE booking_id = :id
         ORDER BY id ASC"
    );
    $stmt->execute([':id' => $bookingId]);

    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'passenger_name');
}

function get_seats_for_booking(int $bookingId, string $legType = 'outbound'): string {
    if (!table_exists('booking_seats') || !column_exists('booking_seats', 'seat_number')) {
        return '';
    }

    if (column_exists('booking_seats', 'leg_type')) {
        $stmt = db()->prepare(
            "SELECT seat_number FROM booking_seats
             WHERE booking_id = :id AND leg_type = :leg_type
             ORDER BY id ASC"
        );
        $stmt->execute([':id' => $bookingId, ':leg_type' => $legType]);
    } else {
        $stmt = db()->prepare(
            "SELECT seat_number FROM booking_seats
             WHERE booking_id = :id
             ORDER BY id ASC"
        );
        $stmt->execute([':id' => $bookingId]);
    }

    return implode(', ', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'seat_number'));
}


function normalize_seat_code(string $seat): string {
    return strtoupper(trim($seat));
}

function booked_seat_key(int|string|null $flightId, string $classType, string $legType): string {
    return (int)$flightId . '|' . strtolower(trim($classType)) . '|' . strtolower(trim($legType));
}

function get_booked_seats_map(): array {
    $map = [];

    // Preferred source: one row per booked seat.
    if (table_exists('booking_seats') && column_exists('booking_seats', 'seat_number')) {
        $joinBookings = table_exists('bookings') && column_exists('booking_seats', 'booking_id') && column_exists('bookings', 'id');
        $hasFlightId = column_exists('booking_seats', 'flight_id');
        $hasClassType = column_exists('booking_seats', 'class_type');
        $hasLegType = column_exists('booking_seats', 'leg_type');

        $sql = "SELECT "
             . ($hasFlightId ? "bs.flight_id" : "NULL AS flight_id") . ", "
             . ($hasClassType ? "bs.class_type" : "'economy' AS class_type") . ", "
             . "bs.seat_number, "
             . ($hasLegType ? "bs.leg_type" : "'outbound' AS leg_type")
             . " FROM booking_seats bs";

        if ($joinBookings) {
            $sql .= " LEFT JOIN bookings b ON b.id = bs.booking_id";
            if (column_exists('bookings', 'status')) {
                $sql .= " WHERE COALESCE(b.status, 'Confirmed') NOT IN ('Cancelled', 'Rejected')";
            }
        }

        $stmt = db()->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $seat = normalize_seat_code((string)($row['seat_number'] ?? ''));
            if ($seat === '') {
                continue;
            }

            $key = booked_seat_key($row['flight_id'] ?? 0, $row['class_type'] ?? 'economy', $row['leg_type'] ?? 'outbound');
            $map[$key][] = $seat;
        }
    }

    // Fallback source: old projects stored seats directly in bookings.
    if (table_exists('bookings') && column_exists('bookings', 'seat_number')) {
        $columns = table_columns('bookings');
        $flightExpr = in_array('flight_id', $columns, true) ? 'flight_id' : 'NULL AS flight_id';
        $classExpr = in_array('class_type', $columns, true) ? 'class_type' : (in_array('class', $columns, true) ? '`class` AS class_type' : "'economy' AS class_type");
        $returnExpr = in_array('return_seat_number', $columns, true) ? 'return_seat_number' : "'' AS return_seat_number";
        $where = in_array('status', $columns, true) ? " WHERE COALESCE(status, 'Confirmed') NOT IN ('Cancelled', 'Rejected')" : '';

        $stmt = db()->query("SELECT id, $flightExpr, $classExpr, seat_number, $returnExpr FROM bookings$where");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $flightId = $row['flight_id'] ?? 0;
            $classType = $row['class_type'] ?? 'economy';

            foreach (split_seats((string)($row['seat_number'] ?? '')) as $seat) {
                $seat = normalize_seat_code($seat);
                if ($seat !== '') {
                    $map[booked_seat_key($flightId, $classType, 'outbound')][] = $seat;
                }
            }

            foreach (split_seats((string)($row['return_seat_number'] ?? '')) as $seat) {
                $seat = normalize_seat_code($seat);
                if ($seat !== '') {
                    $map[booked_seat_key($flightId, $classType, 'return')][] = $seat;
                }
            }
        }
    }

    foreach ($map as $key => $seats) {
        $map[$key] = array_values(array_unique($seats));
        sort($map[$key]);
    }

    return $map;
}

function get_booked_seats_for_flight(?int $flightId, string $classType, string $legType = 'outbound'): array {
    if (!$flightId) {
        return [];
    }

    $map = get_booked_seats_map();
    $key = booked_seat_key($flightId, $classType, $legType);
    return $map[$key] ?? [];
}

function find_taken_seats(?int $flightId, string $classType, string $legType, string $selectedSeats): array {
    $chosen = array_map('normalize_seat_code', split_seats($selectedSeats));
    if (!$flightId || empty($chosen)) {
        return [];
    }

    $taken = get_booked_seats_for_flight($flightId, $classType, $legType);
    return array_values(array_intersect($chosen, $taken));
}

function get_payment_for_booking(int $bookingId, array $row = []): string {
    if (!empty($row['payment'])) {
        return (string)$row['payment'];
    }

    if (!table_exists('payments')) {
        return '';
    }

    $paymentColumn = first_existing_column('payments', ['payment_method', 'method', 'payment']);
    if ($paymentColumn === null || !column_exists('payments', 'booking_id')) {
        return '';
    }

    $stmt = db()->prepare(
        "SELECT " . safe_identifier($paymentColumn) . " AS payment
         FROM payments
         WHERE booking_id = :id
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmt->execute([':id' => $bookingId]);
    return (string)($stmt->fetchColumn() ?: '');
}

function booking_row_to_array(array $row): array {
    $bookingId = (int)($row['id'] ?? 0);
    $userId = (int)($row['user_id'] ?? 0);
    $user = find_user_by_id($userId) ?? [];

    $passengers = get_passenger_names_for_booking($bookingId);

    $outboundSeats = $row['seat_number'] ?? '';
    if ($outboundSeats === '') {
        $outboundSeats = get_seats_for_booking($bookingId, 'outbound');
    }

    $returnSeats = $row['return_seat_number'] ?? '';
    if ($returnSeats === '') {
        $returnSeats = get_seats_for_booking($bookingId, 'return');
    }

    return [
        'id' => $bookingId,
        'user_id' => $userId,
        'user_name' => $row['user_name'] ?? ($user['name'] ?? ''),
        'user_email' => $row['user_email'] ?? ($user['email'] ?? ''),
        'route' => $row['route'] ?? '',
        'flight_name' => $row['flight_name'] ?? '',
        'flight_code' => $row['flight_code'] ?? '',
        'flight_time' => $row['flight_time'] ?? '',
        'date' => $row['flight_date'] ?? ($row['date'] ?? ''),
        'trip_type' => $row['trip_type'] ?? 'oneway',
        'class' => $row['class_type'] ?? ($row['class'] ?? 'economy'),
        'passengers' => (int)($row['passengers'] ?? max(1, count($passengers))),
        'passenger_names' => $passengers,
        'seat_number' => $outboundSeats,
        'return_seat_number' => $returnSeats,
        'insurance' => (float)($row['insurance_price'] ?? $row['insurance'] ?? 0),
        'insurance_name' => $row['insurance_name'] ?? 'None',
        'hand_bags' => (int)($row['hand_bags'] ?? 0),
        'checked_bags' => (int)($row['checked_bags'] ?? 0),
        'bag_fee' => (float)($row['bag_fee'] ?? 0),
        'payment' => get_payment_for_booking($bookingId, $row),
        'status' => $row['status'] ?? 'Pending',
        'total' => (float)($row['total'] ?? 0),
        'created_at' => $row['created_at'] ?? '',
    ];
}

function get_all_bookings(): array {
    $stmt = db()->query("SELECT * FROM bookings ORDER BY created_at DESC, id DESC");
    return array_map('booking_row_to_array', $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function release_booking_seats(int $bookingId): void {
    if ($bookingId <= 0) {
        return;
    }

    if (table_exists('booking_seats') && column_exists('booking_seats', 'booking_id')) {
        $stmt = db()->prepare("DELETE FROM booking_seats WHERE booking_id = :booking_id");
        $stmt->execute([':booking_id' => $bookingId]);
    }
}

function save_all_bookings(array $bookings): void {
    foreach ($bookings as $booking) {
        if (!empty($booking['id'])) {
            $status = $booking['status'] ?? 'Pending';

            update_dynamic('bookings', [
                'status' => $status,
            ], 'id', (int)$booking['id']);

            if (in_array($status, ['Rejected', 'Cancelled'], true)) {
                release_booking_seats((int)$booking['id']);
            }
        }
    }
}

function next_booking_id(): int {
    $stmt = db()->query("SELECT COALESCE(MAX(id), 1000) + 1 AS next_id FROM bookings");
    return (int)$stmt->fetchColumn();
}

function add_booking(array $booking): array {
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $flightId = isset($booking['flight_id']) && (int)$booking['flight_id'] > 0 ? (int)$booking['flight_id'] : null;
        $insuranceId = isset($booking['insurance_id']) && (int)$booking['insurance_id'] > 0 ? (int)$booking['insurance_id'] : null;
        $flightDate = !empty($booking['date']) ? $booking['date'] : null;
        $classType = $booking['class'] ?? 'economy';

        $takenOutboundSeats = find_taken_seats($flightId, $classType, 'outbound', (string)($booking['seat_number'] ?? ''));
        if (!empty($takenOutboundSeats)) {
            throw new RuntimeException('Seat(s) already taken for this flight: ' . implode(', ', $takenOutboundSeats));
        }

        $takenReturnSeats = find_taken_seats($flightId, $classType, 'return', (string)($booking['return_seat_number'] ?? ''));
        if (!empty($takenReturnSeats)) {
            throw new RuntimeException('Return seat(s) already taken for this flight: ' . implode(', ', $takenReturnSeats));
        }

        $bookingId = insert_dynamic('bookings', [
            'user_id' => (int)($booking['user_id'] ?? 0) ?: null,
            'user_name' => $booking['user_name'] ?? 'Traveler',
            'user_email' => $booking['user_email'] ?? '',
            'flight_id' => $flightId,
            'route' => $booking['route'] ?? '',
            'flight_name' => $booking['flight_name'] ?? '',
            'flight_code' => $booking['flight_code'] ?? '',
            'flight_time' => $booking['flight_time'] ?? '',
            'flight_date' => $flightDate,
            'date' => $flightDate,
            'trip_type' => $booking['trip_type'] ?? 'oneway',
            'class_type' => $booking['class'] ?? 'economy',
            'class' => $booking['class'] ?? 'economy',
            'passengers' => (int)($booking['passengers'] ?? 1),
            'seat_number' => $booking['seat_number'] ?? '',
            'return_seat_number' => $booking['return_seat_number'] ?? '',
            'insurance_id' => $insuranceId,
            'insurance_name' => $booking['insurance_name'] ?? 'None',
            'insurance_price' => (float)($booking['insurance'] ?? 0),
            'insurance' => (float)($booking['insurance'] ?? 0),
            'hand_bags' => (int)($booking['hand_bags'] ?? 0),
            'checked_bags' => (int)($booking['checked_bags'] ?? 0),
            'bag_fee' => (float)($booking['bag_fee'] ?? 0),
            'payment' => $booking['payment'] ?? 'Credit Card',
            'status' => 'Pending',
            'total' => (float)($booking['total'] ?? 0),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($bookingId <= 0 && !empty($booking['id'])) {
            $bookingId = (int)$booking['id'];
        }

        if ($bookingId <= 0) {
            $bookingId = next_booking_id() - 1;
        }

        // Save passenger names. Supports both database versions:
        // old column: full_name / new column: passenger_name.
        if (table_exists('booking_passengers') && column_exists('booking_passengers', 'booking_id')) {
            $passengerNameColumn = first_existing_column('booking_passengers', ['passenger_name', 'full_name', 'name']);

            if ($passengerNameColumn !== null) {
                $seatList = split_seats($booking['seat_number'] ?? '');
                foreach (($booking['passenger_names'] ?? []) as $index => $name) {
                    $name = trim((string)$name);
                    if ($name !== '') {
                        insert_dynamic('booking_passengers', [
                            'booking_id' => $bookingId,
                            $passengerNameColumn => $name,
                            'seat_number' => $seatList[$index] ?? null,
                        ]);
                    }
                }
            }
        }

        // Save booked seats if this table exists.
        if (table_exists('booking_seats') && column_exists('booking_seats', 'booking_id')) {
            foreach (split_seats($booking['seat_number'] ?? '') as $seat) {
                insert_dynamic('booking_seats', [
                    'booking_id' => $bookingId,
                    'flight_id' => $flightId,
                    'class_type' => $booking['class'] ?? 'economy',
                    'seat_number' => $seat,
                    'leg_type' => 'outbound',
                ]);
            }

            foreach (split_seats($booking['return_seat_number'] ?? '') as $seat) {
                insert_dynamic('booking_seats', [
                    'booking_id' => $bookingId,
                    'flight_id' => $flightId,
                    'class_type' => $booking['class'] ?? 'economy',
                    'seat_number' => $seat,
                    'leg_type' => 'return',
                ]);
            }
        }

        // Save multi-city segments if this table exists.
        if (!empty($booking['multi_segments']) && is_array($booking['multi_segments']) && table_exists('booking_segments')) {
            foreach ($booking['multi_segments'] as $index => $segment) {
                $from = trim($segment['from'] ?? '');
                $to = trim($segment['to'] ?? '');

                if ($from !== '' || $to !== '') {
                    insert_dynamic('booking_segments', [
                        'booking_id' => $bookingId,
                        'segment_order' => $index + 1,
                        'from_city' => $from,
                        'to_city' => $to,
                        'segment_date' => !empty($segment['date']) ? $segment['date'] : null,
                    ]);
                }
            }
        }

        // Save payment if this table exists.
        if (table_exists('payments') && column_exists('payments', 'booking_id')) {
            insert_dynamic('payments', [
                'booking_id' => $bookingId,
                'payment_method' => $booking['payment'] ?? 'Credit Card',
                'method' => $booking['payment'] ?? 'Credit Card',
                'payment_status' => 'Pending',
                'status' => 'Pending',
                'amount' => (float)($booking['total'] ?? 0),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $pdo->commit();

        $booking['id'] = $bookingId;
        $booking['created_at'] = date('Y-m-d H:i:s');
        return $booking;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}


function set_booking_status(int $bookingId, string $status): void {
    $allowedStatuses = ['Pending', 'Confirmed', 'Rejected', 'Cancelled'];

    if ($bookingId <= 0) {
        throw new InvalidArgumentException('Invalid booking ID.');
    }

    if (!in_array($status, $allowedStatuses, true)) {
        throw new InvalidArgumentException('Invalid booking status.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        update_dynamic('bookings', [
            'status' => $status,
        ], 'id', $bookingId);

        if (table_exists('payments') && column_exists('payments', 'booking_id')) {
            $paymentStatus = match ($status) {
                'Confirmed' => 'Paid',
                'Rejected' => 'Rejected',
                'Cancelled' => 'Cancelled',
                default => 'Pending',
            };

            update_dynamic('payments', [
                'payment_status' => $paymentStatus,
                'status' => $paymentStatus,
            ], 'booking_id', $bookingId);
        }

        if (in_array($status, ['Rejected', 'Cancelled'], true)) {
            release_booking_seats($bookingId);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function get_bookings_for_user(int $userId): array {
    $stmt = db()->prepare("SELECT * FROM bookings WHERE user_id = :user_id ORDER BY created_at DESC, id DESC");
    $stmt->execute([':user_id' => $userId]);
    return array_map('booking_row_to_array', $stmt->fetchAll(PDO::FETCH_ASSOC));
}

/*
|--------------------------------------------------------------------------
| Destinations
|--------------------------------------------------------------------------
*/

function get_all_destinations(): array {
    $stmt = db()->query("SELECT * FROM destinations ORDER BY city ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function save_all_destinations(array $destinations): void {
    $pdo = db();
    $pdo->beginTransaction();

    try {
        foreach ($destinations as $destination) {
            if (!empty($destination['id'])) {
                update_dynamic('destinations', [
                    'city' => trim($destination['city'] ?? ''),
                    'country' => trim($destination['country'] ?? ''),
                    'airport_code' => strtoupper(trim($destination['airport_code'] ?? '')),
                ], 'id', (int)$destination['id']);
            } else {
                insert_dynamic('destinations', [
                    'city' => trim($destination['city'] ?? ''),
                    'country' => trim($destination['country'] ?? ''),
                    'airport_code' => strtoupper(trim($destination['airport_code'] ?? '')),
                ]);
            }
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

/*
|--------------------------------------------------------------------------
| Insurance
|--------------------------------------------------------------------------
*/

function get_all_insurance_options(): array {
    if (table_exists('insurance_options')) {
        $stmt = db()->query("SELECT * FROM insurance_options ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (table_exists('insurance')) {
        $typeColumn = first_existing_column('insurance', ['insurance_type', 'name']);
        $priceColumn = first_existing_column('insurance', ['insurance_price', 'price']);
        $idColumn = first_existing_column('insurance', ['insurance_id', 'id']);

        if ($typeColumn && $priceColumn && $idColumn) {
            $stmt = db()->query(
                "SELECT " . safe_identifier($idColumn) . " AS id,
                        " . safe_identifier($typeColumn) . " AS name,
                        " . safe_identifier($priceColumn) . " AS price,
                        '' AS description
                 FROM insurance
                 ORDER BY " . safe_identifier($idColumn) . " ASC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    return [];
}

function save_all_insurance_options(array $options): void {
    $pdo = db();
    $pdo->beginTransaction();

    try {
        foreach ($options as $option) {
            $id = (int)($option['id'] ?? 0) ?: null;

            if (table_exists('insurance_options')) {
                if ($id) {
                    update_dynamic('insurance_options', [
                        'name' => trim($option['name'] ?? ''),
                        'price' => (float)($option['price'] ?? 0),
                        'description' => trim($option['description'] ?? ''),
                    ], 'id', $id);
                } else {
                    insert_dynamic('insurance_options', [
                        'name' => trim($option['name'] ?? ''),
                        'price' => (float)($option['price'] ?? 0),
                        'description' => trim($option['description'] ?? ''),
                    ]);
                }
            }

            if (table_exists('insurance')) {
                if ($id && column_exists('insurance', 'insurance_id')) {
                    update_dynamic('insurance', [
                        'insurance_type' => trim($option['name'] ?? ''),
                        'insurance_price' => (float)($option['price'] ?? 0),
                        'name' => trim($option['name'] ?? ''),
                        'price' => (float)($option['price'] ?? 0),
                    ], 'insurance_id', $id);
                } else {
                    insert_dynamic('insurance', [
                        'insurance_id' => $id,
                        'insurance_type' => trim($option['name'] ?? ''),
                        'insurance_price' => (float)($option['price'] ?? 0),
                        'name' => trim($option['name'] ?? ''),
                        'price' => (float)($option['price'] ?? 0),
                    ]);
                }
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function next_insurance_id(): int {
    if (table_exists('insurance_options') && column_exists('insurance_options', 'id')) {
        $stmt = db()->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM insurance_options");
        return (int)$stmt->fetchColumn();
    }

    if (table_exists('insurance') && column_exists('insurance', 'insurance_id')) {
        $stmt = db()->query("SELECT COALESCE(MAX(insurance_id), 0) + 1 AS next_id FROM insurance");
        return (int)$stmt->fetchColumn();
    }

    return 1;
}
