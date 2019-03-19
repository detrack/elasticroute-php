<?php

include 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

Detrack\ElasticRoute\Plan::$defaultApiKey = getenv('elasticroute_api_key');
Detrack\ElasticRoute\Plan::$baseURL = getenv('elasticroute_path');

echo 'Default Api Key registered as: '.Detrack\ElasticRoute\Plan::$defaultApiKey;
echo 'Base URL registered as: '.Detrack\ElasticRoute\Plan::$baseURL;
