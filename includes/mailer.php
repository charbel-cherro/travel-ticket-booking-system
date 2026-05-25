<?php
/*
|--------------------------------------------------------------------------
| Email helper for LebaneseAirline
|--------------------------------------------------------------------------
| Requirements:
| 1) Install PHPMailer:
|    composer require phpmailer/phpmailer
|
| 2) Add SMTP settings in includes/config.php:
|
|    define('SMTP_HOST', 'smtp.gmail.com');
|    define('SMTP_PORT', 587);
|    define('SMTP_USERNAME', 'your_email@gmail.com');
|    define('SMTP_PASSWORD', 'your_app_password');
|    define('SMTP_FROM_EMAIL', 'your_email@gmail.com');
|    define('SMTP_FROM_NAME', 'LebaneseAirline');
|
| Important:
| Do not show your SMTP password in screenshots.
*/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function mailer_is_ready(): bool {
    return defined('SMTP_HOST')
        && defined('SMTP_PORT')
        && defined('SMTP_USERNAME')
        && defined('SMTP_PASSWORD')
        && defined('SMTP_FROM_EMAIL')
        && defined('SMTP_FROM_NAME')
        && SMTP_USERNAME !== ''
        && SMTP_PASSWORD !== '';
}

function create_mailer(): PHPMailer {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int)SMTP_PORT;

    $mail->CharSet = 'UTF-8';
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->isHTML(true);

    return $mail;
}

function send_app_email(string $toEmail, string $toName, string $subject, string $htmlBody, string $plainBody = ''): bool {
    if (!mailer_is_ready()) {
        error_log('Email not sent: SMTP settings are missing.');
        return false;
    }

    $toEmail = trim($toEmail);
    if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        error_log('Email not sent: invalid recipient email.');
        return false;
    }

    try {
        $mail = create_mailer();
        $mail->addAddress($toEmail, $toName ?: $toEmail);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $plainBody !== '' ? $plainBody : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('Email error: ' . $e->getMessage());
        return false;
    }
}

function money_email(float $amount): string {
    return '$' . number_format($amount, 0);
}

function send_booking_request_email(string $toEmail, string $toName, array $booking): bool {
    $bookingId = (int)($booking['id'] ?? 0);
    $route = htmlspecialchars($booking['route'] ?? '', ENT_QUOTES, 'UTF-8');
    $flightCode = htmlspecialchars($booking['flight_code'] ?? '', ENT_QUOTES, 'UTF-8');
    $flightTime = htmlspecialchars($booking['flight_time'] ?? '', ENT_QUOTES, 'UTF-8');
    $date = htmlspecialchars($booking['date'] ?? '', ENT_QUOTES, 'UTF-8');
    $seats = htmlspecialchars($booking['seat_number'] ?? '', ENT_QUOTES, 'UTF-8');
    $total = money_email((float)($booking['total'] ?? 0));

    $subject = "Booking request received #{$bookingId}";

    $html = "
        <h2>Booking request received</h2>
        <p>Hi " . htmlspecialchars($toName ?: 'Traveler', ENT_QUOTES, 'UTF-8') . ",</p>
        <p>Your booking request has been received and is now <strong>Pending approval</strong>.</p>
        <table cellpadding='8' cellspacing='0' border='1'>
            <tr><td><strong>Booking ID</strong></td><td>#{$bookingId}</td></tr>
            <tr><td><strong>Route</strong></td><td>{$route}</td></tr>
            <tr><td><strong>Flight code</strong></td><td>{$flightCode}</td></tr>
            <tr><td><strong>Date</strong></td><td>{$date}</td></tr>
            <tr><td><strong>Time</strong></td><td>{$flightTime}</td></tr>
            <tr><td><strong>Seat(s)</strong></td><td>{$seats}</td></tr>
            <tr><td><strong>Total</strong></td><td>{$total}</td></tr>
            <tr><td><strong>Status</strong></td><td>Pending approval</td></tr>
        </table>
        <p>We will email you again when the admin approves or rejects your request.</p>
        <p>LebaneseAirline</p>
    ";

    return send_app_email($toEmail, $toName, $subject, $html);
}

function send_booking_decision_email(array $booking): bool {
    $toEmail = $booking['user_email'] ?? '';
    $toName = $booking['user_name'] ?? 'Traveler';
    $status = $booking['status'] ?? 'Pending';

    if ($status === 'Confirmed') {
        $userStatus = 'Booked';
        $message = 'Good news — your booking has been approved and your flight is now booked.';
    } elseif ($status === 'Rejected') {
        $userStatus = 'Rejected';
        $message = 'Sorry, your booking request was not approved.';
    } elseif ($status === 'Cancelled') {
        $userStatus = 'Cancelled';
        $message = 'Your booking has been cancelled.';
    } else {
        $userStatus = 'Pending approval';
        $message = 'Your booking is still waiting for admin approval.';
    }

    $bookingId = (int)($booking['id'] ?? 0);
    $route = htmlspecialchars($booking['route'] ?? '', ENT_QUOTES, 'UTF-8');
    $flightCode = htmlspecialchars($booking['flight_code'] ?? '', ENT_QUOTES, 'UTF-8');
    $date = htmlspecialchars($booking['date'] ?? '', ENT_QUOTES, 'UTF-8');
    $total = money_email((float)($booking['total'] ?? 0));

    $subject = "Booking #{$bookingId} status: {$userStatus}";

    $html = "
        <h2>Booking status update</h2>
        <p>Hi " . htmlspecialchars($toName ?: 'Traveler', ENT_QUOTES, 'UTF-8') . ",</p>
        <p>{$message}</p>
        <table cellpadding='8' cellspacing='0' border='1'>
            <tr><td><strong>Booking ID</strong></td><td>#{$bookingId}</td></tr>
            <tr><td><strong>Route</strong></td><td>{$route}</td></tr>
            <tr><td><strong>Flight code</strong></td><td>{$flightCode}</td></tr>
            <tr><td><strong>Date</strong></td><td>{$date}</td></tr>
            <tr><td><strong>Total</strong></td><td>{$total}</td></tr>
            <tr><td><strong>Status</strong></td><td>{$userStatus}</td></tr>
        </table>
        <p>LebaneseAirline</p>
    ";

    return send_app_email($toEmail, $toName, $subject, $html);
}

function send_new_flight_email_to_all_users(array $flight): int {
    if (!table_exists('users') || !column_exists('users', 'email')) {
        return 0;
    }

    $stmt = db()->query("SELECT name, email FROM users WHERE role = 'user' AND email IS NOT NULL AND email <> ''");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sent = 0;

    $name = htmlspecialchars($flight['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $code = htmlspecialchars($flight['code'] ?? '', ENT_QUOTES, 'UTF-8');
    $route = htmlspecialchars(($flight['from'] ?? '') . ' → ' . ($flight['to'] ?? ''), ENT_QUOTES, 'UTF-8');
    $departureDate = htmlspecialchars($flight['date'] ?? '', ENT_QUOTES, 'UTF-8');
    $arrivalDate = htmlspecialchars($flight['arrival_date'] ?? ($flight['date'] ?? ''), ENT_QUOTES, 'UTF-8');
    $departure = htmlspecialchars($flight['departure'] ?? '', ENT_QUOTES, 'UTF-8');
    $arrival = htmlspecialchars($flight['arrival'] ?? '', ENT_QUOTES, 'UTF-8');
    $economy = money_email((float)($flight['economy_price'] ?? 0));

    $subject = "New flight available: {$code} {$route}";

    foreach ($users as $user) {
        $toEmail = $user['email'] ?? '';
        $toName = $user['name'] ?? 'Traveler';

        $html = "
            <h2>New flight available</h2>
            <p>Hi " . htmlspecialchars($toName ?: 'Traveler', ENT_QUOTES, 'UTF-8') . ",</p>
            <p>A new flight has been added to LebaneseAirline.</p>
            <table cellpadding='8' cellspacing='0' border='1'>
                <tr><td><strong>Flight</strong></td><td>{$name}</td></tr>
                <tr><td><strong>Code</strong></td><td>{$code}</td></tr>
                <tr><td><strong>Route</strong></td><td>{$route}</td></tr>
                <tr><td><strong>Departure</strong></td><td>{$departureDate} {$departure}</td></tr>
                <tr><td><strong>Arrival</strong></td><td>{$arrivalDate} {$arrival}</td></tr>
                <tr><td><strong>Economy from</strong></td><td>{$economy}</td></tr>
            </table>
            <p>Log in to LebaneseAirline to request a booking.</p>
        ";

        if (send_app_email($toEmail, $toName, $subject, $html)) {
            $sent++;
        }
    }

    return $sent;
}
