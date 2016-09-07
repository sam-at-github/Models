<?php
namespace Models;
use Models\Models;

/**
 * Wraps an active Mongo cursor - the result of MongoCollection::find().
 * Basically so we can do array2object iteratively.
 * Also support paging.
 */
class ModelItemCollection implements ModelItemCollectionInterface
{
  private $cursor;
  private $model;

  public function __construct(\MongoCursor $cursor, Model $model) {
    $this->cursor = $cursor;
    $this->model = $model;
  }

  public function rewind() {
    return $this->cursor->rewind();
  }

  public function current() {
    $item = Models::array2object($this->cursor->current());
    if(is_object($item)) {
      $item = new ModelItem($item, $this->model);
    }
    return $item;
  }

  public function key() {
    return $this->cursor->key();
  }

  public function next() {
    $item = Models::array2object($this->cursor->next());
    if(is_object($item)) {
      $item = new ModelItem($item, $this->model);
    }
    return $item;
  }

  public function valid() {
    return $this->cursor->valid();
  }

  public function count() {
    return $this->cursor->count();
  }

  public function limit($num) {
    return $this->cursor->limit($num);
  }

  public function skip($num) {
    return $this->cursor->skip($num);
  }

  /**
   * Lazy probably should expose the Mongo.
   */
  public function __call($method, $args) {
    return call_user_func_array([$this->cursor, $method], $args);
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

  public function toArray() {
    return iterator_to_array($this);
  }

  public function model() {
    return $this->model;
  }
}
