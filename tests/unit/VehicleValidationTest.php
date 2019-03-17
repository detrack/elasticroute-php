<?php

use PHPUnit\Framework\TestCase;
use Detrack\ElasticRoute\Vehicle;
use Detrack\ElasticRoute\BadFieldException;

final class VehicleValidationTest extends TestCase
{
    /** Returns a perfectly fine vehicle
     * @param $testname name to give the vehicle
     *
     * @return Detrack\ElasticRoute\Vehicle the newly created vehicle
     */
    private function createVehicle($testname = null)
    {
        $vehicle = new Vehicle();
        $vehicle->name = $testname ?? __METHOD__.md5(rand().time());

        return $vehicle;
    }

    public function testMustHaveAtLeastOneVehicle()
    {
        $vehicles = [];
        try {
            $this->assertTrue(Vehicle::validateVehicles($vehicles));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('You must have at least one vehicle%a', $ex->getMessage());
        }
        $vehicles = [$this->createVehicle(__METHOD__.'1'),
            $this->createVehicle(__METHOD__.'2'),
        ];
        $this->assertTrue(Vehicle::validateVehicles($vehicles));
    }

    public function testNamesMustBeDistinct()
    {
        $vehicles = array_map(function ($x) {
            return $this->createVehicle(__METHOD__.'_BAD');
        }, range(0, 1));
        try {
            $this->assertTrue(Vehicle::validateVehicles($vehicles));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Vehicle name must be distinct%a', $ex->getMessage());
        }
        $vehicles = array_map(function ($x) {
            return $this->createVehicle(__METHOD__.$x);
        }, range(0, 1));
        $this->assertTrue(Vehicle::validateVehicles($vehicles));
    }

    public function testNamesCannotBeNull()
    {
        $vehicles = array_map(function ($x) {
            $vehicle = $this->createVehicle(__METHOD__.$x);
            if ($x == 1) {
                $vehicle->name = null;
            } elseif ($x == 2) {
                $vehicle->name = '';
            }

            return $vehicle;
        }, range(0, 2));
        try {
            $this->assertTrue(Vehicle::validateVehicles($vehicles));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Vehicle name cannot be null%a', $ex->getMessage());
        }
    }

    public function testNamesCannotBeLongerThan255Chars()
    {
        $vehicles = array_map(function ($x) {
            if ($x == 1) {
                $name = 'LONG LOOOOOOONG MA'.str_repeat('A', rand(250, 300)).'AAAANNN';
            } else {
                $name = __METHOD__.$x;
            }
            $vehicle = $this->createVehicle($name);

            return $vehicle;
        }, range(0, 1));
        try {
            $this->assertTrue(Vehicle::validateVehicles($vehicles));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Vehicle name cannot be more than 255 chars%a', $ex->getMessage());
        }
    }

    /** Tests that the following fields must be numeric and positive: weight_load, volume_load, seating_load, service_time
     */
    public function testPositiveNumericFields()
    {
        $fields = ['weight_capacity', 'volume_capacity', 'seating_capacity'];
        foreach ($fields as $field) {
            $badValues = [-0.5, '-9001.3'];
            foreach ($badValues as $badValue) {
                $vehicles = array_map(function ($x) use ($badValue,$field) {
                    $vehicle = $this->createVehicle(__METHOD__.$x);
                    if ($x == 1) {
                        $vehicle->$field = $badValue;
                    } else {
                        $vehicle->$field = '1.0';
                    }

                    return $vehicle;
                }, range(0, 1));
                try {
                    $this->assertTrue(Vehicle::validateVehicles($vehicles));
                    $this->fail('No exception thrown for: '.$field.' , '.$badValue);
                } catch (BadFieldException $ex) {
                    $this->assertStringMatchesFormat('Vehicle '.$field.' cannot be negative%a', $ex->getMessage());
                }
            }
            $vehicles = array_map(function ($x) use ($field) {
                $vehicle = $this->createVehicle(__METHOD__.$x);
                if ($x == 1) {
                    $vehicle->$field = 'nani';
                } else {
                    $vehicle->$field = '1.0';
                }

                return $vehicle;
            }, range(0, 1));
            try {
                $this->assertTrue(Vehicle::validateVehicles($vehicles));
                $this->fail('No exception thrown');
            } catch (BadFieldException $ex) {
                $this->assertStringMatchesFormat('Vehicle '.$field.' must be numeric%a', $ex->getMessage());
            }
        }
    }
}
