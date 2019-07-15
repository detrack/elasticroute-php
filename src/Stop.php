<?php

namespace Detrack\ElasticRoute;

/**
 * Represents a Stop (destination) in your Plan. You must have a minimum of two stops in your Plan.
 *
 * @property-read string assign_to The name of a Vehicle in the same Plan that has been assigned to serve this Stop.
 * @property-read int run Run number, (the nth time the Vehicle returns to the depot and sets off again if there is not enough loading space or other constraints)
 * @property-read int sequence Sequence number, the nth stop this Vehicle is serving in the same run.
 * @property-read string eta Date of estimated time of arrival represented in the following format: "YYYY-MM-DD HH:MM:SS"
 * @property-read string exception Note indicating if there was any error in trying to serve this stop. "Unserved" indicates that this stop is unsolvable within the problem constraints.
 */
class Stop implements \JsonSerializable
{
    /** @var string Allows you to limit what vehicle types can serve this stop. There must be a Vehicle with a corresponding type in the same Plan. */
    public $vehicle_type;
    /** @var string Allows you to specify which depot this stop's goods needs to be retrieved from first before it can be stored. This must correspond to a Depot name in the same Plan. */
    public $depot;
    /** @var string|null RESERVED */
    public $group;
    /** @var string The name of the stop. Must be unique within the same Plan. */
    public $name;
    /** @var string The address of the stop. If you do not specify coordinates, this field must be present. */
    public $address;
    /** @var string For countries with postcode systems that allow identifying buildings with only the postal code, you can pass only the postal code without address or coordinates. */
    public $postal_code;
    /** @var float Allows you to specify the weights of the goods to be served to this destination. */
    public $weight_load;
    /** @var float Allows you to specify the volume of the goods to be served to this destination. */
    public $volume_load;
    /** @var float Allows you to specify pax service staff required to serve this destination. */
    public $seating_load;
    /** @var float Allows you to specify how long a vehicle is assumed to spend servicing this stop. */
    public $service_time;
    /** @var float Allows you to specify coordinates for the stop instead of a human readable address. lng must also be specified. */
    public $lat;
    /** @var float Allows you to specify coordinates for the stop instead of a human readable address. lat must also be specified. */
    public $lng;
    /** @var int Allows you to specify the earliest time this stop can be served. If left blank, will default to your service hours in General Settings. */
    public $from;
    /** @var int Allows you to specify the latest time this stop can be served. If left blank, will default to your service hours in General Settings */
    public $till;
    //readonlies, see class properties
    public $assign_to;
    public $run;
    public $sequence;
    public $eta;
    public $exception;

    public function __construct($data = [])
    {
        foreach ($data as $dataKey => $dataValue) {
            $this->$dataKey = $dataValue;
        }
    }

    public function jsonSerialize()
    {
        $returnArray = [
            'vehicle_type' => $this->vehicle_type,
            'depot' => $this->depot,
            'group' => $this->group,
            'name' => $this->name,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'weight_load' => $this->weight_load,
            'volume_load' => $this->volume_load,
            'seating_load' => $this->seating_load,
            'service_time' => $this->service_time,
            'assign_to' => $this->assign_to,
            'run' => $this->run,
            'sequence' => $this->sequence,
            'eta' => $this->eta,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'exception' => $this->exception,
            'from' => $this->from,
            'till' => $this->till,
        ];
        /*
        * HOTFIX
        * ISSUE: Server-side validation reports false negative when lat field is null but address field is present
        * CAUSE: Server correctly places valid coordinates at a higher prirority than address, but erroneously thinks that null is a valid coordinate
        * HOTFIX: On the client side, unset coordinates if coordinates is null
        **/
        if (is_null($returnArray['lat'])) {
            unset($returnArray['lat']);
        }
        if (is_null($returnArray['lng'])) {
            unset($returnArray['lng']);
        }
        /* HOTFIX END **/
        return $returnArray;
    }

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
            if (count(array_filter(array_column($stops, 'name'), function ($v) use ($stop) {
                return $v == $stop['name'];
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
}
