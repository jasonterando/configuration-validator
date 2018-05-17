<?php
namespace ConfigurationValidator\Service;

use Composer\Autoload\ClassLoader;
use Exception;
use ConfigurationValidator\Service\Interfaces\IConfigCollector;

/**
 * Based upon Zend's module structure, combine all configuration files
 */
class ConfigCollectorIniFiles implements IConfigCollector {
    public function __construct($path, $debug = false) {
        $this->path = $path;
        $this->debug = $debug;
    }

    /**
     * Collect all Zend application configuration
     *
     * @return array
     */
    public function collect() {
        $results = [];
        foreach(glob($this->path, GLOB_BRACE) as $configFile) {
            $config = parse_ini_file($configFile, true);
            if(! $config) {
                throw new Exception("$configFile does not appear to be a valid INI file");
            }
            if($this->debug) {
                echo "Added INI Configuration file $configFile" . PHP_EOL;
            }
            $config = $this->splitIniEntries($config);
            Utility::array_merge_into($results, $config);
        }
        return $results;
    }

    /**
     * Split x.y.z entries into a hierarchy
     *
     * @param array $ini
     * @return array
     */
    protected function splitIniEntries($ini) {
        $results = [];
        foreach($ini as $key => $value) {
            if(is_array($value)) {
                $results[$key] = $this->splitIniEntries($value);
            } else {
                $ptr = &$results;
                $nextPart = $key;
                do {
                    $i = strpos($nextPart, '.');
                    if($i !== FALSE) {
                        $part = substr($nextPart, 0, $i);
                        $nextPart = substr($nextPart, $i+1);
                    } else {
                        $part = $nextPart;
                    }
                    if(! array_key_exists($part, $ptr)) {
                        $ptr[$part] = [];
                    }
                    $ptr = &$ptr[$part];
                } while(($i !== FALSE) && (strlen($part) > 0));
                $ptr = $value;
            }
        }
        return $results;
    }
}