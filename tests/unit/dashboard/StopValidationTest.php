<?php

namespace Detrack\ElasticRoute\Testing\Unit\Dashboard;

use PHPUnit\Framework\TestCase;
use Detrack\ElasticRoute\Stop;
use Detrack\ElasticRoute\BadFieldException;

final class StopValidationTest extends TestCase
{
    /** Returns a perfectly fine stop
     * @param $testname name to give the stop
     *
     * @return Detrack\ElasticRoute\Stop the newly created stop
     */
    public function createStop($testname = null)
    {
        $stop = new Stop();
        $stop->name = $testname ?? __METHOD__.md5(rand().time());
        $testAddresses = ['61 Kaki Bukit Ave 1 #04-34, Shun Li Ind Park Singapore 417943',
            '8 Somapah Road Singapore 487372',
            '80 Airport Boulevard (S)819642',
            '80 Mandai Lake Road Singapore 729826',
            '10 Bayfront Avenue Singapore 018956',
            '18 Marina Gardens Drive Singapore 018953', ];
        $stop->address = $testAddresses[array_rand($testAddresses)];

        return $stop;
    }
    
    public function testNamesMustBeDistinct()
    {
        $stops = array_map(function ($x) {
            return $this->createStop(__METHOD__.'_BAD');
        }, range(0, 1));
        try {
            $this->assertTrue(Stop::validateStopsForDashboard($stops));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Stop name must be distinct%a', $ex->getMessage());
        }
        $stops = array_map(function ($x) {
            return $this->createStop(__METHOD__.$x);
        }, range(0, 1));
        $this->assertTrue(Stop::validateStopsForDashboard($stops));
    }

    public function testNamesCannotBeNull()
    {
        $stops = array_map(function ($x) {
            $stop = $this->createStop(__METHOD__.$x);
            if ($x == 1) {
                $stop->name = null;
            } elseif ($x == 2) {
                $stop->name = '';
            }

            return $stop;
        }, range(0, 2));
        try {
            $this->assertTrue(Stop::validateStopsForDashboard($stops));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Stop name cannot be null%a', $ex->getMessage());
        }
    }

    public function testNamesCannotBeLongerThan255Chars()
    {
        $stops = array_map(function ($x) {
            if ($x == 1) {
                $name = 'LONG LOOOOOOONG MA'.str_repeat('A', rand(250, 300)).'AAAANNN';
            } else {
                $name = __METHOD__.$x;
            }
            $stop = $this->createStop($name);

            return $stop;
        }, range(0, 1));
        try {
            $this->assertTrue(Stop::validateStopsForDashboard($stops));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Stop name cannot be more than 255 chars%a', $ex->getMessage());
        }
    }

    public function testCanPassLineAddresses()
    {
        $stop = new Stop();
        $stop->name = __METHOD__;
        $stop->address_1 = 'Singapore University of Technology and Design';
        $stop->address_2 = '8 Somapah Road';
        $stop->address_3 = 'Singapore 487372';
        $this->assertTrue(Stop::validateStopsForDashboard([$stop]));
    }

    /** Tests that the following fields must be numeric and positive: weight_load, volume_load, seating_load, service_time
     */
    public function testPositiveNumericFields()
    {
        $fields = ['weight_load', 'volume_load', 'seating_load', 'service_time'];
        foreach ($fields as $field) {
            $badValues = [-0.5, '-9001.3'];
            foreach ($badValues as $badValue) {
                $stops = array_map(function ($x) use ($badValue,$field) {
                    $stop = $this->createStop(__METHOD__.$x);
                    if ($x == 1) {
                        $stop->$field = $badValue;
                    } else {
                        $stop->$field = '1.0';
                    }

                    return $stop;
                }, range(0, 1));
                try {
                    $this->assertTrue(Stop::validateStopsForDashboard($stops));
                    $this->fail('No exception thrown');
                } catch (BadFieldException $ex) {
                    $this->assertStringMatchesFormat('Stop '.$field.' cannot be negative%a', $ex->getMessage());
                }
            }
            $stops = array_map(function ($x) use ($field) {
                $stop = $this->createStop(__METHOD__.$x);
                if ($x == 1) {
                    $stop->$field = 'nani';
                } else {
                    $stop->$field = '1.0';
                }

                return $stop;
            }, range(0, 1));
            try {
                $this->assertTrue(Stop::validateStopsForDashboard($stops));
                $this->fail('No exception thrown');
            } catch (BadFieldException $ex) {
                $this->assertStringMatchesFormat('Stop '.$field.' must be numeric%a', $ex->getMessage());
            }
        }
    }

    public function testCannotPassNoFormsOfAddress()
    {
        $badStop = new Stop();
        $badStop->name = 'Nani';
        $stops = [$this->createStop(), $badStop];
        try {
            $this->assertTrue(Stop::validateStopsForDashboard($stops));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('No form of address given for Stop%a', $ex->getMessage());
        }
    }
}
