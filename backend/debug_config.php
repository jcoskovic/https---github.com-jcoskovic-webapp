<?php

echo "=== Config Debug Test ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";

$configPath = __DIR__ . '/config';
echo "Config path: $configPath\n";

if (!is_dir($configPath)) {
    echo "ERROR: Config directory does not exist!\n";
    exit(1);
}

$configFiles = glob($configPath . '/*.php');
echo "Found config files: " . count($configFiles) . "\n";

foreach ($configFiles as $file) {
    $name = basename($file, '.php');
    echo "\n--- Testing $name ---\n";

    try {
        $config = require $file;
        $type = gettype($config);
        echo "✓ $name: $type";

        if (!is_array($config)) {
            echo " ❌ ERROR: Expected array, got $type";
            if (is_scalar($config)) {
                echo " (value: " . var_export($config, true) . ")";
            }
        } else {
            echo " (keys: " . count($config) . ")";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "❌ $name: EXCEPTION - " . $e->getMessage() . "\n";
    } catch (Error $e) {
        echo "❌ $name: ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\n=== ENV Variables Test ===\n";
$envVars = ['APP_KEY', 'APP_ENV', 'DB_CONNECTION', 'JWT_TTL', 'JWT_REFRESH_TTL'];
foreach ($envVars as $var) {
    $value = env($var);
    echo "$var: " . gettype($value) . " = " . var_export($value, true) . "\n";
}

echo "\n=== Done ===\n";
