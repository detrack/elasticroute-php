<?php

use PHPUnit\Framework\TestCase;
use Detrack\ElasticRoute\Plan;
use Detrack\ElasticRoute\Solution;
use Detrack\ElasticRoute\Depot;
use Detrack\ElasticRoute\Stop;
use Detrack\ElasticRoute\Vehicle;

final class SimplePlanTest extends TestCase
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

    public function testSimplePlan()
    {
        $plan = new Plan();
        $plan->id = 'TestPlan_'.time();
        $plan->stops = [
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
                'user_lat' => '1.281407',
                'user_lng' => '103.865770',
            ],
            [
                'name' => 'Singapore Zoo',
                'user_lat' => '1.404701',
                'user_lng' => '103.790018',
            ],
        ];
        $plan->vehicles = [
            [
                'name' => 'Van 1',
                'address' => '61 Kaki Bukit Ave 1 #04-34, Shun Li Ind Park Singapore 417943',
                'avail_from' => 900,
                'avail_till' => 1200,
            ],
            [
                'name' => 'Van 2',
                'address' => '61 Kaki Bukit Ave 1 #04-34, Shun Li Ind Park Singapore 417943',
                'avail_from' => 1200,
                'avail_till' => 1400,
            ],
        ];
        $plan->depots = [
            [
                'name' => 'Main Warehouse',
                'address' => '61 Kaki Bukit Ave 1 #04-34, Shun Li Ind Park Singapore 417943',
                'postal_code' => '417943',
            ],
        ];
        $solution = $plan->solve($this->proxy);

        $this->assertInstanceOf(Solution::class, $solution);
        $this->assertIsString($solution->rawResponseString);
        $this->assertIsObject($solution->rawResponseData);
        $this->assertContainsOnlyInstancesOf(Stop::class, $solution->stops);
        $this->assertContainsOnlyInstancesOf(Depot::class, $solution->depots);
        $this->assertContainsOnlyInstancesOf(Vehicle::class, $solution->vehicles);
        $this->assertEquals('planned', $solution->rawResponseData->data->stage);
        $this->assertEquals('planned', $solution->status);
        $this->assertEquals(100, $solution->progress);
    }

    public function testAsyncPlan()
    {
        $dataFilePath = __DIR__.DIRECTORY_SEPARATOR.'bigData.json';
        $testData = json_decode(file_get_contents($dataFilePath));
        $testPlan = new Plan();
        $testPlan->id = 'TestPlan2_'.time();
        $testPlan->connectionType = 'poll';
        $testPlan->stops = $testData->stops;
        $testPlan->depots = $testData->depots;
        $testPlan->vehicles = $testData->vehicles;
        $solution = $testPlan->solve($this->proxy);
        $this->assertEquals('submitted', $solution->status);
        while ($solution->status != 'planned') {
            $solution->refresh($this->proxy);
            sleep(5);
        }
        $this->assertEquals(22, count($solution->getUnsolvedStops()));
    }
}
