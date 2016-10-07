<?php

// Relative path to Lume libraries
define('LIBRARY_PATH', '../lib/');

// Simple autoloader for Lume libraries
function __autoload($class_name) {

	require_once LIBRARY_PATH . str_replace('\\', '/', $class_name) . '.php';
	
}

// Load our type definitions and our app objects (in MyObjects namespace)
require LIBRARY_PATH . 'Lume/Types.php';
require 'MyObjects.php';

// Make a connection to the MySQL database
$connection = new \Lume\Connection('127.0.0.1', 'my_username', 'MyPassword#1', 'my_database');

// Rebuild our tables from scratch on each run.
$connection->rebuild = true;

// Assign connection.
\Lume\Manager::setConnection($connection);

// Introspect our namespace and collect objects.
\Lume\Manager::buildNamespace('MyObjects');

// Set up and build SQL tables.
\Lume\Manager::assemble();

// Create some test objects.
$myManufacturer = new \DB\Manufacturer;
$myManufacturer->name = 'Manufacturer1';

$myModel = new \DB\Model;
$myModel->name = 'Model A';

$myGeometry = new \DB\Geometry;

for ($i = 0; $i < 10; $i++) {
	$myMeasurement = new \DB\Measurement;
	$myMeasurement->name = 'Measurement ' + $i;
	$myMeasurement->value = rand(0, 100);

	$myGeometry->Measurement[] = $myMeasurement;
}

// Link everything together.
$myWidget = new \DB\Widget;
$myWidget->Manufacturer = $myManufacturer;
$myWidget->Model = $myModel;
$myWidget->Geometry = $myGeometry;
$myWidget->is_active = true;

// Save the Widget and all linked objects.
$myWidget->save();
