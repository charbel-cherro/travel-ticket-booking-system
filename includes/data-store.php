<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_DATA_DIR', dirname(__DIR__) . '/data');

function ensure_data_dir(): void {
    if (!is_dir(APP_DATA_DIR)) {
        mkdir(APP_DATA_DIR, 0777, true);
    }
}

function data_file_path(string $name): string {
    ensure_data_dir();
    return APP_DATA_DIR . '/' . $name . '.json';
}

function read_json_data(string $name, array $default = []): array {
    $path = data_file_path($name);
    if (!file_exists($path)) {
        write_json_data($name, $default);
        return $default;
    }

    $raw = file_get_contents($path);
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : $default;
}

function write_json_data(string $name, array $data): void {
    $path = data_file_path($name);
    file_put_contents($path, json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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

function ensure_default_users(): void {
    $users = read_json_data('users', []);
    if (!$users) {
        write_json_data('users', app_default_users());
    }
}

function get_all_users(): array {
    ensure_default_users();
    return read_json_data('users', app_default_users());
}

function save_all_users(array $users): void {
    write_json_data('users', $users);
}

function find_user_by_email(string $email): ?array {
    $email = strtolower(trim($email));
    foreach (get_all_users() as $user) {
        if (strtolower($user['email']) === $email) {
            return $user;
        }
    }
    return null;
}

function next_user_id(): int {
    $users = get_all_users();
    $ids = array_map(fn($user) => (int)$user['id'], $users);
    return $ids ? max($ids) + 1 : 1;
}

function create_user(string $name, string $email, string $password, string $role = 'user'): array {
    $users = get_all_users();
    $user = [
        'id' => next_user_id(),
        'name' => trim($name),
        'email' => strtolower(trim($email)),
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'created_at' => date('c')
    ];
    $users[] = $user;
    save_all_users($users);
    return $user;
}

function get_all_bookings(): array {
    return read_json_data('bookings', []);
}

function save_all_bookings(array $bookings): void {
    write_json_data('bookings', $bookings);
}

function next_booking_id(): int {
    $bookings = get_all_bookings();
    $ids = array_map(fn($booking) => (int)$booking['id'], $bookings);
    return $ids ? max($ids) + 1 : 1001;
}

function add_booking(array $booking): array {
    $bookings = get_all_bookings();
    $booking['id'] = next_booking_id();
    $booking['created_at'] = date('c');
    $bookings[] = $booking;
    save_all_bookings($bookings);
    return $booking;
}

function get_bookings_for_user(int $userId): array {
    $bookings = array_filter(get_all_bookings(), fn($booking) => (int)($booking['user_id'] ?? 0) === $userId);
    usort($bookings, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    return array_values($bookings);
}

function ensure_destinations_loaded(): void {
    $destinations = read_json_data('destinations', []);
    if (!$destinations) {
        write_json_data('destinations', default_destinations());
    }
}

function get_all_destinations(): array {
    ensure_destinations_loaded();
    return read_json_data('destinations', default_destinations());
}

function save_all_destinations(array $destinations): void {
    write_json_data('destinations', $destinations);
}

function next_destination_id(): int {
    $ids = array_map(fn($destination) => (int)$destination['id'], get_all_destinations());
    return $ids ? max($ids) + 1 : 1;
}

function ensure_insurance_loaded(): void {
    $options = read_json_data('insurance-options', []);
    if (!$options) {
        write_json_data('insurance-options', default_insurance_options());
    }
}

function get_all_insurance_options(): array {
    ensure_insurance_loaded();
    return read_json_data('insurance-options', default_insurance_options());
}

function save_all_insurance_options(array $options): void {
    write_json_data('insurance-options', $options);
}

function next_insurance_id(): int {
    $ids = array_map(fn($option) => (int)$option['id'], get_all_insurance_options());
    return $ids ? max($ids) + 1 : 1;
}
