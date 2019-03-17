<?php

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

    public function testMustHaveAtLeastTwoStops()
    {
        $stops = [$this->createStop(__METHOD__.'0')];
        try {
            $this->assertTrue(Stop::validateStops($stops));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('You must have at least two stops%a', $ex->getMessage());
        }
        $stops = [$this->createStop(__METHOD__.'1'),
            $this->createStop(__METHOD__.'2'),
        ];
        $this->assertTrue(Stop::validateStops($stops));
    }

    public function testNamesMustBeDistinct()
    {
        $stops = array_map(function ($x) {
            return $this->createStop(__METHOD__.'_BAD');
        }, range(0, 1));
        try {
            $this->assertTrue(Stop::validateStops($stops));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Stop name must be distinct%a', $ex->getMessage());
        }
        $stops = array_map(function ($x) {
            return $this->createStop(__METHOD__.$x);
        }, range(0, 1));
        $this->assertTrue(Stop::validateStops($stops));
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
            $this->assertTrue(Stop::validateStops($stops));
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
            $this->assertTrue(Stop::validateStops($stops));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Stop name cannot be more than 255 chars%a', $ex->getMessage());
        }
    }

    public function testCanPassCoordinatesOnly()
    {
        $stops = array_map(function ($x) {
            $stop = $this->createStop(__METHOD__.$x);
            if ($x == 1) {
                $stop->address = null;
                $stop->user_lat = 1.3368888888888888;
                $stop->user_lng = 103.91086111111112;
            }

            return $stop;
        }, range(0, 1));
        $this->assertTrue(Stop::validateStops($stops));
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
                    $this->assertTrue(Stop::validateStops($stops));
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
                $this->assertTrue(Stop::validateStops($stops));
                $this->fail('No exception thrown');
            } catch (BadFieldException $ex) {
                $this->assertStringMatchesFormat('Stop '.$field.' must be numeric%a', $ex->getMessage());
            }
        }
    }

    public function testCanPassPostcodeForSupportedCountries()
    {
        $badStop = new Stop();
        $badStop->name = 'Nani';
        $badStop->postal_code = 'S417943';
        $stops = [$this->createStop(), $badStop];
        $mockGeneralSettings = [
            'country' => 'SG',
        ];
        $this->assertTrue(Stop::validateStops($stops, $mockGeneralSettings));

        try {
            $this->assertTrue(Stop::validateStops($stops));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Stop address and coordinates are not given%a', $ex->getMessage());
        }
    }

    public function testCannotPassNoFormsOfAddress()
    {
        $badStop = new Stop();
        $badStop->name = 'Nani';
        $stops = [$this->createStop(), $badStop];
        $mockGeneralSettings = [
            'country' => 'SG',
        ];
        try {
            $this->assertTrue(Stop::validateStops($stops));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Stop address and coordinates are not given%a', $ex->getMessage());
        }
        try {
            $this->assertTrue(Stop::validateStops($stops, $mockGeneralSettings));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Stop address and coordinates are not given, and postcode is not present%a', $ex->getMessage());
        }
    }
}
