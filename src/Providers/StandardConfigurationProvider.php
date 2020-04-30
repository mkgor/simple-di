<?php

namespace SimpleDI\Providers;

/**
 * Class StandardConfigurationProvider
 *
 * @package SimpleDI\Providers
 */
class StandardConfigurationProvider implements ConfigurationProviderInterface
{
    /**
     * @var string
     */
    private $configurationPath;

    /**
     * @return array
     * @throws \Exception
     */
    public function getBuildedContainer()
    {
        if(file_exists($this->getConfigurationPath())) {
            return include $this->getConfigurationPath();
        } else {
            throw new \Exception(sprintf("Container configuration file does not exists of path %s", $this->getConfigurationPath()));
        }
    }

    /**
     * ConfigurationProviderInterface constructor.
     *
     * @param string $configurationPath
     */
    public function __construct($configurationPath = null)
    {
        $this->setConfigurationPath($configurationPath);
    }

    /**
     * @return mixed
     */
    public function getConfigurationPath()
    {
        return $this->configurationPath;
    }

    /**
     * @param mixed $configurationPath
     */
    public function setConfigurationPath($configurationPath)
    {
        $this->configurationPath = $configurationPath;
    }
}