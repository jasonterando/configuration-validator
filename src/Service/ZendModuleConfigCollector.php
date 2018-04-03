<?php
namespace ConfigurationValidator\Service;

use Composer\Autoload\ClassLoader;
use Exception;

/**
 * Based upon Zend's module structure, combine all configuration files
 */
class ZendModuleConfigCollector {
    public function __construct(array $zendAppConfig) {
        $this->zendAppConfig = $zendAppConfig;
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
                            $results = array_merge_recursive($results, require $globConfig);
                        }
                    }
                }
            }
        }
        return $results;
    }
}