<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/data-store.php';

if (!defined('DEFAULT_ECONOMY_SEATS')) { define('DEFAULT_ECONOMY_SEATS', 10); }
if (!defined('DEFAULT_BUSINESS_SEATS')) { define('DEFAULT_BUSINESS_SEATS', 3); }
if (!defined('DEFAULT_FIRST_SEATS')) { define('DEFAULT_FIRST_SEATS', 2); }
if (!defined('MAX_TOTAL_SEATS')) { define('MAX_TOTAL_SEATS', 250); }

function ensure_flight_class_columns_exist(): void {
    $columns = [
        'arrival_date' => "ALTER TABLE flights ADD COLUMN arrival_date DATE NULL AFTER flight_date",
        'seat_count' => "ALTER TABLE flights ADD COLUMN seat_count INT NOT NULL DEFAULT 15 AFTER first_price",
        'economy_seats' => "ALTER TABLE flights ADD COLUMN economy_seats INT NOT NULL DEFAULT 10 AFTER seat_count",
        'business_seats' => "ALTER TABLE flights ADD COLUMN business_seats INT NOT NULL DEFAULT 3 AFTER economy_seats",
        'first_seats' => "ALTER TABLE flights ADD COLUMN first_seats INT NOT NULL DEFAULT 2 AFTER business_seats",
    ];

    foreach ($columns as $column => $sql) {
        $stmt = db()->query("SHOW COLUMNS FROM flights LIKE " . db()->quote($column));
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            db()->exec($sql);
        }
    }

    db()->exec("UPDATE flights SET arrival_date = flight_date WHERE arrival_date IS NULL");
}

function default_flights(): array {
    return [
        [
            'id' => 1,
            'name' => 'Beirut–Paris',
            'code' => 'LA203',
            'from' => 'Beirut',
            'to' => 'Paris',
            'date' => '2026-04-10',
            'arrival_date' => '2026-04-10',
            'departure' => '08:00',
            'arrival' => '12:30',
            'stops' => 0,
            'economy_price' => 420,
            'business_price' => 690,
            'first_price' => 980,
            'status' => 'active',
            'economy_seats' => 10,
            'business_seats' => 3,
            'first_seats' => 2,
            'seat_count' => 15
        ],
        [
            'id' => 2,
            'name' => 'Beirut–London',
            'code' => 'LA118',
            'from' => 'Beirut',
            'to' => 'London',
            'date' => '2026-04-12',
            'arrival_date' => '2026-04-12',
            'departure' => '09:30',
            'arrival' => '14:30',
            'stops' => 1,
            'economy_price' => 350,
            'business_price' => 620,
            'first_price' => 910,
            'status' => 'active',
            'economy_seats' => 10,
            'business_seats' => 3,
            'first_seats' => 2,
            'seat_count' => 15
        ],
        [
            'id' => 3,
            'name' => 'Dubai–Tokyo',
            'code' => 'LA450',
            'from' => 'Dubai',
            'to' => 'Tokyo',
            'date' => '2026-04-15',
            'arrival_date' => '2026-04-16',
            'departure' => '22:00',
            'arrival' => '10:00',
            'stops' => 1,
            'economy_price' => 490,
            'business_price' => 830,
            'first_price' => 1180,
            'status' => 'active',
            'economy_seats' => 10,
            'business_seats' => 3,
            'first_seats' => 2,
            'seat_count' => 15
        ],
        [
            'id' => 4,
            'name' => 'London–Rome',
            'code' => 'LA319',
            'from' => 'London',
            'to' => 'Rome',
            'date' => '2026-04-18',
            'arrival_date' => '2026-04-18',
            'departure' => '11:15',
            'arrival' => '14:05',
            'stops' => 0,
            'economy_price' => 310,
            'business_price' => 520,
            'first_price' => 780,
            'status' => 'active',
            'economy_seats' => 10,
            'business_seats' => 3,
            'first_seats' => 2,
            'seat_count' => 15
        ],
        [
            'id' => 5,
            'name' => 'Beirut–Istanbul',
            'code' => 'LA271',
            'from' => 'Beirut',
            'to' => 'Istanbul',
            'date' => '2026-04-20',
            'arrival_date' => '2026-04-20',
            'departure' => '07:20',
            'arrival' => '09:15',
            'stops' => 0,
            'economy_price' => 260,
            'business_price' => 430,
            'first_price' => 660,
            'status' => 'active',
            'economy_seats' => 10,
            'business_seats' => 3,
            'first_seats' => 2,
            'seat_count' => 15
        ]
    ];
}

function normalize_flight(array $flight): array {
    $economy = isset($flight['economy_price']) ? (float)$flight['economy_price'] : (float)($flight['price'] ?? 0);
    $business = isset($flight['business_price']) ? (float)$flight['business_price'] : $economy + 250;
    $first = isset($flight['first_price']) ? (float)$flight['first_price'] : $economy + 500;

    $flight['id'] = (int)($flight['id'] ?? 0);
    $flight['economy_price'] = $economy;
    $flight['business_price'] = $business;
    $flight['first_price'] = $first;
    $flight['price'] = $economy;
    $flight['stops'] = (int)($flight['stops'] ?? 0);
    $flight['status'] = $flight['status'] ?? 'active';

    $flight['date'] = $flight['date'] ?? date('Y-m-d');
    $flight['arrival_date'] = $flight['arrival_date'] ?? $flight['date'];

    if (empty($flight['name']) && !empty($flight['from']) && !empty($flight['to'])) {
        $flight['name'] = $flight['from'] . '–' . $flight['to'];
    }

    if (!empty($flight['departure'])) {
        $flight['departure'] = substr((string)$flight['departure'], 0, 5);
    }

    if (!empty($flight['arrival'])) {
        $flight['arrival'] = substr((string)$flight['arrival'], 0, 5);
    }

    $economySeats = max(0, (int)($flight['economy_seats'] ?? DEFAULT_ECONOMY_SEATS));
    $businessSeats = max(0, (int)($flight['business_seats'] ?? DEFAULT_BUSINESS_SEATS));
    $firstSeats = max(0, (int)($flight['first_seats'] ?? DEFAULT_FIRST_SEATS));
    $totalSeats = $economySeats + $businessSeats + $firstSeats;

    if ($totalSeats <= 0) {
        $economySeats = DEFAULT_ECONOMY_SEATS;
        $businessSeats = DEFAULT_BUSINESS_SEATS;
        $firstSeats = DEFAULT_FIRST_SEATS;
        $totalSeats = $economySeats + $businessSeats + $firstSeats;
    }

    if ($totalSeats > MAX_TOTAL_SEATS) {
        $totalSeats = MAX_TOTAL_SEATS;

        $nonEconomy = $businessSeats + $firstSeats;
        if ($nonEconomy >= MAX_TOTAL_SEATS) {
            $firstSeats = min($firstSeats, MAX_TOTAL_SEATS);
            $businessSeats = min($businessSeats, MAX_TOTAL_SEATS - $firstSeats);
            $economySeats = 0;
        } else {
            $economySeats = MAX_TOTAL_SEATS - $nonEconomy;
        }
    }

    $flight['economy_seats'] = $economySeats;
    $flight['business_seats'] = $businessSeats;
    $flight['first_seats'] = $firstSeats;
    $flight['seat_count'] = $economySeats + $businessSeats + $firstSeats;

    return $flight;
}

function flight_row_to_array(array $row): array {
    return normalize_flight([
        'id' => $row['id'],
        'name' => $row['name'],
        'code' => $row['code'],
        'from' => $row['from_city'],
        'to' => $row['to_city'],
        'date' => $row['flight_date'],
        'arrival_date' => $row['arrival_date'] ?? $row['flight_date'],
        'departure' => $row['departure_time'],
        'arrival' => $row['arrival_time'],
        'stops' => $row['stops'],
        'economy_price' => $row['economy_price'],
        'business_price' => $row['business_price'],
        'first_price' => $row['first_price'],
        'seat_count' => $row['seat_count'] ?? 15,
        'economy_seats' => $row['economy_seats'] ?? DEFAULT_ECONOMY_SEATS,
        'business_seats' => $row['business_seats'] ?? DEFAULT_BUSINESS_SEATS,
        'first_seats' => $row['first_seats'] ?? DEFAULT_FIRST_SEATS,
        'status' => $row['status'],
    ]);
}

function normalize_flights(array $flights): array {
    return array_values(array_map('normalize_flight', $flights));
}

function get_all_flights(bool $includeCancelled = true): array {
    ensure_flight_class_columns_exist();

    if ($includeCancelled) {
        $stmt = db()->query("SELECT * FROM flights ORDER BY flight_date ASC, departure_time ASC, id ASC");
    } else {
        $stmt = db()->prepare("SELECT * FROM flights WHERE status <> 'cancelled' ORDER BY flight_date ASC, departure_time ASC, id ASC");
        $stmt->execute();
    }

    return array_map('flight_row_to_array', $stmt->fetchAll());
}

function save_all_flights(array $flights): void {
    ensure_flight_class_columns_exist();

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO flights
            (
                id,
                name,
                code,
                from_city,
                to_city,
                flight_date,
                arrival_date,
                departure_time,
                arrival_time,
                stops,
                economy_price,
                business_price,
                first_price,
                seat_count,
                economy_seats,
                business_seats,
                first_seats,
                status
            )
            VALUES
            (
                :id,
                :name,
                :code,
                :from_city,
                :to_city,
                :flight_date,
                :arrival_date,
                :departure_time,
                :arrival_time,
                :stops,
                :economy_price,
                :business_price,
                :first_price,
                :seat_count,
                :economy_seats,
                :business_seats,
                :first_seats,
                :status
            )
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                code = VALUES(code),
                from_city = VALUES(from_city),
                to_city = VALUES(to_city),
                flight_date = VALUES(flight_date),
                arrival_date = VALUES(arrival_date),
                departure_time = VALUES(departure_time),
                arrival_time = VALUES(arrival_time),
                stops = VALUES(stops),
                economy_price = VALUES(economy_price),
                business_price = VALUES(business_price),
                first_price = VALUES(first_price),
                seat_count = VALUES(seat_count),
                economy_seats = VALUES(economy_seats),
                business_seats = VALUES(business_seats),
                first_seats = VALUES(first_seats),
                status = VALUES(status)
        ");

        foreach (normalize_flights($flights) as $flight) {
            $stmt->execute([
                ':id' => (int)($flight['id'] ?? 0) ?: null,
                ':name' => trim($flight['name'] ?? ''),
                ':code' => strtoupper(trim($flight['code'] ?? '')),
                ':from_city' => trim($flight['from'] ?? ''),
                ':to_city' => trim($flight['to'] ?? ''),
                ':flight_date' => $flight['date'] ?? null,
                ':arrival_date' => $flight['arrival_date'] ?? ($flight['date'] ?? null),
                ':departure_time' => $flight['departure'] ?? null,
                ':arrival_time' => $flight['arrival'] ?? null,
                ':stops' => (int)($flight['stops'] ?? 0),
                ':economy_price' => (float)($flight['economy_price'] ?? 0),
                ':business_price' => (float)($flight['business_price'] ?? 0),
                ':first_price' => (float)($flight['first_price'] ?? 0),
                ':seat_count' => (int)($flight['seat_count'] ?? 15),
                ':economy_seats' => (int)($flight['economy_seats'] ?? DEFAULT_ECONOMY_SEATS),
                ':business_seats' => (int)($flight['business_seats'] ?? DEFAULT_BUSINESS_SEATS),
                ':first_seats' => (int)($flight['first_seats'] ?? DEFAULT_FIRST_SEATS),
                ':status' => $flight['status'] ?? 'active',
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function get_flight_by_id(int $id): ?array {
    ensure_flight_class_columns_exist();

    $stmt = db()->prepare("SELECT * FROM flights WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row ? flight_row_to_array($row) : null;
}

function next_flight_id(): int {
    $stmt = db()->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM flights");
    return (int)$stmt->fetchColumn();
}

function flight_duration_minutes(
    string $departure,
    string $arrival,
    ?string $departureDate = null,
    ?string $arrivalDate = null
): int {
    $departureDate = $departureDate ?: date('Y-m-d');
    $arrivalDate = $arrivalDate ?: $departureDate;

    try {
        $start = new DateTime($departureDate . ' ' . $departure);
        $end = new DateTime($arrivalDate . ' ' . $arrival);

        if ($end < $start) {
            $end->modify('+1 day');
        }

        return max(0, (int)(($end->getTimestamp() - $start->getTimestamp()) / 60));
    } catch (Throwable $e) {
        return 0;
    }
}

function flight_duration(
    string $departure,
    string $arrival,
    ?string $departureDate = null,
    ?string $arrivalDate = null
): string {
    $diff = flight_duration_minutes($departure, $arrival, $departureDate, $arrivalDate);

    $days = intdiv($diff, 1440);
    $remainingMinutes = $diff % 1440;
    $hours = intdiv($remainingMinutes, 60);
    $minutes = $remainingMinutes % 60;

    if ($days > 0) {
        return $days . 'd ' . $hours . 'h ' . $minutes . 'm';
    }

    return $hours . 'h ' . $minutes . 'm';
}

function flight_price_for_class(array $flight, string $classType): float {
    $flight = normalize_flight($flight);

    if ($classType === 'business') {
        return (float)$flight['business_price'];
    }

    if ($classType === 'first') {
        return (float)$flight['first_price'];
    }

    return (float)$flight['economy_price'];
}