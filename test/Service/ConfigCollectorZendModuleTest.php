<?php
use ConfigurationValidator\Service\ConfigCollectorZendModule;

class ZendModuleConfigCollectorTest extends BaseTestCase
{
    public function testZendModuleConfigCollect() {
        $tempDir = sys_get_temp_dir() . '/testZendDir';
        $tempFile1 = $tempDir . '/foo1.php';
        $tempFile2 = $tempDir . '/foo2.php';
        try {
            if(! is_dir($tempDir)) mkdir($tempDir);
            file_put_contents($tempFile1, "<?php\r\nreturn ['foo' => ['abc' => '123']];\r\n");
            file_put_contents($tempFile2, "<?php\r\nreturn ['foo' => ['def' => '234']];\r\n");
            
            $svc = new ConfigCollectorZendModule(['module_listener_options' => ['config_glob_paths' => [$tempDir . '/*.php']]], true);
            $this->expectOutputString("Added Zend Configuration file $tempFile1" . PHP_EOL . 
                "Added Zend Configuration file $tempFile2" . PHP_EOL);
            $config = $svc->collect(true);
            $this->assertEquals('123', $config['foo']['abc']);
            $this->assertEquals('234', $config['foo']['def']);
        } finally {
            if(is_file($tempFile1)) unlink($tempFile1);
            if(is_file($tempFile2)) unlink($tempFile2);
            if(is_dir($tempDir)) rmdir($tempDir);
        }
    }
}
