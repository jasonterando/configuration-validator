<?php
namespace ConfigurationValidator\Service;

use ConfigurationValidator\Service\AutoloadConfigDefCollector;
use ConfigurationValidator\Service\ZendModuleConfigCollector;
use ConfigurationValidator\Service\ConfigValidator;
use Exception;
use is_numeric;

class ScriptSupport {

    public function __construct(string $applicationDirectory) {
        $this->applicationDirectory = $applicationDirectory;
    }

    /**
     * Validate the configuration against the configuration 
     *
     * @return void
     */
    public function validate() {
        $this->ensureTimezone();
        return (new ConfigValidator())->validate(
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
            $autoloadFilename = $this->applicationDirectory . DIRECTORY_SEPARATOR . 
                'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

            if(! file_exists($autoloadFilename)) {
                throw new Exception("Autoload file $autoloadFilename not found");
            }
            $autoload = require $autoloadFilename;
            $configDefCollector = new AutoloadConfigDefCollector($autoload);
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
            $appConfigFilename = $this->applicationDirectory . DIRECTORY_SEPARATOR . 
                'config' . DIRECTORY_SEPARATOR . 'application.config.php';
            if(! file_exists($appConfigFilename)) {
                throw new Exception("Application configuration file $appConfigFilename not found");
            }
            $appConfig = require $appConfigFilename;
            $configCollector = new ZendModuleConfigCollector($appConfig);
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
        file_put_contents($templateFileName, $this->generateConfigTemplate());
    }

    /**
     * Return YAML-formatted string
     *
     * @param array $config
     * @param integer $depth
     * @return string
     */
    protected function formatAsYaml(array $config, $depth = 0) {
        $s = "";
        foreach($config as $key => $value) {
            $isArr = is_array($value);
            if($isArr && (! Utility::isAssociativeArray($value))) {
                continue;
            }
            $s .= str_repeat(' ', $depth * 3) . $key . PHP_EOL;
            if($isArr) {
                $s .= $this->formatAsYaml($value, $depth + 1);
            }
        }
        return $s;
    }
    
}