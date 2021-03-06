<?php

use PHPUnit\Framework\TestCase;
use Detrack\ElasticRoute\Plan;
use Detrack\ElasticRoute\Depot;
use Detrack\ElasticRoute\BadFieldException;

final class PlanTest extends TestCase
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

    public function testWillThrowExceptionWhenNoIdIsSet()
    {
        $plan = new Plan();
        try {
            $plan->solve();
            $this->fail('No exception thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('You need to create an id for this plan!%a', $ex->getMessage());
        }
    }

    public function testWillThrowExceptionOnHTTPError()
    {
        //try to intentionally cause an HTTP Error by changing the baseUrl
        $plan = new Plan();
        Plan::$baseUrl = 'https://example.com';
        $plan->id = 'TestPlan_'.time();
        $depots = [new Depot([
            'name' => 'Somewhere',
            'address' => 'Somewhere',
        ])];
        $vehicles = [[
            'name' => 'Some Vehicle',
        ]];
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
                'lat' => '1.281407',
                'lng' => '103.865770',
            ],
            [
                'name' => 'Singapore Zoo',
                'lat' => '1.404701',
                'lng' => '103.790018',
            ],
        ];
        $plan->depots = $depots;
        $plan->vehicles = $vehicles;
        $plan->stops = $stops;
        try {
            $plan->solve($this->proxy);
            $this->fail('No exception thrown!');
        } catch (\RuntimeException $ex) {
            $this->assertStringMatchesFormat('API Return HTTP Code%a', $ex->getMessage());
        }
        //reset the changed url or we gg lol
        Plan::$baseUrl = getenv('elasticroute_path').'/plan';
    }
}
