<?php

namespace Lume\Types;

class Types {} /* for autoloader */

class Type {

	public $sqlDefinition;

	public function getClassName() {

		return get_called_class();

	}

	public function getSqlDefinition($columnName) {

		return !empty($this->sqlDefinition) ? sprintf($this->sqlDefinition, $columnName) : null;

	}
	
	public function getSqlCondition($columnName, $value, \Lume\Connection &$link) {

		return "{$columnName} = '" . $link->esc($this->filterValueToDb($value)) . "'";

	}

	public function getDefaultValue() {

		return null;

	}

	public function filterValueToDb($value) {

		return $value;

	}

	public function filterValueFromDb($value) {

		return $value;

	}

}

class TypeOverrider extends Type {

	public function __construct($subType) {

		$this->subType = $subType;

	}

	public function getClassName() {

		return $this->subType->getClassName();

	}

	public function getSqlDefinition($columnName) {

		return $this->subType->getSqlDefinition($columnName);

	}

	public function getSqlCondition($columnName, $value, \Lume\Connection &$link) {

		return $this->subType->getSqlCondition($columnName, $value, $link);

	}

	public function filterValueFromDb($value) {

		return $this->subType->filterValueFromDb($value);

	}

	public function filterValueToDb($value) {

		return $this->subType->filterValueToDb($value);
		
	}

	public function getDefaultValue() {

		return $this->subType->getDefaultValue();
	}
}

class Index extends TypeOverrider {

	public function getSqlDefinition($columnName) {

		return parent::getSqlDefinition($columnName) . ", KEY ({$columnName})";

	}

}

class UniqueIndex extends TypeOverrider {

	public function getSqlDefinition($columnName) {

		return parent::getSqlDefinition($columnName) . ", UNIQUE KEY ({$columnName})";

	}

}

class String extends Type {

	public $sqlDefinition = '%s VARCHAR(%d) NOT NULL';

	public function __construct($length = 256) {

		$this->length = $length;

	}

	public function getSqlDefinition($columnName) {

		return sprintf($this->sqlDefinition, $columnName, $this->length);

	}

}

class Password extends String {

	public function __construct($length = null) {

		return parent::__construct(32);

	}

	public function filterValueToDb($value) {

		return strlen($value) != 32 ? md5($value . SALT) : $value;

	}
}

class Boolean extends Type {

	public $sqlDefinition = '%s BOOLEAN NOT NULL';

	public function filterValueToDb($value) {

		return $value ? 1 : 0;

	}

	public function filterValueFromDb($value) {

		return $value == 1;

	}
}

class Date extends Type {

	public $sqlDefinition = '%s DATE NOT NULL';

	public function filterValueToDb($value) {

		$value = strtotime($value);
		return $value > 0 ? date('Y-m-d', $value) : '0000-00-00';

	}
}

class Int extends Type {

	public $sqlDefinition = '%s INT UNSIGNED NOT NULL';

}

class Decimal extends Type {

	public $sqlDefinition = '%s DECIMAL(%d, %d) NOT NULL';

	public function __construct($precision = 10, $scale = 4) {

		$this->precision = $precision;
		$this->scale = $scale;

	}

	public function getSqlDefinition($columnName) {

		return sprintf($this->sqlDefinition, $columnName, $this->precision, $this->scale);

	}

}

class Join extends Type {

	public $sqlDefinition = '%1$s INT UNSIGNED NOT NULL, KEY (%1$s)';

	public function __construct($class) {

		if (!$class instanceof \Lume\Record) throw new Error("Expected class instance in constructor for " . $this->getClass());
		$this->joinClass = $class;

	}

	public function filterValueToDb($record) {

		if (empty($record->id)) {
			$record->save();
		}

		return $record->id;

	}

	public function filterValueFromDb($value) {

		$id = (int) $value;
		if (empty($id)) return null;

		$joinClass = $this->joinClass;
		return $joinClass::load($id);

	}

}

class NullType extends Type {

	public $sqlDefinition = null;

	public function getSqlCondition() {

		return null;

	}

}

class JoinSet extends NullType {

	public function __construct($class) {

		if (!$class instanceof \Lume\Record) throw new Error("Expected class instance in constructor for " . $this->getClass());
		$this->joinClass = $class;

	}

}

class Timestamp extends NullType {

	public $sqlDefinition = '%s TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';

}

class ID extends Type {

	public $sqlDefinition = '%s INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT';

}

class JoinedIds extends Type {

	public $sqlDefinition = '%1$s1 INT UNSIGNED NOT NULL, %1$s2 INT UNSIGNED NOT NULL, PRIMARY KEY (%1$s1, %1$s2)';

}
