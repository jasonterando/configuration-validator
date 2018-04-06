<?php
use ConfigurationValidator\Service\ConfigCollectorIniFiles;

class ConfigCollectorIniFileTest extends BaseTestCase
{
    public function testIniFilesConfigCollect() {
        $tempDir = sys_get_temp_dir() . '/testIniDir';
        $tempFile1 = $tempDir . '/foo1.ini';
        $tempFile2 = $tempDir . '/foo2.ini';
        try {
            if(! is_dir($tempDir)) mkdir($tempDir);
            file_put_contents($tempFile1, "[abc]\r\nfoo.1=abc\r\nfoo.2=def");
            file_put_contents($tempFile2, "[abc]\r\nfoo.1=123\r\nbar.1=abc\r\nbar.2=def\r\n[def]\r\nfoo.1=abc\r\nfoo.2=def");
            
            $svc = new ConfigCollectorIniFiles($tempDir . '/*.ini', true);
            $this->expectOutputString("Added INI Configuration file $tempFile1" . PHP_EOL . 
                "Added INI Configuration file $tempFile2" . PHP_EOL);
            $config = $svc->collect(true);
            $this->assertEquals('123', $config['abc']['foo']['1']);
            $this->assertEquals('abc', $config['def']['foo']['1']);
        } finally {
            if(is_file($tempFile1)) unlink($tempFile1);
            if(is_file($tempFile2)) unlink($tempFile2);
            if(is_dir($tempDir)) rmdir($tempDir);
        }
    }
    public function testIniFilesConfigCollectBadIni() {
        $tempDir = sys_get_temp_dir() . '/testIniDir';
        $tempFile1 = $tempDir . '/foo1.ini';
        try {
            if(! is_dir($tempDir)) mkdir($tempDir);
            file_put_contents($tempFile1, "]\\");
            
            $svc = new ConfigCollectorIniFiles($tempDir . '/*.ini', true);
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("$tempFile1 does not appear to be a valid INI file");
            $svc->collect(true);
        } finally {
            if(is_file($tempFile1)) unlink($tempFile1);
            if(is_dir($tempDir)) rmdir($tempDir);
        }
    }
    
}
