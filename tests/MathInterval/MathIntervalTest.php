<?php

class MathIntervalTest extends PHPUnit_Framework_TestCase {
  
  public function dataProviderExceptionInvalid() {
    return array(
      array('qwe'),
      array('(1,2)'),
      array('1,2'),
      array('[1,2] or 3'),

      // The function contructor relies on compute().
      // That function can be tested through this.
      array('[1,2] orq [3,4]'),
      array('[1,2] adn [3,4]'),
      array('[1,2] or [3,5] and, [3,10]'),

      // Test expressions with parenthesis.
      array('[1,2] or [3,5] and ()[3,10])'),
      array('[1,2] or [3,5] and (((([3,10])))'),
      array('(([1,2] or (([3,5]) and [3,10]))'),
      array('([1,2] or ([3,5] and) [3,10])'),
    );
  }

  /**
   * @dataProvider dataProviderExceptionInvalid
   * @expectedException MathIntervalException
   * @expectedExceptionMessage Invalid expression.
   */
  public function testExceptionInvalid($val) {
    new MathInterval($val);
  }

  /**
   * @expectedException MathIntervalException
   * @expectedExceptionMessage Lower bound must be lower than upper bound in [3,2]
   */
  public function testExceptionWrongBounds() {
    new MathInterval('[3,2]');
  }

  /**
   * @expectedException MathIntervalException
   * @expectedExceptionMessage Lower bound must be lower than upper bound in [-9,-10.2]
   */
  public function testExceptionWrongBounds2() {
    new MathInterval('[-9,-10.2]');
  }

  function dataProviderInterval() {
    return array(
      // Intervals.
      array('[1,2]', '[1,2]', TRUE, 1, 2, TRUE, FALSE, FALSE),
      array('[1,2[', '[1,2[', TRUE, 1, 2, FALSE, FALSE, FALSE),
      array(']1,2]', ']1,2]', FALSE, 1, 2, TRUE, FALSE, FALSE),
      array(']1,2[', ']1,2[', FALSE, 1, 2, FALSE, FALSE, FALSE),
      
      // Intervals with floats.
      array('[1.0,2]', '[1.0,2.0]', TRUE, 1, 2, TRUE, FALSE, TRUE),
      array('[1.0,2[', '[1.0,2.0[', TRUE, 1, 2, FALSE, FALSE, TRUE),
      array(']1.0,2]', ']1.0,2.0]', FALSE, 1, 2, TRUE, FALSE, TRUE),
      array(']1.0,2[', ']1.0,2.0[', FALSE, 1, 2, FALSE, FALSE, TRUE),
      array('[1.0,2.25]', '[1.0,2.25]', TRUE, 1, 2.25, TRUE, FALSE, TRUE),
      
      // Empty intervals.
      // $interval, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,1]', '[1,1]', TRUE, 1, 1, TRUE, FALSE, FALSE),
      array(']1,1]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1,1[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']1,1[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      
      // Empty intervals with floats.
      // $interval, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1.0,1]', '[1.0,1.0]', TRUE, 1, 1, TRUE, FALSE, TRUE),
      array(']1.0,1]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[1.0,1[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']1.0,1[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
    );
  }

  /**
   * @dataProvider dataProviderInterval
   */
  public function testInterval($interval, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats) {
    $r = new MathInterval($interval);
    $this->assertEquals($expLBoundIn, $r->includeLowerBound(), 'Include lower bound.');
    $this->assertEquals($expLBound, $r->getLowerBound(), 'Value lower bound.');
    $this->assertEquals($expUBound, $r->getUpperBound(), 'Value upper bound.');
    $this->assertEquals($expUBoundIn, $r->includeUpperBound(), 'Include upper bound.');
    $this->assertEquals($expEmpty, $r->isEmpty(), 'Is empty range.');
    $this->assertEquals($expFloats, $r->allowFloats(), 'Range allows floats.');  
    $this->assertEquals($output, $r->__toString());
  }
  
  public function testInInterval() {
    $interval = new MathInterval('[1,10]');
    $this->assertFalse($interval->inInterval(11));
    $this->assertFalse($interval->inInterval(5.5));
    $this->assertFalse($interval->inInterval(-1));
    $this->assertFalse($interval->inInterval('NaN'));
    $this->assertFalse($interval->inInterval('x3'));
    $this->assertTrue($interval->inInterval('5'));
    $this->assertTrue($interval->inInterval(10));
    $this->assertTrue($interval->inInterval(1));
    $this->assertTrue($interval->inInterval(5));

    $interval = new MathInterval('[1,10[');
    $this->assertFalse($interval->inInterval(10));
    $this->assertTrue($interval->inInterval(1));

    $interval = new MathInterval(']1,10]');
    $this->assertFalse($interval->inInterval(1));
    $this->assertTrue($interval->inInterval(10));

    $interval = new MathInterval(']1,10[');
    $this->assertFalse($interval->inInterval(1));
    $this->assertFalse($interval->inInterval(10));

    $interval = new MathInterval('[1,1]');
    $this->assertTrue($interval->inInterval(1));

    $interval = new MathInterval(']0,0[');
    $this->assertFalse($interval->inInterval(0));

    $interval = new MathInterval('[1.5,10]');
    $this->assertTrue($interval->inInterval(5.5));
    $this->assertTrue($interval->inInterval('3.14159265'));
    $this->assertTrue($interval->inInterval(M_PI));
    $this->assertTrue($interval->inInterval(10));
  }

  function dataProviderIntervalUnion() {
    return array(
      // Intervals with equal values but different inclusions.
      // $interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,10]', '[1,10]', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array('[1,10]', '[1,10[', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array('[1,10]', ']1,10]', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array('[1,10]', ']1,10[', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array('[1,10[', '[1,10]', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array('[1,10[', '[1,10[', '[1,10[', TRUE, 1, 10, FALSE, FALSE, FALSE),
      array('[1,10[', ']1,10]', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array('[1,10[', ']1,10[', '[1,10[', TRUE, 1, 10, FALSE, FALSE, FALSE),
      array(']1,10]', '[1,10]', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array(']1,10]', '[1,10[', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array(']1,10]', ']1,10]', ']1,10]', FALSE, 1, 10, TRUE, FALSE, FALSE),
      array(']1,10]', ']1,10[', ']1,10]', FALSE, 1, 10, TRUE, FALSE, FALSE),
      array(']1,10[', '[1,10]', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array(']1,10[', '[1,10[', '[1,10[', TRUE, 1, 10, FALSE, FALSE, FALSE),
      array(']1,10[', ']1,10]', ']1,10]', FALSE, 1, 10, TRUE, FALSE, FALSE),
      array(']1,10[', ']1,10[', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),
      
      array('[1,10.0]', '[1,10]', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10.0]', '[1,10[', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10.0]', ']1,10]', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10.0]', ']1,10[', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10.0[', '[1,10]', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10.0[', '[1,10[', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array('[1,10.0[', ']1,10]', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10.0[', ']1,10[', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10.0]', '[1,10]', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10.0]', '[1,10[', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10.0]', ']1,10]', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10.0]', ']1,10[', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10.0[', '[1,10]', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10.0[', '[1,10[', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10.0[', ']1,10]', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10.0[', ']1,10[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),

      // $interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      // Cases where an interval fits inside another.
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

      // $interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,3.14]', '[3.14,4]', '[1.0,4.0]', TRUE, 1, 4, TRUE, FALSE, TRUE),
      array('[1,3.14]', '[3.14,4[', '[1.0,4.0[', TRUE, 1, 4, FALSE, FALSE, TRUE),
      array('[1,3.14]', ']3.14,4]', '[1.0,4.0]', TRUE, 1, 4, TRUE, FALSE, TRUE),
      array('[1,3.14]', ']3.14,4[', '[1.0,4.0[', TRUE, 1, 4, FALSE, FALSE, TRUE),
      array('[1,3.14[', '[3.14,4]', '[1.0,4.0]', TRUE, 1, 4, TRUE, FALSE, TRUE),
      array('[1,3.14[', '[3.14,4[', '[1.0,4.0[', TRUE, 1, 4, FALSE, FALSE, TRUE),
      // testIntervalFalseUnion
      //array('[1,3.14[', ']3.14,4]', '[1.0,4.0]', TRUE, 1, 4, TRUE, FALSE, TRUE),
      //array('[1,3.14[', ']3.14,4[', '[1.0,4.0[', TRUE, 1, 4, FALSE, FALSE, TRUE),
      array(']1,3.14]', '[3.14,4]', ']1.0,4.0]', FALSE, 1, 4, TRUE, FALSE, TRUE),
      array(']1,3.14]', '[3.14,4[', ']1.0,4.0[', FALSE, 1, 4, FALSE, FALSE, TRUE),
      array(']1,3.14]', ']3.14,4]', ']1.0,4.0]', FALSE, 1, 4, TRUE, FALSE, TRUE),
      array(']1,3.14]', ']3.14,4[', ']1.0,4.0[', FALSE, 1, 4, FALSE, FALSE, TRUE),
      array(']1,3.14[', '[3.14,4]', ']1.0,4.0]', FALSE, 1, 4, TRUE, FALSE, TRUE),
      array(']1,3.14[', '[3.14,4[', ']1.0,4.0[', FALSE, 1, 4, FALSE, FALSE, TRUE),
      // testIntervalFalseUnion
      //array(']1,3.14[', ']3.14,4]', ']1.0,4.0]', FALSE, 1, 4, TRUE, FALSE, TRUE),
      //array(']1,3.14[', ']3.14,4[', ']1.0,4.0[', FALSE, 1, 4, FALSE, FALSE, TRUE),

      // Cases where an interval fits inside another.
      array('[1,10]', '[3.14,4]', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10]', '[3.14,4[', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10]', ']3.14,4]', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10]', ']3.14,4[', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10[', '[3.14,4]', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array('[1,10[', '[3.14,4[', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      // testIntervalFalseUnion
      //array('[1,10[', ']3.14,4]', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      //array('[1,10[', ']3.14,4[', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10]', '[3.14,4]', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10]', '[3.14,4[', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10]', ']3.14,4]', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10]', ']3.14,4[', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10[', '[3.14,4]', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10[', '[3.14,4[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      // testIntervalFalseUnion
      //array(']1,10[', ']3.14,4]', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      //array(']1,10[', ']3.14,4[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      
      // Intervals overlap on lower bound.
      // $interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,8]', '[1,5]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[1,8]', '[1,5[', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[1,8]', ']1,5]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[1,8]', ']1,5[', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[1,8[', '[1,5]', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array('[1,8[', '[1,5[', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array('[1,8[', ']1,5]', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array('[1,8[', ']1,5[', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array(']1,8]', '[1,5]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array(']1,8]', '[1,5[', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array(']1,8]', ']1,5]', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array(']1,8]', ']1,5[', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array(']1,8[', '[1,5]', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array(']1,8[', '[1,5[', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array(']1,8[', ']1,5]', ']1,8[', FALSE, 1, 8, FALSE, FALSE, FALSE),
      array(']1,8[', ']1,5[', ']1,8[', FALSE, 1, 8, FALSE, FALSE, FALSE),

      array('[1,5]', '[1,8]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[1,5[', '[1,8]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array(']1,5]', '[1,8]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array(']1,5[', '[1,8]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[1,5]', '[1,8[', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array('[1,5[', '[1,8[', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array(']1,5]', '[1,8[', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array(']1,5[', '[1,8[', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array('[1,5]', ']1,8]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[1,5[', ']1,8]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array(']1,5]', ']1,8]', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array(']1,5[', ']1,8]', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array('[1,5]', ']1,8[', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array('[1,5[', ']1,8[', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array(']1,5]', ']1,8[', ']1,8[', FALSE, 1, 8, FALSE, FALSE, FALSE),
      array(']1,5[', ']1,8[', ']1,8[', FALSE, 1, 8, FALSE, FALSE, FALSE),

      array('[1.0,8]', '[1,5]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[1.0,8]', '[1,5[', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[1.0,8]', ']1,5]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[1.0,8]', ']1,5[', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[1.0,8[', '[1,5]', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array('[1.0,8[', '[1,5[', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array('[1.0,8[', ']1,5]', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array('[1.0,8[', ']1,5[', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array(']1.0,8]', '[1,5]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array(']1.0,8]', '[1,5[', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array(']1.0,8]', ']1,5]', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array(']1.0,8]', ']1,5[', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array(']1.0,8[', '[1,5]', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array(']1.0,8[', '[1,5[', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array(']1.0,8[', ']1,5]', ']1.0,8.0[', FALSE, 1, 8, FALSE, FALSE, TRUE),
      array(']1.0,8[', ']1,5[', ']1.0,8.0[', FALSE, 1, 8, FALSE, FALSE, TRUE),

      array('[1,5]', '[1.0,8]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[1,5[', '[1.0,8]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array(']1,5]', '[1.0,8]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array(']1,5[', '[1.0,8]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[1,5]', '[1.0,8[', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array('[1,5[', '[1.0,8[', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array(']1,5]', '[1.0,8[', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array(']1,5[', '[1.0,8[', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array('[1,5]', ']1.0,8]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[1,5[', ']1.0,8]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array(']1,5]', ']1.0,8]', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array(']1,5[', ']1.0,8]', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array('[1,5]', ']1.0,8[', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array('[1,5[', ']1.0,8[', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array(']1,5]', ']1.0,8[', ']1.0,8.0[', FALSE, 1, 8, FALSE, FALSE, TRUE),
      array(']1,5[', ']1.0,8[', ']1.0,8.0[', FALSE, 1, 8, FALSE, FALSE, TRUE),

      // Intervals overlap on upper bound.
      // $interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,8]', '[3,8]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[1,8]', '[3,8[', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[1,8]', ']3,8]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[1,8]', ']3,8[', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[1,8[', '[3,8]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[1,8[', '[3,8[', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array('[1,8[', ']3,8]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[1,8[', ']3,8[', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array(']1,8]', '[3,8]', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array(']1,8]', '[3,8[', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array(']1,8]', ']3,8]', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array(']1,8]', ']3,8[', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array(']1,8[', '[3,8]', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array(']1,8[', '[3,8[', ']1,8[', FALSE, 1, 8, FALSE, FALSE, FALSE),
      array(']1,8[', ']3,8]', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array(']1,8[', ']3,8[', ']1,8[', FALSE, 1, 8, FALSE, FALSE, FALSE),

      array('[3,8]', '[1,8]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[3,8[', '[1,8]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array(']3,8]', '[1,8]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array(']3,8[', '[1,8]', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[3,8]', '[1,8[', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array('[3,8[', '[1,8[', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array(']3,8]', '[1,8[', '[1,8]', TRUE, 1, 8, TRUE, FALSE, FALSE),
      array(']3,8[', '[1,8[', '[1,8[', TRUE, 1, 8, FALSE, FALSE, FALSE),
      array('[3,8]', ']1,8]', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array('[3,8[', ']1,8]', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array(']3,8]', ']1,8]', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array(']3,8[', ']1,8]', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array('[3,8]', ']1,8[', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array('[3,8[', ']1,8[', ']1,8[', FALSE, 1, 8, FALSE, FALSE, FALSE),
      array(']3,8]', ']1,8[', ']1,8]', FALSE, 1, 8, TRUE, FALSE, FALSE),
      array(']3,8[', ']1,8[', ']1,8[', FALSE, 1, 8, FALSE, FALSE, FALSE),

      array('[1.0,8]', '[3,8]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[1.0,8]', '[3,8[', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[1.0,8]', ']3,8]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[1.0,8]', ']3,8[', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[1.0,8[', '[3,8]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[1.0,8[', '[3,8[', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array('[1.0,8[', ']3,8]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[1.0,8[', ']3,8[', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array(']1.0,8]', '[3,8]', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array(']1.0,8]', '[3,8[', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array(']1.0,8]', ']3,8]', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array(']1.0,8]', ']3,8[', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array(']1.0,8[', '[3,8]', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array(']1.0,8[', '[3,8[', ']1.0,8.0[', FALSE, 1, 8, FALSE, FALSE, TRUE),
      array(']1.0,8[', ']3,8]', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array(']1.0,8[', ']3,8[', ']1.0,8.0[', FALSE, 1, 8, FALSE, FALSE, TRUE),

      array('[3,8]', '[1.0,8]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[3,8[', '[1.0,8]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array(']3,8]', '[1.0,8]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array(']3,8[', '[1.0,8]', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[3,8]', '[1.0,8[', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array('[3,8[', '[1.0,8[', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array(']3,8]', '[1.0,8[', '[1.0,8.0]', TRUE, 1, 8, TRUE, FALSE, TRUE),
      array(']3,8[', '[1.0,8[', '[1.0,8.0[', TRUE, 1, 8, FALSE, FALSE, TRUE),
      array('[3,8]', ']1.0,8]', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array('[3,8[', ']1.0,8]', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array(']3,8]', ']1.0,8]', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array(']3,8[', ']1.0,8]', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array('[3,8]', ']1.0,8[', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array('[3,8[', ']1.0,8[', ']1.0,8.0[', FALSE, 1, 8, FALSE, FALSE, TRUE),
      array(']3,8]', ']1.0,8[', ']1.0,8.0]', FALSE, 1, 8, TRUE, FALSE, TRUE),
      array(']3,8[', ']1.0,8[', ']1.0,8.0[', FALSE, 1, 8, FALSE, FALSE, TRUE),
      
      array('[1,3]', '[3,6]', '[1,6]', TRUE, 1, 6, TRUE, FALSE, FALSE),
      array('[1,3]', '[3,6[', '[1,6[', TRUE, 1, 6, FALSE, FALSE, FALSE),
      array('[1,3]', ']3,6]', '[1,6]', TRUE, 1, 6, TRUE, FALSE, FALSE),
      array('[1,3]', ']3,6[', '[1,6[', TRUE, 1, 6, FALSE, FALSE, FALSE),
      array('[1,3[', '[3,6]', '[1,6]', TRUE, 1, 6, TRUE, FALSE, FALSE),
      array('[1,3[', '[3,6[', '[1,6[', TRUE, 1, 6, FALSE, FALSE, FALSE),
      // testIntervalFalseUnion
      //array('[1,3[', ']3,6]', '[1,6]', TRUE, 1, 6, TRUE, FALSE, FALSE),
      //array('[1,3[', ']3,6[', '[1,6[', TRUE, 1, 6, FALSE, FALSE, FALSE),
      array(']1,3]', '[3,6]', ']1,6]', FALSE, 1, 6, TRUE, FALSE, FALSE),
      array(']1,3]', '[3,6[', ']1,6[', FALSE, 1, 6, FALSE, FALSE, FALSE),
      array(']1,3]', ']3,6]', ']1,6]', FALSE, 1, 6, TRUE, FALSE, FALSE),
      array(']1,3]', ']3,6[', ']1,6[', FALSE, 1, 6, FALSE, FALSE, FALSE),
      array(']1,3[', '[3,6]', ']1,6]', FALSE, 1, 6, TRUE, FALSE, FALSE),
      array(']1,3[', '[3,6[', ']1,6[', FALSE, 1, 6, FALSE, FALSE, FALSE),
      // testIntervalFalseUnion
      //array(']1,3[', ']3,6]', ']1,6]', FALSE, 1, 6, TRUE, FALSE, FALSE),
      //array(']1,3[', ']3,6[', ']1,6[', FALSE, 1, 6, FALSE, FALSE, FALSE),
      
      array('[3,6]', '[1,3]', '[1,6]', TRUE, 1, 6, TRUE, FALSE, FALSE),
      array('[3,6[', '[1,3]', '[1,6[', TRUE, 1, 6, FALSE, FALSE, FALSE),
      array(']3,6]', '[1,3]', '[1,6]', TRUE, 1, 6, TRUE, FALSE, FALSE),
      array(']3,6[', '[1,3]', '[1,6[', TRUE, 1, 6, FALSE, FALSE, FALSE),
      array('[3,6]', '[1,3[', '[1,6]', TRUE, 1, 6, TRUE, FALSE, FALSE),
      array('[3,6[', '[1,3[', '[1,6[', TRUE, 1, 6, FALSE, FALSE, FALSE),
      // testIntervalFalseUnion
      //array(']3,6]', '[1,3[', '[1,6]', TRUE, 1, 6, TRUE, FALSE, FALSE),
      //array(']3,6[', '[1,3[', '[1,6[', TRUE, 1, 6, FALSE, FALSE, FALSE),
      array('[3,6]', ']1,3]', ']1,6]', FALSE, 1, 6, TRUE, FALSE, FALSE),
      array('[3,6[', ']1,3]', ']1,6[', FALSE, 1, 6, FALSE, FALSE, FALSE),
      array(']3,6]', ']1,3]', ']1,6]', FALSE, 1, 6, TRUE, FALSE, FALSE),
      array(']3,6[', ']1,3]', ']1,6[', FALSE, 1, 6, FALSE, FALSE, FALSE),
      array('[3,6]', ']1,3[', ']1,6]', FALSE, 1, 6, TRUE, FALSE, FALSE),
      array('[3,6[', ']1,3[', ']1,6[', FALSE, 1, 6, FALSE, FALSE, FALSE),
      // testIntervalFalseUnion
      //array(']3,6]', ']1,3[', ']1,6]', FALSE, 1, 6, TRUE, FALSE, FALSE),
      //array(']3,6[', ']1,3[', ']1,6[', FALSE, 1, 6, FALSE, FALSE, FALSE),
      
      array('[1,3.0]', '[3,6]', '[1.0,6.0]', TRUE, 1, 6, TRUE, FALSE, TRUE),
      array('[1,3.0]', '[3,6[', '[1.0,6.0[', TRUE, 1, 6, FALSE, FALSE, TRUE),
      array('[1,3.0]', ']3,6]', '[1.0,6.0]', TRUE, 1, 6, TRUE, FALSE, TRUE),
      array('[1,3.0]', ']3,6[', '[1.0,6.0[', TRUE, 1, 6, FALSE, FALSE, TRUE),
      array('[1,3.0[', '[3,6]', '[1.0,6.0]', TRUE, 1, 6, TRUE, FALSE, TRUE),
      array('[1,3.0[', '[3,6[', '[1.0,6.0[', TRUE, 1, 6, FALSE, FALSE, TRUE),
      // testIntervalFalseUnion
      //array('[1,3[', ']3,6]', '[1.0,6.0]', TRUE, 1, 6, TRUE, FALSE, TRUE),
      //array('[1,3[', ']3,6[', '[1.0,6.0[', TRUE, 1, 6, FALSE, FALSE, TRUE),
      array(']1,3.0]', '[3,6]', ']1.0,6.0]', FALSE, 1, 6, TRUE, FALSE, TRUE),
      array(']1,3.0]', '[3,6[', ']1.0,6.0[', FALSE, 1, 6, FALSE, FALSE, TRUE),
      array(']1,3.0]', ']3,6]', ']1.0,6.0]', FALSE, 1, 6, TRUE, FALSE, TRUE),
      array(']1,3.0]', ']3,6[', ']1.0,6.0[', FALSE, 1, 6, FALSE, FALSE, TRUE),
      array(']1,3.0[', '[3,6]', ']1.0,6.0]', FALSE, 1, 6, TRUE, FALSE, TRUE),
      array(']1,3.0[', '[3,6[', ']1.0,6.0[', FALSE, 1, 6, FALSE, FALSE, TRUE),
      // testIntervalFalseUnion
      //array(']1,3.0[', ']3,6]', ']1.0,6.0]', FALSE, 1, 6, TRUE, FALSE, TRUE),
      //array(']1,3.0[', ']3,6[', ']1.0,6.0[', FALSE, 1, 6, FALSE, FALSE, TRUE),
      
      array('[3,6]', '[1,3.0]', '[1.0,6.0]', TRUE, 1, 6, TRUE, FALSE, TRUE),
      array('[3,6[', '[1,3.0]', '[1.0,6.0[', TRUE, 1, 6, FALSE, FALSE, TRUE),
      array(']3,6]', '[1,3.0]', '[1.0,6.0]', TRUE, 1, 6, TRUE, FALSE, TRUE),
      array(']3,6[', '[1,3.0]', '[1.0,6.0[', TRUE, 1, 6, FALSE, FALSE, TRUE),
      array('[3,6]', '[1,3.0[', '[1.0,6.0]', TRUE, 1, 6, TRUE, FALSE, TRUE),
      array('[3,6[', '[1,3.0[', '[1.0,6.0[', TRUE, 1, 6, FALSE, FALSE, TRUE),
      // testIntervalFalseUnion
      //array(']3,6]', '[1,3.0[', '[1.0,6.0]', TRUE, 1, 6, TRUE, FALSE, TRUE),
      //array(']3,6[', '[1,3.0[', '[1.0,6.0[', TRUE, 1, 6, FALSE, FALSE, TRUE),
      array('[3,6]', ']1,3.0]', ']1.0,6.0]', FALSE, 1, 6, TRUE, FALSE, TRUE),
      array('[3,6[', ']1,3.0]', ']1.0,6.0[', FALSE, 1, 6, FALSE, FALSE, TRUE),
      array(']3,6]', ']1,3.0]', ']1.0,6.0]', FALSE, 1, 6, TRUE, FALSE, TRUE),
      array(']3,6[', ']1,3.0]', ']1.0,6.0[', FALSE, 1, 6, FALSE, FALSE, TRUE),
      array('[3,6]', ']1,3.0[', ']1.0,6.0]', FALSE, 1, 6, TRUE, FALSE, TRUE),
      array('[3,6[', ']1,3.0[', ']1.0,6.0[', FALSE, 1, 6, FALSE, FALSE, TRUE),
      // testIntervalFalseUnion
      //array(']3,6]', ']1,3.0[', ']1.0,6.0]', FALSE, 1, 6, TRUE, FALSE, TRUE),
      //array(']3,6[', ']1,3.0[', ']1.0,6.0[', FALSE, 1, 6, FALSE, FALSE, TRUE),
      
      // Intervals unite in only one point.
      // $interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,3]', '[3,6]', '[1,6]', TRUE, 1, 6, TRUE, FALSE, FALSE),

      // A union will never results in empty interval unless two empty intervals
      // are united.
      array(']1,1]', ']0,0[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']1,1.0]', ']0,0[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),

      // Unions with empty intervals.
      // $interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,10]', ']0,0[', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array('[1,10[', ']0,0[', '[1,10[', TRUE, 1, 10, FALSE, FALSE, FALSE),
      array(']1,10]', ']0,0[', ']1,10]', FALSE, 1, 10, TRUE, FALSE, FALSE),
      array(']1,10[', ']0,0[', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),

      array(']0,0[', '[1,10]', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array(']0,0[', '[1,10[', '[1,10[', TRUE, 1, 10, FALSE, FALSE, FALSE),
      array(']0,0[', ']1,10]', ']1,10]', FALSE, 1, 10, TRUE, FALSE, FALSE),
      array(']0,0[', ']1,10[', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),

      array(']0,0[', '[1.0,10]', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array(']0,0[', '[1.0,10[', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array(']0,0[', ']1.0,10]', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']0,0[', ']1.0,10[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),

      array('[1.0,10]', ']0,0[', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1.0,10[', ']0,0[', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array(']1.0,10]', ']0,0[', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1.0,10[', ']0,0[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),

      array('[1,10]', ']0.0,0.0[', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10[', ']0.0,0.0[', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10]', ']0.0,0.0[', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10[', ']0.0,0.0[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),

      array(']0.0,0.0[', '[1,10]', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array(']0.0,0.0[', '[1,10[', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array(']0.0,0.0[', ']1,10]', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']0.0,0.0[', ']1,10[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
    );
  }
  
  /**
   * @dataProvider dataProviderIntervalUnion
   */
  public function testIntervalUnion($interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats) {
    $r = new MathInterval($interval);
    $this->assertInstanceOf('MathInterval', $r->union($union));
    $this->assertEquals($expLBoundIn, $r->includeLowerBound(), 'Include lower bound.');
    $this->assertEquals($expLBound, $r->getLowerBound(), 'Value lower bound.');
    $this->assertEquals($expUBound, $r->getUpperBound(), 'Value upper bound.');
    $this->assertEquals($expUBoundIn, $r->includeUpperBound(), 'Include upper bound.');
    $this->assertEquals($expEmpty, $r->isEmpty(), 'Is empty range.');
    $this->assertEquals($expFloats, $r->allowFloats(), 'Range allows floats.');  
    $this->assertEquals($output, $r->__toString());
  }

  function dataProviderIntervalFalseUnion() {
    return array(
      // Intervals with equal values but different inclusions.
      // $interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array(']1,3[', ']3,6]'),
      array(']1,3[', ']3,6['),
      array('[1,3[', ']3,6]'),
      array('[1,3[', ']3,6['),
      array(']3,6]', ']1,3['),
      array(']3,6[', ']1,3['),
      array(']3,6]', '[1,3['),
      array(']3,6[', '[1,3['),
      array(']1.0,3[', ']3,6]'),
      array(']1.0,3[', ']3,6['),
      array('[1.0,3[', ']3,6]'),
      array('[1.0,3[', ']3,6['),
      array(']3,6]', ']1.0,3['),
      array(']3,6[', ']1.0,3['),
      array(']3,6]', '[1.0,3['),
      array(']3,6[', '[1.0,3['),

      array('[1,2]', '[5,6]'),
      array('[1,2]', '[5,6['),
      array('[1,2]', ']5,6]'),
      array('[1,2]', ']5,6['),
      array('[1,2[', '[5,6]'),
      array('[1,2[', '[5,6['),
      array('[1,2[', ']5,6]'),
      array('[1,2[', ']5,6['),
      array(']1,2]', '[5,6]'),
      array(']1,2]', '[5,6['),
      array(']1,2]', ']5,6]'),
      array(']1,2]', ']5,6['),
      array(']1,2[', '[5,6]'),
      array(']1,2[', '[5,6['),
      array(']1,2[', ']5,6]'),
      array(']1,2[', ']5,6['),

      array('[5,6]', '[1,2]'),
      array('[5,6[', '[1,2]'),
      array(']5,6]', '[1,2]'),
      array(']5,6[', '[1,2]'),
      array('[5,6]', '[1,2['),
      array('[5,6[', '[1,2['),
      array(']5,6]', '[1,2['),
      array(']5,6[', '[1,2['),
      array('[5,6]', ']1,2]'),
      array('[5,6[', ']1,2]'),
      array(']5,6]', ']1,2]'),
      array(']5,6[', ']1,2]'),
      array('[5,6]', ']1,2['),
      array('[5,6[', ']1,2['),
      array(']5,6]', ']1,2['),
      array(']5,6[', ']1,2['),

      array('[1.0,2]', '[5,6]'),
      array('[1.0,2]', '[5,6['),
      array('[1.0,2]', ']5,6]'),
      array('[1.0,2]', ']5,6['),
      array('[1.0,2[', '[5,6]'),
      array('[1.0,2[', '[5,6['),
      array('[1.0,2[', ']5,6]'),
      array('[1.0,2[', ']5,6['),
      array(']1.0,2]', '[5,6]'),
      array(']1.0,2]', '[5,6['),
      array(']1.0,2]', ']5,6]'),
      array(']1.0,2]', ']5,6['),
      array(']1.0,2[', '[5,6]'),
      array(']1.0,2[', '[5,6['),
      array(']1.0,2[', ']5,6]'),
      array(']1.0,2[', ']5,6['),

      array('[5,6]', '[1.0,2]'),
      array('[5,6[', '[1.0,2]'),
      array(']5,6]', '[1.0,2]'),
      array(']5,6[', '[1.0,2]'),
      array('[5,6]', '[1.0,2['),
      array('[5,6[', '[1.0,2['),
      array(']5,6]', '[1.0,2['),
      array(']5,6[', '[1.0,2['),
      array('[5,6]', ']1.0,2]'),
      array('[5,6[', ']1.0,2]'),
      array(']5,6]', ']1.0,2]'),
      array(']5,6[', ']1.0,2]'),
      array('[5,6]', ']1.0,2['),
      array('[5,6[', ']1.0,2['),
      array(']5,6]', ']1.0,2['),
      array(']5,6[', ']1.0,2['),
    );
  }
  
  /**
   * @dataProvider dataProviderIntervalFalseUnion
   */
  public function testIntervalFalseUnion($interval, $union) {
    $r = new MathInterval($interval);
    $this->assertFalse($r->union($union));
  }

  function dataProviderIntervalIntersection() {
    return array(

      // Intervals with equal values but different inclusions.
      // $interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,10]', '[1,10]', '[1,10]', TRUE, 1, 10, TRUE, FALSE, FALSE),
      array('[1,10]', '[1,10[', '[1,10[', TRUE, 1, 10, FALSE, FALSE, FALSE),
      array('[1,10]', ']1,10]', ']1,10]', FALSE, 1, 10, TRUE, FALSE, FALSE),
      array('[1,10]', ']1,10[', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),
      array('[1,10[', '[1,10]', '[1,10[', TRUE, 1, 10, FALSE, FALSE, FALSE),
      array('[1,10[', '[1,10[', '[1,10[', TRUE, 1, 10, FALSE, FALSE, FALSE),
      array('[1,10[', ']1,10]', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),
      array('[1,10[', ']1,10[', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),
      array(']1,10]', '[1,10]', ']1,10]', FALSE, 1, 10, TRUE, FALSE, FALSE),
      array(']1,10]', '[1,10[', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),
      array(']1,10]', ']1,10]', ']1,10]', FALSE, 1, 10, TRUE, FALSE, FALSE),
      array(']1,10]', ']1,10[', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),
      array(']1,10[', '[1,10]', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),
      array(']1,10[', '[1,10[', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),
      array(']1,10[', ']1,10]', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),
      array(']1,10[', ']1,10[', ']1,10[', FALSE, 1, 10, FALSE, FALSE, FALSE),

      array('[1,10.0]', '[1,10]', '[1.0,10.0]', TRUE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10.0]', '[1,10[', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array('[1,10.0]', ']1,10]', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array('[1,10.0]', ']1,10[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      array('[1,10.0[', '[1,10]', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array('[1,10.0[', '[1,10[', '[1.0,10.0[', TRUE, 1, 10, FALSE, FALSE, TRUE),
      array('[1,10.0[', ']1,10]', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      array('[1,10.0[', ']1,10[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10.0]', '[1,10]', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10.0]', '[1,10[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10.0]', ']1,10]', ']1.0,10.0]', FALSE, 1, 10, TRUE, FALSE, TRUE),
      array(']1,10.0]', ']1,10[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10.0[', '[1,10]', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10.0[', '[1,10[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10.0[', ']1,10]', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),
      array(']1,10.0[', ']1,10[', ']1.0,10.0[', FALSE, 1, 10, FALSE, FALSE, TRUE),


      // One interval includes the other.
      // $interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,10]', '[3,4]', '[3,4]', TRUE, 3, 4, TRUE, FALSE, FALSE),
      array('[1,10]', '[3,4[', '[3,4[', TRUE, 3, 4, FALSE, FALSE, FALSE),
      array('[1,10]', ']3,4]', ']3,4]', FALSE, 3, 4, TRUE, FALSE, FALSE),
      array('[1,10]', ']3,4[', ']3,4[', FALSE, 3, 4, FALSE, FALSE, FALSE),
      array('[1,10[', '[3,4]', '[3,4]', TRUE, 3, 4, TRUE, FALSE, FALSE),
      array('[1,10[', '[3,4[', '[3,4[', TRUE, 3, 4, FALSE, FALSE, FALSE),
      array('[1,10[', ']3,4]', ']3,4]', FALSE, 3, 4, TRUE, FALSE, FALSE),
      array('[1,10[', ']3,4[', ']3,4[', FALSE, 3, 4, FALSE, FALSE, FALSE),
      array(']1,10]', '[3,4]', '[3,4]', TRUE, 3, 4, TRUE, FALSE, FALSE),
      array(']1,10]', '[3,4[', '[3,4[', TRUE, 3, 4, FALSE, FALSE, FALSE),
      array(']1,10]', ']3,4]', ']3,4]', FALSE, 3, 4, TRUE, FALSE, FALSE),
      array(']1,10]', ']3,4[', ']3,4[', FALSE, 3, 4, FALSE, FALSE, FALSE),
      array(']1,10[', '[3,4]', '[3,4]', TRUE, 3, 4, TRUE, FALSE, FALSE),
      array(']1,10[', '[3,4[', '[3,4[', TRUE, 3, 4, FALSE, FALSE, FALSE),
      array(']1,10[', ']3,4]', ']3,4]', FALSE, 3, 4, TRUE, FALSE, FALSE),
      array(']1,10[', ']3,4[', ']3,4[', FALSE, 3, 4, FALSE, FALSE, FALSE),

      array('[1,10.0]', '[3,4]', '[3.0,4.0]', TRUE, 3, 4, TRUE, FALSE, TRUE),
      array('[1,10.0]', '[3,4[', '[3.0,4.0[', TRUE, 3, 4, FALSE, FALSE, TRUE),
      array('[1,10.0]', ']3,4]', ']3.0,4.0]', FALSE, 3, 4, TRUE, FALSE, TRUE),
      array('[1,10.0]', ']3,4[', ']3.0,4.0[', FALSE, 3, 4, FALSE, FALSE, TRUE),
      array('[1,10.0[', '[3,4]', '[3.0,4.0]', TRUE, 3, 4, TRUE, FALSE, TRUE),
      array('[1,10.0[', '[3,4[', '[3.0,4.0[', TRUE, 3, 4, FALSE, FALSE, TRUE),
      array('[1,10.0[', ']3,4]', ']3.0,4.0]', FALSE, 3, 4, TRUE, FALSE, TRUE),
      array('[1,10.0[', ']3,4[', ']3.0,4.0[', FALSE, 3, 4, FALSE, FALSE, TRUE),
      array(']1,10.0]', '[3,4]', '[3.0,4.0]', TRUE, 3, 4, TRUE, FALSE, TRUE),
      array(']1,10.0]', '[3,4[', '[3.0,4.0[', TRUE, 3, 4, FALSE, FALSE, TRUE),
      array(']1,10.0]', ']3,4]', ']3.0,4.0]', FALSE, 3, 4, TRUE, FALSE, TRUE),
      array(']1,10.0]', ']3,4[', ']3.0,4.0[', FALSE, 3, 4, FALSE, FALSE, TRUE),
      array(']1,10.0[', '[3,4]', '[3.0,4.0]', TRUE, 3, 4, TRUE, FALSE, TRUE),
      array(']1,10.0[', '[3,4[', '[3.0,4.0[', TRUE, 3, 4, FALSE, FALSE, TRUE),
      array(']1,10.0[', ']3,4]', ']3.0,4.0]', FALSE, 3, 4, TRUE, FALSE, TRUE),
      array(']1,10.0[', ']3,4[', ']3.0,4.0[', FALSE, 3, 4, FALSE, FALSE, TRUE),

      // Switch order.
      array('[3,4]', '[1,10]', '[3,4]', TRUE, 3, 4, TRUE, FALSE, FALSE),
      array('[3,4[', '[1,10]', '[3,4[', TRUE, 3, 4, FALSE, FALSE, FALSE),
      array(']3,4]', '[1,10]', ']3,4]', FALSE, 3, 4, TRUE, FALSE, FALSE),
      array(']3,4[', '[1,10]', ']3,4[', FALSE, 3, 4, FALSE, FALSE, FALSE),
      array('[3,4]', '[1,10[', '[3,4]', TRUE, 3, 4, TRUE, FALSE, FALSE),
      array('[3,4[', '[1,10[', '[3,4[', TRUE, 3, 4, FALSE, FALSE, FALSE),
      array(']3,4]', '[1,10[', ']3,4]', FALSE, 3, 4, TRUE, FALSE, FALSE),
      array(']3,4[', '[1,10[', ']3,4[', FALSE, 3, 4, FALSE, FALSE, FALSE),
      array('[3,4]', ']1,10]', '[3,4]', TRUE, 3, 4, TRUE, FALSE, FALSE),
      array('[3,4[', ']1,10]', '[3,4[', TRUE, 3, 4, FALSE, FALSE, FALSE),
      array(']3,4]', ']1,10]', ']3,4]', FALSE, 3, 4, TRUE, FALSE, FALSE),
      array(']3,4[', ']1,10]', ']3,4[', FALSE, 3, 4, FALSE, FALSE, FALSE),
      array('[3,4]', ']1,10[', '[3,4]', TRUE, 3, 4, TRUE, FALSE, FALSE),
      array('[3,4[', ']1,10[', '[3,4[', TRUE, 3, 4, FALSE, FALSE, FALSE),
      array(']3,4]', ']1,10[', ']3,4]', FALSE, 3, 4, TRUE, FALSE, FALSE),
      array(']3,4[', ']1,10[', ']3,4[', FALSE, 3, 4, FALSE, FALSE, FALSE),

      array('[3,4]', '[1,10.0]', '[3.0,4.0]', TRUE, 3, 4, TRUE, FALSE, TRUE),
      array('[3,4[', '[1,10.0]', '[3.0,4.0[', TRUE, 3, 4, FALSE, FALSE, TRUE),
      array(']3,4]', '[1,10.0]', ']3.0,4.0]', FALSE, 3, 4, TRUE, FALSE, TRUE),
      array(']3,4[', '[1,10.0]', ']3.0,4.0[', FALSE, 3, 4, FALSE, FALSE, TRUE),
      array('[3,4]', '[1,10.0[', '[3.0,4.0]', TRUE, 3, 4, TRUE, FALSE, TRUE),
      array('[3,4[', '[1,10.0[', '[3.0,4.0[', TRUE, 3, 4, FALSE, FALSE, TRUE),
      array(']3,4]', '[1,10.0[', ']3.0,4.0]', FALSE, 3, 4, TRUE, FALSE, TRUE),
      array(']3,4[', '[1,10.0[', ']3.0,4.0[', FALSE, 3, 4, FALSE, FALSE, TRUE),
      array('[3,4]', ']1,10.0]', '[3.0,4.0]', TRUE, 3, 4, TRUE, FALSE, TRUE),
      array('[3,4[', ']1,10.0]', '[3.0,4.0[', TRUE, 3, 4, FALSE, FALSE, TRUE),
      array(']3,4]', ']1,10.0]', ']3.0,4.0]', FALSE, 3, 4, TRUE, FALSE, TRUE),
      array(']3,4[', ']1,10.0]', ']3.0,4.0[', FALSE, 3, 4, FALSE, FALSE, TRUE),
      array('[3,4]', ']1,10.0[', '[3.0,4.0]', TRUE, 3, 4, TRUE, FALSE, TRUE),
      array('[3,4[', ']1,10.0[', '[3.0,4.0[', TRUE, 3, 4, FALSE, FALSE, TRUE),
      array(']3,4]', ']1,10.0[', ']3.0,4.0]', FALSE, 3, 4, TRUE, FALSE, TRUE),
      array(']3,4[', ']1,10.0[', ']3.0,4.0[', FALSE, 3, 4, FALSE, FALSE, TRUE),

      // Intervals overlap on lower bound.
      array('[1,8]', '[1,5]', '[1,5]', TRUE, 1, 5, TRUE, FALSE, FALSE),
      array('[1,8]', '[1,5[', '[1,5[', TRUE, 1, 5, FALSE, FALSE, FALSE),
      array('[1,8]', ']1,5]', ']1,5]', FALSE, 1, 5, TRUE, FALSE, FALSE),
      array('[1,8]', ']1,5[', ']1,5[', FALSE, 1, 5, FALSE, FALSE, FALSE),
      array('[1,8[', '[1,5]', '[1,5]', TRUE, 1, 5, TRUE, FALSE, FALSE),
      array('[1,8[', '[1,5[', '[1,5[', TRUE, 1, 5, FALSE, FALSE, FALSE),
      array('[1,8[', ']1,5]', ']1,5]', FALSE, 1, 5, TRUE, FALSE, FALSE),
      array('[1,8[', ']1,5[', ']1,5[', FALSE, 1, 5, FALSE, FALSE, FALSE),
      array(']1,8]', '[1,5]', ']1,5]', FALSE, 1, 5, TRUE, FALSE, FALSE),
      array(']1,8]', '[1,5[', ']1,5[', FALSE, 1, 5, FALSE, FALSE, FALSE),
      array(']1,8]', ']1,5]', ']1,5]', FALSE, 1, 5, TRUE, FALSE, FALSE),
      array(']1,8]', ']1,5[', ']1,5[', FALSE, 1, 5, FALSE, FALSE, FALSE),
      array(']1,8[', '[1,5]', ']1,5]', FALSE, 1, 5, TRUE, FALSE, FALSE),
      array(']1,8[', '[1,5[', ']1,5[', FALSE, 1, 5, FALSE, FALSE, FALSE),
      array(']1,8[', ']1,5]', ']1,5]', FALSE, 1, 5, TRUE, FALSE, FALSE),
      array(']1,8[', ']1,5[', ']1,5[', FALSE, 1, 5, FALSE, FALSE, FALSE),

      array('[1,5]', '[1,8]', '[1,5]', TRUE, 1, 5, TRUE, FALSE, FALSE),
      array('[1,5[', '[1,8]', '[1,5[', TRUE, 1, 5, FALSE, FALSE, FALSE),
      array(']1,5]', '[1,8]', ']1,5]', FALSE, 1, 5, TRUE, FALSE, FALSE),
      array(']1,5[', '[1,8]', ']1,5[', FALSE, 1, 5, FALSE, FALSE, FALSE),
      array('[1,5]', '[1,8[', '[1,5]', TRUE, 1, 5, TRUE, FALSE, FALSE),
      array('[1,5[', '[1,8[', '[1,5[', TRUE, 1, 5, FALSE, FALSE, FALSE),
      array(']1,5]', '[1,8[', ']1,5]', FALSE, 1, 5, TRUE, FALSE, FALSE),
      array(']1,5[', '[1,8[', ']1,5[', FALSE, 1, 5, FALSE, FALSE, FALSE),
      array('[1,5]', ']1,8]', ']1,5]', FALSE, 1, 5, TRUE, FALSE, FALSE),
      array('[1,5[', ']1,8]', ']1,5[', FALSE, 1, 5, FALSE, FALSE, FALSE),
      array(']1,5]', ']1,8]', ']1,5]', FALSE, 1, 5, TRUE, FALSE, FALSE),
      array(']1,5[', ']1,8]', ']1,5[', FALSE, 1, 5, FALSE, FALSE, FALSE),
      array('[1,5]', ']1,8[', ']1,5]', FALSE, 1, 5, TRUE, FALSE, FALSE),
      array('[1,5[', ']1,8[', ']1,5[', FALSE, 1, 5, FALSE, FALSE, FALSE),
      array(']1,5]', ']1,8[', ']1,5]', FALSE, 1, 5, TRUE, FALSE, FALSE),
      array(']1,5[', ']1,8[', ']1,5[', FALSE, 1, 5, FALSE, FALSE, FALSE),

      array('[1.0,8]', '[1,5]', '[1.0,5.0]', TRUE, 1, 5, TRUE, FALSE, TRUE),
      array('[1.0,8]', '[1,5[', '[1.0,5.0[', TRUE, 1, 5, FALSE, FALSE, TRUE),
      array('[1.0,8]', ']1,5]', ']1.0,5.0]', FALSE, 1, 5, TRUE, FALSE, TRUE),
      array('[1.0,8]', ']1,5[', ']1.0,5.0[', FALSE, 1, 5, FALSE, FALSE, TRUE),
      array('[1.0,8[', '[1,5]', '[1.0,5.0]', TRUE, 1, 5, TRUE, FALSE, TRUE),
      array('[1.0,8[', '[1,5[', '[1.0,5.0[', TRUE, 1, 5, FALSE, FALSE, TRUE),
      array('[1.0,8[', ']1,5]', ']1.0,5.0]', FALSE, 1, 5, TRUE, FALSE, TRUE),
      array('[1.0,8[', ']1,5[', ']1.0,5.0[', FALSE, 1, 5, FALSE, FALSE, TRUE),
      array(']1.0,8]', '[1,5]', ']1.0,5.0]', FALSE, 1, 5, TRUE, FALSE, TRUE),
      array(']1.0,8]', '[1,5[', ']1.0,5.0[', FALSE, 1, 5, FALSE, FALSE, TRUE),
      array(']1.0,8]', ']1,5]', ']1.0,5.0]', FALSE, 1, 5, TRUE, FALSE, TRUE),
      array(']1.0,8]', ']1,5[', ']1.0,5.0[', FALSE, 1, 5, FALSE, FALSE, TRUE),
      array(']1.0,8[', '[1,5]', ']1.0,5.0]', FALSE, 1, 5, TRUE, FALSE, TRUE),
      array(']1.0,8[', '[1,5[', ']1.0,5.0[', FALSE, 1, 5, FALSE, FALSE, TRUE),
      array(']1.0,8[', ']1,5]', ']1.0,5.0]', FALSE, 1, 5, TRUE, FALSE, TRUE),
      array(']1.0,8[', ']1,5[', ']1.0,5.0[', FALSE, 1, 5, FALSE, FALSE, TRUE),

      array('[1,5]', '[1.0,8]', '[1.0,5.0]', TRUE, 1, 5, TRUE, FALSE, TRUE),
      array('[1,5[', '[1.0,8]', '[1.0,5.0[', TRUE, 1, 5, FALSE, FALSE, TRUE),
      array(']1,5]', '[1.0,8]', ']1.0,5.0]', FALSE, 1, 5, TRUE, FALSE, TRUE),
      array(']1,5[', '[1.0,8]', ']1.0,5.0[', FALSE, 1, 5, FALSE, FALSE, TRUE),
      array('[1,5]', '[1.0,8[', '[1.0,5.0]', TRUE, 1, 5, TRUE, FALSE, TRUE),
      array('[1,5[', '[1.0,8[', '[1.0,5.0[', TRUE, 1, 5, FALSE, FALSE, TRUE),
      array(']1,5]', '[1.0,8[', ']1.0,5.0]', FALSE, 1, 5, TRUE, FALSE, TRUE),
      array(']1,5[', '[1.0,8[', ']1.0,5.0[', FALSE, 1, 5, FALSE, FALSE, TRUE),
      array('[1,5]', ']1.0,8]', ']1.0,5.0]', FALSE, 1, 5, TRUE, FALSE, TRUE),
      array('[1,5[', ']1.0,8]', ']1.0,5.0[', FALSE, 1, 5, FALSE, FALSE, TRUE),
      array(']1,5]', ']1.0,8]', ']1.0,5.0]', FALSE, 1, 5, TRUE, FALSE, TRUE),
      array(']1,5[', ']1.0,8]', ']1.0,5.0[', FALSE, 1, 5, FALSE, FALSE, TRUE),
      array('[1,5]', ']1.0,8[', ']1.0,5.0]', FALSE, 1, 5, TRUE, FALSE, TRUE),
      array('[1,5[', ']1.0,8[', ']1.0,5.0[', FALSE, 1, 5, FALSE, FALSE, TRUE),
      array(']1,5]', ']1.0,8[', ']1.0,5.0]', FALSE, 1, 5, TRUE, FALSE, TRUE),
      array(']1,5[', ']1.0,8[', ']1.0,5.0[', FALSE, 1, 5, FALSE, FALSE, TRUE),
      
      // Intervals overlap on upper bound.
      array('[1,8]', '[5,8]', '[5,8]', TRUE, 5, 8, TRUE, FALSE, FALSE),
      array('[1,8]', '[5,8[', '[5,8[', TRUE, 5, 8, FALSE, FALSE, FALSE),
      array('[1,8]', ']5,8]', ']5,8]', FALSE, 5, 8, TRUE, FALSE, FALSE),
      array('[1,8]', ']5,8[', ']5,8[', FALSE, 5, 8, FALSE, FALSE, FALSE),
      array('[1,8[', '[5,8]', '[5,8[', TRUE, 5, 8, FALSE, FALSE, FALSE),
      array('[1,8[', '[5,8[', '[5,8[', TRUE, 5, 8, FALSE, FALSE, FALSE),
      array('[1,8[', ']5,8]', ']5,8[', FALSE, 5, 8, FALSE, FALSE, FALSE),
      array('[1,8[', ']5,8[', ']5,8[', FALSE, 5, 8, FALSE, FALSE, FALSE),
      array(']1,8]', '[5,8]', '[5,8]', TRUE, 5, 8, TRUE, FALSE, FALSE),
      array(']1,8]', '[5,8[', '[5,8[', TRUE, 5, 8, FALSE, FALSE, FALSE),
      array(']1,8]', ']5,8]', ']5,8]', FALSE, 5, 8, TRUE, FALSE, FALSE),
      array(']1,8]', ']5,8[', ']5,8[', FALSE, 5, 8, FALSE, FALSE, FALSE),
      array(']1,8[', '[5,8]', '[5,8[', TRUE, 5, 8, FALSE, FALSE, FALSE),
      array(']1,8[', '[5,8[', '[5,8[', TRUE, 5, 8, FALSE, FALSE, FALSE),
      array(']1,8[', ']5,8]', ']5,8[', FALSE, 5, 8, FALSE, FALSE, FALSE),
      array(']1,8[', ']5,8[', ']5,8[', FALSE, 5, 8, FALSE, FALSE, FALSE),

      array('[5,8]', '[1,8]', '[5,8]', TRUE, 5, 8, TRUE, FALSE, FALSE),
      array('[5,8[', '[1,8]', '[5,8[', TRUE, 5, 8, FALSE, FALSE, FALSE),
      array(']5,8]', '[1,8]', ']5,8]', FALSE, 5, 8, TRUE, FALSE, FALSE),
      array(']5,8[', '[1,8]', ']5,8[', FALSE, 5, 8, FALSE, FALSE, FALSE),
      array('[5,8]', '[1,8[', '[5,8[', TRUE, 5, 8, FALSE, FALSE, FALSE),
      array('[5,8[', '[1,8[', '[5,8[', TRUE, 5, 8, FALSE, FALSE, FALSE),
      array(']5,8]', '[1,8[', ']5,8[', FALSE, 5, 8, FALSE, FALSE, FALSE),
      array(']5,8[', '[1,8[', ']5,8[', FALSE, 5, 8, FALSE, FALSE, FALSE),
      array('[5,8]', ']1,8]', '[5,8]', TRUE, 5, 8, TRUE, FALSE, FALSE),
      array('[5,8[', ']1,8]', '[5,8[', TRUE, 5, 8, FALSE, FALSE, FALSE),
      array(']5,8]', ']1,8]', ']5,8]', FALSE, 5, 8, TRUE, FALSE, FALSE),
      array(']5,8[', ']1,8]', ']5,8[', FALSE, 5, 8, FALSE, FALSE, FALSE),
      array('[5,8]', ']1,8[', '[5,8[', TRUE, 5, 8, FALSE, FALSE, FALSE),
      array('[5,8[', ']1,8[', '[5,8[', TRUE, 5, 8, FALSE, FALSE, FALSE),
      array(']5,8]', ']1,8[', ']5,8[', FALSE, 5, 8, FALSE, FALSE, FALSE),
      array(']5,8[', ']1,8[', ']5,8[', FALSE, 5, 8, FALSE, FALSE, FALSE),

      array('[1.0,8]', '[5,8]', '[5.0,8.0]', TRUE, 5, 8, TRUE, FALSE, TRUE),
      array('[1.0,8]', '[5,8[', '[5.0,8.0[', TRUE, 5, 8, FALSE, FALSE, TRUE),
      array('[1.0,8]', ']5,8]', ']5.0,8.0]', FALSE, 5, 8, TRUE, FALSE, TRUE),
      array('[1.0,8]', ']5,8[', ']5.0,8.0[', FALSE, 5, 8, FALSE, FALSE, TRUE),
      array('[1.0,8[', '[5,8]', '[5.0,8.0[', TRUE, 5, 8, FALSE, FALSE, TRUE),
      array('[1.0,8[', '[5,8[', '[5.0,8.0[', TRUE, 5, 8, FALSE, FALSE, TRUE),
      array('[1.0,8[', ']5,8]', ']5.0,8.0[', FALSE, 5, 8, FALSE, FALSE, TRUE),
      array('[1.0,8[', ']5,8[', ']5.0,8.0[', FALSE, 5, 8, FALSE, FALSE, TRUE),
      array(']1.0,8]', '[5,8]', '[5.0,8.0]', TRUE, 5, 8, TRUE, FALSE, TRUE),
      array(']1.0,8]', '[5,8[', '[5.0,8.0[', TRUE, 5, 8, FALSE, FALSE, TRUE),
      array(']1.0,8]', ']5,8]', ']5.0,8.0]', FALSE, 5, 8, TRUE, FALSE, TRUE),
      array(']1.0,8]', ']5,8[', ']5.0,8.0[', FALSE, 5, 8, FALSE, FALSE, TRUE),
      array(']1.0,8[', '[5,8]', '[5.0,8.0[', TRUE, 5, 8, FALSE, FALSE, TRUE),
      array(']1.0,8[', '[5,8[', '[5.0,8.0[', TRUE, 5, 8, FALSE, FALSE, TRUE),
      array(']1.0,8[', ']5,8]', ']5.0,8.0[', FALSE, 5, 8, FALSE, FALSE, TRUE),
      array(']1.0,8[', ']5,8[', ']5.0,8.0[', FALSE, 5, 8, FALSE, FALSE, TRUE),
      
      array('[5,8]', '[1.0,8]', '[5.0,8.0]', TRUE, 5, 8, TRUE, FALSE, TRUE),
      array('[5,8[', '[1.0,8]', '[5.0,8.0[', TRUE, 5, 8, FALSE, FALSE, TRUE),
      array(']5,8]', '[1.0,8]', ']5.0,8.0]', FALSE, 5, 8, TRUE, FALSE, TRUE),
      array(']5,8[', '[1.0,8]', ']5.0,8.0[', FALSE, 5, 8, FALSE, FALSE, TRUE),
      array('[5,8]', '[1.0,8[', '[5.0,8.0[', TRUE, 5, 8, FALSE, FALSE, TRUE),
      array('[5,8[', '[1.0,8[', '[5.0,8.0[', TRUE, 5, 8, FALSE, FALSE, TRUE),
      array(']5,8]', '[1.0,8[', ']5.0,8.0[', FALSE, 5, 8, FALSE, FALSE, TRUE),
      array(']5,8[', '[1.0,8[', ']5.0,8.0[', FALSE, 5, 8, FALSE, FALSE, TRUE),
      array('[5,8]', ']1.0,8]', '[5.0,8.0]', TRUE, 5, 8, TRUE, FALSE, TRUE),
      array('[5,8[', ']1.0,8]', '[5.0,8.0[', TRUE, 5, 8, FALSE, FALSE, TRUE),
      array(']5,8]', ']1.0,8]', ']5.0,8.0]', FALSE, 5, 8, TRUE, FALSE, TRUE),
      array(']5,8[', ']1.0,8]', ']5.0,8.0[', FALSE, 5, 8, FALSE, FALSE, TRUE),
      array('[5,8]', ']1.0,8[', '[5.0,8.0[', TRUE, 5, 8, FALSE, FALSE, TRUE),
      array('[5,8[', ']1.0,8[', '[5.0,8.0[', TRUE, 5, 8, FALSE, FALSE, TRUE),
      array(']5,8]', ']1.0,8[', ']5.0,8.0[', FALSE, 5, 8, FALSE, FALSE, TRUE),
      array(']5,8[', ']1.0,8[', ']5.0,8.0[', FALSE, 5, 8, FALSE, FALSE, TRUE),

      // Intervals do not intersect.
      // $interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,3]', '[4,6]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1,3]', '[4,6[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1,3]', ']4,6]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1,3[', ']4,6[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1,3[', '[4,6]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1,3[', '[4,6[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1,3[', ']4,6]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1,3]', ']4,6[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']1,3]', '[4,6]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']1,3]', '[4,6[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']1,3]', ']4,6]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']1,3]', ']4,6[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']1,3[', '[4,6]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']1,3[', '[4,6[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']1,3[', ']4,6]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']1,3[', ']4,6[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      
      array('[4,6]', '[1,3]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[4,6[', '[1,3]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']4,6]', '[1,3]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']4,6[', '[1,3[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[4,6]', '[1,3[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[4,6[', '[1,3[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']4,6]', '[1,3[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']4,6[', '[1,3]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[4,6]', ']1,3]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[4,6[', ']1,3]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']4,6]', ']1,3]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']4,6[', ']1,3]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[4,6]', ']1,3[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[4,6[', ']1,3[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']4,6]', ']1,3[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']4,6[', ']1,3[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      
      array('[1.0,3]', '[4,6]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[1.0,3]', '[4,6[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[1.0,3]', ']4,6]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[1.0,3[', ']4,6[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[1.0,3[', '[4,6]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[1.0,3[', '[4,6[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[1.0,3[', ']4,6]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[1.0,3]', ']4,6[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']1.0,3]', '[4,6]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']1.0,3]', '[4,6[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']1.0,3]', ']4,6]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']1.0,3]', ']4,6[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']1.0,3[', '[4,6]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']1.0,3[', '[4,6[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']1.0,3[', ']4,6]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']1.0,3[', ']4,6[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      
      array('[4,6]', '[1.0,3]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[4,6[', '[1.0,3]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']4,6]', '[1.0,3]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']4,6[', '[1.0,3[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[4,6]', '[1.0,3[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[4,6[', '[1.0,3[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']4,6]', '[1.0,3[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']4,6[', '[1.0,3]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[4,6]', ']1.0,3]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[4,6[', ']1.0,3]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']4,6]', ']1.0,3]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']4,6[', ']1.0,3]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[4,6]', ']1.0,3[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[4,6[', ']1.0,3[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']4,6]', ']1.0,3[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']4,6[', ']1.0,3[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      
      // Intervals intersect in only one point.
      // $interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,3]', '[3,6]', '[3,3]', TRUE, 3, 3, TRUE, FALSE, FALSE),
      array(']1,3]', '[3,6]', '[3,3]', TRUE, 3, 3, TRUE, FALSE, FALSE),
      array('[1,3]', '[3,6[', '[3,3]', TRUE, 3, 3, TRUE, FALSE, FALSE),
      array(']1,3]', '[3,6[', '[3,3]', TRUE, 3, 3, TRUE, FALSE, FALSE),
      array('[3,6]', '[1,3]', '[3,3]', TRUE, 3, 3, TRUE, FALSE, FALSE),
      array('[3,6]', ']1,3]', '[3,3]', TRUE, 3, 3, TRUE, FALSE, FALSE),
      array('[3,6[', '[1,3]', '[3,3]', TRUE, 3, 3, TRUE, FALSE, FALSE),
      array('[3,6[', ']1,3]', '[3,3]', TRUE, 3, 3, TRUE, FALSE, FALSE),
      
      array(']3,6]', '[1,3]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[3,6]', '[1,3[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']3,6]', '[1,3[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1,3]', ']3,6]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1,3[', '[3,6]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1,3[', ']3,6]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      
      array('[1.0,3]', '[3,6]', '[3.0,3.0]', TRUE, 3, 3, TRUE, FALSE, TRUE),
      array(']1.0,3]', '[3,6]', '[3.0,3.0]', TRUE, 3, 3, TRUE, FALSE, TRUE),
      array('[1.0,3]', '[3,6[', '[3.0,3.0]', TRUE, 3, 3, TRUE, FALSE, TRUE),
      array(']1.0,3]', '[3,6[', '[3.0,3.0]', TRUE, 3, 3, TRUE, FALSE, TRUE),
      array('[3,6]', '[1.0,3]', '[3.0,3.0]', TRUE, 3, 3, TRUE, FALSE, TRUE),
      array('[3,6]', ']1.0,3]', '[3.0,3.0]', TRUE, 3, 3, TRUE, FALSE, TRUE),
      array('[3,6[', '[1.0,3]', '[3.0,3.0]', TRUE, 3, 3, TRUE, FALSE, TRUE),
      array('[3,6[', ']1.0,3]', '[3.0,3.0]', TRUE, 3, 3, TRUE, FALSE, TRUE),

      array(']3,6]', '[1.0,3]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[3,6]', '[1.0,3[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']3,6]', '[1.0,3[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[1.0,3]', ']3,6]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[1.0,3[', '[3,6]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[1.0,3[', ']3,6]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),

      // Intervals intersect.
      // $interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,5]', '[3,8]', '[3,5]', TRUE, 3, 5, TRUE, FALSE, FALSE),
      array('[1,5]', '[3,8[', '[3,5]', TRUE, 3, 5, TRUE, FALSE, FALSE),
      array('[1,5]', ']3,8]', ']3,5]', FALSE, 3, 5, TRUE, FALSE, FALSE),
      array('[1,5]', ']3,8[', ']3,5]', FALSE, 3, 5, TRUE, FALSE, FALSE),
      array('[1,5[', '[3,8]', '[3,5[', TRUE, 3, 5, FALSE, FALSE, FALSE),
      array('[1,5[', '[3,8[', '[3,5[', TRUE, 3, 5, FALSE, FALSE, FALSE),
      array('[1,5[', ']3,8]', ']3,5[', FALSE, 3, 5, FALSE, FALSE, FALSE),
      array('[1,5[', ']3,8[', ']3,5[', FALSE, 3, 5, FALSE, FALSE, FALSE),
      array(']1,5]', '[3,8]', '[3,5]', TRUE, 3, 5, TRUE, FALSE, FALSE),
      array(']1,5]', '[3,8[', '[3,5]', TRUE, 3, 5, TRUE, FALSE, FALSE),
      array(']1,5]', ']3,8]', ']3,5]', FALSE, 3, 5, TRUE, FALSE, FALSE),
      array(']1,5]', ']3,8[', ']3,5]', FALSE, 3, 5, TRUE, FALSE, FALSE),
      array(']1,5[', '[3,8]', '[3,5[', TRUE, 3, 5, FALSE, FALSE, FALSE),
      array(']1,5[', '[3,8[', '[3,5[', TRUE, 3, 5, FALSE, FALSE, FALSE),
      array(']1,5[', ']3,8]', ']3,5[', FALSE, 3, 5, FALSE, FALSE, FALSE),
      array(']1,5[', ']3,8[', ']3,5[', FALSE, 3, 5, FALSE, FALSE, FALSE),
      
      array('[3,8]', '[1,5]', '[3,5]', TRUE, 3, 5, TRUE, FALSE, FALSE),
      array('[3,8[', '[1,5]', '[3,5]', TRUE, 3, 5, TRUE, FALSE, FALSE),
      array(']3,8]', '[1,5]', ']3,5]', FALSE, 3, 5, TRUE, FALSE, FALSE),
      array(']3,8[', '[1,5]', ']3,5]', FALSE, 3, 5, TRUE, FALSE, FALSE),
      array('[3,8]', '[1,5[', '[3,5[', TRUE, 3, 5, FALSE, FALSE, FALSE),
      array('[3,8[', '[1,5[', '[3,5[', TRUE, 3, 5, FALSE, FALSE, FALSE),
      array(']3,8]', '[1,5[', ']3,5[', FALSE, 3, 5, FALSE, FALSE, FALSE),
      array(']3,8[', '[1,5[', ']3,5[', FALSE, 3, 5, FALSE, FALSE, FALSE),
      array('[3,8]', ']1,5]', '[3,5]', TRUE, 3, 5, TRUE, FALSE, FALSE),
      array('[3,8[', ']1,5]', '[3,5]', TRUE, 3, 5, TRUE, FALSE, FALSE),
      array(']3,8]', ']1,5]', ']3,5]', FALSE, 3, 5, TRUE, FALSE, FALSE),
      array(']3,8[', ']1,5]', ']3,5]', FALSE, 3, 5, TRUE, FALSE, FALSE),
      array('[3,8]', ']1,5[', '[3,5[', TRUE, 3, 5, FALSE, FALSE, FALSE),
      array('[3,8[', ']1,5[', '[3,5[', TRUE, 3, 5, FALSE, FALSE, FALSE),
      array(']3,8]', ']1,5[', ']3,5[', FALSE, 3, 5, FALSE, FALSE, FALSE),
      array(']3,8[', ']1,5[', ']3,5[', FALSE, 3, 5, FALSE, FALSE, FALSE),
      
      array('[1.0,5]', '[3,8]', '[3.0,5.0]', TRUE, 3, 5, TRUE, FALSE, TRUE),
      array('[1.0,5]', '[3,8[', '[3.0,5.0]', TRUE, 3, 5, TRUE, FALSE, TRUE),
      array('[1.0,5]', ']3,8]', ']3.0,5.0]', FALSE, 3, 5, TRUE, FALSE, TRUE),
      array('[1.0,5]', ']3,8[', ']3.0,5.0]', FALSE, 3, 5, TRUE, FALSE, TRUE),
      array('[1.0,5[', '[3,8]', '[3.0,5.0[', TRUE, 3, 5, FALSE, FALSE, TRUE),
      array('[1.0,5[', '[3,8[', '[3.0,5.0[', TRUE, 3, 5, FALSE, FALSE, TRUE),
      array('[1.0,5[', ']3,8]', ']3.0,5.0[', FALSE, 3, 5, FALSE, FALSE, TRUE),
      array('[1.0,5[', ']3,8[', ']3.0,5.0[', FALSE, 3, 5, FALSE, FALSE, TRUE),
      array(']1.0,5]', '[3,8]', '[3.0,5.0]', TRUE, 3, 5, TRUE, FALSE, TRUE),
      array(']1.0,5]', '[3,8[', '[3.0,5.0]', TRUE, 3, 5, TRUE, FALSE, TRUE),
      array(']1.0,5]', ']3,8]', ']3.0,5.0]', FALSE, 3, 5, TRUE, FALSE, TRUE),
      array(']1.0,5]', ']3,8[', ']3.0,5.0]', FALSE, 3, 5, TRUE, FALSE, TRUE),
      array(']1.0,5[', '[3,8]', '[3.0,5.0[', TRUE, 3, 5, FALSE, FALSE, TRUE),
      array(']1.0,5[', '[3,8[', '[3.0,5.0[', TRUE, 3, 5, FALSE, FALSE, TRUE),
      array(']1.0,5[', ']3,8]', ']3.0,5.0[', FALSE, 3, 5, FALSE, FALSE, TRUE),
      array(']1.0,5[', ']3,8[', ']3.0,5.0[', FALSE, 3, 5, FALSE, FALSE, TRUE),

      array('[3,8]', '[1.0,5]', '[3.0,5.0]', TRUE, 3, 5, TRUE, FALSE, TRUE),
      array('[3,8[', '[1.0,5]', '[3.0,5.0]', TRUE, 3, 5, TRUE, FALSE, TRUE),
      array(']3,8]', '[1.0,5]', ']3.0,5.0]', FALSE, 3, 5, TRUE, FALSE, TRUE),
      array(']3,8[', '[1.0,5]', ']3.0,5.0]', FALSE, 3, 5, TRUE, FALSE, TRUE),
      array('[3,8]', '[1.0,5[', '[3.0,5.0[', TRUE, 3, 5, FALSE, FALSE, TRUE),
      array('[3,8[', '[1.0,5[', '[3.0,5.0[', TRUE, 3, 5, FALSE, FALSE, TRUE),
      array(']3,8]', '[1.0,5[', ']3.0,5.0[', FALSE, 3, 5, FALSE, FALSE, TRUE),
      array(']3,8[', '[1.0,5[', ']3.0,5.0[', FALSE, 3, 5, FALSE, FALSE, TRUE),
      array('[3,8]', ']1.0,5]', '[3.0,5.0]', TRUE, 3, 5, TRUE, FALSE, TRUE),
      array('[3,8[', ']1.0,5]', '[3.0,5.0]', TRUE, 3, 5, TRUE, FALSE, TRUE),
      array(']3,8]', ']1.0,5]', ']3.0,5.0]', FALSE, 3, 5, TRUE, FALSE, TRUE),
      array(']3,8[', ']1.0,5]', ']3.0,5.0]', FALSE, 3, 5, TRUE, FALSE, TRUE),
      array('[3,8]', ']1.0,5[', '[3.0,5.0[', TRUE, 3, 5, FALSE, FALSE, TRUE),
      array('[3,8[', ']1.0,5[', '[3.0,5.0[', TRUE, 3, 5, FALSE, FALSE, TRUE),
      array(']3,8]', ']1.0,5[', ']3.0,5.0[', FALSE, 3, 5, FALSE, FALSE, TRUE),
      array(']3,8[', ']1.0,5[', ']3.0,5.0[', FALSE, 3, 5, FALSE, FALSE, TRUE),
      
      // Intersection with empty interval will always be empty.
      // $interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,10]', ']0,0[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1,10[', ']0,0[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']1,10]', ']0,0[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']1,10[', ']0,0[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      
      array(']0,0[', '[1,10]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']0,0[', '[1,10[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']0,0[', ']1,10]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']0,0[', ']1,10[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      
      array('[1.0,10]', ']0,0[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[1.0,10[', ']0,0[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']1.0,10]', ']0,0[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']1.0,10[', ']0,0[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      
      array(']0,0[', '[1.0,10]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']0,0[', '[1.0,10[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']0,0[', ']1.0,10]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']0,0[', ']1.0,10[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      
      array(']0.0,0.0[', '[1,10]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']0.0,0.0[', '[1,10[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']0.0,0.0[', ']1,10]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']0.0,0.0[', ']1,10[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      
      array('[1,10]', ']0.0,0.0[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[1,10[', ']0.0,0.0[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']1,10]', ']0.0,0.0[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']1,10[', ']0.0,0.0[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
    );
  }
  
  /**
   * @dataProvider dataProviderIntervalIntersection
   */
  public function testIntervalIntersection($interval, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats) {
    $r = new MathInterval($interval);
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