<?php

namespace Lume;

class Connection
{
	public $handle = null;
	public $rebuild = false;
	public $tablePrefix = '';

	public function __construct($host, $username, $password, $database) {

		$this->handle = mysql_connect($host, $username, $password);
		if (!$this->handle) throw new Error("Can't connect to database at {$host}");

		if (!mysql_select_db($database, $this->handle)) throw new Error("Can't select database {$database} on {$host}");

	}

	public function query($q) {

		$handle = mysql_query($q, $this->handle);
		if ($errorText = mysql_error($this->handle)) throw new Error("Database error: {$errorText} [[{$q}]]");

		return new ConnectionResult($handle);

	}

	public function esc($string) {

		return mysql_real_escape_string($string, $this->handle);

	}

	public function tableExists($tableName) {

		return $this->rebuild ? false : $this->query("SHOW TABLES LIKE '{$tableName}'")->result() ? true : false;

	}

	public function createTable($tableName, $columns) {

		Debug::msg("Creating table {$tableName}");
		$this->query("DROP TABLE IF EXISTS {$tableName}");
		$this->query("CREATE TABLE {$tableName} (" . implode(',', $columns) . ')');

	}

	public function lastInsertId() {

		return mysql_insert_id($this->handle);

	}

}
