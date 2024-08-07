<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars($_POST["email"]);

    $file = 'emails.txt';
    $current = file_get_contents($file);
    $current .= $email . "\n";
    file_put_contents($file, $current);

    header("Location: thankyou.html");
    exit();
}
?>
