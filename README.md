# RTTI

A Run-Time Type Information extractor, useful for getting or setting private and protected attributes from/to PHP objects.

We use [semantic versioning](http://semver.org/). See [our releases](https://github.com/thiagodp/rtti/releases).

Classes:

* [phputil\RTTI](https://github.com/thiagodp/rtti/blob/master/lib/RTTI.php)

### Installation

```command
composer require phputil/rtti
```

### Example

Extracting all attributes from a class (even `private` or `protected`).

```php
<?php
require_once 'vendor/autoload.php'; // or 'RTTI.php' when not using composer

use phputil\RTTI;

class User {
	private $name;
	function __construct( $n ) { $this->name = $n; }
	function getName() { return $this->name; }
}

// array( 'user' => 'Bob' )
var_dump( RTTI::getAttributes( new User( 'Bob' ), RTTI::allFlags() ) );
?>
```

## License

[MIT](https://choosealicense.com/licenses/mit/) (c) [Thiago Delgado Pinto](https://github.com/thiagodp)