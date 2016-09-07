<?php
namespace Models;

/**
 * Basic interface of a persistent CRUDI-able collection.
 */
interface ModelInterface {
  public function create($obj);  # $obj should be an object|array
  public function read($id);
  public function update($obj, $upsert = false);  # $obj should be an object|array
  public function delete($id);
  public function exists($id);
  public function find(array $query = [], array $options = []);
  public function schema();
  public function validateCreate($obj);  # $obj should be an object|array
  public function validateUpdate($obj);  # $obj should be an object|array
}
