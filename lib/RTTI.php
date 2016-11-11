<?php
namespace phputil;

/**
 * Run time type information utilities.
 *
 * @author	Thiago Delgado Pinto
 */
class RTTI {
	
	const IS_PRIVATE	= \ReflectionProperty::IS_PRIVATE;
	const IS_PROTECTED	= \ReflectionProperty::IS_PROTECTED;
	const IS_PUBLIC		= \ReflectionProperty::IS_PUBLIC;
	
	/**
	 * Return all the visibility flags.
	 * @return int
	 */
	static function allFlags() { 
		return \ReflectionProperty::IS_PRIVATE
			|  \ReflectionProperty::IS_PROTECTED
			|  \ReflectionProperty::IS_PUBLIC;
	}
	
	/**
	 *  Just a synonym to allFlags()
	 * @return int
	 */
	static function anyVisibility() {
		return self::allFlags();
	}

	/**
	 *  Retrieve names and values from the attributes of a object, as a map.
	 *  
	 *  @param object $obj					The object.
	 *  
	 *  @param int $visibilityFlags 		Filter visibility flags. Can be added.
	 *  									Example: RTTI::IS_PRIVATE | RTTI::IS_PROTECTED
	 *  									Optional, defaults to RTTI::allFlags().
	 *  
	 *  @param string $getterPrefix			The prefix for getter public methods.
	 *  									Default is 'get'.
	 *  
	 *  @param bool	$useCamelCase			If true, private and protected attributes will
	 *  									be accessed by camelCase public methods.
	 *  									Default is true.
	 *  
	 *  @param bool $convertInternalObjects	If true, converts internal objects.
	 *  									Default is false.
	 *  
	 *  @return array
	 *
	 *  @throws ReflectionException
	 */
	static function getAttributes(
		$obj
		, $visibilityFlags = null
		, $getterPrefix = 'get'
		, $useCamelCase = true
		, $convertInternalObjects = false
		) {
		if ( ! isset( $obj ) ) {
			return array();
		}
		$flags = null === $visibilityFlags ? self::allFlags() : $visibilityFlags;
		$attributes = array();
		$reflectionObject = new \ReflectionObject( $obj );
		$currentClass = new \ReflectionClass( $obj );
		
		while ( $currentClass !== false && ! $currentClass->isInterface() ) {	
		
			$properties = $currentClass->getProperties( $flags );
				
			foreach ( $properties as $property ) {
				
				$attributeName = $property->getName();
				$methodName = $getterPrefix .
					( $useCamelCase ? self::mb_ucfirst( $attributeName ) : $attributeName );
				
				if ( $property->isPrivate() || $property->isProtected() ) {
				
					if ( $reflectionObject->hasMethod( $methodName ) ) {
						
						$method = $reflectionObject->getMethod( $methodName );
						if ( $method->isPublic() ) {
							$attributes[ $attributeName ] = $method->invoke( $obj );
						}
					} else { // maybe has a __call magic method
						$attributes[ $attributeName ] = $obj->{ $methodName }();
					}
					
				} else { // public method
					
					try {
						$attributes[ $attributeName ] = $obj->{ $attributeName };
					} catch ( \Exception $e ) {
						// Ignore
					}
				}
				
			}
			
			// No properties? -> try to retrieve public properties
			if ( count( $properties ) < 1 ) {
				$properties = get_object_vars( $obj );
				foreach ( $properties as $k => $v ) {
					$attributes[ $k ] = $v;
				}
			}
			
			$currentClass = $currentClass->getParentClass();
		}
		
		if ( $convertInternalObjects ) {
			// Analyse all internal objects
			foreach ( $attributes as $key => $value ) {
				if ( is_object( $value ) ) {
					$attributes[ $key ] =
						self::getAttributes( $value, $flags, $getterPrefix, $useCamelCase );
				}
			}
		}
		
		return $attributes;
	}		
	
	/**
	 * Retrieve names and values from the private attributes of a object, as a map.
	 * This method has been kept for backward compatibility.
	 * 
	 * @param object	$obj				The object.
	 * @param string	$getterPrefix		The prefix for getter public methods (defaults to 'get').
	 * @param bool		$useCamelCase		If true, private attributes will be accessed by camelCase public methods (default true).
	 * @return array
	 */
	static function getPrivateAttributes( $obj, $getterPrefix = 'get', $useCamelCase = true ) {
		return self::getAttributes( $obj, self::IS_PRIVATE, $getterPrefix, $useCamelCase );
	}
	
	/**
	 * Set the attribute values of a object.
	 * 
	 * @param array		$map				A map with the attribute names and values to be changed.
	 *
	 * @param object	$obj				The object to be changed.
	 *
	 * @param int		$visibilityFlags 	Filter visibility flags. Can be added.
	 * 										Example: RTTI::IS_PRIVATE | RTTI::IS_PROTECTED
	 * 										Optional, defaults to RTTI::allFlags.
	 *
	 * @param string	$setterPrefix		The prefix for setter public methods (defaults to 'set').
	 *
	 * @param bool		$useCamelCase		If true, private and protected attributes will be set by
	 *										camelCase public methods (default true).
	 */	
	static function setAttributes(
		array $map
		, &$obj
		, $visibilityFlags = null
		, $setterPrefix = 'set'
		, $useCamelCase = true
		) {
		$flags = null === $visibilityFlags ? self::allFlags() : $visibilityFlags;
		$reflectionObject = new \ReflectionObject( $obj );
		$currentClass = new \ReflectionClass( $obj );
		while ( $currentClass !== false && ! $currentClass->isInterface() ) {
		
			$properties = $currentClass->getProperties( $flags );
				
			foreach ( $properties as $property ) {
				
				$attributeName = $property->getName();
				$methodName = $setterPrefix .
					( $useCamelCase ? self::mb_ucfirst( $attributeName ) : $attributeName );
				
				if ( $property->isPrivate() || $property->isProtected() ) {
					
					if ( $reflectionObject->hasMethod( $methodName ) ) {
						
						$method = $reflectionObject->getMethod( $methodName );
						if ( $method->isPublic() && array_key_exists( $attributeName, $map ) ) {
							$method->invoke( $obj, $map[ $attributeName ] );
						}
					}
					
				} else { // public
					try {
						if ( array_key_exists( $attributeName, $map ) ) {
							$obj->{ $attributeName } = $map[ $attributeName ];
						}
					} catch ( \Exception $e ) {
						// Ignore
					}
				}
			}
			
			// No properties? -> try to retrieve only public properties
			if ( count( $properties ) < 1 ) {
				$properties = get_object_vars( $obj );
				foreach ( $properties as $attributeName => $v ) {
					if ( array_key_exists( $attributeName, $map ) ) { 
						try {
							$obj->{ $attributeName } = $map[ $attributeName ];
						} catch ( \Exception $e ) {
							// Ignore
						}
					}
				}
			}			
			
			$currentClass = $currentClass->getParentClass();
		}
	}

	/**
	 * Set the attribute values of a object.
	 * This method has been kept for backward compatibility.
	 * 
	 * @param array		$map				A map with the attribute names and values to be changed.
	 *
	 * @param object	$obj				The object to be changed.
	 *
	 * @param string	$setterPrefix		The prefix for setter public methods (defaults to 'set').
	 *
	 * @param bool		$useCamelCase		If true, private and protected attributes will be set by
	 *										camelCase public methods (default true).
	 */	
	static function setPrivateAttributes(
		array $map, &$obj, $setterPrefix = 'set', $useCamelCase = true
		) {
		self::setAttributes( $map, $obj, \RTTI::IS_PRIVATE, $setterPrefix, $useCamelCase );
	}

	// Multibyte version of ucfirst()
	private static function mb_ucfirst( $str ) {
		if ( ! is_string( $str ) || '' === $str) { return ''; }
		$first = mb_strtoupper( mb_substr( $str, 0, 1 ) );
		if ( 1 === mb_strlen( $str ) ) { return $first; }
		return $first . mb_substr( $str, 1 );
	}
	
}
?>