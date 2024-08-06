<?php
$file = 'counter.txt';

if (!file_exists($file)) {
    if (!touch($file)) {
        die(json_encode(['error' => 'Failed to create counter.txt']));
    }
    if (!chmod($file, 0664)) {
        die(json_encode(['error' => 'Failed to set permissions on counter.txt']));
    }
}

if (!is_readable($file)) {
    die(json_encode(['error' => 'counter.txt is not readable']));
}

$counter = (int)file_get_contents($file);

echo json_encode(['counter' => $counter]);
?>
