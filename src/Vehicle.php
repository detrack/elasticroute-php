<?php

namespace Detrack\ElasticRoute;

class Vehicle implements \JsonSerializable
{
    public $depot;
    public $name;
    public $priority = 1;
    public $weight_capacity;
    public $volume_capacity;
    public $seating_capacity;
    public $buffer;
    public $avail_from;
    public $avail_till;
    public $return_to_depot = false;
    public $vehicle_types;

    public function __construct($data = [])
    {
        foreach ($data as $dataKey => $dataValue) {
            $this->$dataKey = $dataValue;
        }
    }

    public function jsonSerialize()
    {
        return [
            'depot' => $this->depot,
            'name' => $this->name,
            'priority' => $this->priority,
            'weight_capacity' => $this->weight_capacity,
            'volume_capacity' => $this->volume_capacity,
            'seating_capacity' => $this->seating_capacity,
            'buffer' => $this->buffer,
            'avail_from' => $this->avail_from,
            'avail_till' => $this->avail_till,
            'return_to_depot' => $this->return_to_depot,
            'vehicle_types' => $this->vehicle_types,
        ];
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
            if (count(array_filter(array_column($vehicles, 'name'), function ($v) use ($vehicle) {
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
