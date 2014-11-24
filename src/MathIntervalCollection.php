<?php
require 'MathInterval.php';

/**
 * @file MathIntervalCollection.php
 * 
 * @author Daniel da Silva
 * @package MathInterval
 * @version 2.0.0
 */

// In class constants you can't define constants with concatenation
// because of reasons.
// Match: [1,2] or [3,4] and [5,6].
define('MATH_INTERVAL_EXPRESSION_REGEX', MATH_INTERVAL_REGEX. '(?: (?:or|and) '.MATH_INTERVAL_REGEX.')*');
// Match: ([1,2] or [3,4]).
define('MATH_INTERVAL_EXP_ATOM_REGEX', '\(('.MATH_INTERVAL_EXPRESSION_REGEX.')\)');

/**
 * Library to use Mathematical Intervals.
 * 
 * Allows the creation of an interval and the validation
 * of values against said interval.
 * It is also possible to perform the union and intersection of
 * different intervals.
 */
class MathIntervalCollection implements Iterator {

  /**
   * MathInterval version.
   */
   const VERSION = MathInterval::VERSION;

   /**
   * Position of the intervals array.
   * Used by the iterator.
   * @var int
   * @access private
   */
   private $position = 0;

  /**
   * The array containing all the intervals belonging to the collection.
   * I.E. All the intervals to which a value can belong.
   * @var array
   * @access private
   */
   private $intervals = array();

   /**
    * The original expression with which the collection was initialized.
    */
   private $originalExpr = NULL;

  /**
   * MathIntervalCollection constructor.
   * Computes a collection of mathematical intervals from the provided
   * expression.
   * 
   * @uses MathIntervalCollection::compute($expression)
   * 
   * @param string $expression
   *   The interval to compute.
   * 
   * @throws MathIntervalException
   *   - If the provided expression is invalid.
   *   - If the lower bound of the interval is higher then its upper bound.
   * 
   * @return MathInterval
   */
  function __construct($expression) {
    $this->originalExpr = $expression;
    $expression = MathIntervalCollection::compute($expression);
    
    // The expression is already simplified. 
    // It will be in the form of [1,2] or [3,4]
    $ranges = preg_split('/ /', $expression);
    $tot = count($ranges);
    for ($i=0; $i < $tot; $i+=2) {
      $this->intervals[] = new MathInterval($ranges[$i]);
    }
  }

  /**
   * Provides a string representation of the interval that can be used
   * to initialize a new MathInterval.
   * 
   * @return string
   *   The string representation of the interval.
   */
  function __toString() {
    $outStrings = array_map(function($interval) {
        return $interval->__toString();
      }, $this->intervals);

    return implode(' or ', $outStrings);
  }

  /**
   * Simplifies a provided expression and validates it.
   * If the provided expression turns out to be an empty interval
   * ]0,0[ or ]0.0,0.0[ will be returned.
   * @access static
   * 
   * @param string $expression
   *   The interval to compute.
   * 
   * @throws MathIntervalException
   *   - If the provided expression is invalid.
   *   - If the lower bound of the interval is higher then its upper bound.
   * 
   * @return String
   *   The string representation of the interval.
   */
  static function compute($expression) {
    if (preg_match('/^'.MATH_INTERVAL_REGEX.'$/', $expression)) {
      $interval = new MathInterval($expression);
      return $interval->__toString();
    }
    elseif (preg_match('/^'.MATH_INTERVAL_EXPRESSION_REGEX.'$/', $expression, $results)) {
      // There are no atoms to extract.
      // Proceed to compute.
      $atom = $results[0];
      $ranges = preg_split('/ /', $atom);
      
      // First element is always an interval.
      // The regex already validated this.
      $working_interval = new MathIntervalCollection(array_shift($ranges));
      $op = current($ranges);
      
      do {
        $exp = next($ranges);
        
        if ($op == 'or') {
          $working_interval->union($exp);
        }
        elseif ($op == 'and') {
          $working_interval->intersection($exp);
        } 
      }
      while ($op = next($ranges));
      
      return $working_interval->__toString();
    }
    elseif (preg_match('/'.MATH_INTERVAL_EXP_ATOM_REGEX.'/', $expression, $atoms)) {
      // Extracted atom.
      // $atoms[0] contains the expression with parenthesis.
      // $atoms[1] contains the expression without parenthesis.
      $simplified = MathIntervalCollection::compute($atoms[1]);
      // Replace the atom with the simplified expression.
      // If multiple matches are found all will be replaced.
      $expression = str_replace($atoms[0], $simplified, $expression);
      // Run the compute again with the new expression.
      return MathIntervalCollection::compute($expression);
    }
    else {
      throw new MathIntervalException("Invalid expression.");
    }
  }

  /**
   * Check whether a given value fits within the interval.
   * @access public
   * 
   * @param int $value
   *   The value to validate
   * 
   * @return Boolean
   *   The result of the validation.
   */
  public function inInterval($value) {
    // To be true the value needs to belong in just one of the intervals.
    foreach ($this as $interval) {
      if ($interval->inInterval($value)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Check whether the interval collection is empty.
   * @access public
   * 
   * @return Boolean
   *   Whether the interval collection is empty.
   */
  public function isEmpty() {
    // When an interval collection is empty there's only one interval
    // in the intervals array. If there were more than one empty
    // intervals they would be merged.
    return $this->totalIntervals() == 1 ? $this->get(0)->isEmpty() : FALSE;
  }

  /**
   * Check whether the interval collection allows floating values.
   * @access public
   * 
   * @return Boolean
   *   Whether the interval collection allows floating values.
   */
  public function allowFloats() {
    // If one interval allows float values all will.
    return $this->get(0)->allowFloats();
  }

  /**
   * Computes the union of this interval collection with a given one.
   * @access public
   * 
   * @param (string|MathIterval|MathIntervalCollection) $expression
   *   The interval to unite in expression form, MathInterval or
   *   MathIntervalCollection
   * 
   * @throws MathIntervalException
   *   - If the provided expression is invalid.
   *   - If the lower bound of the interval is higher then its upper bound.
   * 
   * @return MathIntervalCollection
   *   The updated interval.
   */
  public function union($expression) {
    if ($expression instanceof MathIntervalCollection) {
      $colToJoin = $expression;
    }
    else {
      if ($expression instanceof MathInterval) {
        $expression = $expression->__toString();
      }
      $colToJoin = new MathIntervalCollection($expression);
    }
    
    $intervalsToAdd = array();
    // Loop over each interval in the collection to join.
    foreach ($colToJoin as $intervalToJoin) {
      $joined = FALSE;
      // Try to join with the intervals in this collection.
      // With unions is enough to join with one.
      // If a union unites with more than one interval
      // it will be taken care when the intervals are joined between themselves.
      foreach ($this as $interval) {
        if ($interval->union($intervalToJoin)) {
          $joined = TRUE;
          break;
        }
      }
      if (!$joined) {
        // Not possible to join. Add.
        $intervalsToAdd[] = $intervalToJoin;
      }
    }
    $this->intervals = array_merge($this->intervals, $intervalsToAdd);

    // After all intervals from the other collection have been merged or added,
    // merge the existing intervals to ensure that overlapping intervals
    // are combined.
    $this->_selfUnion();
    
    return $this;
  }

  /**
   * Computes the intersection of this interval with a given one.
   * @access public
   * 
   * @param string $expression
   *   The interval to intersect.
   * 
   * @throws MathIntervalException
   *   - If the provided expression is invalid.
   *   - If the lower bound of the interval is higher then its upper bound.
   * 
   * @return MathInterval
   *   The updated interval.
   */
  public function intersection($expression) {
    if ($expression instanceof MathIntervalCollection) {
      $colToJoin = $expression;
    }
    else {
      if ($expression instanceof MathInterval) {
        $expression = $expression->__toString();
      }
      $colToJoin = new MathIntervalCollection($expression);
    }

    $newIntervals = array();
    // Loop over each interval in the collection to join.
    foreach ($colToJoin as $intervalToJoin) {
      // Each interval of the collection needs to be intersected with each
      // interval of the interval array.
      //
      // For example:
      // $c = new MathIntervalCollection('[1,10] or [16,20]');
      // $c->intersection(']7,9] or [15,18]');
      //
      // ]7,9] needs to intersect with [1,10] and with [16,20]
      // which results in ]7,9]
      // [15,18] also needs to intersect with [1,10] and with [16,20]
      // which results in [16,18]
      //
      // Merging the two results we get ]7,9] or [16,18]
      // This would have been the equivalent of doing:
      // ([1,10] or [16,20]) and (]7,9] or [15,18])
      //
      // If the intervals were updated on the go the result would be
      // an empty set.
      $copy = array();
      foreach ($this as $interval) {
        // Create a copy of the interval object before the intersection.
        $copyInterval = clone $interval;
        $copyInterval->intersection($intervalToJoin);
        $copy[] = $copyInterval;
      }

      $newIntervals = array_merge($newIntervals, $copy);
    }
    $this->intervals = $newIntervals;

    // After all intervals from the other collection have been merged or added,
    // merge the existing intervals to ensure that overlapping intervals
    // are combined.
    $this->_selfUnion();
  }

  /**
   * Recursively merges all the intervals in the array to flatten it
   * as much as possible.
   */
  private function _selfUnion() {
    while (TRUE) {
      $tot = $this->totalIntervals();
      //print($this . "\n");
      for ($i=0; $i < $tot; $i++) {
        for ($j=$i+1; $j < $tot; $j++) {
          //print('Comparing: ' . $this->get($i) . ' with ' . $this->get($j) . "\n");
          if ($this->get($i)->union($this->get($j))) {
            $this->remove($j);
            continue 3;
          }
        }
      }
      break;
    }
  }

  /**
   * Returns the interval count.
   * 
   * @return int
   *   The total number of intervals
   */
  public function totalIntervals() {
    return count($this->intervals);
  }

  /**
   * Returns interval at the given position.
   * 
   * @param int
   *   Position of the interval.
   * 
   * @return MathInterval
   *   The interval.
   */
  private function get($position) {
    return $this->intervals[$position];
  }

  /**
   * Removes interval at the given position.
   * 
   * @param int
   *   Position of the interval.
   * 
   */
  private function remove($position) {
    $interval = $this->intervals[$position];
    unset($this->intervals[$position]);
    // Re-order array.
    $this->intervals = array_values($this->intervals);
    return $interval;
  }

  /**
   * Iterator method.
   * Rewinds the array.
   */
  public function rewind() {
    $this->position = 0;
  }

  /**
   * Iterator method.
   * Returns the current value.
   */
  public function current() {
    return $this->intervals[$this->position];
  }

  /**
   * Iterator method.
   * Returns the current key.
   */
  public function key() {
    return $this->position;
  }

  /**
   * Iterator method.
   * Moves the internal pointer to the next value.
   */
  public function next() {
    ++$this->position;
  }

  /**
   * Iterator method.
   * Returns Whether the current position is valid.
   */
  public function valid() {
    return isset($this->intervals[$this->position]);
  }
}
