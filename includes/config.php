<?php
$projectFolder = explode('/', trim(dirname($_SERVER['SCRIPT_NAME']), '/'))[0];

define('BASE_URL', '/' . $projectFolder);

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'lebaneseairline@gmail.com');
define('SMTP_PASSWORD', 'ameaqltfinrvjdrp');
define('SMTP_FROM_EMAIL', 'lebaneseairline@gmail.com');
define('SMTP_FROM_NAME', 'LebaneseAirline');
?>