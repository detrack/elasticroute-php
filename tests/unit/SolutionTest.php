<?php

use PHPUnit\Framework\TestCase;
use Detrack\ElasticRoute\Plan;
use Detrack\ElasticRoute\BadFieldException;
use Detrack\ElasticRoute\Depot;

final class SolutionTest extends TestCase
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
        $solution = $plan->solve($this->proxy);
        $solution->plan_id = null;
        try {
            $solution->refresh($this->proxy);
            $this->fail('No exception thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('You need to create an id for this plan!%a', $ex->getMessage());
        }
    }

    public function testWillThrowExceptionOnHTTPError()
    {
        //try to intentionally cause an HTTP Error by changing the baseURL
        $plan = new Plan();
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
        $solution = $plan->solve($this->proxy);
        Plan::$baseURL = 'https://example.com';
        try {
            $solution->refresh($this->proxy);
            $this->fail('No exception thrown');
        } catch (\RuntimeException $ex) {
            $this->assertStringMatchesFormat('API Return HTTP Code%a', $ex->getMessage());
        }
        //reset the changed url or we gg lol
        Plan::$baseURL = getenv('elasticroute_path').'/plan';
    }

    public function testJsonSerialize()
    {
        $plan = new Plan();
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
        $solution = $plan->solve();
        $jsonArray = json_decode(json_encode($solution), true);
        $this->assertIsArray($jsonArray);
        $this->assertIsArray($jsonArray['depots']);
        $this->assertIsArray($jsonArray['stops']);
        $this->assertIsArray($jsonArray['vehicles']);
        $this->assertNotNull($jsonArray['plan_id']);
        $this->assertNotNull($jsonArray['status']);
        $this->assertNotNull($jsonArray['progress']);
        $this->assertIsArray($jsonArray['generalSettings']);
    }
}
