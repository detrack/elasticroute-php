[![Latest Stable Version](https://poser.pugx.org/detrack/elasticroute/v/stable)](https://packagist.org/packages/detrack/elasticroute)
[![Build Status](https://travis-ci.com/detrack/elasticroute-php.svg?branch=master)](https://travis-ci.com/detrack/elasticroute-php)
[![Coverage Status](https://coveralls.io/repos/github/detrack/elasticroute-php/badge.svg?branch=master)](https://coveralls.io/github/detrack/elasticroute-php?branch=master)
[![Total Downloads](https://poser.pugx.org/detrack/elasticroute/downloads)](https://packagist.org/packages/detrack/elasticroute)
[![License](https://poser.pugx.org/detrack/elasticroute/license)](https://packagist.org/packages/detrack/elasticroute)

# ElasticRoute for PHP

![ElasticRoute Logo](http://elasticroute.staging.wpengine.com/wp-content/uploads/2019/02/Elastic-Route-Logo-Text-on-right-e1551344046806.png)

### Notice

> With effect from 02 January 2021, we will deprecate support for this client library and there will be no future updates. If you are currently using the client library, the integrations done based on it should still be able to work. Moving forward, we recommend the use of our API documentation ([Dashboard](https://www.elasticroute.com/dashboard-api-documentation/) and [Routing Engine](https://www.elasticroute.com/routing-engine-api-documentation/)) to build your integration.


### API for solving large scale travelling salesman/fleet routing problems

You have a fleet of just 10 vehicles to serve 500 spots in the city. Some vehicles are only available in the day. Some stops can only be served at night. How would you solve this problem?

You don't need to. Just throw us an array of stops, vehicles and depots and we will do the heavy lifting for you. _Routing as a Service!_

**BETA RELASE:**  ElasticRoute is completely free-to-use until 30th April 2021!

## Preface

ElasticRoute offers two APIs depending on your needs, and different sections of this documentation are relevant to you depending on which API you wish to interact with:

-   **Routing Engine API** – if you already have your own fleet management system and you only wish to use ElasticRoute to solve the routing problem and inspect the solution. This is effectively **using ElasticRoute in a headless environment**, a.k.a. "routing as a service".
-   **Dashboard API** – if your team uses the ElasticRoute web application to review stops and vehicles on a map, and you wish to **push data from your existing applications to the ElasticRoute dashboard**.

Regardless of how you use ElasticRoute, this client library is capable of interacting with both services.

## Quick Start Guide (Routing Engine API)

> Refer to this section if you have a separate application to display the results on a map, table or whichever method you prefer, as long as you are not using the ElasticRoute dashboard as-is.

Install with composer:

    composer require detrack/elasticroute

In your code, set your default API Key (this can be retrieved from the dashboard of the web application):

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

-   The individual stops, vehicles and depots can be passed into the `Plan` as either associative arrays or instances of `Detrack\ElasticRoute\Stop`, `Detrack\ElasticRoute\Vehicle` and `Detrack\ElasticRoute\Depot` respectively. Respective properties are the same as the associative array keys.
-   Solving a plan returns you an instance of `Detrack\ElasticRoute\Solution`, that has mostly the same properties as `Detrack\ElasticRoute\Plan` but not the same functions (see advanced usage)
-   Unlike when creating `Plan`'s, `Solution->stops|vehicles|depots` returns you instances of `Stop`, `Vehicle` and `Depot` accordingly instead of associative arrays.
-   The Stops created by this method (attached to a `Detrack\ElasticRoute\Plan` object) **cannot** be seen in the dashboard of the ElasticRoute web application. Please refer to the Dashboard API.

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

Dropping `from` and `till` keys from either class would result to it being defaulted to `avail_from` and `avail_to` keys in the `Plan->generalSettings` associative array, which in turn defaults to `500` and `1700`.

### Setting home depots

A "home depot" can be set for both Stops and Vehicles. A depot for stops indicate where a vehicle must pick up a stop's goods before arriving, and a depot for vehicles indicate the start and end point of a Vehicle's journey (this implicitly assigns the possible jobs a Vehicle can take).
By default, for every stop and vehicle, if the depot field is not specified we will assume it to be the first depot.

```php
$commonStop = new Stop();
$commonStop->name = "Normal Delivery 1";
$commonStop->depot = "Main Warehouse";
// set stop address and add to plan...
$rareStop = new Stop();
$rareStop->name = "Uncommon Delivery 1";
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

By default, all requests are made in a _synchronous_ manner. Most small to medium-sized datasets can be solved in less than 10 seconds, but for production uses you probably may one to close the HTTP connection first and poll for updates in the following manner:

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

## Quick Start Guide (Dashboard API)

> Refer to this section if you wish to push data to the Dashboard API that other users in your organisation can review.

If you haven't already, install the library:

    composer require detrack/elasticroute

The service revolves around the `Detrack\ElasticRoute\DashboardClient` object. It is responsible for pushing your `Detrack\ElasticRoute\Stop` and `Detrack\ElasticRoute\Vehicle` to the ElasticRoute Dashboard where your team can review and edit.

Set the default API Key (retrieved from the Dashboard, the same key can be used for both the Routing Engine API and the Dashboard API):

```php
use Detrack\ElasticRoute\DashboardClient;

DashboardClient::$defaultApiKey = "your-super-secret-key";
```

Next, you can create a list of stops, either as associative arrays or instances of the `Detrack\ElasticRoute\Stop` object:

```php
use Detrack\ElasticRoute\Stop;

$stop1 = [
    'name' => 'SUTD',
    'address' => '8 Somapah Road Singapore 487372',
];
$stop2 = [
    'name' => 'Changi Airport',
    'address' => '80 Airport Boulevard (S)819642',
],
$stop3 = new Stop();
$stop3->name = 'Gardens By the Bay';
$stop3->address = '18 Marina Gardens Drive Singapore 018953';
$stop4 = new Stop();
$stop4->name = 'Singapore Zoo';
$stop4->address = '80 Mandai Lake Road Singapore 729826';

$stops = [$stop1, $stop2, $stop3, $stop4];
```

Push them to the dashboard using the `uploadStopsOnDate` method:

```php
$client = new DashboardClient();
$client->uploadStopsOnDate($stops, date('Y-m-d'));
```

This would set today's dashboard to show these stops. Use the second argument to pass a string under the `YYYY-MM-DD` format to change the date you want to upload these stops under.

To retrieve existing stops on the dashboard, use the `listAllStopsOnDate` method:

```php
$stops = $client->listAllStopsOnDate(date('Y-m-d'));
```

By default, this would list 100 stops on that date. Pass a second parameter to denote how many you wish to retrieve:

```php
$stops = $client->listAllStopsOnDate(date('Y-m-d'), 50);
```

And a third parameter for pagination:

```php
$stops = $client->listAllStopsOnDate(date('Y-m-d'), 50, 2);
```

To delete all stops on a date, use the `deleteAllStopsOnDate` method:

```php
$client->deleteAllStopsOnDate(date('Y-m-d'));
```

The `Stop` object also has methods to do CRUD operations on individual stops:

```php
$stop = new Stop();
$stop->client = $client; // instance of Detrack\ElasticRoute\DashboardClient
$stop->name = "Test Stop";
$stop->address = "8 Somapah Road";
$stop->date = date('Y-m-d'); // the date must be present if CRUDing individual stops

// create – will throw error if a stop with the same name already exists on the same date
$stop->create();

// read – will return null if a stop with specified name is not found on date (but the object itself is not changed)
$stop->retrieve();

// update – will throw error if a stop with the specified name is not found on date
$stop->name = "Singapore Zoo";
$stop->address = "80 Mandai Lake Road Singapore 729826";
$stop->update();

// delete – will throw error if a stop with the specified name is not found on date
$stop->delete();
```

### Vehicles

Vehicles can also be pushed to the Dashboard (found under settings in the Dashboard) using the `uploadVehicles` method:

```php
use Detrack\ElasticRoute\Vehicle;

$vehicle1 = [
	'name' => 'Van 1',
]

$vehicle2 = new Vehicle();
$vehicle2->name = 'Van 1';

$vehicles = [$vehicle1, $vehicle2];

$client->uploadVehicles($vehicles);
```

The `Vehicle` object also has methods to do CRUD operations on individual stops:

```php
$vehicle = new Vehicle();
$vehicle->client = $client; // instance of Detrack\ElasticRoute\DashboardClient
$vehicle->name = "Test Van";
$vehicle->avail_from = 900;
$vehicle->avail_till = 1500;

// create – will throw error if a vehicle with the same name already exists on the same account
$vehicle->create();

// read – will return null if a vehicle with specified name is not found (but the object itself will not be changed)
$vehicle->retrieve();

// update – will throw error if a vehicle with the specified name is not found on the same account
$vehicle->avail_till = 1700;
$vehicle->update();

// delete – will throw error if a stop with the specified name is not found on date
$vehicle->delete();
```

### Planning

The planning process for a given day/date can be started with the `startPlanningOnDate` method:

```php
$client->startPlanningOnDate(date('Y-m-d'));
```

This would automatically start the planning process on the Dashboard. Note that unlike the Routing Engine API, you **cannot** inspect the results of the route plan through the Dashboard API, you or your team must use the ElasticRoute web application to open the Dashboard to inspect the solution.
