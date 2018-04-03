<?php
use ConfigurationValidator\Service\Utility;

class UtilityTest extends BaseTestCase {
    public function testGetBoolean() {
        $r = false;
        $this->assertEquals(true, Utility::getBoolean(1, $r));
        $this->assertEquals(true, $r);
        $this->assertEquals(true, Utility::getBoolean(100, $r));
        $this->assertEquals(true, $r);
        $this->assertEquals(true, Utility::getBoolean(true, $r));
        $this->assertEquals(true, $r);
        $this->assertEquals(true, Utility::getBoolean("true", $r));
        $this->assertEquals(true, $r);
        $this->assertEquals(true, Utility::getBoolean("yes", $r));
        $this->assertEquals(true, $r);
        $this->assertEquals(true, Utility::getBoolean("y", $r));
        $this->assertEquals(true, $r);

        $this->assertEquals(true, Utility::getBoolean(0, $r));
        $this->assertEquals(false, $r);
        $this->assertEquals(true, Utility::getBoolean(false, $r));
        $this->assertEquals(false, $r);
        $this->assertEquals(true, Utility::getBoolean("false", $r));
        $this->assertEquals(false, $r);
        $this->assertEquals(true, Utility::getBoolean("no", $r));
        $this->assertEquals(false, $r);
        $this->assertEquals(true, Utility::getBoolean("n", $r));
        $this->assertEquals(false, $r);

        $this->assertEquals(false, Utility::getBoolean("xxx", $r));
    }    

    public function testIsAssociativeArray() {
        $this->assertEquals(true, Utility::isAssociativeArray(['foo' => 123, 'bar' => 234]));
        $this->assertEquals(false, Utility::isAssociativeArray([0 => 123, 1 => 234]));
        $this->assertEquals(true, Utility::isAssociativeArray([0 => 123, 2 => 234]));
        $this->assertEquals(false, Utility::isAssociativeArray([]));
    }
}