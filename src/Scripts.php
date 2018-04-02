<?php

namespace ConfigurationValidator;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use ConfigurationValidator\Service\AutoloadConfigDefCollector;
use ConfigurationValidator\Service\ZendModuleConfigCollector;
use ConfigurationValidator\Service\ConfigValidator;

class Scripts {

    /**
     * Validate configuration
     *
     * @param Event $event
     * @return void
     */
    public static function ConfigValidate(Event $event)
    {
        try {
            if (!ini_get('date.timezone')) {
                ini_set('date.timezone', 'UTC');
            }
            
            // The directory we are running in is assumed to be the "top level" where vendor is located
            $dir = getcwd();
            $autoload_filename = $dir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            if(! file_exists($autoload_filename)) {
                throw new Exception("Autoload file $autoload_filename not found");
            }
            
            $appconfig_filename = $dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'application.config.php';
            if(! file_exists($appconfig_filename)) {
                throw new Exception("Application configuration file $appconfig_filename not found");
            }
            
            $autoLoader = require $autoload_filename;
            $zendAppConfig = require $appconfig_filename;
            
            $configDefCollector = new AutoloadConfigDefCollector($autoLoader);
            $configCollector = new ZendModuleConfigCollector();
            
            $configDef = $configDefCollector->getConfigDef();
            $config = $configCollector->collect($zendAppConfig);
            
            $validator = new ConfigValidator();
           
            $warnings = $validator->validate($configDef, $config);
            if(count($warnings) == 0) {
                echo "Validation successful!" . PHP_EOL;
            } else {
                fputs(STDERR, "WARNING:  One or more configuration problems were identified" . PHP_EOL);
                foreach($warnings as $warning) {
                    fputs(STDERR, "- $warning" . PHP_EOL);
                }
                exit(-2);
            }
        } catch(Exception $e) {
            fputs(STDERR, "ERROR: " . $e->getMessage() . PHP_EOL);
            exit(-1);
        }
    }
}