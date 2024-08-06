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

if (!is_writable($file)) {
    die(json_encode(['error' => 'counter.txt is not writable']));
}

if (file_put_contents($file, '0') === false) {
    die(json_encode(['error' => 'Failed to write to counter.txt']));
}

echo json_encode(['counter' => 0]);
?>
