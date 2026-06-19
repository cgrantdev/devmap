<?php
// Run via: ssh forge ... php decrypt_test.php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$enc = app(\Illuminate\Contracts\Encryption\Encrypter::class);
$cookie = $_SERVER['argv'][1] ?? '';
$layer = 0;
while ($cookie && $layer < 5) {
    try {
        $cookie = $enc->decrypt($cookie, false);
        $layer++;
        echo "Layer $layer (len=" . strlen($cookie) . "): " . substr($cookie, 0, 80) . "\n";
        if (!str_starts_with($cookie, 'eyJpdiI6')) break;
    } catch (\Throwable $e) {
        echo "Decrypt failed at layer " . ($layer + 1) . ": " . $e->getMessage() . "\n";
        break;
    }
}
echo "\nFinal value: " . $cookie . "\n";
