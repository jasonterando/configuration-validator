<?php
use ConfigurationValidator\Service\Interfaces\IConfigDefFileScanner;
use ConfigurationValidator\Service\ConfigDefCollector;

class ConfigDefCollectorTest extends BaseTestCase
{
    protected $fileScanner = [];
    
    public function setUp() {
        $this->yaml = [
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

        $this->fileScanner = $this->getMockBuilder(IConfigDefFileScanner::class)
            ->setMethods(['scanForFiles'])
            ->getMock();
    }

    public function testConfigDefCollect() {
        $svc = $this->getMockBuilder(ConfigDefCollector::class)
            ->setConstructorArgs([[$this->fileScanner]])
            ->setMethods(['format'])
            ->getMock();

        $mappedYaml = ['abc' => ['def' => 123]];
        $expectedResults = ['abc' => ['def' => (object) ['type' => 'any', 'required' => true]]];
        
        $this->fileScanner->expects($this->at(0))
            ->method('scanForFiles')
            ->willReturn(['foo.yaml' => $mappedYaml]);

        $svc->expects($this->at(0))
            ->method('format')
            ->with($mappedYaml)
            ->willReturn($expectedResults);
        
        $results = $svc->collect();
        $this->assertEquals($expectedResults, $results);
    }

    public function testFormatterValid() {
        $svc = new ConfigDefCollector([$this->fileScanner]);
        $config = $this->callMethod($svc, 'format', [$this->yaml]);
        $this->assertEquals('any', $config['aws']['version']->type);
        $this->assertEquals(false, $config['aws']['version']->required);
        $this->assertEquals('any', $config['aws']['region']->type);
        $this->assertEquals(true, $config['aws']['region']->required);
        $this->assertEquals('string', $config['aws']['credentials']['key']->type);
        $this->assertEquals(true, $config['aws']['credentials']['key']->required);
        $this->assertEquals('string', $config['aws']['credentials']['secret']->type);
        $this->assertEquals(true, $config['aws']['credentials']['secret']->required);
        $this->assertEquals('url', $config['app']['janus']['url']->type);
        $this->assertEquals(true, $config['app']['janus']['url']->required);
        $this->assertEquals('any', $config['app']['folder']->type);
        $this->assertEquals(false, $config['app']['folder']->required);
        $this->assertEquals('any', $config['app']['foo']->type);
        $this->assertEquals(true, $config['app']['foo']->required);
        $this->assertEquals('any', $config['app']['bar']->type);
        $this->assertEquals(true, $config['app']['bar']->required);
        $this->assertEquals('any', $config['app']['fubar']->type);
        $this->assertEquals(true, $config['app']['fubar']->required);
    }

    public function testFormatterBadType() {
        $this->yaml['aws']['version']['type']= 'BOGUS';
        $svc = new ConfigDefCollector([$this->fileScanner]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid type \"BOGUS\" specified for aws/version");
        $this->callMethod($svc, 'format', [$this->yaml]);
    }

    public function testFormatterBadRequired() {
        $this->yaml['aws']['version']['required']= 'BOGUS';
        $svc = new ConfigDefCollector([$this->fileScanner]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid required value \"BOGUS\" specified for aws/version");
        $this->callMethod($svc, 'format', [$this->yaml]);
    }
}
