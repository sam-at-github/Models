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

print "###### Create\n";
$models->loadModelsFromFiles([
  'test-data/models/user.json'
]);
$models->drop();
$model = $models->getModel('user');

print "###### Model loaded. Data loaded.\n";
$users = json_decode(file_get_contents("data/users.json"));
$model->create($users->users[0]);
$model->create($users->users[1]);

print "###### Read\n";
print $model->read(1) . "\n";
$user = $model->read(2)->item();

print "###### Update\n";
$user->firstName = "XXXX";
$model->update($user);
$user->firstName = "X";
try {
  $model->update($user);
}
catch(Exception $e) {
  print "Update Error: " . $e->getMessage() . "\n";
}
print "###### Delete\n";
print $model->find()->count() . "\n";
$model->delete(2);
print $model->find()->count() . "\n";
$model->delete(1);
print $model->find()->count() . "\n";
$model->delete(0);
