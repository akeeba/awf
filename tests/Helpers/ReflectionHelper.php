<?php
/**
 * @package        awf
 * @subpackage     tests.helpers
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Helpers;

/**
 * A helper class to interact with the objects of the system under test using Reflection
 */
class ReflectionHelper
{
	/**
	 * Helper method that gets a protected or private property in a class by relfection.
	 *
	 * @param   object  $object        The object from which to return the property value.
	 * @param   string  $propertyName  The name of the property to return.
	 *
	 * @return  mixed  The value of the property.
	 *
	 * @throws  \InvalidArgumentException if property not available.
	 */
	public static function getValue($object, $propertyName)
	{
		$mirror = new \ReflectionClass($object);

		// First check if the property is easily accessible.
		if ($mirror->hasProperty($propertyName))
		{
			$property = $mirror->getProperty($propertyName);
			$property->setAccessible(true);

			return $property->getValue($object);
		}

		// Hrm, maybe dealing with a private property in the parent class.
		if (get_parent_class($object))
		{
			$property = new \ReflectionProperty(get_parent_class($object), $propertyName);
			$property->setAccessible(true);

			return $property->getValue($object);
		}

		throw new \InvalidArgumentException(sprintf('Invalid property [%s] for class [%s]', $propertyName, get_class($object)));
	}

	/**
	 * Helper method that invokes a protected or private method in a class by reflection.
	 *
	 * Example usage:
	 *
	 * $this->assertTrue(TestReflection::invoke('methodName', $this->object, 123));
	 *
	 * @param   object  $object      The object on which to invoke the method.
	 * @param   string  $methodName  The name of the method to invoke.
	 *
	 * @return  mixed
	 */
	public static function invoke($object, $methodName)
	{
		// Get the full argument list for the method.
		$args = func_get_args();

		// Remove the method name from the argument list.
		array_shift($args);
		array_shift($args);

		$mirrorMethod = new \ReflectionMethod($object, $methodName);
		$mirrorMethod->setAccessible(true);

		$result = $mirrorMethod->invokeArgs(is_object($object) ? $object : null, $args);

		return $result;
	}

	/**
	 * Helper method that sets a protected or private property in a class by reflection.
	 *
	 * @param   object  $object        The object for which to set the property.
	 * @param   string  $propertyName  The name of the property to set.
	 * @param   mixed   $value         The value to set for the property.
	 *
	 * @return  void
	 */
	public static function setValue($object, $propertyName, $value)
	{
		$mirror = new \ReflectionClass($object);

		// First check if the property is easily accessible.
		if ($mirror->hasProperty($propertyName))
		{
			$property = $mirror->getProperty($propertyName);
			$property->setAccessible(true);

			$property->setValue($object, $value);
		}
		// Hrm, maybe dealing with a private property in the parent class.
		elseif (get_parent_class($object))
		{
			$property = new \ReflectionProperty(get_parent_class($object), $propertyName);
			$property->setAccessible(true);

			$property->setValue($object, $value);
		}
	}
} 