# SimpleDI
SimpleDI is a lightweight and easy to use dependency injection container for PHP.

## Installation
You can install SimpleDI via composer

``composer require mkgor/simple-di``

That's it! You already can use it, because it need a little configuration to provide base functionality of DIC

## How to use it?
Just call ``$container->get()`` method and it'll resolve all dependencies of specified class and returns it to you!


*SomeDependency.php*
```php

<?php

class SomeDependency {
    public $anotherDependency;

	public function __construct(AnotherDependency $b) {
		$this->anotherDependency = $b;
	}
}
```

*AnotherDependency.php*

```php

<?php

class AnotherDependency {
	public function sayHello() {
		echo "Hello from AnotherDependency";
	}
}
```


*test.php*

```php
<?php

require "vendor/autoload.php";

$container = new \SimpleDI\Container(__DIR__ . '/config.php');

//Output: Hello from AnotherDependency
$container->get(SomeDependency::class)->anotherDependency->sayHello();
```

*config.php*

```php
<?php

return [
    'singleton' => [],
    'definition' => [],
];
```

## Getting class by alias
You can specify alias for some class and call it by that alias using SimpleDI

```php
<?php

return [
    'singleton' => [],

    'definition' => [
        'aliasName' => [
            'classname' => SomeClass::class,
            'arguments' => []
        ]
    ],
];
```

```php
<?php

require "vendor/autoload.php";

$container = new \SimpleDI\Container(__DIR__ . '/config.php');

/** @var SomeClass $someClass */
$someClass = $container->get('aliasName');
```

## Declaring singletons
If you have some class which will be created one time during request lifetime, you can declare it like singleton in configuration.
SimpleDI will create its instance one time and save it in singletons container

```php
<?php

return [
    'singleton' => [
        'aliasForSingleton' => [
            'classname' => SomeSingleton::class,
            'arguments' => []
        ]
    ],

    'definition' => []
];
```

```php
<?php

require "vendor/autoload.php";

$container = new \SimpleDI\Container(__DIR__ . '/config.php');

/** @var SomeSingleton $someClass */
$someClass = $container->get('aliasForSingleton');
$someClass->a = 4;

// Output: 4
echo $someClass->a;

$someClass2 = $container->get('aliasForSingleton');

// Output: 4
echo $someClass2->a;
```
