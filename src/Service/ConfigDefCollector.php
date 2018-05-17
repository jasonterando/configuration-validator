<?php
namespace ConfigurationValidator\Service;

use StdClass;
use Exception;
use ConfiguraitonValidator\Service\Interfaces\IConfigDefFileScanner;

/**
 * This class is the base class for collecting configuration, and then formatting it
 * so it can be used for configuration validation.  For the most part, this process
 * is to make sure we are properly identifying lists of configuration settings versus
 * the properties of those settings ("type" and "required")
 */
 class ConfigDefCollector {
    /**
     * Holds the objects we will be using to scan file config def files
     *
     * @var array
     */
    protected $fileScanners = [];

    /**
     * Hold the formatted configuration definition
     *
     * @var array
     */
    protected $configDef = [];

    public function __construct(array $fileScanners, bool $debug = false) {
        foreach($fileScanners as $fileScanner) {
            if(is_subclass_of($fileScanner, 'IConfigDefFileScanner')) {
                throw new Exception('File scanners must implement IConfigDefFileScanner');
            }
        }
        $this->fileScanners = $fileScanners;
        $this->debug = $debug;
    }

    /**
     * Utility function that collects, formats and returns configuration definition
     *
     * @return array
     */
    public function collect() {
        // Collect all configuration definitions
        $configDefs = [];
        foreach($this->fileScanners as $fileScanner) {
            $configDefFiles = $fileScanner->scanForFiles();
            foreach($configDefFiles as $configDefFileName => $configDefContents) {
                $configDefs[$configDefFileName] = $this->format($configDefContents);
            }
        }

        $results = [];
        foreach($configDefs as $configDef) {
            Utility::array_merge_into($results, $configDef);
        }
        return $results;
    }

    /**
     * Format the raw config data into a workable hierarchy,
     * if a node is an array, it will be the parent of other nodes,
     * nodes must have a "type" and a "required" flag
     *
     * @return void
     */
    protected function format($config) {
        $configDef = [];
        foreach($config as $key => $value) {
            $this->formatConfigDefNode('', $configDef, $key, $value);
        }
        return $configDef;
    }

    /**
     * Formats the config definition node into an array if it has children,
     * or an object if it is a node
     *
     * @param string $parentKey
     * @param string $key
     * @param array  $results
     * @param any    $value
     * @return void
     */
    protected function formatConfigDefNode($parentKey, &$results, $key, $value) {
        // Determine if the only thing below this level are field properties
        $isTerminal = true;
        $type = null;
        $required = true;

        // If the value is an array and is an ordinal array (0 => ..., 1 => ...)
        // then it's a "dead end" and we don't care about this anymore
        $valueIsArray = is_array($value);
        $valueIsAssocArray = $valueIsArray && Utility::isAssociativeArray($value);
        $hasChildren = $valueIsArray ? count($value) > 0 : false;

        if($valueIsAssocArray) {
            $isTerminal = true;
            $name = $key;
            foreach($value as $subkey => $subvalue) {
                if(strcmp($subkey, "required") == 0) {
                    $r = false;
                    if(Utility::getBoolean($subvalue, $r)) {
                        $required = $r;
                    } else {
                        throw new Exception("Invalid required value \"$subvalue\" specified for " . $parentKey . $key);
                    }
                    $required = $r;
                    $isTerminal = true;
                } else if (strcmp($subkey, "type") == 0) {
                    if($this->isValidType($subvalue)) {
                        $type = $subvalue;
                        $isTerminal = true;
                    } else {
                        throw new Exception("Invalid type \"$subvalue\" specified for " . $parentKey . $key);
                    }
                } else {
                    // If node contains items other than "required" or "type" assume it is a new child list
                    $isTerminal = false;
                    break;
                }
            }
        } else if($valueIsArray) {
            $isTerminal = ! $hasChildren;
        }

        // To try and "guess" whether the node we are working with is a value of 
        // an ordinal (# => value) or associative array (key => value)
        if($isTerminal) {
            // If this is a terminal value, then return an object
            $result = new StdClass();
            if(! $valueIsArray) {
                // Look for inline definitions of type or not required (false)
                if((! isset($type)) && $this->isValidType($value)) {
                    $type = $value;
                } else {
                    $r = false;
                    if(Utility::getBoolean($value, $r)) {
                        $required = $r;
                    }
                }
            }
            $result->required = $required;
            $result->type = isset($type) ? $type : "any";
            $results[$key] = $result;
        } else {
            // If we are here, then value has children
            $children = [];
            $idx = 0;
            foreach($value as $subkey => $subvalue) {
                if($subkey === $idx) {
                    $this->formatConfigDefNode($parentKey . $key . '/', $children, $subvalue, []);
                    $idx++;
                } else {
                    $this->formatConfigDefNode($parentKey . $key . '/', $children, $subkey, $subvalue);
                }
            }
            if(count($children) > 0) {
                $results[$key] = $children;
            }
        }
    }

    /**
     * Returns True if the passed value is a valid type indicator
     *
     * @param string $type
     * @return boolean
     */
    protected function isValidType($type) {
        return in_array($type, ConfigValidator::$types);
    }
}