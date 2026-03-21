<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/data-store.php';

function default_flights(): array {
    return [
        [
            'id' => 1,
            'name' => 'Beirut–Paris',
            'code' => 'LA203',
            'from' => 'Beirut',
            'to' => 'Paris',
            'date' => '2026-04-10',
            'departure' => '08:00',
            'arrival' => '12:30',
            'price' => 420,
            'status' => 'active'
        ],
        [
            'id' => 2,
            'name' => 'Beirut–London',
            'code' => 'LA118',
            'from' => 'Beirut',
            'to' => 'London',
            'date' => '2026-04-12',
            'departure' => '09:30',
            'arrival' => '14:30',
            'price' => 350,
            'status' => 'active'
        ],
        [
            'id' => 3,
            'name' => 'Dubai–Tokyo',
            'code' => 'LA450',
            'from' => 'Dubai',
            'to' => 'Tokyo',
            'date' => '2026-04-15',
            'departure' => '22:00',
            'arrival' => '10:00',
            'price' => 490,
            'status' => 'active'
        ],
        [
            'id' => 4,
            'name' => 'London–Rome',
            'code' => 'LA319',
            'from' => 'London',
            'to' => 'Rome',
            'date' => '2026-04-18',
            'departure' => '11:15',
            'arrival' => '14:05',
            'price' => 310,
            'status' => 'active'
        ],
        [
            'id' => 5,
            'name' => 'Beirut–Istanbul',
            'code' => 'LA271',
            'from' => 'Beirut',
            'to' => 'Istanbul',
            'date' => '2026-04-20',
            'departure' => '07:20',
            'arrival' => '09:15',
            'price' => 260,
            'status' => 'active'
        ]
    ];
}

function ensure_flights_loaded(): void {
    $flights = read_json_data('flights', []);
    if (!$flights) {
        write_json_data('flights', default_flights());
    }
}

function get_all_flights(bool $includeCancelled = true): array {
    ensure_flights_loaded();
    $flights = read_json_data('flights', default_flights());
    if ($includeCancelled) {
        return $flights;
    }
    return array_values(array_filter($flights, fn($flight) => ($flight['status'] ?? 'active') !== 'cancelled'));
}

function save_all_flights(array $flights): void {
    write_json_data('flights', $flights);
}

function get_flight_by_id(int $id): ?array {
    foreach (get_all_flights() as $flight) {
        if ((int)$flight['id'] === $id) {
            return $flight;
        }
    }
    return null;
}

function next_flight_id(): int {
    $ids = array_map(fn($flight) => (int)$flight['id'], get_all_flights());
    return $ids ? max($ids) + 1 : 1;
}

function flight_duration(string $departure, string $arrival): string {
    [$depH, $depM] = array_map('intval', explode(':', $departure));
    [$arrH, $arrM] = array_map('intval', explode(':', $arrival));
    $depMinutes = $depH * 60 + $depM;
    $arrMinutes = $arrH * 60 + $arrM;
    if ($arrMinutes < $depMinutes) {
        $arrMinutes += 24 * 60;
    }
    $diff = $arrMinutes - $depMinutes;
    return floor($diff / 60) . 'h ' . ($diff % 60) . 'm';
}
