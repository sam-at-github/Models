# Overview
Provides a thin API over MongoDB for a database of document resources, adding Json Schema validation. Like [Mongoose](http://mongoosejs.com/index.html) for PHP, but less mature, and using JSON Schema for validation. Only supports MongoDB, and is MongoDB centric. Not bothered trying to support alternate backends.

Note that while MongoDB does have validation that can be arbitrarily complex:

  1. MongoDB will not generate meaningful error messages.
  2. Front ends don't exist for doing validation off MongoDB validation schema (AFAIK).
  3. Json Schema makes sense as a stand alone portable schema language. MongoDB validators dont really.

A key idea here is externalizable sharable schemas.

# Synopsis
Basic workflow is you pass a MongoDB connection and a list of JSON Schema documents into `Models`, then grab a given `Model` object and start doing som CRUD on it:

```php
<?php
require 'vendor/autoload.php';
$mongoDb = (new MongoClient("mongodb://test:pass@localhost/test"))->{"test"};
$models = new Models\Models($mongoDb);
$models->loadModels(['{
  "type": "object",
  "name": "event",
  "create": {
    "properties": {
      "time": { "type": "integer", "minimum": 0 },
      "duration": { "type": "integer", "minimum": 0 },
      "description": { "type": "string", "maxLength": 1023 }
    },
    "required": ["time", "duration", "description"]
  },
  "update": { "$ref": "#/create" }
}']);
$models->drop();  # Clear all collections underlying loaded models.
$model = $models->getModel('event');
$model->create(['time' => time(), 'duration' => 60, 'description' => 'Anomaly']);
$events = $model->find();
print $events . "\n";
$event = $events->toArray()[1];
$event->description  ='Supernova';
$event->update();
print $events . "\n";
$events = $model->find(['duration' => 60]);
print $events . "\n";
```

Notes:

  - If you try to create or update a document tha is invalid a `Models\Exception\ValidationFailedException` will be thrown.
  - `Models` requires the schema you load have a basic shape:
      const MODEL_META_JSON_SCHEMA = '{
        "type": "object",
        "properties": {
          "create" : {"type": "object"},
          "update" : {"type": "object"},
          "id":      {"type": "string", "format": "uri"},
          "name":    {"type": "string", "minLength": 1}
        },
        "required": ["create", "update", "name"]
      }'
  - Validation is split into validation for creation and for updates. This is because they are commonly different and it may be impossible to specify the correct constraints in a common schema. If create and update share a schema or large parts of, you can use refs.
  - JSON Schema refs to external documents will load, if they are valid local resources. You can control the loading of JSON Schema refs by constructing and passing the `PhpJsonSchema\JsonDocs` loader to Models.
  - `Models` requires *all* instace of *any* model have a basic shape. That is, there is actually two schema that both must validate; Your schema and `Models` basic instance schema. This schema just requires there is *always* an `_id` on update, and *never* an `_id` on create.
  - In general all your schema should be valid against the `http://json-schema.org/draft-04/schema` schema but that is not checked explicitly (most likely the schema will fail to parse).

See [docs/class-structure-overview.md](class-structure-overview.md). For a description of the classes in this library.

# Json Schema
This lib uses [PhpJsonSchema](https://github.com/sam-at-github/PhpJsonSchema) for validation. You can reference other models via use of hyper links, and `$ref`. This only only likely going to work by default if you load you JSON Schema from file, and your `$ref`s resovle to other valid local files.
