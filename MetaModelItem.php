<?php
namespace Models;

/**
 * A ModelItem representing a model itself.
 * Its sometimes useful to view a Model in the same fashion as the ModelItems.
 * Models::getModelsAsModelItems() is to Model::read()
 * Note MetatModelItem::model() returns self.
 * @see Models:getModelsAsModelItems(), Models::getModels()
 */
class MetaModelItem extends ModelItem
{
  /**
   * @override
   */
  public function __construct(Model $model) {
    $item = new \StdClass();
    $item->_id = $model->name();
    parent::__construct($item, $model);
  }

  /**
   * @override
   */
  public function schema() {
    return Models::MODEL_META_JSON_SCHEMA;
  }

  public function sync() {
    // Not implemented
  }

  public function update() {
    // Not implemented
  }

  public function delete() {
    // Not implemented
  }

}
