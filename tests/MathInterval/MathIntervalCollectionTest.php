<?php

class MathIntervalCollectionTest extends PHPUnit_Framework_TestCase {

  public function dataProviderExceptionInvalid() {
    return array(
      array('qwe'),
      array('(1,2)'),
      array('1,2'),
      array('[1,2'),
      array('[1,2]]'),
      array('[1 , 2]'),

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
    MathIntervalCollection::compute($val);
  }

  public function testUnion() {
    $c = new MathIntervalCollection('[1,3]');
    $this->assertEquals('[1,3]', $c->__toString());
    $c->union('[4,6]');
    $this->assertEquals('[1,3] or [4,6]', $c->__toString());
    $c->union('[-40,0]');
    $this->assertEquals('[1,3] or [4,6] or [-40,0]', $c->__toString());
    // Once a union with decimals is made, all accept floats.
    $c->union(']7.0,10]');
    $this->assertEquals('[1.0,3.0] or [4.0,6.0] or [-40.0,0.0] or ]7.0,10.0]', $c->__toString());
    $c->union(']5,12] or ]-1,1[');
    $this->assertEquals('[-40.0,3.0] or [4.0,12.0]', $c->__toString());
    $c->union(']0,0[');
    $this->assertEquals('[-40.0,3.0] or [4.0,12.0]', $c->__toString());
    $c->union(']0.0,0.0[');
    $this->assertEquals('[-40.0,3.0] or [4.0,12.0]', $c->__toString());

    // Test with empty collection.
    // An empty collection will always have at least one interval.
    $c = new MathIntervalCollection(']0,0[');
    $this->assertEquals(']0,0[', $c->__toString());
    $c->union(']0,0[ or ]0,0[ or ]0,0[');
    $this->assertEquals(']0,0[', $c->__toString());
    $c->union(']0.0,0.0[');
    $this->assertEquals(']0.0,0.0[', $c->__toString());
    $c->union(']1,2[');
    $this->assertEquals(']1.0,2.0[', $c->__toString());

    $c = new MathIntervalCollection('[2,4]');
    $c->union(new MathInterval('[4,5]'));
    $this->assertEquals('[2,5]', $c->__toString());
    $c->union(new MathIntervalCollection('[8,9]'));
    $this->assertEquals('[2,5] or [8,9]', $c->__toString());
  }

  public function testIntersection() {
    $c = new MathIntervalCollection('[1,30]');
    $c->intersection('[2,10]');
    $this->assertEquals('[2,10]', $c->__toString());
    $c->intersection('[2,15] and ]2,10[');
    $this->assertEquals(']2,10[', $c->__toString());

    $c = new MathIntervalCollection('[1,30]');
    $c->intersection('[11,15] and ([3,20] and ]10,12[)');
    $this->assertEquals('[11,12[', $c->__toString());

    // Test with empty collection.
    // An empty collection will always have at least one interval.
    $c = new MathIntervalCollection(']0,0[');
    $this->assertEquals(']0,0[', $c->__toString());
    $c->intersection(']0,0[ and ]0,0[ and ]0,0[');
    $this->assertEquals(']0,0[', $c->__toString());
    $c->intersection(']0.0,0.0[');
    $this->assertEquals(']0.0,0.0[', $c->__toString());
    $c->intersection(']1,2]');
    $this->assertEquals(']0.0,0.0[', $c->__toString());

    $c = new MathIntervalCollection('[1,5]');
    $c->intersection(']0,0[');
    $this->assertEquals(']0,0[', $c->__toString());
  }

  public function testUnionIntersection() {
    $c = new MathIntervalCollection('[1,30]');
    $c->intersection('[2,10]');
    $this->assertEquals('[2,10]', $c->__toString());
    $c->union('[-3,5]');
    $this->assertEquals('[-3,10]', $c->__toString());
    $c->intersection(']0,0[');
    $this->assertEquals(']0,0[', $c->__toString());
    $c->union('[4,8] or [10,20] or ]30,35[');
    $this->assertEquals('[4,8] or [10,20] or ]30,35[', $c->__toString());
    $c->intersection(']7,9] or [15,18]');
    $this->assertEquals(']7,8] or [15,18]', $c->__toString());

    $c = new MathIntervalCollection('[1,10]');
    $c->intersection(']7,9] or [15,18]');
    $this->assertEquals(']7,9]', $c->__toString());

    $c = new MathIntervalCollection('[1,10]');
    $c->intersection(']7,9] or [15,18] or ]0,0[');
    $this->assertEquals(']7,9]', $c->__toString());

    $c = new MathIntervalCollection('[1,10]');
    $c->intersection(']7.5,12.3]');
    $this->assertEquals(']7.5,10.0]', $c->__toString());

    $c = new MathIntervalCollection('[2,10]');
    $c->intersection(new MathInterval('[3,8]'));
    $this->assertEquals('[3,8]', $c->__toString());
    $c->intersection(new MathIntervalCollection('[3,4] or [6,15]'));
    $this->assertEquals('[3,4] or [6,8]', $c->__toString());
  }

  public function testIsEmpty() {
    $c = new MathIntervalCollection('[1,3]');
    $this->assertFalse($c->isEmpty());

    $c = new MathIntervalCollection('[1,3] or ]7,10[');
    $this->assertFalse($c->isEmpty());

    $c = new MathIntervalCollection(']0,0[');
    $this->assertTrue($c->isEmpty());
  }

  public function testAllowFloats() {
    $c = new MathIntervalCollection('[1,3]');
    $this->assertFalse($c->allowFloats());

    $c = new MathIntervalCollection('[1,3] or [8.0,15.2]');
    $this->assertTrue($c->allowFloats());

    $c = new MathIntervalCollection(']0,0[');
    $this->assertFalse($c->allowFloats());

    $c = new MathIntervalCollection(']0.0,0.0[');
    $this->assertTrue($c->allowFloats());
  }

  public function dataProviderCompute() {
    return array(
      // With simple intervals, there's nothing to simplify
      // so the input is equal to the output.
      array('[1,4]', '[1,4]'),
      array('[1,4[', '[1,4['),
      array(']1,4]', ']1,4]'),
      array(']1,4[', ']1,4['),

      array('[1.0,4]', '[1.0,4.0]'),
      array('[1.0,4[', '[1.0,4.0['),
      array(']1.0,4]', ']1.0,4.0]'),
      array(']1.0,4[', ']1.0,4.0['),

      // Empty intervals always evaluate to ]0.0[.
      array('[1,1[', ']0,0['),
      array(']1,1]', ']0,0['),
      array(']1,1[', ']0,0['),

      array('[1.0,1[', ']0.0,0.0['),
      array(']1.0,1]', ']0.0,0.0['),
      array(']1.0,1[', ']0.0,0.0['),

      // The other tests will test the union and intersection methods
      // exhaustively. Here we only test if the expected results are
      // correct since the used methods are common.
      array('[1,5] or [3,9]', '[1,9]'),
      array('[3,9] or [1,5]', '[1,9]'),
      array('[3,9] or [1,5] or [10,13]', '[1,9] or [10,13]'),
      array('[3,9] or ]1,5] or [10,13] or [1,2[', '[1,9] or [10,13]'),
      array('[3,9] or [1,5] or [10,13] or ]0,0[', '[1,9] or [10,13]'),

      // Parenthesis with only unions are completely useless.
      // However test if working properly.
      array('(([1,5])) or [3,9]', '[1,9]'),
      array('((([3,9]) or [1,5]))', '[1,9]'),
      array('(([3,9]) or [1,5]) or [10,13]', '[1,9] or [10,13]'),
      array('[3,9] or ((]1,5] or [10,13]) or [1,2[)', '[1,9] or [10,13]'),
      array('[3,9] or ([1,5] or (([10,13]) or ]0,0[))', '[1,9] or [10,13]'),

      array('[1,5] and [3,9]', '[3,5]'),
      array('[1,50] and [3,45] and ]40,41]', ']40,41]'),
      array('[1,50] and [3,45] and ]0,0[', ']0,0['),

      // With union and intersection order is very important.
      array('[1,5] and [3,9] or [8,15]', '[3,5] or [8,15]'),
      array('[3,9] or [8,15] and [1,5]', '[3,5]'),
      array('[3,9] or [9,15] and [15,20]', '[15,15]'),
      array('[3,9] or [9,15] and ]15,20]', ']0,0['),

      // Test expressions with parenthesis.
      array('([1,5])', '[1,5]'),
      array('(([1,5]))', '[1,5]'),
      array('[1,5] and ([3,9] or [8,15])', '[3,5]'),
      array('[3,9] or ([10,15] and ([1,5]))', '[3,9]'),
      array('([3,9] or ([9,15] and ([15,20])))', '[3,9] or [15,15]'),
      array('(([3,9[) and [9,15]) and ]15,20]', ']0,0['),
      array('([1,5] and [3,4]) and ([1,10] or ([1,5] and [3,4]))', '[3,4]'),

    );
  }

  /**
   * @dataProvider dataProviderCompute
   */
  public function testCompute($input, $output) {
    // The compute function's only job is to simplify intervals to be
    // handled by the constructor.
    // It can be a recursive function if an expression with atoms is
    // used.
    // If along the way an invalid expression shows up throws an
    // exception.
    $this->assertEquals($output, MathIntervalCollection::compute($input));
  }

  public function dataProviderInInterval() {
    return array(
      // The inInterval function of MathIntervalCollection uses the inInterval
      // function MathInterval. Therefore we're not interested in testing this
      // What needs to be tested is when there are multiple intervals in the
      // collection.
      array('[1,4]', 1, TRUE),
      array('[1,4]', 4, TRUE),
      array('[1,4]', 5, FALSE),
      array('[1,4]', 7, FALSE),
      array('[1,4]', -1, FALSE),
      array('[1,4]', 1.1, FALSE),
      array('[1,4]', 2.1, FALSE),
      array(']1,4]', 1, FALSE),

      array(']1.0,4.0]', 1, FALSE),
      array(']1.0,4.0]', 1.5, TRUE),
      array(']1.0,4.0]', 4, TRUE),
      array(']1.0,4.0]', 2, TRUE),

      array('[1,5] or [7,9]', 1, TRUE),
      array(']1,5] or [7,9]', 1, FALSE),
      array('[1,5] or [7,9]', 5, TRUE),
      array('[1,5] or [7,9]', 6, FALSE),
      array('[1,5] or [7,9]', 8, TRUE),
      array('[1,5] or [7,9[', 9, FALSE),
      array('[1,5] or [7,9[ or [10,12]', 10, TRUE),
      
      // If one interval accepts floats, all accept floats.
      array('[1,5] or [7.0,9.0[ or [10,12]', 1.5, TRUE),
    );
  }

  /**
   * @dataProvider dataProviderInInterval
   */
  public function testInInterval($expression, $value, $expected) {
    $c = new MathIntervalCollection($expression);
    $this->assertEquals($expected, $c->inInterval($value));
  }

  public function testIterator() {
    $c = new MathIntervalCollection('[1,2] or [3,4] or [5,6]');
    
    $expected = array('[1,2]', '[3,4]', '[5,6]');
    foreach ($c as $key => $value) {
      $this->assertEquals($expected[$key], $value);
    }
  }

}