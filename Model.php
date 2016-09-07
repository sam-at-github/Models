<?php
namespace Models;
use JsonSchema\JsonSchema;
use JsonDocs\JsonDocs;
use JsonDocs\Uri;
use Models\Exception\ValidationFailedException;
require_once 'ObservableModel.php';

/**
 * Concrete generic model.
 * More or less a container for Models to stuff stuff in.
 */
class Model extends ObservableModel
{
  private $models;
  private $name;
  private $collection;
  private $schema;
  private $schemaUri;

  /**
   * Init Model.
   * @param $models Models singleton of which this model is a collection.
   * @throws \PhpJsonSchema\ParseException if the schema does not parse.
   * @todo factor out $schemaUri parameter. Only use one place.
   */
  public function __construct(Models $models, $schema, Uri $schemaUri) {
    $this->models = $models;
    $this->schemaUri = $schemaUri;
    $this->name = $schema->name;
    $this->schema = new JsonSchema($schema);
    $this->collection = $models->getDbh()->{$this->name};
  }

  public function name() {
    return $this->name;
  }

  public function models() {
    return $this->models;
  }

  /**
   * Get schema.
   * @returns String encoded schema the model was created from.
   * @todo Argh which schema. Just return create schema.
   */
  public function schema() {
    return $this->models->getJsonDocs()->getSrc($this->schemaUri);
  }

  /**
   * @param $obj array|object. Likely more eff to pass in object, but allow array for convenience.
   * @returns The inserted object as a StdClass or throws.
   */
  protected function _create($obj) {
    $obj = $this->ensureObject($obj);
    $valid = $this->validateCreate($obj);
    if(!isset($obj->_id)) {
      $obj->_id = $this->models->getNextAutoId($this->name);
    }
    if($valid !== true) {
      throw new ValidationFailedException("Input is not valid:\n" . $valid);
    }
    try {
      $result = $this->collection->insert($obj);
    }
    catch(\MongoDuplicateKeyException $e) {
      throw new Exception\DuplicateKeyException("Create failed. Object with id='{$obj->_id}' exists.");
    }
    if(!(bool)$result['ok']) {
      throw new \RuntimeException("Could not insert item: '{$result['errmsg']}'");
    }
    return new ModelItem($obj, $this);
  }

  /**
   * Read exactly one item specified by it's unique id.
   * If you want to select one via other criteria use find().
   * @returns the item or null.
   */
  protected function _read($id) {
    $selector = ['_id' => $id];
    $item = $this->collection->findOne($selector);
    if($item === null) {
       throw new Exception\ResourceNotFoundException("Resource with _id = $id does not exist");
    }
    return new ModelItem(Models::array2object($item), $this);
  }

  /**
   * Update an item. The item must exist.
   * The _id parameter must be set in the input object and must identify the target to update.
   * @param $obj array|object. Likely more eff to pass in object, but allow array for convenience.
   * @returns The inserted object as a StdClass or throws.
   * @see exists().
   */
  protected function _update($obj, $upsert = false) {
    $obj = $this->ensureObject($obj);
    if($upsert && !isset($obj->_id)) {
      return $this->create($obj);
    }
    $valid = $this->validateUpdate($obj);
    if($valid !== true) {
      throw new \BadMethodCallException("Input is not valid:\n" . $valid);
    }
    $id = $obj->_id;
    $selector = ['_id' => $id];
    if(!$this->exists($id)) {
      throw new Exception\ResourceNotFoundException("Resource with _id = $id does not exist");
    }
    $result = $this->collection->update($selector, $obj); // Should always.
    if(!(bool)$result['ok']) {
      throw new \RuntimeException("Could not insert item: '{$result['errmsg']}'");
    }
    return new ModelItem($obj, $this);
  }

  /**
   * Delete one item by _id. Since its by _id 0|1 will be deleted.
   * @returns Bool indicating whether or not something was deleted.
   */
  protected function _delete($id) {
    return (bool)$this->collection->remove(['_id' => $id]);
  }

  /**
   * Find stuff. Options are specified on the cursor which is only hydradated after we attempt to load data.
   * Projections are not supported.
   * @todo Happy to allow query operators. What are the sec concerns rel not sanitizing the query obj?
   * @see http://php.net/manual/en/class.mongocursor.php
   * @see https://docs.mongodb.org/manual/reference/operator/query/
   */
  protected function _find(array $query = [], array $options = []) {
    $cursor = $this->collection->find($query);
    if(isset($options['limit'])) {
      $cursor->limit($options['limit']);
    }
    if(isset($options['skip'])) { // I.e. offset.
      $cursor->skip($options['skip']);
    }
    return new ModelItemCollection($cursor, $this);
  }

  /**
   * Test if exists.
   * @returna bool existence.
   */
  public function exists($id) {
    try {
      $result = $this->_read($id);
      return (bool)$result;
    }
    catch(Exception $e) {
      return false;
    }
  }

  /**
   * Validate on creation.
   * Models->validateCreate enforces a meta structure on all model items.
   */
  public function validateCreate($obj) {
    $valid = $this->models->validateCreate($obj);
    if($valid === true) {
      $valid = $this->schema->validate($obj, "/create");
    }
    return $valid;
  }

  /**
   * Validate on update.
   * Models->validateUpdate enforces a meta structure on all model items.
   */
  public function validateUpdate($obj) {
    $valid = $this->models->validateUpdate($obj);
    if($valid === true) {
      $valid = $this->schema->validate($obj, "/update");
    }
    return $valid;
  }

  private function ensureObject($obj) {
    if(!is_object($obj) && !is_array($obj)) {
      throw new \InvalidArgumentException("Expected array|object, got " . gettype($obj));
    }
    if(is_array($obj)) {
      $obj = Models::array2object($obj);
    }
    return $obj;
  }
}
