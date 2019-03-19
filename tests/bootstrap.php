<?php

include 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

Detrack\ElasticRoute\Plan::$defaultApiKey = getenv('elasticroute_api_key');
Detrack\ElasticRoute\Plan::$baseURL = getenv('elasticroute_path');

echo "\nDefault Api Key registered as: ".Detrack\ElasticRoute\Plan::$defaultApiKey;
echo "\nBase URL registered as: ".Detrack\ElasticRoute\Plan::$baseURL."\n";
