<?php

// In class constants you can't define constants with concatenation
// because of reasons.
// Match: [1,2].
define('MATH_INTERVAL_REGEX', '(\[|\])(-?[0-9]+(?:\.?[0-9]+)?),(-?[0-9]+(?:\.?[0-9]+)?)(\[|\])');
// Match: [1,2] or [3,4] and [5,6].
define('MATH_INTERVAL_EXPRESSION_REGEX', MATH_INTERVAL_REGEX . '(?: (?:or|and) '.MATH_INTERVAL_REGEX.')*');
// Match: ([1,2] or [3,4]).
define('MATH_INTERVAL_EXP_ATOM_REGEX', '\(('.MATH_INTERVAL_EXPRESSION_REGEX.'\)');

class MathInterval {

  private $lBoundIn = NULL;
  private $lBound = NULL;
  private $uBound = NULL;
  private $uBoundIn = NULL;
  private $allowFloat = TRUE;
  private $emptyInterval = FALSE;
  
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
    /*elseif (preg_match('/^'.MATH_INTERVAL_EXPRESSION_REGEX.'$/', $expression, $results)) {
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
    }*/
    /*elseif (preg_match('/'.MATH_INTERVAL_EXP_ATOM_REGEX.'/', $expression, $atoms)) {
      // Extracted atom.
      $atom = $atoms[1];
      print "$atom\n";
      compute($atom);
    }*/
    else {
      throw new MathIntervalException("Invalid expression.");
    }
  }

  private function setEmpty() {
    $this->lBoundIn = FALSE;
    $this->lBound = 0;
    $this->uBound = 0;
    $this->uBoundIn = FALSE;
    $this->emptyInterval = TRUE;
    
    return $this;
  }
  
  public function getUpperBound() {
    return $this->uBound;
  }
  
  public function getLowerBound() {
    return $this->lBound;
  }
  
  public function includeLowerBound() {
    return $this->lBoundIn;
  }
  
  public function includeUpperBound() {
    return $this->uBoundIn;
  }
  
  public function isEmpty() {
    return $this->emptyInterval;
  }
  
  public function allowFloats() {
    return $this->allowFloat;
  }
  
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
}

class MathIntervalException extends Exception { }