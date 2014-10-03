<?php

class MathRangeTest extends PHPUnit_Framework_TestCase {
  
  public function dataProviderExceptionInvalid() {
    return array(
      array('qwe'),
      array('(1,2)'),
      array('1,2'),
      array('[1,2] or 3'),
    );
  }

  /**
   * @dataProvider dataProviderExceptionInvalid
   * @expectedException MathRangeException
   * @expectedExceptionMessage Invalid expression.
   */
  public function testExceptionInvalid($val) {
    new MathRange($val);
  }

  /**
   * @expectedException MathRangeException
   * @expectedExceptionMessage Lower bound must be lower than upper bound in [3,2]
   */
  public function testExceptionWrongBounds() {
    new MathRange('[3,2]');
  }

  /**
   * @expectedException MathRangeException
   * @expectedExceptionMessage Lower bound must be lower than upper bound in [-9,-10.2]
   */
  public function testExceptionWrongBounds2() {
    new MathRange('[-9,-10.2]');
  }
  
  public function testRange() {
    $r = new MathRange('[1,2]');
    $this->assertTrue($r->includeLowerBound());
    $this->assertEquals(1, $r->getLowerBound());
    $this->assertEquals(2, $r->getUpperBound());
    $this->assertTrue($r->includeUpperBound());
    $this->assertFalse($r->isEmpty());
    $this->assertFalse($r->allowFloats());
    $this->assertEquals('[1,2]', $r->__toString());
    
    $r = new MathRange(']1,2]');
    $this->assertFalse($r->includeLowerBound());
    $this->assertEquals(1, $r->getLowerBound());
    $this->assertEquals(2, $r->getUpperBound());
    $this->assertTrue($r->includeUpperBound());
    $this->assertFalse($r->isEmpty());
    $this->assertFalse($r->allowFloats());
    $this->assertEquals(']1,2]', $r->__toString());
    
    $r = new MathRange('[1,2[');
    $this->assertTrue($r->includeLowerBound());
    $this->assertEquals(1, $r->getLowerBound());
    $this->assertEquals(2, $r->getUpperBound());
    $this->assertFalse($r->includeUpperBound());
    $this->assertFalse($r->isEmpty());
    $this->assertFalse($r->allowFloats());
    $this->assertEquals('[1,2[', $r->__toString());
    
    $r = new MathRange(']1,2[');
    $this->assertFalse($r->includeLowerBound());
    $this->assertEquals(1, $r->getLowerBound());
    $this->assertEquals(2, $r->getUpperBound());
    $this->assertFalse($r->includeUpperBound());
    $this->assertFalse($r->isEmpty());
    $this->assertFalse($r->allowFloats());
    $this->assertEquals(']1,2[', $r->__toString());
  }
  
  public function testRangeWithFloats() {
    $r = new MathRange('[1.0,2]');
    $this->assertTrue($r->includeLowerBound());
    $this->assertEquals(1.0, $r->getLowerBound());
    $this->assertEquals(2, $r->getUpperBound());
    $this->assertTrue($r->includeUpperBound());
    $this->assertFalse($r->isEmpty());
    $this->assertTrue($r->allowFloats());
    $this->assertEquals('[1.0,2.0]', $r->__toString());
    
    $r = new MathRange(']1.0,2]');
    $this->assertFalse($r->includeLowerBound());
    $this->assertEquals(1.0, $r->getLowerBound());
    $this->assertEquals(2, $r->getUpperBound());
    $this->assertTrue($r->includeUpperBound());
    $this->assertFalse($r->isEmpty());
    $this->assertTrue($r->allowFloats());
    $this->assertEquals(']1.0,2.0]', $r->__toString());
    
    $r = new MathRange('[1.0,2[');
    $this->assertTrue($r->includeLowerBound());
    $this->assertEquals(1.0, $r->getLowerBound());
    $this->assertEquals(2, $r->getUpperBound());
    $this->assertFalse($r->includeUpperBound());
    $this->assertFalse($r->isEmpty());
    $this->assertTrue($r->allowFloats());
    $this->assertEquals('[1.0,2.0[', $r->__toString());
    
    $r = new MathRange(']1.0,2[');
    $this->assertFalse($r->includeLowerBound());
    $this->assertEquals(1.0, $r->getLowerBound());
    $this->assertEquals(2, $r->getUpperBound());
    $this->assertFalse($r->includeUpperBound());
    $this->assertFalse($r->isEmpty());
    $this->assertTrue($r->allowFloats());
    $this->assertEquals(']1.0,2.0[', $r->__toString());
    
    $r = new MathRange('[1,2.25]');
    $this->assertTrue($r->includeLowerBound());
    $this->assertEquals(1.0, $r->getLowerBound());
    $this->assertEquals(2.25, $r->getUpperBound());
    $this->assertTrue($r->includeUpperBound());
    $this->assertFalse($r->isEmpty());
    $this->assertTrue($r->allowFloats());
    $this->assertEquals('[1.0,2.25]', $r->__toString());
  }
  
  public function testRangeEmpty() {
    $r = new MathRange('[1,1]');
    $this->assertTrue($r->includeLowerBound());
    $this->assertEquals(1, $r->getLowerBound());
    $this->assertEquals(1, $r->getUpperBound());
    $this->assertTrue($r->includeUpperBound());
    $this->assertFalse($r->isEmpty());
    $this->assertFalse($r->allowFloats());
    $this->assertEquals('[1,1]', $r->__toString());
    
    $r = new MathRange(']1,1]');
    $this->assertFalse($r->includeLowerBound());
    $this->assertEquals(0, $r->getLowerBound());
    $this->assertEquals(0, $r->getUpperBound());
    $this->assertFalse($r->includeUpperBound());
    $this->assertTrue($r->isEmpty());
    $this->assertFalse($r->allowFloats());
    $this->assertEquals(']0,0[', $r->__toString());
    
    $r = new MathRange('[1,1[');
    $this->assertFalse($r->includeLowerBound());
    $this->assertEquals(0, $r->getLowerBound());
    $this->assertEquals(0, $r->getUpperBound());
    $this->assertFalse($r->includeUpperBound());
    $this->assertTrue($r->isEmpty());
    $this->assertFalse($r->allowFloats());
    $this->assertEquals(']0,0[', $r->__toString());
    
    $r = new MathRange(']1,1[');
    $this->assertFalse($r->includeLowerBound());
    $this->assertEquals(0, $r->getLowerBound());
    $this->assertEquals(0, $r->getUpperBound());
    $this->assertFalse($r->includeUpperBound());
    $this->assertTrue($r->isEmpty());
    $this->assertFalse($r->allowFloats());  
    $this->assertEquals(']0,0[', $r->__toString());
  }
  
  public function testRangeEmptyWithFloats() {
    $r = new MathRange('[1.0,1]');
    $this->assertTrue($r->includeLowerBound());
    $this->assertEquals(1, $r->getLowerBound());
    $this->assertEquals(1, $r->getUpperBound());
    $this->assertTrue($r->includeUpperBound());
    $this->assertFalse($r->isEmpty());
    $this->assertTrue($r->allowFloats());
    $this->assertEquals('[1.0,1.0]', $r->__toString());
    
    $r = new MathRange(']1.0,1]');
    $this->assertFalse($r->includeLowerBound());
    $this->assertEquals(0, $r->getLowerBound());
    $this->assertEquals(0, $r->getUpperBound());
    $this->assertFalse($r->includeUpperBound());
    $this->assertTrue($r->isEmpty());
    $this->assertTrue($r->allowFloats());
    $this->assertEquals(']0.0,0.0[', $r->__toString());
    
    $r = new MathRange('[1.0,1[');
    $this->assertFalse($r->includeLowerBound());
    $this->assertEquals(0, $r->getLowerBound());
    $this->assertEquals(0, $r->getUpperBound());
    $this->assertFalse($r->includeUpperBound());
    $this->assertTrue($r->isEmpty());
    $this->assertTrue($r->allowFloats());
    $this->assertEquals(']0.0,0.0[', $r->__toString());
    
    $r = new MathRange(']1.0,1[');
    $this->assertFalse($r->includeLowerBound());
    $this->assertEquals(0, $r->getLowerBound());
    $this->assertEquals(0, $r->getUpperBound());
    $this->assertFalse($r->includeUpperBound());
    $this->assertTrue($r->isEmpty());
    $this->assertTrue($r->allowFloats());  
    $this->assertEquals(']0.0 ,0.0[', $r->__toString());
  }
}