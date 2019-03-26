[![Latest Stable Version](https://poser.pugx.org/detrack/elasticroute/v/stable)](https://packagist.org/packages/detrack/elasticroute)
[![Build Status](https://travis-ci.com/detrack/elasticroute-php.svg?branch=master)](https://travis-ci.com/detrack/elasticroute-php)
[![Coverage Status](https://coveralls.io/repos/github/detrack/elasticroute-php/badge.svg?branch=master)](https://coveralls.io/github/detrack/elasticroute-php?branch=master)
[![Total Downloads](https://poser.pugx.org/detrack/elasticroute/downloads)](https://packagist.org/packages/detrack/elasticroute)
[![License](https://poser.pugx.org/detrack/elasticroute/license)](https://packagist.org/packages/detrack/elasticroute)
# ElasticRoute for PHP
![ElasticRoute Logo](http://elasticroute.staging.wpengine.com/wp-content/uploads/2019/02/Elastic-Route-Logo-Text-on-right-e1551344046806.png)
### API for solving large scale travelling salesman/fleet routing problems

You have a fleet of just 10 vehicles to serve 500 spots in the city. Some vehicles are only available in the day. Some stops can only be served at night. How would you solve this problem?

You don't need to. Just throw us an array of stops, vehicles and depots and we will do the heavy lifting for you. *Routing as a Service!*

**PRE-RELEASE:**  Preview only, full service launches 27th March 2019

## Quick Start Guide
Install with composer:
```
composer require detrack/elasticroute
```
In your code, set your default API Key (this can be retrieved from the dashboard of the web application:)
```php
use Detrack\ElasticRoute\Plan;
Plan::$defaultApiKey = "my_super_secret_key";
```
Create a new `Plan` object and give it a name/id:
```php
$plan = new Plan();
$plan->id = "my_first_plan";
```
Give us an array of stops:
```php
$plan->stops = [
    [
        "name" => "Changi Airport",
        "address" => "80 Airport Boulevard (S)819642",
    ],
    [
        "name" => "Gardens By the Bay",
        "lat" => "1.281407",
        "lng" => "103.865770",
    ],
    // add more stops!
    // both human-readable addresses and machine-friendly coordinates work!
];
```
Give us an array of your available vehicles:
```php
$plan->vehicles = [
    [
        "name" => "Van 1"
    ],
    [
        "name" => "Van 2"
    ],
];
```
Give us an array of depots (warehouses):
```php
$plan->depots = [
    [
        "name" => "Main Warehouse",
        "address" => "61 Kaki Bukit Ave 1 #04-34, Shun Li Ind Park Singapore 417943",
    ],
];
```
Set your country and timezone (for accurate geocoding):
```php
$plan->generalSettings["country"] = "SG";
$plan->generalSettings["timezone"] = "Asia/Singapore";
```
Call `solve()` and save the result to a variable:
```php
$solution = $plan->solve();
```
Inspect the solution!
```php
foreach($solution->stops as $stop){
    // $stop is an instance of \Detrack\ElasticRoute\Stop, more details below
    print("Stop ".$stop->name." will be served by ".$stop->assign_to." at time".$stop->eta);
}
```
Quick notes:
- The individual stops, vehicles and depots can be passed into the `Plan` as either associative arrays or instances of `Detrack\ElasticRoute\Stop`, `Detrack\ElasticRoute\Vehicle` and `Detrack\ElasticRoute\Depot` respectively. Respective properties are the same as the associative array keys.
- Solving a plan returns you an instance of `Detrack\ElasticRoute\Solution`, that has mostly the same properties as `Detrack\ElasticRoute\Plan` but not the same functions (see advanced usage)
- Unlike when creating `Plan`'s, `Solution->stops|vehicles|depots` returns you instances of `Stop`, `Vehicle` and `Depot` accordingly instead of associative arrays.

## Advanced Usage
### Setting time constraints
Time constraints for Stops and Vehicles can be set with the `from` and `till` keys/properties:
```php
$morningOnlyStop = new Stop();
$morningOnlyStop->name  = "Morning Delivery 1";
$morningOnlyStop->from = 900;
$morningOnlyStop->till = 1200;
// add address and add to plan...
$morningShiftVan = new Vehicle();
$morningShiftVan->name = "Morning Shift 1";
$morningShiftVan->from = 900;
$morningShiftVan->till = 1200;
// add to plan and solve...
```
Dropping `from` and `till` keys from either class would result to it being defaulted to `avail_from` and `avail_to` keys in the `Plan->generalSettings` associative array, which in turn defaults to `590` and `1700`.
### Setting home depots
A "home depot" can be set for both Stops and Vehicles. A depot for stops indicate where a vehicle must pick up a stop's goods before arriving, and a depot for vehicles indicate the start and end point of a Vehicle's journey (this implicitly assigns the possible jobs a Vehicle can take).
By default, for every stop and vehicle, if the depot field is not specified we will assume it to be the first depot.
```php
$commonStop = new Stop();
$commonStop->name = "Normal Delivery 1"
$commonStop->depot = "Main Warehouse";
// set stop address and add to plan...
$rareStop = new Stop();
$rareStop->name = ""
$rareStop->depot = "Auxillary Warehouse";
// set stop address and add to plan...
$plan->vehicles = [
    [
        "name" => "Van 1",
        "depot" => "Main Warehouse",
    ],
    [
        "name" => "Van 2",
        "depot" => "Auxillary Warehouse",
    ],
];
$plan->depots = [
    [
        "name" => "Main Warehouse",
        "address" => "Somewhere",
    ],
    [
        "name" => "Auxillary Warehouse",
        "address" => "Somewhere else",
    ]
];
// solve and get results...
```
**IMPORTANT:** The value of the `depot` fields MUST correspond to a matching `Depot` in the same plan with the same name!

### Setting load constraints
Each vehicle can be set to have a cumulative maximum weight, volume and (non-cumulative) seating capacity which can be used to determine how many stops it can serve before it has to return to the depot. Conversely, each stop can also be assigned weight, volume and seating loads.
The fields are `weight_load`, `volume_load`, `seating_load` for Stops and `weight_capacity`, `volume_capacity` and `seating_capacity` for Vehicles.

### Alternative connection types (for large datasets)
By default, all requests are made in a *synchronous* manner. Most small to medium-sized datasets can be solved in less than 10 seconds, but for production uses you probably may one to close the HTTP connection first and poll for updates in the following manner:
```php
$plan = new Plan();
$plan->connectionType = "poll";
// do the usual stuff
$solution = $plan->solve();
while($solution->status != "planned"){
    $solution->refresh();
    sleep(2);
}
```
Setting the `connectionType` to `"poll"` will cause the server to return you a response immediately after parsing the request data. You can monitor the status with the `status` and `progress` properties while fetching updates with the `refresh()` method.

In addition, setting the `connectionType` to `"webhook"` will also cause the server to post a copy of the response to your said webhook. The exact location of the webhook can be specified with the `webhook` property of `Plan` objects.
