<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

class ChamameNoopAdmin extends ChamameAdmin {
  public function registerHooks() {
    // No operation
    return;
  }
}
