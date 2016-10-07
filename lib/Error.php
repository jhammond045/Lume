<?php

namespace Lume;

class Error extends \Exception
{
	public function __construct($message) {

		die($message);

	}
}
