<?php

require __DIR__ . '/../vendor/autoload.php';

$content = file_get_contents(__DIR__ . '/storage/app/test_mikhmon_config.txt');

echo "Content length: " . strlen($content) . "\n\n";

preg_match_all('/\$data\s*\[\'([^\']+)\'\]\s*=\s*array\s*\((.*?)\);/s', $content, $matches, PREG_SET_ORDER);

echo "Found " . count($matches) . " matches\n\n";

foreach ($matches as $m) {
    echo "Key: " . $m[1] . "\n";

    // Extract values
    preg_match_all("/'([^']*)'/", $m[2], $lines);
    $values = $lines[1];

    foreach ($values as $v) {
        if (str_contains($v, '!') && str_contains($v, ':')) {
            echo "  Address found: " . $v . "\n";
        }
        if (str_contains($v, '@|@')) {
            list(, $username) = explode('@|@', $v, 2);
            echo "  Username: " . $username . "\n";
        }
    }
    echo "\n";
}
