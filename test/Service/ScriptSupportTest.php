<?php
use ConfigurationValidator\Service\ScriptSupport;

class ScriptSupportTest extends BaseTestCase
{
    public function testTimezone() {
        $svc = new ScriptSupport('');
        ini_set('date.timezone', NULL);
        $this->callMethod($svc, 'ensureTimezone');
        $this->assertEquals('UTC', ini_get('date.timezone'));
    }

    public function testScriptValidate() {
        $svc = $this->getMockBuilder(ScriptSupport::class)
            ->setConstructorArgs([''])
            ->setMethods(['getConfig', 'getConfigDef'])
            ->getMock();
        $svc->method('getConfigDef')->willReturn(['foo' => [
            'abc' => (object) ['required' => true, 'type' => 'number'], 
            'def' => (object) ['required' => true, 'type' => 'number']
        ]]);
        $svc->method('getConfig')->willReturn(['foo' => ['abc' => 123]]);
        $warnings = $svc->validate();
        $this->assertEquals('Missing element: foo/def', $warnings[0]);
    }

    public function testScriptGetConfigDef() {
        // Turns out, this is really complicated to test...
        $tmpDir = sys_get_temp_dir();
        $tmpDir1 = $tmpDir . '/scriptGetConfigDefTest';
        $tmpDir2 = $tmpDir1 . '/vendor';
        $autoloadFile = $tmpDir2 . '/autoload.php';
        $configDefFile = $tmpDir1 . '/config-definition.yaml';

        try {
            if(! is_dir($tmpDir1)) mkdir($tmpDir1);
            if(! is_dir($tmpDir2)) mkdir($tmpDir2);
            $mock = "<?php" . PHP_EOL . 
                "\$loader = new Composer\\Autoload\\ClassLoader();" . PHP_EOL . 
                "\$loader->addPsr4(\"foo\\\\\", [\"$tmpDir1\"]);" . PHP_EOL .  
                "return \$loader;";
            file_put_contents($autoloadFile, $mock);
            file_put_contents($configDefFile, "foo:" . PHP_EOL . "   bar" . PHP_EOL);
            $this->expectOutputString("Using Autoload file $autoloadFile" . PHP_EOL .
                "Added Configuration Definition file $tmpDir" . DIRECTORY_SEPARATOR . "scriptGetConfigDefTest/config-definition.yaml" . PHP_EOL);
            $svc = new ScriptSupport($tmpDir1, null, true);
            $configDef = $this->callMethod($svc, 'getConfigDef');
            $this->assertEquals(true, $configDef['foo']['bar']->required);
        } finally {
            unlink($autoloadFile);
            unlink($configDefFile);
            rmdir($tmpDir2);
            rmdir($tmpDir1);
        }
    }

    public function testScriptGetConfigDefMissingAutoload() {
        $tmpDir = sys_get_temp_dir();
        $tmpDir1 = $tmpDir . '/scriptGetConfigDefTest';

        try {
            if(! is_dir($tmpDir1)) mkdir($tmpDir1);
            $svc = new ScriptSupport($tmpDir1);
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("Autoload file ");
            $this->callMethod($svc, 'getConfigDef');
        } finally {
            rmdir($tmpDir1);
        }
    }
    
    public function testScriptGetConfig() {
        // Turns out, this is really complicated to test...
        $tmpDir = sys_get_temp_dir();
        $tmpDir1 = $tmpDir . '/scriptGetConfigTest';
        $tmpDir2 = $tmpDir . '/scriptGetConfigTest/config';
        $appConfigFile = $tmpDir2 . '/application.config.php';
        $configFile = $tmpDir1 . '/config.php';

        try {
            if(! is_dir($tmpDir1)) mkdir($tmpDir1);
            if(! is_dir($tmpDir2)) mkdir($tmpDir2);
            $mockAppConfig = "<?php" . PHP_EOL . 
                "return ['module_listener_options' => ['config_glob_paths' => ['$configFile']]];";
            $mockConfig = "<?php" . PHP_EOL .
                "return ['foo' => ['abc' => 123]];";
            file_put_contents($appConfigFile, $mockAppConfig);
            file_put_contents($configFile, $mockConfig);
            $svc = new ScriptSupport($tmpDir1, null, true);
            $this->expectOutputString("Using Application Configuration file $appConfigFile" . PHP_EOL .
                "Added Zend Configuration file $configFile" . PHP_EOL);
            $config = $this->callMethod($svc, 'getConfig');
            $this->assertEquals(123, $config['foo']['abc']);
        } finally {
            unlink($appConfigFile);
            unlink($configFile);
            rmdir($tmpDir2);
            rmdir($tmpDir1);
        }
    }

    public function testScriptGetConfigMissingAppConfig() {
        $tmpDir = sys_get_temp_dir();
        $tmpDir1 = $tmpDir . '/scriptGetConfigTest';
        $appConfigFile = $tmpDir1 . '/config/application.config.php';

        try {
            if(! is_dir($tmpDir1)) mkdir($tmpDir1);
            $svc = new ScriptSupport($tmpDir1);
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("Application Configuration file $appConfigFile not found");
            $this->callMethod($svc, 'getConfig');
        } finally {
            rmdir($tmpDir1);
        }
    }
    
    public function testScriptGenerateConfigTemplate() {
        $svc = $this->getMockBuilder(ScriptSupport::class)
            ->setConstructorArgs([''])
            ->setMethods(['getConfig'])
            ->getMock();
        $svc->method('getConfig')->willReturn(['foo' => ['abc' => 123, 'def' => 245]]);
        $yaml = $svc->generateConfigTemplate();
        $this->assertEquals('foo:' . PHP_EOL . '   abc' . PHP_EOL . '   def' . PHP_EOL, $yaml);
    }

    public function testScriptSaveConfigTemplate() {
        $tmpDir = sys_get_temp_dir();
        $yamlFile = $tmpDir . '/config-definition-test.yaml';

        try {
            $svc = $this->getMockBuilder(ScriptSupport::class)
                ->setConstructorArgs(['', null, true])
                ->setMethods(['getConfig'])
                ->getMock();
            $svc->method('getConfig')->willReturn(['foo' => ['abc' => 123, 'def' => 245, 'efg' => [1, 2, 3]]]);

            $this->expectOutputString("Saved Configutation Template to $yamlFile" . PHP_EOL);
            $svc->saveConfigTemplate($yamlFile);
            $yaml = file_get_contents($yamlFile);
            $this->assertEquals('foo:' . PHP_EOL . '   abc' . PHP_EOL . '   def' . PHP_EOL, $yaml);
        } finally {
            unlink($yamlFile);
        }
    }

    public function testScriptSaveConfigTemplateBadFileName() {
        $tmpDir = sys_get_temp_dir();
        $yamlFile = '::\\.\\##??/config-definition-test.yaml';

        try {
            $svc = $this->getMockBuilder(ScriptSupport::class)
                ->setConstructorArgs([''])
                ->setMethods(['getConfig'])
                ->getMock();
            $svc->method('getConfig')->willReturn(['foo' => ['abc' => 123, 'def' => 245, 'efg' => [1, 2, 3]]]);
            $this->expectException(Exception::class);
            $svc->saveConfigTemplate($yamlFile);
        } finally {
            if(is_file($yamlFile)) unlink($yamlFile); // Should not be...
        }
    }
    
    public function testScriptGenerateFromIniFile() {
        $tmpDir = sys_get_temp_dir();
        $tmpDir1 = $tmpDir . '/scriptGenFromIniTest';
        $iniFile = $tmpDir . '/test.ini';

        try {
            if(! is_dir($tmpDir1)) mkdir($tmpDir1);
            file_put_contents($iniFile, "[abc]\r\nfoo.bar=123\r\n");
            $svc = new ScriptSupport('', $iniFile, true);
            $this->expectOutputString("Added INI Configuration file $iniFile" . PHP_EOL);
            $yaml = $svc->generateConfigTemplate();
            $this->assertEquals("abc:" . PHP_EOL . "   foo:" . PHP_EOL . "      bar" . PHP_EOL, $yaml);
        } finally {
            if(is_file($iniFile)) unlink($iniFile);
            if(is_dir($tmpDir1)) rmdir($tmpDir1);
        }
    }

}