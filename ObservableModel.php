<?php
namespace Models;
require_once 'ModelInterface.php';

/**
 * The class add an observer implementation to the ModelInterface model interface.
 * It delegates implementation of all ModelInterface CRUD ops to the parent.
 * Renames GRUD method from x() to _x() for the parent to implement to prevent parent overriding important behav.
 */
abstract class ObservableModel implements ModelInterface {

  /** Observers */
  private $observers = [
    'beforeCreate' => [],
    'afterCreate' => [],
    'beforeRead' => [],
    'afterRead' => [],
    'beforeUpdate' => [],
    'afterUpdate' => [],
    'beforeDelete' => [],
    'afterDelete' => []
  ];

  /** _x() has exactly the same protocol as x() */
  abstract protected function _create($obj);  # $obj should be an object|array
  abstract protected function _read($id);
  abstract protected function _update($obj, $upsert = false);  # $obj should be an object|array
  abstract protected function _delete($id);
  abstract protected function _find(array $query = [], array $options = []);

  final public function addListener($event, callable $callable) {
  }

  final public function removeListener($event, callable $callable) {
  }

  final public function getListeners($event) {
    return new ArrayIterator($this->observers[$event]);
  }

  /**
   * Dispatch. Note listeners can modify opt *ref* as they please.
   * Listeners throw before to cancel, and after to lament.
   */
  private function dispatch($event, &$opt) {
    foreach($this->observers[$event] as $callable) {
      call_user_func($callable, $event, $opt);
    }
  }

  final public function create($obj) {
    $this->dispatch('beforeCreate', $obj);
    $retval = $this->_create($obj);
    $this->dispatch('afterCreate', $obj);
    return $retval;
  }

  final public function read($id) {
    $this->dispatch('beforeRead', $id);
    $retval = $this->_read($id);
    $this->dispatch('afterRead', $id);
    return $retval;
  }

  final public function update($obj, $upsert = false) {
    $this->dispatch('beforeUpdate', $obj);
    $retval = $this->_update($obj);
    $this->dispatch('afterUpdate', $obj);
    return $retval;
  }

  final public function delete($id) {
    $this->dispatch('beforeDelete', $id);
    $retval = $this->_delete($id);
    $this->dispatch('afterDelete', $id);
    return $retval;
  }

  final public function find(array $query = [], array $options = []) {
    return $this->_find($query, $options);
  }
}
