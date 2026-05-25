<?php
$projectFolder = explode('/', trim(dirname($_SERVER['SCRIPT_NAME']), '/'))[0];
define('BASE_URL', '/' . $projectFolder);
?>