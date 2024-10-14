<?php

use App\WebScraper;

require __DIR__ . '/../vendor/autoload.php';


header('Content-Type: application/json');

$url = file('url.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$scraper = new WebScraper();

$results = $scraper->scrapeSite($url[0]);

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);