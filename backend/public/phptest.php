<?php
// Basic PHP test - no Laravel
echo "PHP is working! Version: " . PHP_VERSION;
echo "<br>Current time: " . date('Y-m-d H:i:s');
echo "<br>Environment variables:";
echo "<br>APP_NAME: " . ($_ENV['APP_NAME'] ?? 'not set');
echo "<br>DB_CONNECTION: " . ($_ENV['DB_CONNECTION'] ?? 'not set');
echo "<br>DATABASE_URL: " . (empty($_ENV['DATABASE_URL']) ? 'not set' : 'set');
?>