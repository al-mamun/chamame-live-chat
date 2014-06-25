<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

class ChamameConstructor {
  private $builder;

  public function __construct( $builder ) {
    $this->builder = $builder;
  }

  public function construct() {
    $this->builder->buildInstaller();
    $this->builder->buildChatClient();
    $this->builder->buildAdmin();
    $this->builder->buildAjax();
    return $this->builder->getResult();
  }
}
