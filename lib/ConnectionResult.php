<?php

namespace Lume;

class ConnectionResult
{
	public $handle;

	public function __construct($handle) {

		$this->handle = $handle;

	}

	public function result($index = 0) {

		return $this->handle ? mysql_result($this->handle, $index) : null;

	}

	public function fetchObject() {

		return $this->handle ? mysql_fetch_object($this->handle) : null;

	}

	public function fetchAll() {

		$rows = array();
		while ($row = mysql_fetch_object($this->handle)) {
			$rows[] = $row;
		}

		return $rows;

	}

}
