<?php
namespace ConfigurationValidator\Service\Interfaces;

interface IConfigDefFileScanner {
    /**
     * This function must return a hierarchical array of configuration definition info
     *
     * @return array
     */
    public function scanForFiles();
}