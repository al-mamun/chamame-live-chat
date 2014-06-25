<div class="chamameChatClient" data-bind="fadeVisible: display">

  <div class="chamameChatHeader" data-bind="click: toggleContainer">
    <h1 class="chamameChatHeaderHeading"><?php esc_html_e( 'Chat with us now', $textDomain ) ?></h1>
  </div><!-- .chamameChatHeader -->

  <div class="chamameChatClientContainer" data-bind="slideVisible: displayContainer">

    <?php if ( $isOperator ): ?>

      <div class="chamameSystemMessage" style="display: block;">
        <?php esc_html_e( 'Administrators are not allowed to chat from here', $textDomain ) ?>
      </div>

    <?php else: ?>

      <div class="chamameSystemMessage" data-bind="text: systemMessage, style: { display: systemMessage() ? 'block' : 'none' }"></div>

      <div class="chamameChatLoginForm" data-bind="slideVisible: displayLoginForm">
        <p class="chamameChatFormLead"><?php esc_html_e( 'Ask us a question', $textDomain ) ?></p>
        <form>
          <label><?php esc_html_e( 'Your name', $textDomain ) ?></label>
          <input type="text" placeholder="<?php esc_attr_e( 'Your name', $textDomain ) ?>" name="userName" data-bind="enable: enableControl, hasFocus: focusLoginForm, value: userName, valueUpdate: 'input', pressEnter: login" />

          <label><?php esc_html_e( 'Your email', $textDomain ) ?></label>
          <input type="text" placeholder="<?php esc_attr_e( 'Your email', $textDomain ) ?>" name="userEmail" data-bind="enable: enableControl, value: userEmail, valueUpdate: 'input', pressEnter: login" />

          <div class="chamameValidationMessages" data-bind="style: { display: isAnyMessageShown() ? 'block' : 'none' }">
            <div class="chamameValidationMessage" data-bind="validationMessage: userName"></div>
            <div class="chamameValidationMessage" data-bind="validationMessage: userEmail"></div>
          </div>

          <div class="chamameChatLoginButton">
            <button type="button" class="chamameChatClientButton" value="startChat" data-bind="enable: enableControl, click: login"><?php esc_html_e( 'Start chat', $textDomain ) ?></button>
          </div>
        </form>
      </div><!-- .chamameChatLoginForm -->

      <div class="chamameChatTerminal" data-bind="slideVisible: displayTerminal">
        <div class="chamameChatMessages" data-bind="style: { display: existsChatMessage() ? 'block' : 'none' }, scrollBottom: scroll, foreach: chatMessages">
          <div class="chamameChatMessage">
            <div class="chamameChatMessageMeta">
              <span class="chamameSenderName" data-bind="text: senderName"></span> <span data-bind="text: timestamp"></span>
            </div>
            <div class="chamameChatMessageMessage">
              <span data-bind="text: message"></span>
            </div>
          </div>
        </div>
        <div class="chamameChatReplyForm">
          <form>
            <textarea rows="1" placeholder="<?php esc_attr_e( 'Press enter to send chat', $textDomain ) ?>" maxlength="300" data-bind="enable: enableControl, hasFocus: focusReplyForm, value: chatMessageToSend, valueUpdate: 'input', pressEnter: sendChatMessage, autoResize: chatMessageToSend"></textarea>
          </form>
        </div>
        <div class="chamameChatEnd">
          <a href="" data-bind="click: logout"><?php esc_html_e( 'End chat', $textDomain ) ?></a>
        </div>
      </div><!-- .chamameChatTerminal -->

    <?php endif; // $isOperator ?>

  </div><!-- .chamameChatClientContainer -->


</div>
