<?php
namespace ConfigurationValidator\Service;

use ConfigurationValidator\Service\Interfaces\IConfigDefScanner;
use Exception;

class ConfigDefScannerDirectory extends ConfigDefScannerYaml implements IConfigDefScanner  {
    public function __construct($directories, $debug = false) {
        $this->setDebug($debug);
        $this->directories = $directories;
    }

    /**
     * Collects all "raw" confiugration data and returns it
     *
     * @return array
     */
    public function scanForFiles() {
        $yamlFiles = [];
        foreach($this->directories as $dir) {
            if(is_dir($dir)) {
                $this->checkDirForConfigYaml($dir, $yamlFiles);
            } else {
                throw new Exception("$dir is not an accessible directory");
            }            
        }
        return $yamlFiles;
    }
}