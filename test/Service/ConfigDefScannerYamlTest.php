<?php
use Composer\Autoload\ClassLoader;
use ConfigurationValidator\Service\ConfigDefScannerYaml;

class ConfigDefScannerYamlTest extends BaseTestCase
{
    function testCheckDirForConfigYamlValid() {
        $tmp = sys_get_temp_dir();
        $tmpDir = $tmp . '/testCDFCY';
        $tmpFile1 = $tmpDir . '/config-definition.yaml';
        $tmpFile2 = $tmpDir . '/config-definition.yml';
        $tmpYaml1 = "foo:\r\n   abc: 1\r\n   def: 2\r\n";
        $tmpYaml2 = "bar:\r\n   123: a\r\n   456: 'b'\r\n";
        
        try {
            if(! is_dir($tmpDir)) mkdir($tmpDir);
            file_put_contents($tmpFile1, $tmpYaml1);
            file_put_contents($tmpFile2, $tmpYaml2);
            $svc = new ConfigDefScannerYaml();
            $yamlFiles = [];
            $this->expectOutputString(
                "Added Configuration Definition file $tmpFile1" . PHP_EOL .
                "Added Configuration Definition file $tmpFile2" . PHP_EOL
            );
            $this->callMethod($svc, 'setDebug', [true]);
            $this->callMethod($svc, 'checkDirForConfigYaml', [$tmpDir, &$yamlFiles]);
            $this->assertEquals($yamlFiles[$tmpFile1], ['foo' => ['abc' => 1, 'def' => 2]]);
            $this->assertEquals($yamlFiles[$tmpFile2], ['bar' => [123 => 'a', 456 => 'b']]);
        } finally {
            if(is_file($tmpFile2)) unlink($tmpFile2);
            if(is_file($tmpFile1)) unlink($tmpFile1);
            if(is_dir($tmpDir)) rmdir($tmpDir);
        }
    }

    function testCheckDirForConfigYamlBadYaml() {
        $tmp = sys_get_temp_dir();
        $tmpDir = $tmp . '/testCDFCY_BAD';
        $tmpFile1 = $tmpDir . '/config-definition.yaml';
        $tmpYaml1 = "";
        
        try {
            if(! is_dir($tmpDir)) mkdir($tmpDir);
            file_put_contents($tmpFile1, $tmpYaml1);
            $svc = new ConfigDefScannerYaml();
            $yamlFiles = [];
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("$tmpFile1 is not a valid YAML file");
            $this->callMethod($svc, 'checkDirForConfigYaml', [$tmpDir, &$yamlFiles]);
        } finally {
            if(is_file($tmpFile1)) unlink($tmpFile1);
            if(is_dir($tmpDir)) rmdir($tmpDir);
        }
    }
}