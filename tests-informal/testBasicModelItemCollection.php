<?php
require_once 'vendor/autoload.php';
require_once 'config.php';
use JsonDocs\Uri;
use JsonDocs\JsonDocs;
use JsonDocs\JsonLoader;
error_reporting((E_ALL)&~(E_NOTICE|E_STRICT));
chdir(dirname(__file__));
$mongoDb = (new MongoClient($mongoDbConnStr))->{$mongoDbConf['db']};
$models = new Models\Models($mongoDb);
$models->drop(); // Drop database completely.
$models->loadModelsFromFiles([
  'user' => 'test-data/models/user.json'
]);
$modelsCollection = $models->getModelsAsModelItems();
var_dump($modelsCollection->count());
foreach($modelsCollection as $model) {
  print $model . "\n";
  print $model->schema() . "\n";
}
