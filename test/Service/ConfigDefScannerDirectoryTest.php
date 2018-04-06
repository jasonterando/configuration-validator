<?php
use Composer\Autoload\ClassLoader;
use ConfigurationValidator\Service\ConfigDefScannerDirectory;

class ConfigDefScannerDirectoryTest extends BaseTestCase
{
    public function testScanForFiles() {
        $tmp = sys_get_temp_dir();
        $tmpDir = $tmp . '/CDSAT';
        try {
            if(! is_dir($tmpDir)) mkdir($tmpDir);
            $svc = $this->getMockBuilder(ConfigDefScannerDirectory::class)
                ->setConstructorArgs([[$tmpDir]])
                ->setMethods(['checkDirForConfigYaml'])
                ->getMock();
            
            $yaml = ['abc' => 123];
            $svc->expects($this->at(0))
                ->method('checkDirForConfigYaml')
                ->with($tmpDir, [])
                ->will($this->returnCallback(function($dir, &$yamlFiles) use ($yaml) {
                    $yamlFiles['foo'] = $yaml;
                }));

            $results = $svc->scanForFiles();
            $this->assertEquals(['foo' => $yaml], $results);
        } finally {
            if(is_dir($tmpDir)) rmdir($tmpDir);
        }
    }

    public function testScanForFilesBadDir() {
        $bogus = '#@::@#$*';
        $svc = new ConfigDefScannerDirectory([$bogus]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("$bogus is not an accessible directory");
        $svc->scanForFiles();
    }
}
