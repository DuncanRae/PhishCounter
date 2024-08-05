<?php
$file = 'counter.txt';
$logFile = 'log.txt';

function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

try {
    if (!file_exists($file)) {
        if (!touch($file)) {
            throw new Exception('Failed to create counter.txt');
        }
        if (!chmod($file, 0664)) {
            throw new Exception('Failed to set permissions on counter.txt');
        }
    }

    if (!is_writable($file)) {
        throw new Exception('counter.txt is not writable');
    }

    $counter = (int)file_get_contents($file);
    $counter++;
    if (file_put_contents($file, (string)$counter) === false) {
        throw new Exception('Failed to write to counter.txt');
    }

    echo json_encode(['counter' => $counter]);
} catch (Exception $e) {
    logMessage($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
