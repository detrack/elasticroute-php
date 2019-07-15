<?php

use PHPUnit\Framework\TestCase;
use Detrack\ElasticRoute\DashboardClient;

final class TestDashboardClient extends TestCase
{
    protected $proxy = [];

    public function setUp(): void
    {
        if (getenv('elasticroute_proxy_enabled') == 'true') {
            $this->proxy = [
                CURLOPT_PROXY => getenv('elasticroute_proxy_url'),
                CURLOPT_HEADEROPT => CURLHEADER_UNIFIED,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYSTATUS => false,
            ];
        }
    }

    public function testUploadStops()
    {
        $client = new DashboardClient();
        $stops = [
            [
                'name' => 'SUTD',
                'address' => '8 Somapah Road Singapore 487372',
            ],
            [
                'name' => 'Changi Airport',
                'address' => '80 Airport Boulevard (S)819642',
            ],
            [
                'name' => 'Gardens By the Bay',
                'address' => '18 Marina Gardens Drive Singapore 018953',
            ],
            [
                'name' => 'Singapore Zoo',
                'address' => '80 Mandai Lake Road Singapore 729826',
            ],
        ];
        $client->apiKey = getenv('elasticroute_api_key');
        DashboardClient::$baseUrl = 'https://staging.elasticroute.com/api/v1'.'/account';
        $client->uploadStopsOnDate($stops, date('Y-m-d'), $this->proxy);
    }

    public function testDeleteStops()
    {
        $this->testUploadStops();
        $client = new DashboardClient();
        $client->apiKey = getenv('elasticroute_api_key');
        DashboardClient::$baseUrl = 'https://staging.elasticroute.com/api/v1'.'/account';
        $client->deleteAllStopsOnDate(date('Y-m-d'), $this->proxy);
    }
    
    public function testStartPlan()
    {
        $this->testUploadStops();
        $client = new DashboardClient();
        $client->apiKey = getenv('elasticroute_api_key');
        DashboardClient::$baseUrl = 'https://staging.elasticroute.com/api/v1'.'/account';
        $client->startPlanningOnDate(date('Y-m-d'), $this->proxy);
    }

    public function testUploadVehicles()
    {
        $client = new DashboardClient();
        $client->apiKey = getenv('elasticroute_api_key');
        DashboardClient::$baseUrl = 'https://staging.elasticroute.com/api/v1'.'/account';
        $vehicles = [
            [
                'name' => 'Van1',
            ],
            [
                'name' => 'Van2',
            ],
        ];
        $client->uploadVehicles($vehicles, $this->proxy);
    }
}
