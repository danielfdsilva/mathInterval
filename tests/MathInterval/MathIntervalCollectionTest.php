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

  public function dataProviderCompute() {
    return array(
      // With simple intervals, there's nothing to simplify
      // so the input is equal to the output.
      array('[1,4]', '[1,4]'),
      array('[1,4[', '[1,4['),
      array(']1,4]', ']1,4]'),
      array(']1,4[', ']1,4['),

      // Differently from what happens with the output of a
      // MathInterval object, when an interval is valid is returned
      // as is.
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
/*
      array('[1,5] and [3,9]', '[3,5]'),
      array('[1,50] and [3,45] and ]40,41]', ']40,41]'),
      array('[1,50] and [3,45] and ]0,0[', ']0,0['),

      // With union and intersection order is very important.
      array('[1,5] and [3,9] or [10,15]', '[3,15]'),
      array('[3,9] or [10,15] and [1,5]', '[3,5]'),
      array('[3,9] or [9,15] and [15,20]', '[15,15]'),
      array('[3,9] or [9,15] and ]15,20]', ']0,0['),

      // Test expressions with parenthesis.
      array('([1,5])', '[1,5]'),
      array('(([1,5]))', '[1,5]'),
      array('[1,5] and ([3,9] or [10,15])', '[3,5]'),
      array('[3,9] or ([10,15] and ([1,5]))', '[3,9]'),
      array('([3,9] or ([9,15] and ([15,20])))', '[3,15]'),
      array('(([3,9[) and [9,15]) and ]15,20]', ']0,0['),
      array('([1,5] and [3,4]) and ([1,10] or ([1,5] and [3,4]))', '[3,4]'),
*/
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

}