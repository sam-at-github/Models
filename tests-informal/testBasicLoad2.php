<?php
require_once 'vendor/autoload.php';
use JsonDocs\Uri;
use JsonDocs\JsonDocs;
use JsonDocs\JsonLoader;

error_reporting((E_ALL)&~(E_NOTICE|E_STRICT));
chdir(dirname(__file__));

$schema = '{
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
}';
$mongoDb = (new MongoClient("mongodb://test:pass@localhost/test"))->{"test"};
$models = new Models\Models($mongoDb);
$models->loadModels([$schema]);
$models->drop();
$model = $models->getModel('event');
print "Model loaded. Loading data\n";
$data = json_decode(file_get_contents("test-data/data/events.json"));
var_dump($data);
foreach($data->events as $k => $event) {
   try {
      $model->create($event);
      print "OK $k: " . json_encode($event) . "\n";
   }
   catch(Exception $e) {
      print "Create failed:\n";
      print get_class($e) . ":: ". $e->getMessage() ."\n";
      print "With: \n";
      print json_encode($event);
      print "\n\n";
   }
}
