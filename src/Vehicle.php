<?php

namespace Detrack\ElasticRoute;

/**
 * @property string   $name            Name of the vehicle. Must be unique.
 * @property string   $depot           Name of the depot that the driver collects its goods from. Must correspond to a depot with the same name.
 * @property int      $priority        The priority of the vehicle. Higher priority ones will be dispatched first.
 * @property float    $weight_capacity Allows you to specify the maximum weight of goods this vehicle can carry
 * @property float    $volume_capacity Allows you to specify the maximum volume of goods this vehicle can carry
 * @property float    $seating_capcity Allows you to specify the number of human staff this vehicle can seat
 * @property int      $buffer          Allows you to specify the buffer time, the amount of travelling time in minutes to factor in for each additional stop.
 * @property int      $avail_from      The starting time where the vehicle is available, represented as 24hr time.
 * @property int      $avail_to        The ending time where the vehicle is available, represented as 24hr time.
 * @property string[] $vehicle_types   An array of strings representing what vehicle types (classes) this vehicle is classified under
 * @property bool     $return_to_depot Specify whether the vehicle requires to return to the depot. The time taken to return to depot will be considered if this field is set to true.
 * @property bool     $avail           DASHBOARD ONLY: Availability of the vehicle to be included for planning
 * @property bool     $avail_mon       DASHBOARD ONLY: Availability of the vehicle to be included for planning on Mondays
 * @property bool     $avail_tue       DASHBOARD ONLY: Availability of the vehicle to be included for planning on Tuesdays
 * @property bool     $avail_wed       DASHBOARD ONLY: Availability of the vehicle to be included for planning on Wednesdays
 * @property bool     $avail_thu       DASHBOARD ONLY: Availability of the vehicle to be included for planning on Thursdays
 * @property bool     $avail_fri       DASHBOARD ONLY: Availability of the vehicle to be included for planning on Fridays
 * @property bool     $avail_sat       DASHBOARD ONLY: Availability of the vehicle to be included for planning on Saturdays
 * @property bool     $avail_sun       DASHBOARD ONLY: Availability of the vehicle to be included for planning on Sundays
 * @property string[] $groups          DASHBOARD ONLY: Specifies an array of groups this vehicle belongs to; Only stops in the same group as the vehicle can be assigned to the vehicle. Used to separate your runs.
 * @property bool     $all_groups      DASHBOARD ONLY: Specifies whether this Vehicle is omnipotent and can serve all stops assigned to any group. Overrides $groups.
 * @property string[] $zones           DASHBOARD ONLY: Specifies an array of geofenced zones where this Vehicle can only serve stops within this geographical zones. Used for geofencing.
 * @property-read string   $created_at      DASHBOARD ONLY: UTC time when the stop was created, as seen in the dashboard
 * @property-read string   $updated_at      DASHBOARD ONLY: UTC time when the stop was updated, as seen in the dashboard
 */
class Vehicle implements \JsonSerializable
{
    /** @var array encapsulates the data of the model */
    protected $data = [
        'depot' => null,
        'name' => null,
        'priority' => 1,
        'weight_capacity' => null,
        'volume_capacity' => null,
        'seating_capacity' => null,
        'buffer' => null,
        'avail_from' => null,
        'avail_till' => null,
        'return_to_depot' => false,
        'vehicle_types' => null,
        // dashboard only
        'avail' => null,
        'avail_mon' => null,
        'avail_tue' => null,
        'avail_wed' => null,
        'avail_thu' => null,
        'avail_fri' => null,
        'avail_sat' => null,
        'avail_sun' => null,
        'zones' => null,
        'groups' => null,
    ];

    /** @var mixed[] keeps track of the immediate previous value of the attribute */
    protected $previousAttributeValues = [];

    public function __construct($data = [])
    {
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

    public function jsonSerialize()
    {
        $callback = function ($k, $v) {
            return !(is_null($v) && !array_key_exists($k, $this->previousAttributeValues));
        };

        return array_filter($this->data, $callback, ARRAY_FILTER_USE_BOTH);
    }

    public static function validateVehicles($vehicles)
    {
        //check vehicles
        //min:1
        if (count($vehicles) < 1) {
            throw new BadFieldException('You must have at least one vehicle');
        }
        foreach ($vehicles as $vehicle) {
            if ($vehicle instanceof self) {
                $vehicle = $vehicle->jsonSerialize();
            } elseif ($vehicle instanceof \stdClass) {
                $vehicle = json_decode(json_encode($vehicle), true);
            }
            //check vehicle.name
            //required
            if (!isset($vehicle['name']) || $vehicle['name'] === '') {
                throw new BadFieldException('Vehicle name cannot be null', $vehicle);
            }
            //max:255
            if (strlen($vehicle['name']) > 255) {
                throw new BadFieldException('Vehicle name cannot be more than 255 chars', $vehicle);
            }
            //distinct
            if (count(array_filter(array_map(function ($v) {
                return ($v instanceof self | $v instanceof \stdClass) ? $v->name : $v['name'];
            }, $vehicles), function ($v) use ($vehicle) {
                return $v == $vehicle['name'];
            })) > 1) {
                throw new BadFieldException('Vehicle name must be distinct', $vehicle);
            }

            //check numeric|min:0|nullable for the following:
            //weight_load, volume_load, seating_load, service_time
            $postiveNumericFields = ['weight_capacity', 'volume_capacity', 'seating_capacity'];
            foreach ($postiveNumericFields as $field) {
                if (isset($vehicle[$field])) {
                    if (!is_numeric($vehicle[$field])) {
                        throw new BadFieldException('Vehicle '.$field.' must be numeric', $vehicle);
                    }
                    if ((float) $vehicle[$field] < 0) {
                        throw new BadFieldException('Vehicle '.$field.' cannot be negative', $vehicle);
                    }
                }
            }
        }

        return true;
    }
}
