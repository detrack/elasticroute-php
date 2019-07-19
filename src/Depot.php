<?php

namespace Detrack\ElasticRoute;

class Depot implements \JsonSerializable
{
    /** @var string Name of the depot. Must be unique. */
    public $name;
    /** @var string Address of the depot. If the lat and lng are not given, the address will be used for geocoding. */
    public $address;
    /** @var string Postal code of the depot. */
    public $postal_code;
    /** @var float Latitude of the stop. Required if address is not given. */
    public $lat;
    /** @var float Longitude of the depot. Required if address is not given. */
    public $lng;
    /** @var bool Determines whether this would be the first depot that will automatically be used for vehicles and stops that do not have a depot specified. */
    public $default;

    public function __construct($data = [])
    {
        foreach ($data as $dataKey => $dataValue) {
            $this->$dataKey = $dataValue;
        }
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'default' => $this->default,
        ];
    }

    /**
     * Determines whether this list of depots are valid to be submitted to the Routing Engine API.
     *
     * @param mixed $depots          The array of depots, either as instances of the Depot class or as associative arrays
     * @param mixed $generalSettings The contextual generalSettings array to be submitted to the Routing Engine API
     *
     * @throws BadFieldException on validation failure, with exception message containing details of the validation error
     *
     * @return bool returns true on successful validation
     */
    public static function validateDepots($depots, $generalSettings = [])
    {
        //check depots
        //min:1
        if (count($depots) < 1) {
            throw new BadFieldException('You must have at least one depot');
        }
        foreach ($depots as $depot) {
            if ($depot instanceof self) {
                $depot = $depot->jsonSerialize();
            } elseif ($depot instanceof \stdClass) {
                $depot = json_decode(json_encode($depot), true);
            }
            //check depot.name
            //required
            if (!isset($depot['name']) || $depot['name'] === '') {
                throw new BadFieldException('Depot name cannot be null', $depot);
            }
            //max:255
            if (strlen($depot['name']) > 255) {
                throw new BadFieldException('Depot name cannot be more than 255 chars', $depot);
            }
            //distinct
            if (count(array_filter(array_column($depots, 'name'), function ($v) use ($depot) {
                return $v == $depot['name'];
            })) > 1) {
                throw new BadFieldException('Depot name must be distinct', $depot);
            }
            //check address/postcode/latlong
            if ((!isset($depot['lat']) || $depot['lat'] === '') || (!isset($depot['lng']) || $depot['lng'] === '')) {
                //if no coordinates found, check for address
                if (!isset($depot['address']) || $depot['address'] === '') {
                    //if no address found, check for postcode for supported countries
                    $validCountries = ['SG'];
                    if (!(isset($generalSettings['country']) && in_array($generalSettings['country'], $validCountries))) {
                        throw new BadFieldException('Depot address and coordinates are not given', $depot);
                    } else {
                        if (!isset($depot['postal_code']) || $depot['postal_code'] === '') {
                            throw new BadFieldException('Depot address and coordinates are not given, and postcode is not present', $depot);
                        }
                    }
                }
            }
        }

        return true;
    }
}
