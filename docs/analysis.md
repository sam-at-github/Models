# Some Analysis
Scenario:

    Models
      addModel(key, schema)
      getModel(key)
      getModels();
      getKeys();

    Model
      Model create()
      Model read()
      void update()
      void delete()
      List<Model> find()
      Schema schema()
      bool validate()

## Issue: find()

  Q1 How to represent collections - should collection of have their own type?
  Q2 Should find() be part of the Model interface.
  Q3 Should items of a find()ed collection be a ListItems?
  Q4 Aggregations.
  Q5 Paging / cursors.
  Q6 What about findOne().
  Q7 What about singletons.

Q1, Q2: I've spent thought on this before - can't remember conclusions. Consider the HTTP query for such a collection `GET /foo?q=size>2&&width<3`. The collection is its own resource type. But it's common for the component that is handling the CRUD to handle retrieving collections too regardless of the fact that the collection is a different type. So A2 = each model should be responsible for implementing a find(Query) method, which shall take some type of query and return some sort of collection of the given Model's type. This collection type could be an array but its probably better to have something a little more intelligent.
Q3: Scenario: You have a collection you want to render it as a table with headers. But the items may not even be objects. If they are objects which fields to render? Some sort of simple type detection could solve non objects. And a simple answer to part two is just render all fields. If you want specific view overload the view. Alternatively the renderer could look for hints in the schema. Note only stringifiable fields will be directly renderable. Can expand complexx field easily in a generic view would think. So A3=no
Q4: We also have to consider aggregation which are concpetually a type of query. Also consider SQL count, group by. If we allow find() to return such it violates the rule List<Model>. Alts: 1. Just allow find to return this stuff. 2. Add another model key to handle this stuff for a given node. This model wont have a find() method that make sense. `GET  /node-facts?q=(max,min,average,middle,count)`.
Q5: That's whay the answer to Q1 should be something more complexx. Can think about later.
Q6: A6 = findOne is find() with a limit parameter. In HTTP GET /node/0 ~== GET /node?q=id=0&limit=1
Q7: A7 = Singletons are just Models. C,D will cause and exception.

Note an underlyinng assertion is that all Model types have a scalar primary key field - this is a pretty universal constraint now days.

## Dealing With Json Schema $refs
...

## Validation
The correct validation may actually depend on the write operation. There are exactly two write operations Create, Update. Essentially, some fields are generated and are readonly. The prim example is _id. Thus we need two validations:

  validate(Update|Create)($obj)

This implies we need two schema too. We could require just one schema and require the create and update schemas to be located within it at fixed pointer - /create, /update. but not doign that for now.

## MongoIds
Are annoying. They are very long, and encoding them straight from the db ends up with ~ `"_id":{"$id":"56879e748ead0ec34b8b4567"}`.

## Ids and Common Structure
We are enforcing that all entities have the following structure:

  {
    "type": "object",
    "properties": ["_id": {"type": "integer", "minimum": 1}],
    "required": ["_id"]
  }

At least after the entity is created. Before creation it may or may not have an _id field. Client schemas have to be consistent with this. Also note that _id is the primary key. This key is used exclusively to refer to a given object in read(), delete(), update(). This just simplifies things for this proof of concept immplementation.

## Uniqueness
Apart from the implicit uniqueness of _id its not supported. Todo: would be nnice to support pulling from the schema. Something like:

  {
    type: object,
    properties: [
      foo: { ... unique: true }
    ],
    ...
  }

## Exceptions
The model must use exceptions consistently. Exception should be groupable into generic groups based on general semantics of the error. Consider the HTTP interface. Exceptions generated at teh model level will be expose as HTTP error types which are generic.

## The Semantics of the CRUD
Being pretty strict. Generally if you refer to a resource by id and it does not exist we'll throw. If a resource is expected not to exist and it does (create) we'll throw. This is why exists($id) has to be part of the Entity API.

Create:
  - Not valid: throw InvalidArgumentException.
  - Key exists: throw DuplicateKeyException extends BadMethodCallException
  - Some other typ of error. Mongo generally throws for everything that is not a vlaid insert so throw that.
Read
  - Does not exist: throw ResourceNotFoundException.
Update:
  - Not valid: throw InvalidArgumentException.
  - Does not exist: throw ResourceNotFoundException.
  - Db failure to update throw that.
Delete:
  - Does not exist: throw ResourceNotFoundException.
  - Db failure to update throw that.

Another issue is what to represent documents as. Mongo will accept either. But we want to enforce a type either array or StdClass at teh CRUD interface to avoid ugly type checking boiler plate.  Mongo returns assoc array. So using that will reduce costs especially with find. But JsonSchema does not treat hashes and objects the same. Simplest path is to force ojects. Lets do that. Results of find are wrapped in a container which can incrementally unfold to reduce costs.
