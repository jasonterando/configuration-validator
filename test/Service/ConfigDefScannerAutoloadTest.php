<?php
use Composer\Autoload\ClassLoader;
use ConfigurationValidator\Service\ConfigDefScannerAutoload;

class ConfigDefScannerAutoloadTest extends BaseTestCase
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

        $svc = $this->getMockBuilder(ConfigDefScannerAutoload::class)
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
        $this->assertEquals($yaml1, $results['test1.yaml']);
        $this->assertEquals($yaml2, $results['test2.yaml']);
    }

    function testDrilldown() {
        $tmp = sys_get_temp_dir();
        $svc = $this->getMockBuilder(ConfigDefScannerAutoload::class)
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
}
