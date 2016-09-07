<?php
$mongoDbConf = [
  'user' => 'test',
  'password' => 'pass',
  'host'=> 'localhost',
  'port'=> '27017',
  'db'=> 'test'
];
$mongoDbConnStr = "mongodb://{$mongoDbConf['user']}:{$mongoDbConf['password']}@{$mongoDbConf['host']}/{$mongoDbConf['db']}";
