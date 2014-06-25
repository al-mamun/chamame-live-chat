<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

class ChamameNoopChatClient extends ChamameChatClient {
  public function registerHooks() {
    // No operation
    return;
  }
}
