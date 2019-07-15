<?php

namespace Detrack\ElasticRoute;

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
                return ($v instanceof self | $v instanceof \stdClass) ? $v->name : $v["name"];
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
