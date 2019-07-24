<?php

use PHPUnit\Framework\TestCase;
use Detrack\ElasticRoute\DashboardClient;
use Detrack\ElasticRoute\Vehicle;

final class VehicleTest extends TestCase
{
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
        } else {
            $this->proxy = [];
        }
        DashboardClient::$defaultApiKey = getenv('elasticroute_api_key');
        DashboardClient::$baseUrl = 'https://staging.elasticroute.com/api/v1'.'/account';
    }

    public function testCreate()
    {
        $client = new DashboardClient();
        $client->curlOptions = $this->proxy;
        $testData = new \stdClass();
        $testData->name = 'Testing Vehicle 123'.rand();
        $testData->weight_capacity = 8000;
        $vehicle = new Vehicle();
        $vehicle->dashboardClient = $client;
        $vehicle->name = $testData->name;
        $vehicle->weight_capacity = 8000;
        $vehicle->avail_from = 900;
        $vehicle->avail_till = 1700;
        $returnValue = $vehicle->create($this->proxy);
        // tests that Vehicle::create returns an instance of Vehicle
        $this->assertInstanceOf(Vehicle::class, $returnValue);
        // tests that Vehicle::create specifically returns itself
        $this->assertSame($returnValue, $vehicle);
        $this->assertSame($testData->name, $vehicle->name);
        $this->assertSame($testData->weight_capacity, $vehicle->weight_capacity);

        return $vehicle;
    }

    public function testRetrieve()
    {
        $createdVehicle = $this->testCreate();
        $client = new DashboardClient();
        $client->curlOptions = $this->proxy;
        $vehicle = new Vehicle();
        $vehicle->dashboardClient = $client;
        $vehicle->name = $createdVehicle->name;
        $vehicle->retrieve();
        $this->assertSame($createdVehicle->name, $vehicle->name);
        $this->assertSame($createdVehicle->weight_capacity, $vehicle->weight_capacity);

        return $vehicle;
    }

    public function testRetrieveNonExistentVehicleReturnsNull()
    {
        $vehicle = new Vehicle();
        $vehicle->name = 'This vehicle does not exist';
        $returnValue = $vehicle->retrieve();
        $this->assertNull($returnValue);
    }

    public function testDelete()
    {
        $retrievedVehicle = $this->testRetrieve();
        $returnValue = $retrievedVehicle->delete();
        $this->assertTrue($returnValue);
        $returnValue = $retrievedVehicle->retrieve();
        $this->assertNull($returnValue);
    }

    public function testDeleteNonExistentVehicleThrowsException()
    {
        $vehicle = new Vehicle();
        $vehicle->name = 'This vehicle does not exist';
        $this->expectException(\RuntimeException::class);
        $vehicle->delete();
    }

    public function testSimpleUpdate()
    {
        $vehicle = $this->testCreate();
        $vehicle->weight_capacity = 16000;
        $returnValue = $vehicle->update();
        $this->assertInstanceOf(Vehicle::class, $returnValue);
        $this->assertSame(16000, $returnValue->weight_capacity);
        $this->assertSame(16000, $vehicle->weight_capacity);
    }

    public function testCanUpdateNameWithoutCreatingAdditionalVehicle()
    {
        $vehicle = $this->testCreate();
        $originalName = $vehicle->name;
        $vehicle->name = 'Testing vehicle 1234'.rand();
        $returnValue = $vehicle->update();
        $this->assertSame($returnValue->name, $vehicle->name);
        $this->assertNotSame($originalName, $vehicle->name);
        $this->assertNotSame($originalName, $returnValue->name);

        $originalVehicle = new Vehicle();
        $originalVehicle->name = $originalName;

        $returnValue = $originalVehicle->retrieve();
        $this->assertNull($returnValue);
    }
}
