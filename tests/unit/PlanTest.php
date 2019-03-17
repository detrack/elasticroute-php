<?php

use PHPUnit\Framework\TestCase;
use Detrack\ElasticRoute\Plan;
use Detrack\ElasticRoute\Depot;
use Detrack\ElasticRoute\BadFieldException;

final class PlanTest extends TestCase
{
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
        //try to intentionally cause an HTTP Error by changing the baseURL
        $plan = new Plan();
        Plan::$baseURL = 'https://example.com';
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
                'user_lat' => '1.281407',
                'user_lng' => '103.865770',
            ],
            [
                'name' => 'Singapore Zoo',
                'user_lat' => '1.404701',
                'user_lng' => '103.790018',
            ],
        ];
        $plan->depots = $depots;
        $plan->vehicles = $vehicles;
        $plan->stops = $stops;
        try {
            $plan->solve();
            $this->fail('No exception thrown!');
        } catch (\RuntimeException $ex) {
            $this->assertStringMatchesFormat('API Return HTTP Code%a', $ex->getMessage());
        }
        //reset the changed url or we gg lol
        Plan::$baseURL = getenv('elasticroute_path');
    }
}
