<?php
namespace Models;
use JsonSchema\JsonSchema;
use JsonDocs\JsonDocs;
use JsonDocs\Uri;
use Models\Exception\ModelsException;
use Models\Exception\ModelExistsException;

/**
 * This class is basically a wrapper / hydrator over a document database (currently MongoDB). Its tightly coupled to a MongoDB handle and a given db.
 * It provides a collection of Model entities nameed by a unique name and handles Json validation.
 * It relies on meta data stored in the underlying database but will initialize this data at init. Namely autoids_.
 *  db.autoids_ holds auto incrementing integer id for model elements. We decided we want these. MongoDB does not implement them unfortunately.
 *  db.schema_. Not currently used, but reserved for stroing schema later maybe.
 */
class Models implements \IteratorAggregate
{
  private $dbh;
  private $docs;
  private $models = [];
  private $schema;
  /** Meta schema. All model schema must have this minimal shape. If update & create are the same use a ref. */
  const MODEL_META_JSON_SCHEMA = '{
    "type": "object",
    "properties": {
      "create" : {"type": "object"},
      "update" : {"type": "object"},
      "id":      {"type": "string", "format": "uri"},
      "name":    {"type": "string", "minLength": 1}
    },
    "required": ["create", "update", "name"]
  }';
  /** Enforce this common model item structure. Never _id on create, always _id on update */
  const VALIDATE_ITEM_JSON_SCHEMA = '{
    "create": {
      "type": "object",
      "not": {
        "required": ["_id"]
      }
    },
    "update": {
      "type": "object",
      "properties": {
        "_id" : {"type": "integer", "minimum": 1}
      },
      "required": ["_id"]
    }
  }';

  /**
   * Construct. Require valid connection to Mongo data store.
   * @param $dbh MongoClient
   * @param $docs JsonDocs optional pre initialized JsonDocs document loader/cache.
   */
  public function __construct(\MongoDB $dbh, JsonDocs $docs = null) {
    $this->dbh = $dbh;
    $this->docs = $docs ? $docs : new JsonDocs(new \JsonDocs\JsonLoader());
    $this->metaSchema = new JsonSchema(json_decode(self::MODEL_META_JSON_SCHEMA));
    $this->schema = new JsonSchema(json_decode(self::VALIDATE_ITEM_JSON_SCHEMA));
    $this->initMongoDb();
  }

  /**
   * Create a new model and add to collection.
   * Note its upto the JsonDocs instance provide at init to be able to load the specified schema Uri here.
   * @param $schema Uri location of serialized JSON Schema JSON document for the new model.
   * @returns The new model.
   */
  public function addModel(Uri $schemaUri) {
    $schema = $this->docs->loadUri($schemaUri);
    if($this->exists($schema->name)) {
      throw new ModelExistsException("Model '{$schema->name}' already exists");
    }
    $this->validateModelSchema($schema);
    $model = new Model($this, $schema, $schemaUri);
    $this->models[$schema->name] = $model;
    return $model;
  }

  /**
   * Convenience loader to load array of models.
   * If one model refers to another via a relative $ref the referee must be loaded first.
   * Use addModel() if you need to control URI address for the schema.
   * @param $modes array of JSON doc strings.
   */
  public function loadModels(array $models) {
    foreach($models as $modelStr) {
      if(!is_string($modelStr)) {
        throw new \InvalidArgumentException("Require string, found " . gettype($modelStr));
      }
      $obj = json_decode($modelStr);
      $uri = new Uri("file:///tmp/models/{$obj->name}");
      $this->docs->loadDocObj($obj, $uri);
      $this->addModel($uri);
    }
  }

  /**
   * Convenience loader from file.
   */
  public function loadModelsFromFiles(array $modelMap) {
    foreach($modelMap as $file) {
      $file = self::makePath($file);
      $this->addModel(new Uri($file));
    }
  }

  /**
   * Get the model at $name if exists.
   */
  public function getModel($name) {
    return isset($this->models[$name]) ? $this->models[$name] : null;
  }

  /**
   * Get all models.
   * @returns array.
   */
  public function getModels() {
    return $this->models;
  }

  /**
   * Get all models as ModelItems
   * @returns MetaModelItemCollection
   */
  public function getModelsAsModelItems() {
    return new MetaModelItemCollection($this->models);
  }

  /**
   * Convenience iterator.
   */
  public function getIterator() {
    return new \ArrayIterator($this->models);
  }

  /**
   * Get all names.
   */
  public function getNames() {
    return array_keys($this->models);
  }

  /**
   * Test name is a model name.
   */
  public function exists($name) {
     return isset($this->models[$name]);
  }

  /**
   * Get the shared db handle.
   */
  public function getDbh() {
    return $this->dbh;
  }

  /**
   * Get the share json docs cache.
   * Note sharing this is not the greatest.
   */
  public function getJsonDocs() {
    return $this->docs;
  }

  /**
   * Drop everything we manage. Useful for testing. Use dbh->drop() to drop whole DB.
   */
  public function drop($all = false) {
    foreach($this->models as $name => $val) {
      $this->dbh->{$name}->drop();
    }
    $this->dbh->autoids_->drop();
    if($all) {
      $this->models = [];
      $this->docs->clear();
    }
  }

  /**
   * Meta validation of a new object.
   */
  public function validateCreate(\StdClass $obj) {
    return $this->schema->validate($obj, "/create");
  }

  /**
   * Meta validation of an updated object.
   */
  public function validateUpdate(\StdClass $obj) {
    return $this->schema->validate($obj, "/update");
  }

  /**
   * Generate an integer for use as _id. MongoIds are shit. Too long, encode to {$id: ...} not strings.
   * Have to use this BS hack to get an Auto ID - Grrr Mongo. Fragile.
   * @param String should be the name of the model.
   * @return int _id
   */
  public function getNextAutoId($name) {
    $result = $this->dbh->autoids_->findAndModify(
      ['_id' => $name],
      [ '$inc' => ['seq' => 1]],
      null,
      ['new' => true, 'upsert' => true]
    );
    return $result['seq'];
  }

  /**
   * Init mongo dbh. Initialize models from _schema collection.
   * Schemas have a URI that may be referenced from other schema.
   * So we need to preload all schema into the JsonDocs (you do this with JsonDocs::loadDoc());
   */
  private function initMongoDb() {
    $this->dbh->setWriteConcern(1);
  }

  /**
   * Check the models Json Schema has the required structure.
   * Has the side-effect of preloading the schema Uri too - failing if it can't be loaded.
   * @see self::MODEL_META_JSON_SCHEMA
   */
  private function validateModelSchema($doc) {
    $valid = $this->metaSchema->validate($doc);
    if($valid !== true) {
      throw new \BadMethodCallException("Input is not valid:\n" . $valid);
    }
  }

  /**
   * Helper.
   */
  public static function array2object($d) {
    if (is_array($d)) {
      return (object) array_map(__METHOD__, $d);
    }
    else {
      return $d;
    }
  }

  /**
   * Helper.
   */
  public static function object2array($d) {
    if (is_object($d)) {
      return array_map(__METHOD__, (array)$d);
    }
    else {
      return $d;
    }
  }

  public static function makePath($file) {
    @list($file, $frag) = explode("#", $file);
    $file = realpath($file);
    $frag = $frag ? "$frag" : "/";
    if(!$file) {
      return false;
    }
    return "file://$file#$frag";
  }
}
