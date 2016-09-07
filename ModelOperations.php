<?php
namespace Models;

/**
 * A slow ass enum representing the model operations in an abstract way.
 * Its useful to formalize this concept here for export. Not currently used here.
 */
class ModelOperations
{
  const CREATE = "CREATE";
  const READ = "READ";
  const UPDATE = "UPDATE";
  const DELETE = "DELETE";
  const FIND = "FIND";
  private $value;
  private static $OPERATIONS = ["CREATE","READ","UPDATE","DELETE","FIND"];

  function __construct($x) {
    if(!in_array($x, self::$OPERATIONS)) {
      throw new \InvalidArgumentException();
    }
    $this->value = $x;
  }

  function __toString() {
    return $this->value;
  }
}
