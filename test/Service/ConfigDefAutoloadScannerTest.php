<?php
use Composer\Autoload\ClassLoader;
use ConfigurationValidator\Service\ConfigDefAutoloadScanner;

class ConfigDefAutoloadScannerTest extends BaseTestCase
{
    public function setUp() {
        $this->yaml1 = [
            "app" => [
                "abc" => "url"
            ]
        ];
        $this->yaml2 = [
            "aws" => [
                "version" => [
                    "required" => false
                ],
                "region" => [
                    "required" => true
                ],
                "credentials" => [
                    "key" => [
                        "type" => "string"
                    ],
                    "secret" => "string"
                ]
            ],
            "app" => [
                "abc" => 123,
                "foo",
                "bar",
                "janus" => [
                    "url" => "url"
                ],
                "folder" => false,
                "fubar"
            ]
        ];

        $this->classLoader = $this->createMock(ClassLoader::class);
    }

    public function testScanForFiles() {
        $yamlFiles = [];
        $this->classLoader->expects($this->at(0))
            ->method('getPrefixesPsr4')
            ->willReturn(['/tmp/foo1']);
        $this->classLoader->expects($this->at(1))
            ->method('getClassMap')
            ->willReturn(['/tmp/foo2']);

        $svc = $this->getMockBuilder(ConfigDefAutoloadScanner::class)
            ->setConstructorArgs([$this->classLoader])
            ->setMethods(['drilldown'])
            ->getMock();
        
        $yaml1 = $this->yaml1;
        $svc->expects($this->at(0))
            ->method('drilldown')
            ->with('/tmp/foo1', [])
            ->will($this->returnCallback(function($dir, &$yf) use ($yaml1) {
                $yf['test1.yaml'] = $yaml1;
            }));
        $yaml2 = $this->yaml2;
        $svc->expects($this->at(1))
            ->method('drilldown')
            ->with('/tmp/foo2', ['test1.yaml' => $yaml1])
            ->will($this->returnCallback(function($dir, &$yf) use ($yaml2) {
                $yf['test2.yaml'] = $yaml2;
            }));

        $results = $svc->scanForFiles();
        $this->assertEquals($results['test1.yaml'], $yaml1);
        $this->assertEquals($results['test2.yaml'], $yaml2);
    }

    function testDrilldown() {
        $tmp = sys_get_temp_dir();
        $svc = $this->getMockBuilder(ConfigDefAutoloadScanner::class)
            ->setConstructorArgs([$this->classLoader])
            ->setMethods(['checkDirForConfigYaml'])
            ->getMock();
        $svc
            ->expects($this->at(0))
            ->method('checkDirForConfigYaml')
            ->with($tmp, [])
            ->will($this->returnCallback(function(string $tmp, array &$yf) {
                $yf[] = 'test1.yaml';
            }));

        $results = [];
        $this->callMethod($svc, 'drilldown', [['tmp' => $tmp], &$results]);
        $this->assertEquals('test1.yaml', $results[0]);
    }

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
            $svc = new ConfigDefAutoloadScanner($this->classLoader, true);
            $yamlFiles = [];
            $this->expectOutputString(
                "Added Configuration Definition file $tmpFile1" . PHP_EOL .
                "Added Configuration Definition file $tmpFile2" . PHP_EOL
            );
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
            $svc = new ConfigDefAutoloadScanner($this->classLoader, true);
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
