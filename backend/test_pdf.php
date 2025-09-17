<?php
require_once '/var/www/bootstrap/app.php';

try {
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML('<h1>Test PDF</h1><p>This is a test</p>');
    echo "PDF generation successful!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}