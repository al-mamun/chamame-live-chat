<div class="wrap">
  <audio class="chamameChatMessageAlert" data-bind="playSound: doMessageAlert">
    <source src="<?php echo $pluginUrl ?>/sounds/receiveChatMessage.mp3">
    <source src="<?php echo $pluginUrl ?>/sounds/receiveChatMessage.wav">
  </audio>
  <audio class="chamameConversationAlert" data-bind="playSound: doConversationAlert">
    <source src="<?php echo $pluginUrl ?>/sounds/foundConversation.mp3">
    <source src="<?php echo $pluginUrl ?>/sounds/foundConversation.wav">
  </audio>

  <h2><?php esc_html_e( 'Chat', $textDomain ) ?></h2>
  <div class="chamameChatAdminConsole">

    <div class="chamameSystemMessage" data-bind="text: systemMessage, style: { display: systemMessage() ? 'block' : 'none' }"></div>

    <p class="chamameNoConversation" data-bind="style: { display: conversations().length === 0 ? 'block' : 'none' }">
      <?php esc_html_e( 'Currently there is no conversation', $textDomain ) ?>
    </p>

    <div class="chamameConversations chamameClearfix" data-bind="foreach: conversations">
      <div class="chamameConversation" data-bind="click: $root.joinConversation, attr: { class: $root.activeConversationId() === id ? 'chamameConversation chamameActiveConversation' : 'chamameConversation' }">
        <span class="chamameConversationSenderName" data-bind="attr: { class: read ? 'chamameConversationSenderName' : 'chamameConversationSenderName chamameUnread' }">
          <?php echo esc_html_x( 'From: ', 'sender prefix', $textDomain ) ?>
          <span data-bind="text: senderName"></span>
        </span>
        <div>
          <span class="chamameConversationTimestamp"><span data-bind="text: lastTimestamp"></span></span>
        </div>
        <div>
          <span class="chamameConversationMessage" data-bind="text: lastMessage"></span>
        </div>
      </div>
    </div>

    <div class="chamameChatTerminal" data-bind="visible: displayTerminal">
      <div class="chamameChatMessages" data-bind="style: { display: existsChatMessage() ? 'block' : 'none' }, scrollBottom: onRenderChatMessage, foreach: chatMessages">
        <div class="chamameChatMessage">
          <div class="chamameChatMessageMeta">
            <span class="chamameSenderName" data-bind="text: senderName"></span> <span data-bind="text: timestamp"></span>
          </div>
          <div class="chamameChatMessageMessage">
            <span data-bind="text: message"></span>
          </div>
        </div>
      </div>
      <div class="chamameChatReplyForm" data-bind="visible: displayReplyForm">
        <form>
          <textarea rows="1" placeholder="<?php esc_html_e( 'Press enter to send chat', $textDomain ) ?>" maxlength="300" data-bind="enable: enableControl, hasFocus: focusReplyForm, value: chatMessageToSend, valueUpdate: 'input', pressEnter: sendChatMessage, autoResize: chatMessageToSend"></textarea>
        </form>
      </div>
    </div>
  </div>

  <div class="chamameCloseConversation" data-bind="visible: displayTerminal">
    <button type="button" class="chamameCloseConversationButton button" value="close" data-bind="enable: enableControl,click: leaveConversation">
      <?php esc_html_e( 'Hide conversation', $textDomain ) ?>
    </button>
  </div>

</div>
