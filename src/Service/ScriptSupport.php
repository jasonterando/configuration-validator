<?php
namespace ConfigurationValidator\Service;

use ConfigurationValidator\Service\ConfigDefCollector;
use ConfigurationValidator\Service\ConfigDefScannerAutoload;
use ConfigurationValidator\Service\ConfigCollectorIniFiles;
use ConfigurationValidator\Service\ConfigCollectorZendModule;
use ConfigurationValidator\Service\ConfigValidator;
use Exception;
use is_numeric;

class ScriptSupport {

    public function __construct($applicationDirectory, $iniFile = null, $debug = false) {
        $this->applicationDirectory = $applicationDirectory;
        $this->iniFile = $iniFile;
        $this->debug = $debug == true;
    }

    /**
     * Validate the configuration against the configuration 
     *
     * @return void
     */
    public function validate() {
        $this->ensureTimezone();
        return (new ConfigValidator($this->debug))->validate(
            $this->getConfigDef(), 
            $this->getConfig());
    }

    /**
     * Ensure we have a timezone set to migitage PHP warnings
     *
     * @return void
     */
    protected function ensureTimezone() {
        if (! ini_get('date.timezone')) {
            ini_set('date.timezone', 'UTC');
        }
    }

    /**
     * Return the configuration definition
     * 
     * @return array
     */
    protected function getConfigDef() {
        if(! isset($this->configDef)) {
            $autoloadFilename = $this->applicationDirectory . '/' . 
                'vendor' . '/' . 'autoload.php';
            if(! file_exists($autoloadFilename)) {
                throw new Exception("Autoload file $autoloadFilename not found");
            }
            if($this->debug) {
                echo "Using Autoload file $autoloadFilename" . PHP_EOL;
            }
            $autoload = require $autoloadFilename;
            $autoloadFileScanner = new ConfigDefScannerAutoload($autoload, $this->debug);
            $configDefCollector = new ConfigDefCollector([$autoloadFileScanner], $this->debug);
            $this->configDef = $configDefCollector->collect();
        }
        return $this->configDef;
    }

    /**
     * Return the application configuration
     * 
     * @return array
     */
    protected function getConfig() {
        if(! isset($this->config)) {
            if($this->iniFile) {
                $configCollector = new ConfigCollectorIniFiles($this->iniFile, $this->debug);
            } else {
                $appConfigFilename = $this->applicationDirectory . '/' . 
                    'config' . '/' . 'application.config.php';
                if(! file_exists($appConfigFilename)) {
                    throw new Exception("Application Configuration file $appConfigFilename not found");
                }
                if($this->debug) {
                    echo "Using Application Configuration file $appConfigFilename" . PHP_EOL;
                }
                $appConfig = require $appConfigFilename;
                $configCollector = new ConfigCollectorZendModule($appConfig, $this->debug);
            }
            $this->config = $configCollector->collect();
        }
        return $this->config;
    }

    /**
     * Return configuration template formatted as YAML
     *
     * @return string
     */
    public function generateConfigTemplate() {
        return $this->formatAsYaml($this->getConfig());
    }

    /**
     * Saves configuration template formatted as YAML to the specified file name
     *
     * @return void
     */
    public function saveConfigTemplate($templateFileName) {
        try {
            file_put_contents($templateFileName, $this->generateConfigTemplate());
            if($this->debug) {
                echo "Saved Configutation Template to $templateFileName" . PHP_EOL;
            }
        } catch(Exception $ex) {
            throw new Exception("Unable to save Configuration Template to $templateFileName: " . $ex->getMessage());
        }   
    }

    /**
     * Return YAML-formatted string
     *
     * @param array $config
     * @param integer $depth
     * @return string
     */
    protected function formatAsYaml($config, $depth = 0) {
        $s = "";
        foreach($config as $key => $value) {
            $isArr = is_array($value);
            if($isArr && (! Utility::isAssociativeArray($value))) {
                continue;
            }
            $s .= str_repeat(' ', $depth * 3) . 
                $key . 
                ($isArr ? ':' : '') .
                PHP_EOL;
            if($isArr) {
                $s .= $this->formatAsYaml($value, $depth + 1);
            }
        }
        return $s;
    }
}