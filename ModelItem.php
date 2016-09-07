<?php
namespace Models;
use JsonSchema\JsonSchema;
use JsonDocs\JsonDocs;
use JsonDocs\Uri;

/**
 * Represent a Model instance. Wrapper over a document/row.
 * Model::create|read|update() will always return ModelItem, never a raw array or object.
 * Model::find() will return a `ModelItemCollection` which is a lazy container returning ModelItem.
 * @todo Mapper impl possibly.
 */
class ModelItem implements \IteratorAggregate
{
  protected $item;
  protected $model;

  public function __construct(\StdClass $item, Model $model) {
    $this->item = $item;
    $this->model = $model;
  }

  public function sync() {
    $_item = $this->model->read($this->item->_id);
    $this->item =  $_item->item;
  }

  public function update() {
    $_item = $this->model->update($this->item);
    $this->item =  $_item->item;
  }

  public function delete() {
    $this->model->delete($this->item->_id);
  }

  public function item() {
    return $this->item;
  }

  public function fields() {
    return array_keys((array)$this->item);
  }

  public function model() {
    return $this->model;
  }

  public function schema() {
    return $this->model->schema();
  }

  public function __get($part) {
    return isset($this->item->$part) ? $this->item->$part : null;
  }

  public function __set($part, $value) {
    $this->item->{$part} = $value;
  }

  public function __unset($part) {
    unset($this->item->{$part});
  }

  public function __toString() {
    return json_encode($this->item);
  }

  public function getIterator() {
    return new \ArrayIterator((array)$this->item);
  }
}
