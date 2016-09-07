<?php
use Models\Models;
use Models\Exception\ValidationFailedException;

/**
 *
 */
class ModelsTest extends PHPUnit_Framework_TestCase
{
  public static $dbh;

  public static function setUpBeforeClass() {
    $dsn = getenv('MONGO_DB_DSN');
    $db = trim(parse_url($dsn)['path'], '/');
    static::$dbh = (new MongoClient($dsn))->{$db};
  }

  /**
   * Load models to frame other tests and also test loading.
   */
  public function setup() {
  }

  public function testLoadModelsFromFiles() {
    $models = new Models(static::$dbh);
    $models->loadModelsFromFiles([
      getenv('DATADIR') . '/models/user.json',
      getenv('DATADIR') . '/models/event.json'
    ]);
    $models->drop();
    $this->assertEquals(count($models->getNames()), 2);
  }

  /**
   * Test load via JSON strings.
   */
  public function testLoadModelsFromObj() {
    $models = new Models(static::$dbh);
    $models->loadModels([
      file_get_contents(getenv('DATADIR') . '/models/user.json'),
      file_get_contents(getenv('DATADIR') . '/models/event.json')
    ]);
    $this->assertInstanceOf('\Models\Model', $models->getModel('user'));
    $this->assertInstanceOf('\Models\Model', $models->getModel('event'));
    $models->drop();
    $this->assertEquals($models->getNames(), ['user', 'event']);
    $models->drop(true);
    $this->assertEquals(count($models->getNames()), 0);
  }

  public function testInputValidation() {
    $models = $this->loadTestModels();
    $model = $models->getModel('user');
    $users = json_decode(file_get_contents(getenv('DATADIR') . '/data/users.json'));
    foreach($users->users as $k => $user) {
      try {
        $_user = $model->create($user);
        if(preg_match('/\binvalid\b/', $user->comment)) {
          $this->fail('Item is supposed to be invalid, but passed validation: ' . json_encode($user));
        }
        else {
          $this->assertInstanceOf('\Models\ModelItem', $_user);
          $this->assertEquals($_user->_id, $k+1);
        }
      }
      catch(ValidationFailedException $e) {
        if(preg_match('/\bvalid\b/', $user->comment)) {
          $this->fail('Item is supposed to be valid, but failed validation: ' . json_encode($user));
        }
      }
    }
  }

  public function testRead() {
    $models = $this->loadTestModels();
    $this->loadTestData($models);
    $model = $models->getModel('user');
    $this->assertInstanceOf('\Models\ModelItem', $model->read(1));  # Assumes autoids reset and start at 1.
    $this->assertInstanceOf('\Models\ModelItem', $model->read(2));
    $this->assertInstanceOf('\Models\ModelItem', $model->read(3));
    $this->assertEquals($model->read(1)->_id, 1);
    $this->assertTrue(is_string($model->read(2)->firstName));
    $this->assertNull($model->read(3)->commonName);
  }

  /**
   * @expectedException \Models\Exception\ResourceNotFoundException
   */
  public function testReadNotExists() {
    $models = $this->loadTestModels();
    $this->loadTestData($models);
    $model = $models->getModel('user');
    $this->assertInstanceOf('\Models\ModelItem', $model->read(4));
  }

  public function testUpdate() {
    $models = $this->loadTestModels();
    $this->loadTestData($models);
    $model = $models->getModel('user');
    $item = $model->read(2);
    $item->firstName = "Foo";
    $item->update();
    $this->assertEquals($item->firstName, "Foo");
    $item = $model->read(2);
    $this->assertEquals($item->firstName, "Foo");
  }

  /**
   * @expectedException \Models\Exception\ResourceNotFoundException
   */
  public function testDelete() {
    $models = $this->loadTestModels();
    $this->loadTestData($models);
    $model = $models->getModel('user');
    $model->read(2)->delete();
    $model->read(2);
  }

  private function loadTestModels() {
    $models = new Models(static::$dbh);
    $models->loadModelsFromFiles([
      getenv('DATADIR') . '/models/user.json',
      getenv('DATADIR') . '/models/event.json'
    ]);
    $models->drop();
    return $models;
  }

  private function loadTestData($models) {
    $model = $models->getModel('user');
    $users = json_decode(file_get_contents(getenv('DATADIR') . '/data/users.json'));
    $model->create($users->users[0]);
    $model->create($users->users[1]);
    $model->create($users->users[2]);
  }
}
