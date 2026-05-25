<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/data-store.php';

function default_flights(): array {
    return [
        ['id' => 1, 'name' => 'Beirut–Paris', 'code' => 'LA203', 'from' => 'Beirut', 'to' => 'Paris', 'date' => '2026-04-10', 'departure' => '08:00', 'arrival' => '12:30', 'stops' => 0, 'economy_price' => 420, 'business_price' => 690, 'first_price' => 980, 'status' => 'active'],
        ['id' => 2, 'name' => 'Beirut–London', 'code' => 'LA118', 'from' => 'Beirut', 'to' => 'London', 'date' => '2026-04-12', 'departure' => '09:30', 'arrival' => '14:30', 'stops' => 1, 'economy_price' => 350, 'business_price' => 620, 'first_price' => 910, 'status' => 'active'],
        ['id' => 3, 'name' => 'Dubai–Tokyo', 'code' => 'LA450', 'from' => 'Dubai', 'to' => 'Tokyo', 'date' => '2026-04-15', 'departure' => '22:00', 'arrival' => '10:00', 'stops' => 1, 'economy_price' => 490, 'business_price' => 830, 'first_price' => 1180, 'status' => 'active'],
        ['id' => 4, 'name' => 'London–Rome', 'code' => 'LA319', 'from' => 'London', 'to' => 'Rome', 'date' => '2026-04-18', 'departure' => '11:15', 'arrival' => '14:05', 'stops' => 0, 'economy_price' => 310, 'business_price' => 520, 'first_price' => 780, 'status' => 'active'],
        ['id' => 5, 'name' => 'Beirut–Istanbul', 'code' => 'LA271', 'from' => 'Beirut', 'to' => 'Istanbul', 'date' => '2026-04-20', 'departure' => '07:20', 'arrival' => '09:15', 'stops' => 0, 'economy_price' => 260, 'business_price' => 430, 'first_price' => 660, 'status' => 'active']
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

    if (empty($flight['name']) && !empty($flight['from']) && !empty($flight['to'])) {
        $flight['name'] = $flight['from'] . '–' . $flight['to'];
    }

    if (!empty($flight['departure'])) {
        $flight['departure'] = substr((string)$flight['departure'], 0, 5);
    }
    if (!empty($flight['arrival'])) {
        $flight['arrival'] = substr((string)$flight['arrival'], 0, 5);
    }

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
        'departure' => $row['departure_time'],
        'arrival' => $row['arrival_time'],
        'stops' => $row['stops'],
        'economy_price' => $row['economy_price'],
        'business_price' => $row['business_price'],
        'first_price' => $row['first_price'],
        'status' => $row['status'],
    ]);
}

function normalize_flights(array $flights): array {
    return array_values(array_map('normalize_flight', $flights));
}

function get_all_flights(bool $includeCancelled = true): array {
    if ($includeCancelled) {
        $stmt = db()->query("SELECT * FROM flights ORDER BY flight_date ASC, departure_time ASC, id ASC");
    } else {
        $stmt = db()->prepare("SELECT * FROM flights WHERE status <> 'cancelled' ORDER BY flight_date ASC, departure_time ASC, id ASC");
        $stmt->execute();
    }
    return array_map('flight_row_to_array', $stmt->fetchAll());
}

function save_all_flights(array $flights): void {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO flights
            (id, name, code, from_city, to_city, flight_date, departure_time, arrival_time, stops, economy_price, business_price, first_price, status)
            VALUES
            (:id, :name, :code, :from_city, :to_city, :flight_date, :departure_time, :arrival_time, :stops, :economy_price, :business_price, :first_price, :status)
            ON DUPLICATE KEY UPDATE
            name = VALUES(name), code = VALUES(code), from_city = VALUES(from_city), to_city = VALUES(to_city),
            flight_date = VALUES(flight_date), departure_time = VALUES(departure_time), arrival_time = VALUES(arrival_time),
            stops = VALUES(stops), economy_price = VALUES(economy_price), business_price = VALUES(business_price),
            first_price = VALUES(first_price), status = VALUES(status)");
        foreach (normalize_flights($flights) as $flight) {
            $stmt->execute([
                ':id' => (int)($flight['id'] ?? 0) ?: null,
                ':name' => trim($flight['name'] ?? ''),
                ':code' => strtoupper(trim($flight['code'] ?? '')),
                ':from_city' => trim($flight['from'] ?? ''),
                ':to_city' => trim($flight['to'] ?? ''),
                ':flight_date' => $flight['date'] ?? null,
                ':departure_time' => $flight['departure'] ?? null,
                ':arrival_time' => $flight['arrival'] ?? null,
                ':stops' => (int)($flight['stops'] ?? 0),
                ':economy_price' => (float)($flight['economy_price'] ?? 0),
                ':business_price' => (float)($flight['business_price'] ?? 0),
                ':first_price' => (float)($flight['first_price'] ?? 0),
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
    $stmt = db()->prepare("SELECT * FROM flights WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ? flight_row_to_array($row) : null;
}

function next_flight_id(): int {
    $stmt = db()->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM flights");
    return (int)$stmt->fetchColumn();
}

function flight_duration_minutes(string $departure, string $arrival): int {
    [$depH, $depM] = array_map('intval', explode(':', $departure));
    [$arrH, $arrM] = array_map('intval', explode(':', $arrival));
    $depMinutes = $depH * 60 + $depM;
    $arrMinutes = $arrH * 60 + $arrM;
    if ($arrMinutes < $depMinutes) {
        $arrMinutes += 24 * 60;
    }
    return $arrMinutes - $depMinutes;
}

function flight_duration(string $departure, string $arrival): string {
    $diff = flight_duration_minutes($departure, $arrival);
    return floor($diff / 60) . 'h ' . ($diff % 60) . 'm';
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
