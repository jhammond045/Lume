<?php

namespace Lume;

class Record
{
	public function __construct() {

		$prototype = self::getPrototype();
		if ($prototype) {
			foreach ($prototype as $property => $definition) {
				if (!$prototype->isProperty($property)) continue;

				$this->$property = $definition->getDefaultValue();
			}
		}

	}
	public function basePrototype() {

		$this->id = new Types\ID;

	}

	public static function instance() {

		$className = self::getClassName();
		return new $className;

	}

	public static function load($accessor) {

		if (is_integer($accessor)) {
			return self::loadById($accessor);
		} else if (is_array($accessor)) {
			return self::loadByArray($accessor);
		}

	}

	public static function loadSet($accessor) {

		return self::load($accessor);

	}

	public static function loadById($id) {

		$prototype = self::getPrototype();
		$where = $prototype->id->getSqlCondition('id', $id, self::getConnection());

		return self::loadByWheres(array($where));

	}

	public static function loadByArray($args) {

		$prototype = self::getPrototype();
		$wheres = array();

		foreach ($args as $key => $value) {
			if (!isset($prototype->$key)) throw new Error("Can't look up by nonexistent property {$key}");

			$wheres[] = $prototype->$key->getSqlCondition($key, $value, self::getConnection());
		}

		return self::loadByWheres($wheres);

	}

	private static function loadByWheres($wheres) {

		$prototype = self::getPrototype();
		$connection = self::getConnection();
		$tableName = self::getTableName();

		$sql = "SELECT * FROM {$tableName} WHERE (" . implode(") AND (", $wheres) . ")";
		$rows = $connection->query($sql)->fetchAll();

		$records = array();
		foreach ($rows as $row) {
			$recordClass = self::getClass();
			$record = new $recordClass;

			foreach ($row as $field => $value) {
				if (!isset($prototype->$field)) throw new Error("Can't set value for nonexistent property {$field}");
				$record->$field = $prototype->$field->filterValueFromDb($value);
			}

			$records[$record->id] = $record;
		}

		if (count($records) == 0) return null;
		if (count($records) == 1) return reset($records);
		return $records;

	}

	public static function assemble() {

		$myClass = self::getClass();
		Debug::msg("Self-assembly for {$myClass}");

		$prototype = self::getPrototype();

		if (empty($prototype)) {
			throw new Error("Class {$myClass} has no assembled prototype");
		}

		$tableName = self::getTableName();

		if (self::getConnection()->tableExists($tableName) == false) {
			self::createTable();
		}

		foreach ($prototype as $property => $definition) {
			if (!$prototype->isProperty($property)) continue;

			if ($definition instanceof Types\JoinSet) {
				$tableName = self::getJoinTableName($definition->joinClass->getClassName());

				if (self::getConnection()->tableExists($tableName) == false) {
					self::createJoinTable($tableName);
				}
			}
		}

	}

	public static function getClass() {

		return get_called_class();

	}

	public static function getPrototype() {

		return Manager::getPrototype(self::getClass());

	}

	public static function getConnection() {

		return self::getPrototype()->_connection;

	}

	public static function getTableName() {

		return self::getConnection()->tablePrefix . str_replace('\\', '_', self::getClass());

	}

	public static function getJoinTableName($joinClass) {
		
		return self::getTableName() . '_' . Manager::getClassAlias($joinClass);

	}
	
	public static function createTable() {

		$tableName = self::getTableName();
		$prototype = self::getPrototype();

		$columns = array();

		foreach ($prototype as $property => $definition) {
			if (!$prototype->isProperty($property)) continue;

			if ($columnDef = $definition->getSqlDefinition($property)) {
				$columns[] = $columnDef;
			}
		}

		self::getConnection()->createTable($tableName, $columns);

	}

	public static function createJoinTable($tableName) {

		$joinedIds = new Types\JoinedIds;

		self::getConnection()->createTable($tableName, array($joinedIds->getSqlDefinition('id')));

	}

	public function isProperty($property) {

		return substr($property, 0, 1) != '_';

	}

	public function getClassName() {

		return get_called_class();

	}

	public function save() {

		$tableName = self::getTableName();
		$keyValues = array();

		$prototype = self::getPrototype();
		$connection = self::getConnection();

		foreach ($prototype as $property => $definition) {
			if (!$this->isProperty($property)) continue;

			$condition = $definition->getSqlCondition($property, $this->$property, $connection);
			if (!empty($condition)) $keyValues[$property] = $condition;
		}

		$this->id = (int) $this->id;

		Debug::msg("Saving record to {$tableName}");

		if ($this->id) {
			$connection->query("UPDATE {$tableName} SET " . implode(',', $keyValues) . " WHERE id = {$this->id}");
		}

		else {
			unset($keyValues['id']);
			$connection->query("INSERT INTO {$tableName} " . (count($keyValues) > 0 ? "SET " . implode(',', $keyValues) : " VALUES()"));
			$this->id = $connection->lastInsertId();
		}

		foreach ($prototype as $property => $definition) {
			if (!$prototype->isProperty($property)) continue;

			if ($definition instanceof Types\JoinSet) {
				$joinTableName = self::getJoinTableName($definition->joinClass->getClassName());
				$connection->query("DELETE FROM {$joinTableName} WHERE id1 = {$this->id}");

				foreach ($this->$property as $setItem) {
					if (!($setItem instanceof Record)) throw new Error("Unexpected value in field set when saving in set table {$joinTableName}");

					if (empty($setItem->id)) {
						$setItem->save();
					}

					if ($id = (int) $setItem->id) {
						$connection->query("INSERT INTO {$joinTableName} SET id1 = {$this->id}, id2 = {$id}");
					}
				}
			}
		}

	}
	
}
