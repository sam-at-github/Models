<?php
namespace Models;

/**
 * Collection of Model instances satisfying ModelItemCollectionInterface.
 * @see MetaModelItem, Models:getModelsAsModelItems(), Models::getModels().
 */
class MetaModelItemCollection implements ModelItemCollectionInterface
{
  private $models;
  private $it;
  private $data = [];

  public function __construct(array $models) {
    $this->data = $models;
    $this->it = new \ArrayIterator($models);
  }

  public function rewind() {
    $this->it->rewind();
  }

  public function current() {
    return new MetaModelItem($this->it->current());
  }

  public function key() {
    return $this->it->key();
  }

  public function next() {
    $next = $this->it->next();
    if($next) {
      $next = new MetaModelItem($next);
    }
    return $next;
  }

  public function valid() {
    return $this->it->valid();
  }

  /**
   * Zero indicates no limit. Calling this resets the iterator.
   */
  public function limit($num) {
    if($num < 0) {
      throw new \OutOfRangeException("Limit must be >=0");
    }
    $this->limit = $num;
    $this->setSlice();
  }

  /**
   * Calling this resets the iterator.
   */
  public function skip($num) {
    if($num < 0) {
      throw new \OutOfRangeException("Seek must be >=0");
    }
    $this->seek = $num;
    $this->setSlice();
  }

  /**
   * Return the count.
   */
  public function count() {
    if($this->limit == 0) {
      return count($this->data) - $this->seek;
    }
    else {
      return min(count($this->data) - $this->seek, $this->limit);
    }
  }

  private function setSlice() {
    if($this->seek > 0 || $this->limit > 0) {
      $limit = $this->limit > 0 ? $this->limit : null;
      $this->it = new \ArrayIterator(array_slice($this->data, $this->seek, $limit));
    }
    else {
      $this->it = new \ArrayIterator($this->data);
    }
  }

  public function __toString() {
    $json = "[";
    $sep = "";
    foreach($this as $item) {
      $json .= $sep . $item;
      $sep = ",";
    }
    $json .= "]";
    return $json;
  }

  /**
   * Model has no model.
   */
  public function model() {
    return null;
  }
}
