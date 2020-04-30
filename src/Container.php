<?php

namespace SimpleDI;

use Exception;
use ReflectionClass;
use ReflectionException;
use SimpleDI\Providers\ConfigurationProviderInterface;
use SimpleDI\Providers\StandardConfigurationProvider;

/**
 * Class Container
 *
 * @package SimpleDI
 */
class Container
{
    const CONTAINER_SINGLETON = 'singleton';
    const CONTAINER_DEFINITION = 'definition';
    
    /**
     * @var ConfigurationProviderInterface
     */
    private $configurationProvider;

    /**
     * @var string
     */
    private $configurationPath;

    /**
     * @var array
     */
    private $container;

    /**
     * @var array
     */
    private $singletons = [];

    /**
     * @var array
     */
    private $constructorValuesTmp = [];

    /**
     * @return array
     */
    public function getConstructorValuesTmp()
    {
        return $this->constructorValuesTmp;
    }

    /**
     * @param string $item
     *
     * @return bool|mixed
     */
    public function getConstructorValue($item)
    {
        return $item ? $this->constructorValuesTmp[$item] : false;
    }

    /**
     * @param array $constructorValuesTmp
     */
    public function setConstructorValuesTmp($constructorValuesTmp)
    {
        $this->constructorValuesTmp = array_merge($this->constructorValuesTmp, $constructorValuesTmp);
    }

    /**
     * @return array
     */
    public function getSingletons()
    {
        return $this->singletons;
    }

    /**
     * @param array $singletons
     */
    public function setSingletons($singletons)
    {
        $this->singletons = $singletons;
    }

    /**
     * @return string
     */
    public function getConfigurationPath()
    {
        return $this->configurationPath;
    }

    /**
     * @param string $configurationPath
     */
    public function setConfigurationPath($configurationPath)
    {
        $this->configurationPath = $configurationPath;
    }

    /**
     * @param string $block
     *
     * @return array
     */
    public function getContainer($block = null)
    {
        if (!$this->container) {
            $this->container = $this->getConfigurationProvider()->getBuildedContainer();
        }

        return $block ? $this->container[$block] : $this->container;
    }

    /**
     * @param array $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return ConfigurationProviderInterface
     */
    public function getConfigurationProvider()
    {
        if ($this->configurationProvider) {
            $this->configurationProvider->setConfigurationPath($this->getConfigurationPath());

            return $this->configurationProvider;
        }

        return new StandardConfigurationProvider($this->getConfigurationPath());
    }

    /**
     * @param ConfigurationProviderInterface $configurationProvider
     */
    public function setConfigurationProvider(ConfigurationProviderInterface $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * Container constructor.
     *
     * @param string $configurationPath
     */
    public function __construct($configurationPath = null)
    {
        $this->setConfigurationPath($configurationPath);
    }

    /**
     * Resolves dependencies and returns class
     *
     * @param string $name - Alias or full name of class which required
     * @param array  $arguments
     *
     * @return mixed|void
     * @throws ReflectionException
     * @throws Exception
     */
    public function get($name, $arguments = [])
    {
        $this->setConstructorValuesTmp($arguments);

        if (array_key_exists($name, $this->getContainer(self::CONTAINER_SINGLETON)) ||
            array_key_exists($name, $this->getContainer(self::CONTAINER_DEFINITION))) {

            return $this->resolveByAlias($name);
        } else {
            if (class_exists($name)) {
                return $this->resolveByClassName($name);
            } else {
                throw new Exception(sprintf("Class %s does not exists", $name));
            }
        }
    }

    /**
     * Binds new class in container
     *
     * @param string $alias
     * @param string $classname
     * @param array  $arguments
     * @param string $type
     */
    public function bind($alias, $classname, $arguments = [], $type = self::CONTAINER_DEFINITION)
    {
        $this->container[$type][$alias] = [
            'classname' => $classname,
            'arguments' => $arguments
        ];
    }

    /**
     * Getting block of bindings (singleton/definition)
     *
     * @param string $alias
     *
     * @return int|string
     * @throws Exception
     */
    private function getBindingsBlock($alias)
    {
        foreach ($this->getContainer() as $block => $items) {
            foreach ($items as $key => $value) {
                if ($key == $alias) {
                    return $block;
                }
            }
        }

        throw new Exception(sprintf("Class with alias %s not found in container", $alias));
    }

    /**
     * Resolving class's dependencies by alias using configurations
     *
     * @param string $alias
     *
     * @return mixed
     * @throws Exception
     */
    private function resolveByAlias($alias)
    {
        $bindingsBlock = $this->getBindingsBlock($alias);

        switch ($bindingsBlock) {
            case "singleton":
            {
                if (array_key_exists($alias, $this->getSingletons())) {
                    return $this->singletons[$alias];
                } else {
                    $containerItemName = self::CONTAINER_SINGLETON;
                }

                break;
            }

            case "definition":
            {
                $containerItemName = self::CONTAINER_DEFINITION;
                break;
            }

            default:
            {
                throw new Exception(sprintf("Can't resolve type of %s", $alias));
            }
        }

        $containerItem = $this->container[$containerItemName][$alias];;

        $resolvedClass = $this->resolveConstructorDependencies(new ReflectionClass($containerItem['classname']), $containerItem['arguments']);

        if ($bindingsBlock == self::CONTAINER_SINGLETON) {
            $this->singletons[$alias] = $resolvedClass;
        }

        return $resolvedClass;
    }

    /**
     * Checking for binding, and if there are no binding with specified class, resolving it by class name, else,
     * resolving by alias with all configurations
     *
     * @param string $name
     *
     * @return mixed
     * @throws ReflectionException
     * @throws Exception
     */
    private function resolveByClassName($name)
    {
        /**
         * Finding alias of specified classname
         */
        foreach ($this->getContainer() as $containerItem) {
            foreach ($containerItem as $alias => $class) {
                if ($class['classname'] == $name) {
                    return $this->resolveByAlias($alias);
                }
            }
        }

        return $this->resolveConstructorDependencies(new ReflectionClass($name));
    }

    /**
     * Resolving all dependencies and setting arguments to class's constructor
     *
     * @param ReflectionClass $class
     *
     * @param array           $arguments
     *
     * @return mixed
     * @throws Exception
     */
    private function resolveConstructorDependencies(ReflectionClass $class, $arguments = [])
    {
        /**
         * If class does not have constructor, just returning its instance
         */
        if (!$class->getConstructor()) {
            $className = $class->getName();

            return new $className;
        }

        /** @var \ReflectionParameter[] $parameters */
        $parameters = $class->getConstructor()->getParameters();

        $resolvedParameters = $arguments;

        if (!$resolvedParameters || $this->getConstructorValuesTmp()) {
            foreach ($parameters as $parameter) {
                /**
                 * If user specified some values in get() method, setting it and ignoring bindings/default values
                 */
                if ($this->getConstructorValue($parameter->getName())) {
                    $resolvedParameters[] = $this->getConstructorValue($parameter->getName());
                } else {
                    /**
                     * If parameter is a class, resolving its dependencies and passing it to constructor
                     *
                     * It works recursively, so it will resolve all dependencies in chain
                     */
                    if ($parameter->getClass()) {
                        $resolvedParameters[] = $this->get($parameter->getClass()->getName());
                    } else {
                        /**
                         * If default value is available, setting it
                         */
                        if ($parameter->isDefaultValueAvailable()) {
                            $resolvedParameters[] = $parameter->getDefaultValue();
                        } else {
                            throw new Exception(sprintf('`%s` constructor need value for `%s` argument, bind it firstly!', $class->getName(), $parameter->getName()));
                        }
                    }
                }
            }
        }

        /** Calling class and passing parameters to its constructor */
        return $class->newInstanceArgs($resolvedParameters);
    }
}