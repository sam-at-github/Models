<?php

/**
 * Update Acl with default rules on Entity add/remove.
 */
class AclEntityUpdateListener {
  private $acl;
  private $auth;
  private $model;
  private $l

  public function __construct(\Model $model) {
    $this->acl = App::instance()->getAcl();
    $this->auth = App::instance()->getAuth();
    $this->l = App::instance()->getLogger();
    $this->model = $model;
    $model->addListener('afterCreate', [$this, 'afterCreate']);
    $model->addListener('afterDelete', [$this, 'afterDelete']);
  }

  public function afterCreate() {
    $this->l->debug("afterCreate");
    $aco = $this->buildAclName($opt);
    $aro = $this->auth->getUser();
    $this->acl->grant($aro, $aco, 'GET|PUT|POST|DELETE');
  }

  public function afterDelete() {
    $this->l->debug("afterDelete");
    $aco = $this->buildAclName($opt);
    $this->acl->revoke(null, $aco);
  }

  protected function buildAclName(array $opt) {
    $class = get_called_class();
    $obj = $this->read($opt);
    $objId = $obj->id;
    $count = 1;
    $class = str_replace("Model\\", "", $class, $count);
    return "/{$class}/{$objId}";
  }
}
