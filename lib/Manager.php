<?php

namespace Lume;

class Manager
{
	public static $connection;
	public static $classAliases = array();
	public static $prototypes = array();

	public static function setConnection(Connection $connection = null) {

		self::$connection = $connection;

	}

	public static function buildNamespace($namespace) {

		$classList = get_declared_classes();
		$classBase = '\\' . __NAMESPACE__ . '\Record';
		$classes = array();

		Debug::msg("Building namespace {$namespace}");

		foreach ($classList as $className) {
			if (substr($className, 0, strlen($namespace) + 1) == "{$namespace}\\") {
				if (is_subclass_of($className, $classBase)) {
					$classes[$className] = substr($className, strlen($namespace) + 1);

					Debug::msg("Autodiscovered {$className}");
				}
			}
		}

		foreach ($classes as $fullClass => $classAlias)
		{
			$prototype = new $fullClass();
			
			$prototype->basePrototype();
			$prototype->prototype();

			$prototype->mod_date = new Types\Timestamp;

			foreach ($prototype as $property => $definition) {
				if (!$prototype->isProperty($property)) continue;

				if (!is_object($definition) || $definition instanceOf Types\Type == false) throw new Error("Property {$property} in class {$fullClass} must be a Types\Type instance");
			}

			$prototype->_connection = self::$connection;

			self::$prototypes[$fullClass] = $prototype;
			self::$classAliases[$fullClass] = $classes[$fullClass];
		}

	}

	public static function assemble() {

		foreach (self::$classAliases as $fullClass => $class) {
			$fullClass::assemble();
		}

	}

	public static function getPrototype($fullClass) {

		return !empty(self::$prototypes[$fullClass]) ? self::$prototypes[$fullClass] : null;

	}

	public static function getClassAlias($fullClass) {

		return self::$classAliases[$fullClass];

	}

}
