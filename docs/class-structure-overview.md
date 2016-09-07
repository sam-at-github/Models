**Models.php**
Entry point to this library. Is a map of <name,`Model`> pairs.

  public function __construct(\MongoDB $dbh, JsonDocs $docs = null)
  public function addModel(Uri $schemaUri)
  public function loadModels(array $models)
  public function loadModelsFromFiles(array $modelMap)
  public function getModel($name)
  public function getModels()
  public function getModelsAsModelItems()
  public function getIterator()
  public function getNames()
  public function exists($name)
  public function getDbh()
  public function getJsonDocs()
  public function drop($all = false)
  public function validateCreate(\StdClass $obj)
  public function validateUpdate(\StdClass $obj)

**Model.php**
A wrapper over a MongoDB `collection`, and a JSON Schema schema (uses `PhpJsonSchema` UTH). Inherits create(), read(), update(), delete() from `ModelInterface`.

  public function __construct(Models $models, $schema, Uri $schemaUri)
  public function name()
  public function models()
  public function schema()
  protected function _create(\StdClass $obj)
  protected function _read($id)
  protected function _update(\StdClass $obj, $upsert = false)
  protected function _delete($id)
  protected function _find(\StdClass $query, array $options = [])
  public function exists($id)
  public function validateCreate(\StdClass $obj)
  public function validateUpdate(\StdClass $obj)

**ModelInterface.php**
This is base abstraction of a CRUDI-able thing. `Model` implements this by way of `ObservableModel`.

  public function create(\StdClass $obj);
  public function read($id);
  public function update(\StdClass $obj);
  public function delete($id);
  public function exists($id);
  public function find(\StdClass $query, array $options = []);
  public function schema();
  public function validateCreate(\StdClass $obj);
  public function validateUpdate(\StdClass $obj);

**ObservableModel.php**
Implements `ModelInterface`, and provide pre|post events to listeners on CRUD operations. It's only role is dispathing these events and managing listeners.

**ModelItemCollection.php**
Model::create|update|read() return ModelItem which is a thin intelligent wrapper over a hash representing the given document. Model::find() logically should then return a list of ModelItem. `ModelItemCollection` is a lazy list of `ModelItem` returned by Model::find()

**ModelItemCollectionInterface.php**
`ModelItemCollection` is backed by a MongoDB cursor. This interface is just to segregate the `collection` part of ModelItemCollection so we can create consistent collections of `ModelItem` without the MongoDB.

**ModelOperations.php**
Not used. Not important.

**MetaModelItem.php, MetaModelItemCollection.php**
`Model` is a collection on `ModelItem`. `Models` is a collection of `Model`. These two classes allow us to treat Models/Model the same as Model/ModelItem. Currently CRUD on Models is not supported. Currently you use `Models::getModelsAsModelItems()` to return a `MetaModelItemCollection` of `MetatModelItem`.
