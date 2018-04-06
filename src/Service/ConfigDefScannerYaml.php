<?php
namespace ConfigurationValidator\Service;
use Exception;

class ConfigDefScannerYaml {
    protected $debug = false;

    protected function setDebug(bool $debug) {
        $this->debug = $debug;
    }
    /**
     * Check the specified directory for a configuration file 
     *
     * @param string $dirName
     * @param array ref $yamlFiles
     * @return void
     */
    protected function checkDirForConfigYaml(string $dirName, array &$yamlFiles) {
        $mask = "$dirName/config-definition*.{yaml,yml}";
        foreach(glob($mask, GLOB_BRACE) as $configDefFileName) {
            $configDef = spyc_load_file($configDefFileName);
            if((! isset($configDef)) || (count($configDef) == 0)) {
                throw new Exception("$configDefFileName is not a valid YAML file");
            }
            if($this->debug) {
                echo "Added Configuration Definition file $configDefFileName" . PHP_EOL;
            }
            $yamlFiles[$configDefFileName] = $configDef;
        }
    }
}