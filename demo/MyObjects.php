<?php

namespace MyObjects;

use \Lume\Record;
use \Lume\Types\Int;
use \Lume\Types\String;
use \Lume\Types\Password;
use \Lume\Types\Boolean;
use \Lume\Types\Date;
use \Lume\Types\Decimal;

use \Lume\Types\Index;
use \Lume\Types\UniqueIndex;

use \Lume\Types\Join;
use \Lume\Types\JoinSet;

class Widget extends Record
{
	public function prototype() {
		
		$this->Manufacturer = new Join(new Manufacturer);
		$this->Model = new Join(new Model);
		$this->Geometry = new Join(new Geometry);
		$this->is_active = new Boolean;
		$this->create_date = new Date;

	}
}

class Manufacturer extends Record
{
	public function prototype() {

		$this->name = new UniqueIndex(new String);
		$this->website = new String;

	}
}

class Model extends Record
{
	public function prototype() {

		$this->name = new UniqueIndex(new String);
		$this->description = new String;

	}
}

class Geometry extends Record
{
	public function prototype() {

		$this->Measurement = new JoinSet(new Measurement);

	}
}

class Measurement extends Record
{
	public function prototype() {

		$this->name = new String;
		$this->value = new Decimal;

	}
}
