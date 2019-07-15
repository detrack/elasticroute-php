<?php

namespace Detrack\ElasticRoute;

use DateTime;

class DashboardClient
{
    public static $baseUrl = 'https://app.elasticroute.com/api/v1/account';
    public static $defaultApiKey = '';
    public $apiKey = '';

    /**
     * Uploads the given stops to a certain date on the dashboard.
     *
     * @param Stop[] $stops       an array of stops to upload
     * @param string $date        the date of which to upload the stops to
     * @param mixed  $curlOptions
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
     * Clears all stops on a certain date on the Dashboard.
     *
     * @param string $date        the date of which to upload the stops to
     * @param mixed  $curlOptions
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
