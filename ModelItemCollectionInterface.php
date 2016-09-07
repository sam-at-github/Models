<?php
namespace Models;

/**
 * Provide an abstract base for Collections of ModelItems.
 * Mostly we are interested in collections that map directly to the result of a db query.
 * Hence ModelItemCollection wraps a MongoDB cursor.
 * But segregating this interface allows us to build compatible collections of ModelItem.
 */
interface ModelItemCollectionInterface extends \Iterator, \Countable
{
  public function limit($num);
  public function skip($num);
}
