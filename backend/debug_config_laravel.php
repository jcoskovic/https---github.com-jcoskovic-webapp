<?php

// Bootstrap basic Laravel environment
require_once __DIR__ . '/vendor/autoload.php';

// Create a basic application for env() function
$app = new \Illuminate\Foundation\Application(__DIR__);

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Laravel Config Debug Test ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";

$configPath = __DIR__ . '/config';
echo "Config path: $configPath\n";

$configFiles = glob($configPath . '/*.php');
echo "Found config files: " . count($configFiles) . "\n";

foreach ($configFiles as $file) {
    $name = basename($file, '.php');
    echo "\n--- Testing $name ---\n";

    try {
        // Use eval to catch return value
        $configCode = file_get_contents($file);
        if (strpos($configCode, '<?php') === 0) {
            $configCode = substr($configCode, 5);
        }

        ob_start();
        $config = eval($configCode);
        $output = ob_get_clean();

        if ($output) {
            echo "Output during eval: $output\n";
        }

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
    } catch (ParseError $e) {
        echo "❌ $name: PARSE ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\n=== ENV Variables Test ===\n";
$envVars = ['APP_KEY', 'APP_ENV', 'DB_CONNECTION', 'JWT_TTL', 'JWT_REFRESH_TTL'];
foreach ($envVars as $var) {
    $value = env($var);
    echo "$var: " . gettype($value) . " = " . var_export($value, true) . "\n";
}

echo "\n=== Done ===\n";
