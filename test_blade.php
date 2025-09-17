<?php

require_once '/var/www/bootstrap/app.php';

$app = require_once '/var/www/bootstrap/app.php';

try {
    $html = view('pdf.abbreviations-simple', [
        'abbreviations' => collect([]), 
        'exportDate' => '2025-09-14', 
        'totalCount' => 0, 
        'filters' => ['search' => null, 'category' => null]
    ])->render();
    
    echo "Template renders OK: " . strlen($html) . " characters\n";
    echo "First 100 chars: " . substr($html, 0, 100) . "\n";
} catch (Exception $e) {
    echo "Template error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}