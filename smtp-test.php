<?php
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/mailer.php';

$sent = send_app_email(
    'lebaneseairline@gmail.com',
    'LebaneseAirline Test',
    'LebaneseAirline SMTP test',
    '<h2>SMTP test successful</h2><p>Your LebaneseAirline project can send emails.</p>'
);

echo $sent
    ? 'Test email sent. Check the inbox/spam folder.'
    : 'Email failed. Check your SMTP email, app password, and Apache error log.';