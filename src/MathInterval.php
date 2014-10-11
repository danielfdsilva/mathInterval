<?php
/**
 * @file MathInterval.php
 * Contains the MathInterval Library.
 * 
 * @author Daniel da Silva
 * @package MathInterval
 * @version 1.1.0
 */

// In class constants you can't define constants with concatenation
// because of reasons.
// Match: [1,2].
define('MATH_INTERVAL_REGEX', '(\[|\])(-?[0-9]+(?:\.?[0-9]+)?),(-?[0-9]+(?:\.?[0-9]+)?)(\[|\])');
// Match: [1,2] or [3,4] and [5,6].
define('MATH_INTERVAL_EXPRESSION_REGEX', MATH_INTERVAL_REGEX . '(?: (?:or|and) '.MATH_INTERVAL_REGEX.')*');
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
class MathInterval {

  /**
   * MathInterval version.
   */
   const VERSION = '1.1.0';

  /**
   * Whether the beginning of the interval is closed.
   * @var boolean
   * @access private
   */
  private $lBoundIn = NULL;

  /**
   * The value for the lower bound of the interval
   * @var int
   * @access private
   */
  private $lBound = NULL;

  /**
   * The value for the upper bound of the interval
   * @var int
   * @access private
   */
  private $uBound = NULL;

  /**
   * Whether the ending of the interval is closed.
   * @var boolean
   * @access private
   */
  private $uBoundIn = NULL;

  /**
   * Whether the interval allows float values.
   * Default to TRUE
   * @var boolean
   * @access private
   */
  private $allowFloat = TRUE;

  /**
   * Whether the interval is empty. In this case the interval will
   * assume ]0,0[
   * @var boolean
   * @access private
   */
  private $emptyInterval = FALSE;

  /**
   * MathInterval constructor.
   * Computes a mathematical range from the provided expression.
   * 
   * @uses MathInterval::compute($expression)
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
    $interval = MathInterval::compute($expression);

    // Check for empty.
    switch ($interval) {
      case ']0,0[':
         $this->allowFloat = FALSE;
      case ']0.0,0.0[':
        // If compute returned an empty interval initialize as such.
        return $this->setEmpty();
      break;
    }

    // Chop pieces. Validation was already done in compute().
    preg_match('/^'.MATH_INTERVAL_REGEX.'$/', $interval, $pieces);
    list(, $lbound_in_ex, $lbound, $ubound, $ubound_in_ex) = $pieces;
    // Convert upper and lower bound to number by adding 0.
    $lbound += 0;
    $ubound += 0;

    $this->lBoundIn = $lbound_in_ex == '[' ? TRUE : FALSE;
    $this->lBound = $lbound;
    $this->uBound = $ubound;
    $this->uBoundIn = $ubound_in_ex == ']' ? TRUE : FALSE;

    $this->allowFloat = !(is_int($lbound) && is_int($ubound));
  }

  /**
   * Provides a string representation of the interval that can be used
   * to initialize a new MathInterval.
   * 
   * @return string
   *   The string representation of the interval.
   */
  function __toString() {
    if ($this->isEmpty()) {
      if ($this->allowFloats()) {
        return ']0.0,0.0[';
      }
      else {
        return ']0,0[';
      }
    }
    
    $out = '';

    $out .= $this->includeLowerBound() ? '[' : ']';
    // If the bounds is something like 1.0, the 0 won't be printed.
    // To correctly allow decimals we need it.
    $out .= $this->getLowerBound();    
    if ($this->allowFloats()) {
      $decimal_part = $this->getLowerBound() - floor($this->getLowerBound());
      if ($decimal_part == 0) {
        $out .= '.0';
      }
    }

    $out .= ',';

    // If the bounds is something like 1.0, the 0 won't be printed.
    // To correctly allow decimals we need it.
    $out .= $this->getUpperBound();    
    if ($this->allowFloats()) {
      $decimal_part = $this->getUpperBound() - floor($this->getUpperBound());
      if ($decimal_part == 0) {
        $out .= '.0';
      }
    }

    $out .= $this->includeUpperBound() ? ']' : '[';
    
    return $out;
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
    if (preg_match('/^'.MATH_INTERVAL_REGEX.'$/', $expression, $pieces)) {
      // Extracted interval.
      list(, $lbound_in_ex, $lbound, $ubound, $ubound_in_ex) = $pieces;
      // Convert upper and lower bound to number by adding 0.
      $lbound += 0;
      $ubound += 0;

      // Validate interval.
      if ($lbound > $ubound) {
        throw new MathIntervalException("Lower bound must be lower than upper bound in $expression");
      }
      elseif (($ubound > $lbound) || ($lbound == $ubound && $lbound_in_ex == '[' && $ubound_in_ex == ']')) { 
        // [1,1] is a valid range allowing only the number 1.
        return $expression;
      }
      else {
        // ]1,1], [1,1[ and ]1,1[ are all empty.
        // Return as empty interval .]0,0[ Accounting for floats.
        return (is_int($lbound) && is_int($ubound)) ? ']0,0[' : ']0.0,0.0[';
      }
    }
    elseif (preg_match('/^'.MATH_INTERVAL_EXPRESSION_REGEX.'$/', $expression, $results)) {
      // There are no atoms to extract.
      // Proceed to compute.
      $atom = $results[0];
      $ranges = preg_split('/ /', $atom);
      
      // First element is always a range.
      // The regex already validated this.
      $working_range = new MathInterval(array_shift($ranges));
      $op = current($ranges);
      
      do {
        $exp = next($ranges);
        
        if ($op == 'or') {
          $working_range->union($exp);
        }
        elseif ($op == 'and') {
          $working_range->intersection($exp);
        } 
      }
      while ($op = next($ranges));
      
      return $working_range->__toString();
    }
    elseif (preg_match('/'.MATH_INTERVAL_EXP_ATOM_REGEX.'/', $expression, $atoms)) {
      // Extracted atom.
      // $atoms[0] contains the expression with parenthesis.
      // $atoms[1] contains the expression without parenthesis.
      $simplified = MathInterval::compute($atoms[1]);
      // Replace the atom with the simplified expression.
      // If multiple matches are found all will be replaced.
      $expression = str_replace($atoms[0], $simplified, $expression);
      // Run the compute again with the new expression.
      return MathInterval::compute($expression);
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
    if (!is_numeric($value)) {
      return FALSE;
    }
    else if ($this->isEmpty()) {
      return FALSE;
    }
    else if (!$this->allowFloats() && is_double($value)) {
      return FALSE;
    }
    else if ($this->includeLowerBound() && $value < $this->getLowerBound()) {
      return FALSE;
    }
    else if (!$this->includeLowerBound() && $value <= $this->getLowerBound()) {
      return FALSE;
    }
    else if ($this->includeUpperBound() && $value > $this->getUpperBound()) {
      return FALSE;
    }
    else if (!$this->includeUpperBound() && $value >= $this->getUpperBound()) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Returns the value for the interval's upper bound.
   * @access public
   * 
   * @return int
   *   The value for the interval's upper bound.
   */
  public function getUpperBound() {
    return $this->uBound;
  }

  /**
   * Returns the value for the interval's lower bound.
   * @access public
   * 
   * @return int
   *   The value for the interval's lower bound.
   */
  public function getLowerBound() {
    return $this->lBound;
  }

  /**
   * Check whether the beginning of the interval is closed.
   * @access public
   * 
   * @return Boolean
   *   Whether the beginning of the interval is closed.
   */
  public function includeLowerBound() {
    return $this->lBoundIn;
  }

  /**
   * Check whether the ending of the interval is closed.
   * @access public
   * 
   * @return Boolean
   *   Whether the ending of the interval is closed.
   */
  public function includeUpperBound() {
    return $this->uBoundIn;
  }

  /**
   * Check whether the interval is empty.
   * @access public
   * 
   * @return Boolean
   *   Whether the interval is empty.
   */
  public function isEmpty() {
    return $this->emptyInterval;
  }

  /**
   * Check whether the interval allows floating values.
   * @access public
   * 
   * @return Boolean
   *   Whether the interval allows floating values.
   */
  public function allowFloats() {
    return $this->allowFloat;
  }

  /**
   * Computes the union of this interval with a given one.
   * @access public
   * 
   * @param string $expression
   *   The interval to unite.
   * 
   * @throws MathIntervalException
   *   - If the provided expression is invalid.
   *   - If the lower bound of the interval is higher then its upper bound.
   * 
   * @return MathInterval
   *   The updated interval.
   */
  public function union($expression) {
    $toJoin = new MathInterval($expression);

    // Handle float values.
    $this->allowFloat = $this->allowFloat || $toJoin->allowFloats();

    // Handle empty intervals.
    if ($toJoin->isEmpty()) {
      return $this;
    }
    elseif ($this->isEmpty()) {
      // Copy $toJoin.
      $this->lBoundIn = $toJoin->includeLowerBound();
      $this->lBound = $toJoin->getLowerBound();
      $this->uBound = $toJoin->getUpperBound();
      $this->uBoundIn = $toJoin->includeUpperBound();
      // Allowing floats depends on both intervals. Do not copy this property.
      // $this->allowFloat = $toJoin->allowFloats();
      $this->emptyInterval = $toJoin->isEmpty();
      return $this;
    }

    // No empty intervals. Unite.
    // Upper bound.
    if ($toJoin->getUpperBound() > $this->getUpperBound()) {
      // Since the upper bound of the expression to join is higher
      // the new upper bound is the one from that expression.
      $this->uBound = $toJoin->getUpperBound();
      $this->uBoundIn = $toJoin->includeUpperBound();
    }
    elseif ($this->getUpperBound() == $toJoin->getUpperBound()) {
      // With equal upper bounds, if one is included the resulting
      // union also has an included upper bound.
      if ($toJoin->includeUpperBound()) {
        $this->uBoundIn = TRUE;
      }
    }
    // else the current interval stays as is.

    // Lower bound.
    if ($toJoin->getLowerBound() < $this->getLowerBound()) {
      // Since the lower bound of the expression to join is lower
      // the new lower bound is the one from that expression.
      $this->lBound = $toJoin->getLowerBound();
      $this->lBoundIn = $toJoin->includeLowerBound();
    }
    elseif ($this->getLowerBound() == $toJoin->getLowerBound()) {
      // With equal lower bounds, if one is included the resulting
      // union also has an included lower bound.
      if ($toJoin->includeLowerBound()) {
        $this->lBoundIn = TRUE;
      }
    }
    // else the current interval stays as is.

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
    $toJoin = new MathInterval($expression);
    
    // Handle float values.
    $this->allowFloat = $this->allowFloat || $toJoin->allowFloats();

    // Handle empty intervals.
    // Intersection with an empty interval is always empty.
    if ($toJoin->isEmpty()) {
      return $this->setEmpty();
    }
    elseif ($this->isEmpty()) {
      return $this;
    }

    // No empty intervals. Intersect.
    // Check if $this contains $toJoin;
    if ($this->getLowerBound() < $toJoin->getLowerBound() && $this->getUpperBound() > $toJoin->getUpperBound()) {
      // Copy $toJoin.
      $this->lBoundIn = $toJoin->includeLowerBound();
      $this->lBound = $toJoin->getLowerBound();
      $this->uBound = $toJoin->getUpperBound();
      $this->uBoundIn = $toJoin->includeUpperBound();
      // Allowing floats depends on both intervals. Do not copy this property.
      // $this->allowFloat = $toJoin->allowFloats();
      $this->emptyInterval = $toJoin->isEmpty();
      return $this;
    }
    // Check if $toJoin contains $this;
    elseif ($this->getLowerBound() > $toJoin->getLowerBound() && $this->getUpperBound() < $toJoin->getUpperBound()) {
      return $this;
    }
    
    // Find out which interval comes first.
    if ($this->getLowerBound() <= $toJoin->getLowerBound() && $this->getUpperBound() <= $toJoin->getUpperBound()) {
      $first = $this;
      $second = $toJoin;
    }
    else {
      $first = $toJoin;
      $second = $this;
    }
    
    // Case:
    //  __________  _________
    //           | |
    //------------------------
    if ($first->getUpperBound() < $second->getLowerBound()) {
      // No intersection.
      return $this->setEmpty();
    }
    // Case: (the upper and lower are the same.)
    //  ___________________
    //           |
    //------------------------
    elseif ($first->getUpperBound() == $second->getLowerBound()) {
      // The only possible option here is an interval with only one value allowed
      // like [1,1] but for that both bounds need to be included.
      if ($first->includeUpperBound() && $second->includeLowerBound()) {
        $this->lBoundIn = TRUE;
        $this->lBound = $first->getUpperBound();
        $this->uBound = $first->getUpperBound();
        $this->uBoundIn = TRUE;
        return $this;
      }
      else {
        // No intersection.
        return $this->setEmpty();
      }
    }
    // Case: (Intervals overlap. Account for inclusions.)
    //   _____________
    //  |____________|         --> $second
    //  |            |         --> $first
    //------------------------
    elseif ($first->getLowerBound() == $second->getLowerBound() && $first->getUpperBound() == $second->getUpperBound()) {
      // Values are the same. Set only inclusions.
      $this->lBoundIn = $first->includeLowerBound() && $second->includeLowerBound();
      $this->uBoundIn = $first->includeUpperBound() && $second->includeUpperBound();
      return $this;
    }
    // Case: (Intervals overlap on lower.)
    //   __________________
    //  |_____________    |    --> $second
    //  |            |         --> $first
    //------------------------
    elseif ($first->getLowerBound() == $second->getLowerBound() && $first->getUpperBound() < $second->getUpperBound()) {
      // Values are the same. Set only inclusions.
      $this->lBoundIn = $first->includeLowerBound() && $second->includeLowerBound();
      $this->lBound = $first->getLowerBound();
      $this->uBound = $first->getUpperBound();
      $this->uBoundIn = $first->includeUpperBound();
      return $this;
    }
    // Case: (Intervals overlap on upper.)
    //   __________________
    //  |    _____________|    --> $first
    //      |            |     --> $second
    //------------------------
    elseif ($first->getLowerBound() < $second->getLowerBound() && $first->getUpperBound() == $second->getUpperBound()) {
      // Values are the same. Set only inclusions.
      $this->lBoundIn = $second->includeLowerBound();
      $this->lBound = $second->getLowerBound();
      $this->uBound = $second->getUpperBound();
      $this->uBoundIn = $first->includeUpperBound() && $second->includeUpperBound();
      return $this;
    }
    // Having excluded all other cases the remaining one is:
    //         ____________
    //  ______|_______    |    --> $second
    //               |         --> $first
    //------------------------
    // The upper bound belongs to $first and
    // the lower bound belongs to $second.
    else {
      $this->lBoundIn = $second->includeLowerBound();
      $this->lBound = $second->getLowerBound();
      $this->uBound = $first->getUpperBound();
      $this->uBoundIn = $first->includeUpperBound();
      return $this;
    }
  }

  /**
   * Sets an interval as empty.
   * @access private
   * 
   * @return MathInterval
   *   Self to allow chaining.
   */
  private function setEmpty() {
    $this->lBoundIn = FALSE;
    $this->lBound = 0;
    $this->uBound = 0;
    $this->uBoundIn = FALSE;
    $this->emptyInterval = TRUE;
    return $this;
  }
}

/**
 * Exception class used by MathInterval.
 */
class MathIntervalException extends Exception { }