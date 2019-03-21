<?php

use PHPUnit\Framework\TestCase;
use Detrack\ElasticRoute\Depot;
use Detrack\ElasticRoute\BadFieldException;

final class DepotValidationTest extends TestCase
{
    /** Returns a perfectly fine depot
     * @param $testname name to give the depot
     *
     * @return Detrack\ElasticRoute\Depot the newly created depot
     */
    public function createDepot($testname = null)
    {
        $depot = new Depot();
        $depot->name = $testname ?? __METHOD__.md5(rand().time());
        $testAddresses = ['61 Kaki Bukit Ave 1 #04-34, Shun Li Ind Park Singapore 417943',
            '8 Somapah Road Singapore 487372',
            '80 Airport Boulevard (S)819642',
            '80 Mandai Lake Road Singapore 729826',
            '10 Bayfront Avenue Singapore 018956',
            '18 Marina Gardens Drive Singapore 018953', ];
        $depot->address = $testAddresses[array_rand($testAddresses)];

        return $depot;
    }

    public function testMustHaveAtLeastOneDepot()
    {
        $depots = [];
        try {
            $this->assertTrue(Depot::validateDepots($depots));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('You must have at least one depot%a', $ex->getMessage());
        }
        $depots = [$this->createDepot(__METHOD__.'1'),
            $this->createDepot(__METHOD__.'2'),
        ];
        $this->assertTrue(Depot::validateDepots($depots));
    }

    public function testNamesMustBeDistinct()
    {
        $depots = array_map(function ($x) {
            return $this->createDepot(__METHOD__.'_BAD');
        }, range(0, 1));
        try {
            $this->assertTrue(Depot::validateDepots($depots));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Depot name must be distinct%a', $ex->getMessage());
        }
        $depots = array_map(function ($x) {
            return $this->createDepot(__METHOD__.$x);
        }, range(0, 1));
        $this->assertTrue(Depot::validateDepots($depots));
    }

    public function testNamesCannotBeNull()
    {
        $depots = array_map(function ($x) {
            $depot = $this->createDepot(__METHOD__.$x);
            if ($x == 1) {
                $depot->name = null;
            } elseif ($x == 2) {
                $depot->name = '';
            }

            return $depot;
        }, range(0, 2));
        try {
            $this->assertTrue(Depot::validateDepots($depots));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Depot name cannot be null%a', $ex->getMessage());
        }
    }

    public function testNamesCannotBeLongerThan255Chars()
    {
        $depots = array_map(function ($x) {
            if ($x == 1) {
                $name = 'LONG LOOOOOOONG MA'.str_repeat('A', rand(250, 300)).'AAAANNN';
            } else {
                $name = __METHOD__.$x;
            }
            $depot = $this->createDepot($name);

            return $depot;
        }, range(0, 1));
        try {
            $this->assertTrue(Depot::validateDepots($depots));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Depot name cannot be more than 255 chars%a', $ex->getMessage());
        }
    }

    public function testCanPassCoordinatesOnly()
    {
        $depots = array_map(function ($x) {
            $depot = $this->createDepot(__METHOD__.$x);
            if ($x == 1) {
                $depot->address = null;
                $depot->lat = 1.3368888888888888;
                $depot->lng = 103.91086111111112;
            }

            return $depot;
        }, range(0, 1));
        $this->assertTrue(Depot::validateDepots($depots));
    }

    public function testCanPassPostcodeForSupportedCountries()
    {
        $depot = new Depot();
        $depot->name = 'Nani';
        $depot->postal_code = 'S417943';
        $mockGeneralSettings = [
            'country' => 'SG',
        ];
        $this->assertTrue(Depot::validateDepots([$depot], $mockGeneralSettings));

        try {
            $this->assertTrue(Depot::validateDepots([$depot]));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Depot address and coordinates are not given%a', $ex->getMessage());
        }
    }

    public function testCannotPassNoFormsOfAddress()
    {
        $depot = new Depot();
        $depot->name = 'Nani';
        $mockGeneralSettings = [
            'country' => 'SG',
        ];
        try {
            $this->assertTrue(Depot::validateDepots([$depot]));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Depot address and coordinates are not given%a', $ex->getMessage());
        }
        try {
            $this->assertTrue(Depot::validateDepots([$depot],$mockGeneralSettings));
            $this->fail('No exception was thrown');
        } catch (BadFieldException $ex) {
            $this->assertStringMatchesFormat('Depot address and coordinates are not given, and postcode is not present%a', $ex->getMessage());
        }
    }
}
