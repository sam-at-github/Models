<?php
chdir(dirname(__file__));
require_once 'vendor/autoload.php';
require_once 'config.php';
use JsonDocs\Uri;
use JsonDocs\JsonDocs;
use JsonDocs\JsonLoader;

$mongoDb = (new MongoClient($mongoDbConnStr))->{$mongoDbConf['db']};
$models = new Models\Models($mongoDb, new  JsonDocs(new JsonLoader()));
var_dump($models->getNextAutoId('user'));
