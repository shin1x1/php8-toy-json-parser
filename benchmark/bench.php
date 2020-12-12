<?php
declare(strict_types=1);

//require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../single.php';

use Shin1x1\ToyJsonParser\JsonParser;

const LOOP = 10000;

$json = file_get_contents(__DIR__ . '/a.json');

$now = microtime(true);
for ($i = 0; $i < LOOP; $i++) {
    $sut = new JsonParser();
    $sut->parse($json);
}
printf("=== JsonParser : %f\n", microtime(true) - $now);

$now = microtime(true);
for ($i = 0; $i < LOOP; $i++) {
    json_decode($json, associative: true);
}
printf("=== json_decode: %f\n", microtime(true) - $now);
