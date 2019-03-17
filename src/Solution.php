<?php

namespace Detrack\ElasticRoute;

class Solution implements \JsonSerializable
{
    /** @var string the api key */
    public $apiKey;
    /** @var string the id of the plan stored on our servers */
    public $plan_id;
    /** @var float percentage completion of calculating the solution */
    public $progress;
    /** @var string status of calculating the solution – submitted, planing, planned */
    public $status;
    /** @var string the raw, undecoded raw response string from the API. */
    public $rawResponseString;
    /** @var string the raw, decoded JSON response object from the API. */
    public $rawResponseData;
    /** @var Depot[] the list of depots that will be used in the solution. It will be a subset of depots provided in the original plan. */
    public $depots = [];
    /** @var Stops[] the list of stops served in the solution. It will be a subset of stops provided in the original plan. */
    public $stops = [];
    /** @var Vehicle[] the list of vehicles that will be used in the solution. It will be a subset of vehicles provided in the original plan. */
    public $vehicles = [];
    public $generalSettings = [];

    /**
     * If plan was solved with the async option, use this method to periodically poll for updates and completion.
     *
     * This method should only be used with async.
     *
     * @param mixed $curlOptions
     */
    public function refresh($curlOptions = [])
    {
        if ($this->plan_id == null) {
            throw new BadFieldException('You need to create an id for this plan!');
        }

        $ch = curl_init((Plan::$baseURL).'/'.$this->plan_id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.($this->apiKey ?? Plan::$defaultApiKey),
        ]);
        curl_setopt_array($ch, $curlOptions);

        curl_setopt($ch, CURLOPT_HTTPGET, true);

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

        $this->progress = $responseObject->data->progress;
        $this->status = $responseObject->data->stage;

        $this->rawResponseString = $responseString;
        $this->rawResponseData = $responseObject;
        $solvedStops = [];
        foreach ($responseObject->data->details->stops as $receivedStop) {
            $solvedStop = new Stop(json_decode(json_encode($receivedStop), true));
            array_push($solvedStops, $solvedStop);
        }
        $this->stops = $solvedStops;
        $solvedVehicles = [];
        foreach ($responseObject->data->details->vehicles as $receivedVehicle) {
            $solvedVehicle = new Vehicle(json_decode(json_encode($receivedVehicle), true));
            array_push($solvedVehicles, $solvedVehicle);
        }
        $this->vehicles = $solvedVehicles;
        $solvedDepots = [];
        foreach ($responseObject->data->details->depots as $receivedDepot) {
            $solvedDepot = new Depot(json_decode(json_encode($receivedDepot), true));
            array_push($solvedDepots, $solvedDepot);
        }
        $this->depots = $solvedDepots;
        $this->plan_id = $responseObject->data->plan_id;
    }

    /**
     * Retrieves a list of stops that, for whatever reason, will not be served in this solution.
     *
     * Reason for not being able to serve will be put under the $exception property of each stop.
     */
    public function getUnsolvedStops()
    {
        return array_filter($this->stops, function ($stop) {
            return !is_null($stop->exception) && $stop->exception != '';
        });
    }

    /**
     * Turns this object into an array for serializing into JSON.
     *
     * @return array an associative array containing this object's properties
     */
    public function jsonSerialize()
    {
        return [
            'stops' => $this->stops,
            'depots' => $this->depots,
            'vehicles' => $this->vehicles,
            'plan_id' => $this->plan_id,
            'progress' => $this->progress,
            'status' => $this->status,
            'generalSettings' => $this->generalSettings,
        ];
    }
}
