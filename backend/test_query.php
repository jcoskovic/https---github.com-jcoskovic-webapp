<?php

use App\Models\Abbreviation;

// Test just the query part
$query = Abbreviation::with(['user', 'votes', 'comments.user'])
    ->where('status', 'approved')
    ->whereIn('id', [15]);

$abbreviations = $query->get();

echo "Found abbreviations: " . $abbreviations->count() . "\n";

foreach ($abbreviations as $abbr) {
    echo "ID: {$abbr->id}, Title: {$abbr->abbreviation}, Status: {$abbr->status}\n";
}