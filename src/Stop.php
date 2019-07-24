<?php

namespace Detrack\ElasticRoute;

/**
 * Represents a Stop (destination) in your Plan. You must have a minimum of two stops in your Plan.
 *
 * @property string $vehicle_type Allows you to limit what vehicle types can serve this stop. There must be a Vehicle with a corresponding type in the same Plan.
 * @property string $depot        Allows you to specify which depot this stop's goods needs to be retrieved from first before it can be stored. This must correspond to a Depot name in the same Plan.
 * @property string $name         The name of the stop. Must be unique within the same Plan.
 * @property string $address      The address of the stop. If you do not specify coordinates, this field must be present.
 * @property string $postal_code  For countries with postcode systems that allow identifying buildings with only the postal code, you can pass only the postal code without address or coordinates.
 * @property float  $weight_load  Allows you to specify the weights of the goods to be served to this destination.
 * @property float  $volume_load  Allows you to specify the volume of the goods to be served to this destination.
 * @property float  $seating_load Allows you to specify pax service staff required to serve this destination.
 * @property float  $service_time Allows you to specify how long a vehicle is assumed to spend servicing this stop.
 * @property float  $lat          Allows you to specify coordinates for the stop instead of a human readable address. lng must also be specified.
 * @property float  $lng          Allows you to specify coordinates for the stop instead of a human readable address. lat must also be specified.
 * @property int    $from         Allows you to specify the earliest time this stop can be served. If left blank, will default to your service hours in General Settings.
 * @property int    $till         Allows you to specify the latest time this stop can be served. If left blank, will default to your service hours in General Settings
 * @property string $detrack_id   DASHBOARD ONLY: Detrack Job id for optional integration with the Detrack Dashboard, with one Job representing one Stop.
 * @property string $date         DASHBOARD ONLY: The date to save this stop to. Only applicable for CRUD operations on individual stops.
 * @property string $address_1    DASHBOARD ONLY: line addresses used as an alternative to address or lat/lng
 * @property string $address_2    DASHBOARD ONLY: line addresses used as an alternative to address or lat/lng
 * @property string $address_3    DASHBOARD ONLY: line addresses used as an alternative to address or lat/lng
 * @property string $country      DASHBOARD ONLY: line addresses used as an alternative to address or lat/lng
 * @property string $city         DASHBOARD ONLY: line addresses used as an alternative to address or lat/lng
 * @property string $state        DASHBOARD ONLY: line addresses used as an alternative to address or lat/lng
 * @property-read string $assign_to         The name of a Vehicle in the same Plan that has been assigned to serve this Stop.
 * @property-read int    $run               Run number, (the nth time the Vehicle returns to the depot and sets off again if there is not enough loading space or other constraints)
 * @property-read int    $sequence          Sequence number, the nth stop this Vehicle is serving in the same run.
 * @property-read string $eta               Date of estimated time of arrival represented in the following format: "YYYY-MM-DD HH:MM:SS"
 * @property-read string $exception         Note indicating if there was any error in trying to serve this stop. "Unserved" indicates that this stop is unsolvable within the problem constraints. "Unmapped" indicates that the address of this stop cannot be geocoded.
 * @property-read string $exception_reason  DASHBOARD ONLY: reason for unserved stop, as seen in the dashboard
 * @property-read string $plan_vehicle_type DASHBOARD ONLY: the vehicle being assigned to the stop, as seen in the dashboard
 * @property-read string $plan_depot        DASHBOARD ONLY: the depot being assigned to the stop, as seen in the dashboard
 * @property-read string $plan_service_time DASHBOARD ONLY: the value of service_time taken in planning the spot, as seen in the dashboard
 * @property-read string $mapped_at         DASHBOARD ONLY: UTC time when the address is ocnverted into its coressponding latitude and longitude, as seen in the dashboard
 * @property-read string $planned_at        DASHBOARD ONLY: UTC time when the stop was planned, as seen in the dashboard
 * @property-read string $created_at        DASHBOARD ONLY: UTC time when the stop was created, as seen in the dashboard
 * @property-read string $updated_at        DASHBOARD ONLY: UTC time when the stop was updated, as seen in the dashboard
 * @property-read bool   $sorted            DASHBOARD ONLY: Indicates whether the run of the stop is being manually resorted in the dashboard
 * @property-read bool   $violations        DASHBOARD ONLY: Indicates violations after the run has been manually resorted, e.g. if the sorted run exceeds the Time Window of the stop or the Vehicle Working Hours
 */
class Stop extends Model
{
    /** @var array encapsulates the data of the model */
    protected $data = [
        'detrack_id' => null,
        'vehicle_type' => null,
        'depot' => null,
        'group' => null,
        'date' => null,
        'name' => null,
        'time_window' => null,
        'address' => null,
        'address_1' => null,
        'address_2' => null,
        'address_3' => null,
        'postal_code' => null,
        'city' => null,
        'state' => null,
        'country' => null,
        'weight_load' => null,
        'volume_load' => null,
        'seating_load' => null,
        'service_time' => null,
        'from' => null,
        'till' => null,
        'assign_to' => null,
        'run' => null,
        'sequence' => null,
        'eta' => null,
        'lat' => null,
        'lng' => null,
        'exception' => null,
        'exception_reason' => null,
        'violations' => null,
        'sorted' => false,
        'plan_vehicle_type' => null,
        'plan_depot' => null,
        'plan_time_window' => null,
        'plan_service_time' => null,
    ];

    /**
     * Determines whether this list of stops are valid to be submitted to the Routing Engine API.
     *
     * @param mixed $stops           The array of stops, either as instances of the Stop class or as associative arrays
     * @param mixed $generalSettings The contextual generalSettings array to be submitted to the Routing Engine API
     *
     * @throws BadFieldException on validation failure, with exception message containing details of the validation error
     *
     * @return bool returns true on successful validation
     */
    public static function validateStops($stops, $generalSettings = [])
    {
        //check stop
        //min:2
        if (count($stops) < 2) {
            throw new BadFieldException('You must have at least two stops');
        }
        foreach ($stops as $stop) {
            if ($stop instanceof self) {
                $stop = $stop->jsonSerialize();
            } elseif ($stop instanceof \stdClass) {
                $stop = json_decode(json_encode($stop), true);
            }
            //check stop.name
            //required
            if (!isset($stop['name']) || $stop['name'] === '') {
                throw new BadFieldException('Stop name cannot be null', $stop);
            }
            //max:255
            if (strlen($stop['name']) > 255) {
                throw new BadFieldException('Stop name cannot be more than 255 chars', $stop);
            }
            //distinct
            if (count(array_filter(array_map(function ($s) {
                return ($s instanceof self | $s instanceof \stdClass) ? $s->name : $s['name'];
            }, $stops), function ($s) use ($stop) {
                return $s == $stop['name'];
            })) > 1) {
                throw new BadFieldException('Stop name must be distinct', $stop);
            }
            //check address/postcode/latlong
            if ((!isset($stop['lat']) || $stop['lat'] === '') || (!isset($stop['lng']) || $stop['lng'] === '')) {
                //if no coordinates found, check for address
                if (!isset($stop['address']) || $stop['address'] === '') {
                    //if no address found, check for postcode for supported countries
                    $validCountries = ['SG'];
                    if (!(isset($generalSettings['country']) && in_array($generalSettings['country'], $validCountries))) {
                        throw new BadFieldException('Stop address and coordinates are not given', $stop);
                    } else {
                        if (!isset($stop['postal_code']) || $stop['postal_code'] === '') {
                            throw new BadFieldException('Stop address and coordinates are not given, and postcode is not present', $stop);
                        }
                    }
                }
            }
            //check numeric|min:0|nullable for the following:
            //weight_load, volume_load, seating_load, service_time
            $postiveNumericFields = ['weight_load', 'volume_load', 'seating_load', 'service_time'];
            foreach ($postiveNumericFields as $field) {
                if (isset($stop[$field])) {
                    if (!is_numeric($stop[$field])) {
                        throw new BadFieldException('Stop '.$field.' must be numeric', $stop);
                    }
                    if ((float) $stop[$field] < 0) {
                        throw new BadFieldException('Stop '.$field.' cannot be negative', $stop);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Validates a list of Stops and asserts that their fields are suitable to be passed to the API in a dashboard context without error.
     *
     * @param Stop[] $stops a list of stops
     *
     * @throws BadFieldException on validation failure, with exception message containing details of the validation error
     *
     * @return bool returns true upon sucessful validation
     */
    public static function validateStopsForDashboard($stops)
    {
        foreach ($stops as $stop) {
            if ($stop instanceof self) {
                $stop = $stop->jsonSerialize();
            } elseif ($stop instanceof \stdClass) {
                $stop = json_decode(json_encode($stop), true);
            }
            //check stop.name
            //required
            if (!isset($stop['name']) || $stop['name'] === '') {
                throw new BadFieldException('Stop name cannot be null', $stop);
            }
            //max:255
            if (strlen($stop['name']) > 255) {
                throw new BadFieldException('Stop name cannot be more than 255 chars', $stop);
            }
            //distinct
            if (count(array_filter(array_map(function ($s) {
                return ($s instanceof self | $s instanceof \stdClass) ? $s->name : $s['name'];
            }, $stops), function ($s) use ($stop) {
                return $s == $stop['name'];
            })) > 1) {
                throw new BadFieldException('Stop name must be distinct', $stop);
            }
            //check address/postcode/latlong
            if ((!isset($stop['lat']) || $stop['lat'] === '') || (!isset($stop['lng']) || $stop['lng'] === '')) {
                //if no coordinates found, check for address
                if (!isset($stop['address']) || $stop['address'] === '') {
                    //if no address found, check for postcode for supported countries
                    $line1 = !isset($stop['address_1']) || $stop['address_1'] === '';
                    $line2 = !isset($stop['address_2']) || $stop['address_2'] === '';
                    $line3 = !isset($stop['address_3']) || $stop['address_3'] === '';
                    if ($line1 && $line2 && $line3) {
                        throw new BadFieldException('No form of address given for Stop', $stop);
                    }
                }
            }
            //check numeric|min:0|nullable for the following:
            //weight_load, volume_load, seating_load, service_time
            $postiveNumericFields = ['weight_load', 'volume_load', 'seating_load', 'service_time'];
            foreach ($postiveNumericFields as $field) {
                if (isset($stop[$field])) {
                    if (!is_numeric($stop[$field])) {
                        throw new BadFieldException('Stop '.$field.' must be numeric', $stop);
                    }
                    if ((float) $stop[$field] < 0) {
                        throw new BadFieldException('Stop '.$field.' cannot be negative', $stop);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Common path in the REST API.
     */
    protected static $path = 'stops';

    /**
     * Internal function for generating the internal prefix for all endpoints relating to single route CRUD.
     *
     * @return string the full path
     */
    protected function resolvePath()
    {
        $date = $this->previousAttributeValues['date'] ?? $this->date;

        return DashboardClient::$baseUrl.'/'.static::$path.'/'.$date;
    }

    /**
     * Overrides parent Model function to tell the model where to send the POST request for creating stops.
     *
     * @return string the full path
     */
    protected function resolveCreatePath()
    {
        return $this->resolvePath();
    }

    /**
     * Overrides parent Model function to tell the model what to send in the POST request body for creating stops.
     *
     * This will validate the stop before sending it.
     *
     * @throws BadFieldException upon validation error
     *
     * @return mixed associative array to send to json_encode
     */
    protected function resolveCreateBody()
    {
        static::validateStopsForDashboard([$this]);

        return parent::resolveCreateBody();
    }

    /**
     * Overrides parent Model function to tell the model where to send the GET request for retrieving stops.
     *
     * @return string the full path
     */
    protected function resolveRetrievePath()
    {
        $name = $this->name;

        return $this->resolvePath().'/'.rawurlencode($name);
    }

    /**
     * Overrides parent Model function to tell the model where to send the PUT request for updating stops.
     *
     * This will attempt to use the previous name if any, to allow the user to be able to change the name of the stop without creating a new model.
     * Because if the current name is used, it would create an additional stop.
     *
     * @return string the full path
     */
    protected function resolveUpdatePath()
    {
        $name = $this->previousAttributeValues['name'] ?? $this->name;

        return $this->resolvePath().'/'.rawurlencode($name);
    }

    /**
     * Overrides parent Model function to tell the model what to send in the PUT request for updating stops.
     *
     * This will validate the Stop before sending it.
     *
     * @throws BadFieldException upon validation error
     *
     * @return mixed associative array to send to json_encode
     */
    protected function resolveUpdateBody()
    {
        static::validateStopsForDashboard([$this]);

        return parent::resolveUpdateBody();
    }

    /**
     * Overrides parent Model function to tell the model what to send in the DELETE request for deleting stops.
     *
     * @return string the full path
     */
    protected function resolveDeletePath()
    {
        $name = $this->name;

        return $this->resolvePath().'/'.rawurlencode($name);
    }
}
