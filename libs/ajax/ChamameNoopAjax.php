<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

class ChamameNoopAjax extends ChamameAjax {
  public function registerHooks() {
    // No operation
    return;
  }
}
