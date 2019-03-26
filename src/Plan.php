<?php

namespace Detrack\ElasticRoute;

class Plan implements \JsonSerializable
{
    public static $baseURL = 'https://app.elasticroute.com/api/plan';
    public static $defaultApiKey;
    public $apiKey;
    public $id = '';
    public $generalSettings = [
        'country' => 'SG',
        'timezone' => 'Asia/Singapore',
        'map' => 'OpenStreetMap',
        'map_api_key' => null,
        'route_osm' => 0,
        'loading_time' => 20,
        'buffer' => 40,
        'service_time' => 5,
        'distance_unit' => 'km',
        'max_time' => null,
        'max_distance' => null,
        'max_stops' => 30,
        'max_runs' => 1,
        'exclude_groups' => null,
        'avail_from' => 900,
        'avail_till' => 1700,
        'webhook_url' => null,
    ];
    public $stops = [];
    public $depots = [];
    public $vehicles = [];
    /** @var string either "sync", "poll" or "webhook". If you use "webhook", you MUST specify a webhook under generalSettings["webhook_url"] */
    public $connectionType = 'sync';

    public function solve($curlOptions = [])
    {
        if ($this->id == null) {
            throw new BadFieldException('You need to create an id for this plan!');
        }
        $queryVar = '';
        if ($this->connectionType == 'sync') {
            $queryVar = '?c=sync';
        }
        $ch = curl_init((static::$baseURL).'/'.$this->id.$queryVar);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.($this->apiKey ?? static::$defaultApiKey),
        ]);
        curl_setopt_array($ch, $curlOptions);

        //validate data
        Stop::validateStops($this->stops, $this->generalSettings);
        Vehicle::validateVehicles($this->vehicles);
        Depot::validateDepots($this->depots, $this->generalSettings);

        //morph to objects and fill in defaults
        foreach ($this->stops as &$stop) {
            if (is_array($stop)) {
                $stop = new Stop($stop);
            }
            if (is_string($stop->lat) && is_numeric($stop->lat)) {
                $stop->lat = (float) $stop->lat;
            }
            if (is_string($stop->lng) && is_numeric($stop->lng)) {
                $stop->lng = (float) $stop->lng;
            }
            if ($stop->from == null) {
                $stop->from = $this->generalSettings['avail_from'];
            }
            if ($stop->till == null) {
                $stop->till = $this->generalSettings['avail_till'];
            }
            if ($stop->depot == null) {
                if (is_array($this->depots[0])) {
                    $defaultDepotName = $this->depots[0]['name'];
                } else {
                    $defaultDepotName = $this->depots[0]->name;
                }
                $stop->depot = $defaultDepotName;
            }
        }
        foreach ($this->vehicles as &$vehicle) {
            if (is_array($vehicle) || $vehicle instanceof \stdClass) {
                $vehicle = new Vehicle($vehicle);
            }
            if ($vehicle->avail_from == null) {
                $vehicle->avail_from = $this->generalSettings['avail_from'];
            }
            if ($vehicle->avail_till == null) {
                $vehicle->avail_till = $this->generalSettings['avail_till'];
            }
            if ($vehicle->depot == null) {
                if (is_array($this->depots[0])) {
                    $defaultDepotName = $this->depots[0]['name'];
                } else {
                    $defaultDepotName = $this->depots[0]->name;
                }
                $vehicle->depot = $defaultDepotName;
            }
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this));
        curl_setopt($ch, CURLOPT_POST, true);
        $responseString = curl_exec($ch);
        $error = curl_error($ch);
        // @codeCoverageIgnoreStart
        if ($error != '') {
            throw new \RuntimeException('Curl Error occurred: '.$error);
        }
        // @codeCoverageIgnoreEnd
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($httpCode != '200') {
            throw new \RuntimeException('API Return HTTP Code '.$httpCode.' : '.$responseString);
        }
        curl_close($ch);
        $responseObject = json_decode($responseString);

        $solution = new Solution();
        $solution->apiKey = $this->apiKey;
        $solution->rawResponseString = $responseString;
        $solution->rawResponseData = $responseObject;
        $solvedStops = [];
        foreach ($responseObject->data->details->stops as $receivedStop) {
            $solvedStop = new Stop(json_decode(json_encode($receivedStop), true));
            array_push($solvedStops, $solvedStop);
        }
        $solution->stops = $solvedStops;
        $solvedVehicles = [];
        foreach ($responseObject->data->details->vehicles as $receivedVehicle) {
            $solvedVehicle = new Vehicle(json_decode(json_encode($receivedVehicle), true));
            array_push($solvedVehicles, $solvedVehicle);
        }
        $solution->vehicles = $solvedVehicles;
        $solvedDepots = [];
        foreach ($responseObject->data->details->depots as $receivedDepot) {
            $solvedDepot = new Depot(json_decode(json_encode($receivedDepot), true));
            array_push($solvedDepots, $solvedDepot);
        }
        $solution->depots = $solvedDepots;
        $solution->plan_id = $responseObject->data->plan_id;
        $solution->progress = $responseObject->data->progress;
        $solution->status = $responseObject->data->stage;
        $solution->generalSettings = $this->generalSettings;

        return $solution;
    }

    public function jsonSerialize()
    {
        return [
            'stops' => $this->stops,
            'depots' => $this->depots,
            'vehicles' => $this->vehicles,
            'generalSettings' => $this->generalSettings,
        ];
    }
}
