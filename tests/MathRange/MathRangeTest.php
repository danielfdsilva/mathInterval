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

  public function dataProviderCompute() {
    return array(
      // With simple ranges, there's nothing to simplify
      // so the input is equal to the output.
      array('[1,4]', '[1,4]'),
      array('[1,4[', '[1,4['),
      array(']1,4]', ']1,4]'),
      array(']1,4[', ']1,4['),
      
      // Differently from what happens with the output of a
      // MathRange object, when a range is valid is returned
      // as is.
      array('[1.0,4]', '[1.0,4]'),
      array('[1.0,4[', '[1.0,4['),
      array(']1.0,4]', ']1.0,4]'),
      array(']1.0,4[', ']1.0,4['),
      
      // Empty ranges always evaluate to ]0.0[.
      array('[1,1[', ']0,0['),
      array(']1,1]', ']0,0['),
      array(']1,1[', ']0,0['),
      
      array('[1.0,1[', ']0.0,0.0['),
      array(']1.0,1]', ']0.0,0.0['),
      array(']1.0,1[', ']0.0,0.0['),
    );
  }
  
  /**
   * @dataProvider dataProviderCompute
   */
  public function testCompute($input, $output) {
    // The compute function's only job is to simplify ranges to be
    // handled by the constructor.
    // It can be a recursive function if an expression with atoms is
    // used.
    // If along the way an invalid expression shows up throws an
    // exception.
    
    $this->assertEquals($output, MathRange::compute($input));
  }

  function dataProviderRange() {
    return array(
      // Ranges.
      array('[1,2]', '[1,2]', TRUE, 1, 2, TRUE, FALSE, FALSE),
      array('[1,2[', '[1,2[', TRUE, 1, 2, FALSE, FALSE, FALSE),
      array(']1,2]', ']1,2]', FALSE, 1, 2, TRUE, FALSE, FALSE),
      array(']1,2[', ']1,2[', FALSE, 1, 2, FALSE, FALSE, FALSE),
      
      // Ranges with floats.
      array('[1.0,2]', '[1.0,2.0]', TRUE, 1, 2, TRUE, FALSE, TRUE),
      array('[1.0,2[', '[1.0,2.0[', TRUE, 1, 2, FALSE, FALSE, TRUE),
      array(']1.0,2]', ']1.0,2.0]', FALSE, 1, 2, TRUE, FALSE, TRUE),
      array(']1.0,2[', ']1.0,2.0[', FALSE, 1, 2, FALSE, FALSE, TRUE),
      array('[1.0,2.25]', '[1.0,2.25]', TRUE, 1, 2.25, TRUE, FALSE, TRUE),
      
      // Empty ranges.
      // $range, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1,1]', '[1,1]', TRUE, 1, 1, TRUE, FALSE, FALSE),
      array(']1,1]', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array('[1,1[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      array(']1,1[', ']0,0[', FALSE, 0, 0, FALSE, TRUE, FALSE),
      
      // Empty Ranges with floats.
      // $range, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
      array('[1.0,1]', '[1.0,1.0]', TRUE, 1, 1, TRUE, FALSE, TRUE),
      array(']1.0,1]', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array('[1.0,1[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
      array(']1.0,1[', ']0.0,0.0[', FALSE, 0, 0, FALSE, TRUE, TRUE),
    );
  }

  /**
   * @dataProvider dataProviderRange
   */
  public function testRange($range, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats) {
    $r = new MathRange($range);
    $this->assertEquals($expLBoundIn, $r->includeLowerBound(), 'Include lower bound.');
    $this->assertEquals($expLBound, $r->getLowerBound(), 'Value lower bound.');
    $this->assertEquals($expUBound, $r->getUpperBound(), 'Value upper bound.');
    $this->assertEquals($expUBoundIn, $r->includeUpperBound(), 'Include upper bound.');
    $this->assertEquals($expEmpty, $r->isEmpty(), 'Is empty range.');
    $this->assertEquals($expFloats, $r->allowFloats(), 'Range allows floats.');  
    $this->assertEquals($output, $r->__toString());
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

      // Ranges with equal values but different inclusions.
      // $range, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
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


      // One range includes the other.
      // $range, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
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

      // Range overlap on lower bound.
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
      
      // Range overlap on upper bound.
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

      // Ranges do not intersect.
      // $range, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
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
      
      // Ranges intersect in only one point.
      // $range, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
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

      // Ranges intersect.
      // $range, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
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
      
      // Intersection with empty will always be empty
      // $range, $union, $output, $expLBoundIn, $expLBound, $expUBound, $expUBoundIn, $expEmpty, $expFloats
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