<?php
namespace ConfigurationValidator\Service;

use Composer\Autoload\ClassLoader;
use Exception;
use ConfigurationValidator\Service\Interfaces\IConfigCollector;

/**
 * Based upon Zend's module structure, combine all configuration files
 */
class ConfigCollectorZendModule implements IConfigCollector {
    public function __construct(array $zendAppConfig, bool $debug = false) {
        $this->zendAppConfig = $zendAppConfig;
        $this->debug = $debug;
    }

    /**
     * Collect all Zend application configuration
     *
     * @return array
     */
    public function collect() {
        $results = [];
        if(array_key_exists('module_listener_options', $this->zendAppConfig)) {
            if(array_key_exists('config_glob_paths', $this->zendAppConfig['module_listener_options'])) {
                foreach($this->zendAppConfig['module_listener_options']['config_glob_paths'] as $globConfigs) {
                    foreach(glob($globConfigs, GLOB_BRACE) as $globConfig) {
                        if(file_exists($globConfig)) {
                            if($this->debug) {
                                echo "Added Zend Configuration file $globConfig" . PHP_EOL;
                            }
                            $results = array_merge_recursive($results, require $globConfig);
                        }
                    }
                }
            }
        }
        return $results;
    }
}