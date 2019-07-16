<?php

namespace Detrack\ElasticRoute;

use DateTime;

class DashboardClient
{
    public static $baseUrl = 'https://app.elasticroute.com/api/v1/account';
    public static $defaultApiKey = null;
    public $apiKey = null;

    /**
     * Uploads the given stops to a certain date on the dashboard.
     *
     * @param Stop[] $stops       an array of stops to upload
     * @param string $date        the date of which to upload the stops to
     * @param mixed  $curlOptions
     *
     * @throws BadFieldException if api key or date is not set
     * @throws RuntimeException  if the API returns a non-successful status code
     */
    public function uploadStopsOnDate($stops, $date, $curlOptions = [])
    {
        $apiKey = $this->apiKey ?? static::$defaultApiKey;
        $apiKey = trim($apiKey);
        if ($apiKey == '') {
            throw new BadFieldException('API Key is missing!');
        }
        if (DateTime::createFromFormat('Y-m-d', $date)->format('Y-m-d') !== $date) {
            throw new BadFieldException('Please use YYYY-MM-DD as the format of the date!');
        }
        $endpoint = static::$baseUrl.'/'.'stops/'.$date.'/bulk';
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$apiKey,
        ]);
        curl_setopt_array($ch, $curlOptions);

        Stop::validateStops($stops);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['data' => $stops]));
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
    }

    /**
     * Retrieves a list of stops scheduled on a certain date on the dashboard.
     *
     * @param string $date        the date you want to retrieve jobs from
     * @param int    $limit       the number of stops to retrieve at once
     * @param int    $page        page number, if you are retriving the list of jobs in batches
     * @param mixed  $curlOptions an associative array containing options to pass to curl via curl_setopt_array
     *
     * @throws BadFieldException if api key or date is not set
     * @throws RuntimeException  if the API returns a non-successful status code
     */
    public function listAllStopsOnDate($date, $limit = 100, $page = 1, $curlOptions = [])
    {
        $apiKey = $this->apiKey ?? static::$defaultApiKey;
        $apiKey = trim($apiKey);
        if ($apiKey == '') {
            throw new BadFieldException('API Key is missing!');
        }
        if (DateTime::createFromFormat('Y-m-d', $date)->format('Y-m-d') !== $date) {
            throw new BadFieldException('Please use YYYY-MM-DD as the format of the date!');
        }
        $endpoint = static::$baseUrl.'/'.'stops/'.$date.'?limit='.$limit;
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$apiKey,
        ]);
        curl_setopt_array($ch, $curlOptions);

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

        $response = json_decode($responseString);
        $stops = [];
        foreach ($response->data as $responseStop) {
            $stop = new Stop($responseStop);
            array_push($stops, $stop);
        }

        return $stops;
    }

    /**
     * Clears all stops on a certain date on the Dashboard.
     *
     * @param string $date        the date of which to upload the stops to
     * @param mixed  $curlOptions an associative array containing options to pass to curl via curl_setopt_array
     *
     * @throws BadFieldException if api key or date is not set
     * @throws RuntimeException  if the API returns a non-successful status code
     */
    public function deleteAllStopsOnDate($date, $curlOptions = [])
    {
        $apiKey = $this->apiKey ?? static::$defaultApiKey;
        $apiKey = trim($apiKey);
        if ($apiKey == '') {
            throw new BadFieldException('API Key is missing!');
        }
        if (DateTime::createFromFormat('Y-m-d', $date)->format('Y-m-d') !== $date) {
            throw new BadFieldException('Please use YYYY-MM-DD as the format of the date!');
        }
        $endpoint = static::$baseUrl.'/'.'stops/'.$date;
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$apiKey,
        ]);
        curl_setopt_array($ch, $curlOptions);
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
    }

    /**
     * Starts the planning process on a certain date.
     *
     * @param mixed $date        the date to start the planning process
     * @param mixed $curlOptions an associative array containing options to pass to curl via curl_setopt_array
     *
     * @throws BadFieldException if api key or date is not set
     * @throws RuntimeException  if the API returns a non-successful status code
     */
    public function startPlanningOnDate($date, $curlOptions = [])
    {
        $apiKey = $this->apiKey ?? static::$defaultApiKey;
        $apiKey = trim($apiKey);
        if ($apiKey == '') {
            throw new BadFieldException('API Key is missing!');
        }
        if (DateTime::createFromFormat('Y-m-d', $date)->format('Y-m-d') !== $date) {
            throw new BadFieldException('Please use YYYY-MM-DD as the format of the date!');
        }
        $endpoint = static::$baseUrl.'/'.'stops/'.$date.'/plan';
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$apiKey,
        ]);
        curl_setopt_array($ch, $curlOptions);
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
    }

    /**
     * Retrieves the status of the route plan on a certain date.
     *
     * @param mixed $date        the date you want to retrieve the route plan for
     * @param mixed $curlOptions an associative array containing options to pass to curl via curl_setopt_array
     *
     * @throws BadFieldException if api key or date is not set
     * @throws RuntimeException  if the API returns a non-successful status code
     *
     * @return string status of the plan, either "planned" or "submitted"
     */
    public function getPlanStatusOnDate($date, $curlOptions = [])
    {
        $apiKey = $this->apiKey ?? static::$defaultApiKey;
        $apiKey = trim($apiKey);
        if ($apiKey == '') {
            throw new BadFieldException('API Key is missing!');
        }
        if (DateTime::createFromFormat('Y-m-d', $date)->format('Y-m-d') !== $date) {
            throw new BadFieldException('Please use YYYY-MM-DD as the format of the date!');
        }
        $endpoint = static::$baseUrl.'/'.'stops/'.$date.'/plan/status';
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$apiKey,
        ]);
        curl_setopt_array($ch, $curlOptions);
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

        return json_decode($responseString)->data->stage;
    }

    /**
     * Uploads the given vehicles onto the Dashboard.
     *
     * @param Vehicle[] $vehicles    an array of vehicles to upload
     * @param mixed     $curlOptions
     */
    public function uploadVehicles($vehicles, $curlOptions = [])
    {
        $apiKey = $this->apiKey ?? static::$defaultApiKey;
        $apiKey = trim($apiKey);
        if ($apiKey == '') {
            throw new BadFieldException('API Key is missing!');
        }
        $endpoint = static::$baseUrl.'/vehicles/bulk';
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$apiKey,
        ]);
        curl_setopt_array($ch, $curlOptions);

        Vehicle::validateVehicles($vehicles);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['data' => $vehicles]));
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
    }
}
