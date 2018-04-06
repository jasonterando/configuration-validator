<?php
namespace ConfigurationValidator\Service\Interfaces;

interface IConfigDefScanner {
    /**
     * This function must return a hierarchical array of configuration definition info
     *
     * @return array
     */
    public function scanForFiles();
}