<?php

namespace Detrack\ElasticRoute;

use JsonSerializable;

abstract class Model implements JsonSerializable
{
    /** @var array encapsulates the data of the model */
    protected $data = [];

    /** @var mixed[] keeps track of the immediate previous value of the attribute */
    protected $previousAttributeValues = [];

    /**
     * Constructs a new model.
     *
     * @param mixed $data
     */
    public function __construct($data = [])
    {
        if (!is_array($data)) {
            $data = json_decode(json_encode($data), true);
        }
        foreach ($data as $dataKey => $dataValue) {
            $this->$dataKey = $dataValue;
        }
    }

    public function __set($key, $value)
    {
        // if this key exists in the data array
        if (array_key_exists($key, $this->data)) {
            // if there was a change in data values
            if (is_null($this->data[$key]) || $this->data[$key] !== $value) {
                // save it in previous attributes, just in case we need it later
                $this->previousAttributeValues[$key] = $this->data[$key];
                $this->data[$key] = $value;
            }
        }
    }

    public function __get($key)
    {
        return $this->data[$key];
    }

    /**
     * Converts this Model into an associative array for use with json_encode.
     *
     * In an attempt to prevent accidental overwrites on the Dashboard and to decrease the size of the JSON document sent to the server, this function will only return attributes that have been modified since creation.
     *
     * @return mixed[] an associative array to be encoded into JSON format
     */
    public function jsonSerialize()
    {
        $callback = function ($v, $k) {
            return !(is_null($v) && !array_key_exists($k, $this->previousAttributeValues));
        };
        $returnArray = array_filter($this->data, $callback, ARRAY_FILTER_USE_BOTH);

        return $returnArray;
    }

    protected static $path;

    /** @var DashboardClient DASHBOARD ONLY: instance of the dashboard client this model belongs to */
    public $dashboardClient;

    public function save()
    {
    }

    /**
     * Child classes are to override this method to determine what is the document path to send the http request to for the create method.
     *
     * @return string the path
     */
    protected function resolveCreatePath()
    {
        return $this->path;
    }

    /**
     * Child classes are to override this method to determine what is the content of the body sent in the http request for the create method.
     *
     * By default, wraps itself in an associative array with index "data".
     *
     * @return mixed|null the desired data to send in the request body
     */
    protected function resolveCreateBody()
    {
        return ['data' => $this];
    }

    /**
     * Provides a generic template for creating a new model. Will not work unless the child classes override the relevant methods.
     *
     * @see Model::resolveCreatePath to change where the request is sent to
     * @see Model::resolveCreateBody to change the payload data of the request
     *
     * @param mixed $curlOptions
     *
     * @return self
     */
    public function create($curlOptions = [])
    {
        $path = $this->resolveCreatePath();
        $apiKey = $this->dashboardClient->apiKey ?? DashboardClient::$defaultApiKey;
        $apiKey = trim($apiKey);
        if ($apiKey == '') {
            throw new BadFieldException('API Key is missing!');
        }
        $endpoint = $path;
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$apiKey,
        ]);
        curl_setopt_array($ch, $this->dashboardClient->curlOptions ?? []);
        curl_setopt_array($ch, $curlOptions);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->resolveCreateBody()));
        $responseString = curl_exec($ch);
        $error = curl_error($ch);
        // @codeCoverageIgnoreStart
        if ($error != '') {
            throw new \RuntimeException('Curl Error occurred: '.$error);
        }
        // @codeCoverageIgnoreEnd
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($httpCode != '200') {
            throw new \RuntimeException('API Return HTTP Code '.$httpCode.' : '.$responseString);
        }
        $responseData = json_decode($responseString, true);

        $this->previousAttributeValues = [];
        $this->data = $responseData['data'];

        return $this;
    }

    /**
     * Child classes are to override this method to determine what is the document path to send the http request to for the retrieve method.
     *
     * @return string the path
     */
    protected function resolveRetrievePath()
    {
        return $this->path;
    }

    /**
     * Child classes are to override this method to determine what is the content of the body sent in the http request for the retrieve method.
     *
     * By default, does not send any data for the request body.
     *
     * @return mixed|null the desired data to send in the request body
     */
    protected function resolveRetrieveBody()
    {
        return null;
    }

    /**
     * Provides a generic template for retrieving a model. Will not work unless the child classes override the relevant methods.
     *
     * Will populate itself with updated values from the API, and then return itself.
     * If the endpoint sends a HTTP status 404, it will return NULL without modifying itself.
     *
     * @see Model::resolveRetrievePath to change where the request is sent to
     * @see Model::resolveRetrieveBody to change the payload data of the request
     *
     * @param mixed $curlOptions
     *
     * @return self|null returns itself with updated values, or NULL if not found
     */
    public function retrieve($curlOptions = [])
    {
        $path = $this->resolveRetrievePath();
        $apiKey = $this->dashboardClient->apiKey ?? DashboardClient::$defaultApiKey;
        $apiKey = trim($apiKey);
        if ($apiKey == '') {
            throw new BadFieldException('API Key is missing!');
        }
        $endpoint = $path;
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$apiKey,
        ]);
        curl_setopt_array($ch, $this->dashboardClient->curlOptions ?? []);
        curl_setopt_array($ch, $curlOptions);
        $responseString = curl_exec($ch);
        $error = curl_error($ch);
        // @codeCoverageIgnoreStart
        if ($error != '') {
            throw new \RuntimeException('Curl Error occurred: '.$error);
        }
        // @codeCoverageIgnoreEnd
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
        if ($httpCode == '404') {
            return null;
        }

        if ($httpCode != '200') {
            throw new \RuntimeException('API Return HTTP Code '.$httpCode.' : '.$responseString);
        }
        $responseData = json_decode($responseString, true);

        $this->previousAttributeValues = [];
        $this->data = $responseData['data'];

        return $this;
    }

    /**
     * Child classes are to override this method to determine what is the document path to send the http request to for the delete method.
     *
     * @return string the path
     */
    protected function resolveDeletePath()
    {
        return $this->path;
    }

    /**
     * Child classes are to override this method to determine what is the content of the body sent in the http request for the delete method.
     *
     * By default, does not send any data for the request body.
     *
     * @return mixed|null the desired data to send in the request body
     */
    protected function resolveDeleteBody()
    {
        return null;
    }

    /**
     * Provides a generic template for deleting a model. Will not work unless the child classes override the relevant methods.
     *
     * Will populate itself with updated values from the API, and then return itself.
     * If the endpoint sends a HTTP status 404, it will return NULL without modifying itself.
     *
     * @see Model::resolveDeletePath to change where the request is sent to
     * @see Model::resolveDeleteBody to change the payload data of the request
     *
     * @param mixed $curlOptions
     *
     * @return self|null returns itself with updated values, or NULL if not found
     */
    public function delete($curlOptions = [])
    {
        $path = $this->resolveDeletePath();
        $apiKey = $this->dashboardClient->apiKey ?? DashboardClient::$defaultApiKey;
        $apiKey = trim($apiKey);
        if ($apiKey == '') {
            throw new BadFieldException('API Key is missing!');
        }
        $endpoint = $path;
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$apiKey,
        ]);
        curl_setopt_array($ch, $this->dashboardClient->curlOptions ?? []);
        curl_setopt_array($ch, $curlOptions);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->resolveDeleteBody()));
        $responseString = curl_exec($ch);
        $error = curl_error($ch);
        // @codeCoverageIgnoreStart
        if ($error != '') {
            throw new \RuntimeException('Curl Error occurred: '.$error);
        }
        // @codeCoverageIgnoreEnd
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($httpCode != '200') {
            throw new \RuntimeException('API Return HTTP Code '.$httpCode.' : '.$responseString);
        } else {
            return true;
        }
    }

    /**
     * Child classes are to override this method to determine what is the document path to send the http request to for the delete method.
     *
     * @return string the path
     */
    protected function resolveUpdatePath()
    {
        return $this->path;
    }

    /**
     * Child classes are to override this method to determine what is the content of the body sent in the http request for the update method.
     *
     * By default, wraps itself in an associative array with index "data".
     *
     * @return mixed|null the desired data to send in the request body
     */
    protected function resolveUpdateBody()
    {
        return ['data' => $this];
    }

    /**
     * Provides a generic template for updating a model. Will not work unless the child classes override the relevant methods.
     *
     * Will populate itself with updated values from the API, and then return itself.
     * If the endpoint sends a HTTP status 404, it will return NULL without modifying itself.
     *
     * @see Model::resolveUpdatePath to change where the request is sent to
     * @see Model::resolveUpdateBody to change the payload data of the request
     *
     * @param mixed $curlOptions
     *
     * @return self|null returns itself with updated values, or NULL if not found
     */
    public function update($curlOptions = [])
    {
        $path = $this->resolveUpdatePath();
        $apiKey = $this->dashboardClient->apiKey ?? DashboardClient::$defaultApiKey;
        $apiKey = trim($apiKey);
        if ($apiKey == '') {
            throw new BadFieldException('API Key is missing!');
        }
        $endpoint = $path;
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$apiKey,
        ]);
        curl_setopt_array($ch, $this->dashboardClient->curlOptions ?? []);
        curl_setopt_array($ch, $curlOptions);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->resolveUpdateBody()));
        $responseString = curl_exec($ch);
        $error = curl_error($ch);
        // @codeCoverageIgnoreStart
        if ($error != '') {
            throw new \RuntimeException('Curl Error occurred: '.$error);
        }
        // @codeCoverageIgnoreEnd
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($httpCode != '200') {
            throw new \RuntimeException('API Return HTTP Code '.$httpCode.' : '.$responseString);
        }
        $responseData = json_decode($responseString, true);

        $this->previousAttributeValues = [];
        $this->data = $responseData['data'];

        return $this;
    }
}
