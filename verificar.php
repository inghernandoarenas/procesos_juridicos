<?php
echo "<h3>Buscando PHPMailer...</h3>";

$paths = [
    'vendor/phpmailer/phpmailer/src/PHPMailer.php',
    'vendor/PHPMailer/PHPMailer/src/PHPMailer.php',
    'phpmailer/phpmailer/src/PHPMailer.php',
    'PHPMailer/src/PHPMailer.php'
];

foreach($paths as $path) {
    $full = __DIR__ . '/' . $path;
    if(file_exists($full)) {
        echo "✅ Encontrado en: $path<br>";
    } else {
        echo "❌ No encontrado en: $path<br>";
    }
}