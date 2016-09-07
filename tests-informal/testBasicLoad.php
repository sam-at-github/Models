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
$models->loadModelsFromFiles([
  'test-data/models/user.json'
]);
$models->drop();

$model = $models->getModel('user');
print "Model loaded. Loading data\n";
$users = json_decode(file_get_contents("test-data/data/users.json"));
foreach($users->users as $k => $user) {
   try {
      $model->create($user);
      print "OK $k: " . json_encode($user) . "\n";
   }
   catch(Exception $e) {
      print "Create failed:\n";
      print get_class($e) . ":: ". $e->getMessage() ."\n";
      print "With: \n";
      print json_encode($user);
      print "\n\n";
   }
}
