<?php

namespace ConfigurationValidator;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use ConfigurationValidator\Service\ScriptSupport;
use DateTime;
use is_set;
use getcwd;
use file_put_contents;
use strtolower;

/**
 * @codeCoverageIgnore
 */
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
            $args = Scripts::parseArguments($event->getArguments());
            // The directory we are running in is assumed to be the "top level" where vendor is located,
            // there is probably a better way to get this from $event
            $support = new ScriptSupport(getcwd(), $args['debug']);
            $warnings = $support->validate();
            if($args['debug']) fputs(STDERR, PHP_EOL);
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
            if($args['debug']) fputs(STDERR, PHP_EOL);
            fputs(STDERR, "ERROR: " . $e->getMessage() . PHP_EOL);
            exit(-1);
        }
    }

    public static function ConfigSaveTemplate(Event $event)
    {
        try {
            $args = Scripts::parseArguments($event->getArguments());
            $support = new ScriptSupport(getcwd(), $args['debug']);
            $saveTo = getcwd() . '/config-definition-' . (new DateTime())->format('Y-m-d-G-i-s') . '.yaml';
            $support->saveConfigTemplate($saveTo);
        } catch(Exception $e) {
            fputs(STDERR, "ERROR: " . $e->getMessage() . PHP_EOL);
            exit(-1);
        }        
    }

    /**
     * Parse arguments 
     *
     * @param array $argments
     * @return array
     */
    public static function parseArguments(array $arguments) {
        $debug = false;
        foreach($arguments as $a) {
            switch(strtolower($a)) {
                case "debug":
                    $debug = true;
                    break;
            }
        }
        return [
            'debug' => $debug
        ];
    }
}