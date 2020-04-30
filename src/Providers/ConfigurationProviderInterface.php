<?php

namespace SimpleDI\Providers;

/**
 * Interface ConfigurationProviderInterface
 *
 * @package SimpleDI\Providers
 */
interface ConfigurationProviderInterface
{
    /**
     * @return array
     */
    public function getBuildedContainer();

    /**
     * @return string
     */
    public function getConfigurationPath();

    /**
     * @param $configurationPath
     */
    public function setConfigurationPath($configurationPath);

    /**
     * ConfigurationProviderInterface constructor.
     *
     * @param $configurationPath
     */
    public function __construct($configurationPath = null);
}