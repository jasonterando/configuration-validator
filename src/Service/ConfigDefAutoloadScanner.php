<?php
namespace ConfigurationValidator\Service;
use Composer\Autoload\ClassLoader;
use ConfigurationValidator\Service\Interfaces\IConfigDefFileScanner;
use Exception;

class ConfigDefAutoloadScanner implements IConfigDefFileScanner {
    public function __construct(ClassLoader $loader, bool $debug = false) {
        $this->loader = $loader;
        $this->debug = $debug;
    }

    /**
     * Collects all "raw" confiugration data and returns it
     *
     * @return array
     */
    public function scanForFiles() {
        $yamlFiles = [];
        foreach($this->loader->getPrefixesPsr4() as $dir) {
            $this->drilldown($dir, $yamlFiles);
        }
        foreach($this->loader->getClassMap() as $dir) {
            $this->drilldown($dir, $yamlFiles);
        }
        return $yamlFiles;
    }

    /**
     * Recursively make way down through associative array, looking for directory entries
     *
     * @param string $item
     * @param array ref $yamlFiles
     * @return void
     */
    protected function drilldown($item, array &$yamlFiles) {
        if(is_array($item)) {
            foreach($item as $subitem) {
                $this->drilldown($subitem, $yamlFiles);
            }
        } else {
            $dir = realpath($item); // We need this for is_dir to work consistently
            if(is_dir($dir)) {
                $this->checkDirForConfigYaml($dir, $yamlFiles);
            }
        }
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