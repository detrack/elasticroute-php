<?php

include 'vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::create(__DIR__);
    $dotenv->load();
} catch (Dotenv\Exception\InvalidPathException $ex) {
    // .env file doesn't exist...
}

Detrack\ElasticRoute\Plan::$defaultApiKey = getenv('elasticroute_api_key');
Detrack\ElasticRoute\Plan::$baseUrl = getenv('elasticroute_path').'/plan';
Detrack\ElasticRoute\DashboardClient::$defaultApiKey = getenv('elasticroute_api_key');
Detrack\ElasticRoute\DashboardClient::$baseUrl = getenv('elasticroute_path').'/account';

echo "\nDefault Api Key registered as: ".Detrack\ElasticRoute\Plan::$defaultApiKey;
echo "\nRouting Engine Base URL registered as: ".Detrack\ElasticRoute\Plan::$baseUrl;
echo "\nDashboard Engine Base URL registered as: ".Detrack\ElasticRoute\DashboardClient::$baseUrl."\n";
