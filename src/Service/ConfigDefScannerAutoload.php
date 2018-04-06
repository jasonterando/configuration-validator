<?php
namespace ConfigurationValidator\Service;

use ConfigurationValidator\Service\Interfaces\IConfigDefScanner;
use Composer\Autoload\ClassLoader;
use Exception;

class ConfigDefScannerAutoload extends ConfigDefScannerYaml implements IConfigDefScanner {
    public function __construct(ClassLoader $loader, bool $debug = false) {
        $this->setDebug($debug);
        $this->loader = $loader;
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
}