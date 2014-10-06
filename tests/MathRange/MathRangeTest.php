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
    $this->assertEquals(']0.0,0.0[', $r->__toString());
  }

  function dataProviderRangeUnion() {
    return array(
      // $range, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,2]', '[3,4]', '[1,4]', TRUE, 1, 4, TRUE, FALSE, FALSE),
      array('[1,2]', '[3,4[', '[1,4[', TRUE, 1, 4, FALSE, FALSE, FALSE),
      array('[1,2]', ']3,4]', '[1,4]', TRUE, 1, 4, TRUE, FALSE, FALSE),
      array('[1,2]', ']3,4[', '[1,4[', TRUE, 1, 4, FALSE, FALSE, FALSE),
      array('[1,2[', '[3,4]', '[1,4]', TRUE, 1, 4, TRUE, FALSE, FALSE),
      array('[1,2[', '[3,4[', '[1,4[', TRUE, 1, 4, FALSE, FALSE, FALSE),
      array('[1,2[', ']3,4]', '[1,4]', TRUE, 1, 4, TRUE, FALSE, FALSE),
      array('[1,2[', ']3,4[', '[1,4[', TRUE, 1, 4, FALSE, FALSE, FALSE),
      array(']1,2]', '[3,4]', ']1,4]', FALSE, 1, 4, TRUE, FALSE, FALSE),
      array(']1,2]', '[3,4[', ']1,4[', FALSE, 1, 4, FALSE, FALSE, FALSE),
      array(']1,2]', ']3,4]', ']1,4]', FALSE, 1, 4, TRUE, FALSE, FALSE),
      array(']1,2]', ']3,4[', ']1,4[', FALSE, 1, 4, FALSE, FALSE, FALSE),
      array(']1,2[', '[3,4]', ']1,4]', FALSE, 1, 4, TRUE, FALSE, FALSE),
      array(']1,2[', '[3,4[', ']1,4[', FALSE, 1, 4, FALSE, FALSE, FALSE),
      array(']1,2[', ']3,4]', ']1,4]', FALSE, 1, 4, TRUE, FALSE, FALSE),
      array(']1,2[', ']3,4[', ']1,4[', FALSE, 1, 4, FALSE, FALSE, FALSE),

      // Cases where a range fits inside another.
      array('[1,10]', '[3,4]', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array('[1,10]', '[3,4[', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array('[1,10]', ']3,4]', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array('[1,10]', ']3,4[', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array('[1,10[', '[3,4]', '[1,10[', TRUE, 1, 10, FALSE, FALSE, FALSE),
      array('[1,10[', '[3,4[', '[1,10[', TRUE, 1, 10, FALSE, FALSE, FALSE),
      array('[1,10[', ']3,4]', '[1,10[', TRUE, 1, 10, FALSE, FALSE, FALSE),
      array('[1,10[', ']3,4[', '[1,10[', TRUE, 1, 10, FALSE, FALSE, FALSE),
      array(']1,10]', '[3,4]', ']1,10]', FALSE, 1, 10, TRUE, FALSE, FALSE),
      array(']1,10]', '[3,4[', ']1,10]', FALSE, 1, 10, TRUE, FALSE, FALSE),
      array(']1,10]', ']3,4]', ']1,10]', FALSE, 1, 10, TRUE, FALSE, FALSE),
      array(']1,10]', ']3,4[', ']1,10]', FALSE, 1, 10, TRUE, FALSE, FALSE),
      array(']1,10[', '[3,4]', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),
      array(']1,10[', '[3,4[', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),
      array(']1,10[', ']3,4]', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),
      array(']1,10[', ']3,4[', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),

      // $range, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,3.14]', '[3.14,4]', '[1.0,4.0]', TRUE, 1, 4, TRUE, FALSE, TRUE),
      array('[1,3.14]', '[3.14,4[', '[1.0,4.0[', TRUE, 1, 4, FALSE, FALSE, TRUE),
      array('[1,3.14]', ']3.14,4]', '[1.0,4.0]', TRUE, 1, 4, TRUE, FALSE, TRUE),
      array('[1,3.14]', ']3.14,4[', '[1.0,4.0[', TRUE, 1, 4, FALSE, FALSE, TRUE),
      array('[1,3.14[', '[3.14,4]', '[1.0,4.0]', TRUE, 1, 4, TRUE, FALSE, TRUE),
      array('[1,3.14[', '[3.14,4[', '[1.0,4.0[', TRUE, 1, 4, FALSE, FALSE, TRUE),
      array('[1,3.14[', ']3.14,4]', '[1.0,4.0]', TRUE, 1, 4, TRUE, FALSE, TRUE),
      array('[1,3.14[', ']3.14,4[', '[1.0,4.0[', TRUE, 1, 4, FALSE, FALSE, TRUE),
      array(']1,3.14]', '[3.14,4]', ']1.0,4.0]', FALSE, 1, 4, TRUE, FALSE, TRUE),
      array(']1,3.14]', '[3.14,4[', ']1.0,4.0[', FALSE, 1, 4, FALSE, FALSE, TRUE),
      array(']1,3.14]', ']3.14,4]', ']1.0,4.0]', FALSE, 1, 4, TRUE, FALSE, TRUE),
      array(']1,3.14]', ']3.14,4[', ']1.0,4.0[', FALSE, 1, 4, FALSE, FALSE, TRUE),
      array(']1,3.14[', '[3.14,4]', ']1.0,4.0]', FALSE, 1, 4, TRUE, FALSE, TRUE),
      array(']1,3.14[', '[3.14,4[', ']1.0,4.0[', FALSE, 1, 4, FALSE, FALSE, TRUE),
      array(']1,3.14[', ']3.14,4]', ']1.0,4.0]', FALSE, 1, 4, TRUE, FALSE, TRUE),
      array(']1,3.14[', ']3.14,4[', ']1.0,4.0[', FALSE, 1, 4, FALSE, FALSE, TRUE),

      // Cases where a range fits inside another.
      array('[1,10]', '[3.14,4]', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10]', '[3.14,4[', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10]', ']3.14,4]', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10]', ']3.14,4[', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10[', '[3.14,4]', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array('[1,10[', '[3.14,4[', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array('[1,10[', ']3.14,4]', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array('[1,10[', ']3.14,4[', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10]', '[3.14,4]', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10]', '[3.14,4[', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10]', ']3.14,4]', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10]', ']3.14,4[', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10[', '[3.14,4]', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10[', '[3.14,4[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10[', ']3.14,4]', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10[', ']3.14,4[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),

      // A union will never results in empty range unless two empty ranges
      // are united.
      array(']1,1]', ']0,0[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']1,1.0]', ']0,0[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      
      // Unions with empty ranges.
      // $range, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,10]', ']0,0[', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array('[1,10[', ']0,0[', '[1,10[', TRUE, 1, 10, FALSE, FALSE, FALSE),
      array(']1,10]', ']0,0[', ']1,10]', FALSE, 1, 10, TRUE, FALSE, FALSE),
      array(']1,10[', ']0,0[', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),
      
      array(']0,0[', '[1,10]', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array(']0,0[', '[1,10[', '[1,10[', TRUE, 1, 10, FALSE, FALSE, FALSE),
      array(']0,0[', ']1,10]', ']1,10]', FALSE, 1, 10, TRUE, FALSE, FALSE),
      array(']0,0[', ']1,10[', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),
    );
  }
  
  /**
   * @dataProvider dataProviderRangeUnion
   */
  public function testRangeUnion($range, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats) {
    $r = new MathRange($range);
    $r->union($union);
    $this->assertEquals($expLBoundIn, $r->includeLowerBound(), 'Include lower bound.');
    $this->assertEquals($expLBound, $r->getLowerBound(), 'Value lower bound.');
    $this->assertEquals($expUBound, $r->getUpperBound(), 'Value upper bound.');
    $this->assertEquals($expUBoundIn, $r->includeUpperBound(), 'Include upper bound.');
    $this->assertEquals($expEmpty, $r->isEmpty(), 'Is empty range.');
    $this->assertEquals($expFloats, $r->allowFloats(), 'Range allows floats.');  
    $this->assertEquals($output, $r->__toString());
  }

  function dataProviderRangeIntersection() {
    return array(
      // $range, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,10]', '[3,4]', '[3,4]', TRUE, 3, 4, TRUE, FALSE, FALSE),
      array('[3,4]', '[1,10]', '[3,4]', TRUE, 3, 4, TRUE, FALSE, FALSE),
      
      array('[1,3]', '[4,6]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[4,6]', '[1,3]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1.0,3]', '[4,6]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[4,6]', '[1.0,3]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),

      array('[1,3]', '[3,6]', '[3,3]', TRUE, 3, 3, TRUE, FALSE, FALSE),
      array('[3,6]', '[1,3]', '[3,3]', TRUE, 3, 3, TRUE, FALSE, FALSE),
      array('[3,6]', '[1,3[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1.0,3]', '[3,6]', '[3.0,3.0]', TRUE, 3, 3, TRUE, FALSE, TRUE),
      array('[3,6]', '[1.0,3]', '[3.0,3.0]', TRUE, 3, 3, TRUE, FALSE, TRUE),
    );
  }
  
  /**
   * @dataProvider dataProviderRangeIntersection
   */
  public function testRangeIntersection($range, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats) {
    $r = new MathRange($range);
    $r->intersection($union);
    $this->assertEquals($expLBoundIn, $r->includeLowerBound(), 'Include lower bound.');
    $this->assertEquals($expLBound, $r->getLowerBound(), 'Value lower bound.');
    $this->assertEquals($expUBound, $r->getUpperBound(), 'Value upper bound.');
    $this->assertEquals($expUBoundIn, $r->includeUpperBound(), 'Include upper bound.');
    $this->assertEquals($expEmpty, $r->isEmpty(), 'Is empty range.');
    $this->assertEquals($expFloats, $r->allowFloats(), 'Range allows floats.');  
    $this->assertEquals($output, $r->__toString());
  }
}