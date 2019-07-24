<?php

use PHPUnit\Framework\TestCase;
use Detrack\ElasticRoute\DashboardClient;
use Detrack\ElasticRoute\Stop;

final class StopTest extends TestCase
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
        $testData->date = date('Y-m-d');
        $testData->name = 'Testing Stop 123'.rand();
        $testData->address = '8 Somapah Road Singapore 487372';
        $stop = new Stop();
        $stop->dashboardClient = $client;
        $stop->date = $testData->date;
        $stop->name = $testData->name;
        $stop->address = $testData->address;
        $returnValue = $stop->create();
        // tests that Stop::create returns an instance of Stop
        $this->assertInstanceOf(Stop::class, $returnValue);
        // tests that Stop::create specifically returns itself
        $this->assertSame($returnValue, $stop);
        $this->assertSame($testData->date, $stop->date);
        $this->assertSame($testData->name, $stop->name);
        $this->assertSame($testData->address, $stop->address);

        return $stop;
    }

    public function testRetrieve()
    {
        $createdStop = $this->testCreate();
        $client = new DashboardClient();
        $client->curlOptions = $this->proxy;
        $stop = new Stop();
        $stop->dashboardClient = $client;
        $stop->date = $createdStop->date;
        $stop->name = $createdStop->name;
        $stop->retrieve();
        $this->assertSame($createdStop->date, $stop->date);
        $this->assertSame($createdStop->name, $stop->name);
        $this->assertSame($createdStop->address, $stop->address);

        return $stop;
    }

    public function testRetrieveNonExistentStopReturnsNull()
    {
        $stop = new Stop();
        $stop->name = 'This stop does not exist';
        $stop->date = date('Y-m-d');
        $returnValue = $stop->retrieve();
        $this->assertNull($returnValue);
    }

    public function testDelete()
    {
        $retrievedStop = $this->testRetrieve();
        $returnValue = $retrievedStop->delete();
        $this->assertTrue($returnValue);
        $returnValue = $retrievedStop->retrieve();
        $this->assertNull($returnValue);
    }

    public function testDeleteNonExistentStopThrowsException()
    {
        $stop = new Stop();
        $stop->name = 'This stop does not exist';
        $stop->date = date('Y-m-d');
        $this->expectException(\RuntimeException::class);
        $stop->delete();
    }

    public function testSimpleUpdate()
    {
        $stop = $this->testCreate();
        $stop->address = '80 Airport Boulevard (S)819642';
        $returnValue = $stop->update();
        $this->assertInstanceOf(Stop::class, $returnValue);
        $this->assertSame('80 Airport Boulevard (S)819642', $returnValue->address);
        $this->assertSame('80 Airport Boulevard (S)819642', $stop->address);
    }

    public function testCanUpdateNameWithoutCreatingAdditionalStop()
    {
        $stop = $this->testCreate();
        $originalName = $stop->name;
        $stop->name = 'Testing stop 1234'.rand();
        $returnValue = $stop->update();
        $this->assertSame($returnValue->name, $stop->name);
        $this->assertNotSame($originalName, $stop->name);
        $this->assertNotSame($originalName, $returnValue->name);

        $originalStop = new Stop();
        $originalStop->name = $originalName;
        $originalStop->date = $stop->date;

        $returnValue = $originalStop->retrieve();
        $this->assertNull($returnValue);
    }
}
