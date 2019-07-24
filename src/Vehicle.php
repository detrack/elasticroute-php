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
 * @property int      $avail_till      The ending time where the vehicle is available, represented as 24hr time.
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
class Vehicle extends Model
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

    /**
     * Determines whether this list of vehicles are valid to be submitted to the Routing Engine API.
     *
     * @param mixed $vehicles        The array of vehicles, either as instances of the Vehicle class or as associative arrays
     * @param mixed $generalSettings The contextual generalSettings array to be submitted to the Routing Engine API
     *
     * @throws BadFieldException on validation failure, with exception message containing details of the validation error
     *
     * @return bool returns true on successful validation
     */
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

    /**
     * Common path in the REST API.
     */
    protected static $path = 'vehicles';

    /**
     * Internal function for generating the internal prefix for all endpoints relating to single route CRUD.
     *
     * @return string the full path
     */
    protected function resolvePath()
    {
        return DashboardClient::$baseUrl.'/'.static::$path;
    }

    /**
     * Overrides parent Model function to tell the model where to send the POST request for creating vehicles.
     *
     * @return string the full path
     */
    protected function resolveCreatePath()
    {
        return $this->resolvePath();
    }

    /**
     * Child classes are to override this method to determine what is the document path to send the http request to for the retrieve method.
     *
     * @return string the path
     */
    protected function resolveRetrievePath()
    {
        return $this->resolvePath().'/'.rawurlencode($this->name);
    }

    /**
     * Overrides parent Model function to tell the model where to send the PUT request for updating vehicles.
     *
     * This will attempt to use the previous name if any, to allow the user to be able to change the name of the stop without creating a new model.
     * Because if the current name is used, it would create an additional vehicle.
     *
     * @return string the full path
     */
    protected function resolveUpdatePath()
    {
        $name = $this->previousAttributeValues['name'] ?? $this->name;

        return $this->resolvePath().'/'.rawurlencode($name);
    }

    /**
     * Overrides parent Model function to tell the model what to send in the DELETE request for deleting vehicles.
     *
     * @return string the full path
     */
    protected function resolveDeletePath()
    {
        return $this->resolvePath().'/'.rawurlencode($this->name);
    }
}
