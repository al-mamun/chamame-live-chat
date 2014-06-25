;(function( window, undefined ) {

(function( $ ) {

  if ( ! window.console ) {
    window.console = { log: function(){} };
  }

  // Knockout custom bindings
  ko.bindingHandlers.playSound = {
    init: function( element, valueAccessor ) {
      valueAccessor().subscribe(function() {
        $( element )[0].play();
      });
    }
  };
  ko.bindingHandlers.scrollBottom = {
    init: function( element, valueAccessor ) {
      valueAccessor().subscribe(function() {
        $( element ).animate( { scrollTop: $( element ).prop( 'scrollHeight') }, 'fast' );
      });
    }
  };
  ko.bindingHandlers.autoResize = {
    init: function( element, valueAccessor ) {
      $( element ).autosize( { append: '' } );
      $( element ).val( 'dummy' );
      $( element ).trigger( 'autosize.resize' );
      $( element ).val( '' );

      valueAccessor().subscribe(function() {
        $( element ).trigger( 'autosize.resize' );
      });
    }
  };
  ko.bindingHandlers.pressEnter = {
    init: function( element, valueAccessor, allBindingsAccessor, viewModel ) {
      ko.utils.registerEventHandler( element, 'keydown', function( e ) {
        if ( e.keyCode === 13 && !e.shiftKey ) {
          e.preventDefault();
          valueAccessor().call( viewModel, element );
        }
      })
    }
  };
  ko.bindingHandlers.fadeVisible = {
    init: function( element, valueAccessor ) {
      $( element ).toggle( ko.utils.unwrapObservable( valueAccessor() ) );
    },
    update: function( element, valueAccessor, allBindings ) {
      var v = valueAccessor();
      var e = element;

      if ( allBindings.has( 'xFadeTarget' ) ) {
        if ( ko.utils.unwrapObservable( v ) ) {
          $( e ).fadeIn( 'fast' ).promise().done(function() {
            allBindings.get( 'xFadeTarget' )( false );
          });
        } else {
          $( e ).fadeOut( 'fast' ).promise().done(function() {
            allBindings.get( 'xFadeTarget' )( true );
          });
        }
      } else {
        ko.utils.unwrapObservable( v ) ? $( e ).fadeIn( 'fast' ) : $( e ).fadeOut( 'fast' );
      }
    }
  };

  ko.validation.init({
    insertMessages: false,
    decorateInputElement: true
  });

  var ChatAdmin = function( params ) {
    var self = this;

    self.displayTerminal = ko.observable( false );
    self.displayReplyForm = ko.observable( false );
    self.conversations = ko.observableArray();
    self.chatMessages = ko.observableArray();
    self.onRenderChatMessage = ko.observable();
    self.chatMessageToSend = ko.observable();
    self.existsChatMessage = ko.observable( false );
    self.focusReplyForm = ko.observable( false );
    self.systemMessage = ko.observable( '' );
    self.enableControl = ko.observable( true );
    self.doMessageAlert = ko.observable( false );
    self.doConversationAlert = ko.observable( false );

    self.conversationsMeta = {};

    self.needMessageAlert = false;
    self.conversationAlert = false;
    self.processingSendChatMessage = false;
    self.processingPollChatMessage = false;
    self.processingPollConversation = false;

    self.lastReceivedConversationId = 0;
    self.activeConversationId = ko.observable( 0 );
    self.conversationMissingCount = 0;
    self.conversationPolling;

    self.lastReceivedMessageId = 0;
    self.chatMessageMissingCount = 0;
    self.chatMessagePolling;

    self.unload = false;

    self.init = function() {
      $.ajaxSetup({
        cache : false,
        type: 'post',
        dataType: 'json',
        url: params.ajaxUrl
      });

      $( window ).on( 'beforeunload', function() {
        self.unload = true;
      });

      if ( params.loggedIn ) {
        if ( params.conversationId ) {
          self.joinConversation( { id: params.conversationId } );
        } else {
          self.pollConversation();
        }
      } else {
        self.login();
      }
    };
    self.login = function() {
      $.ajax({
        data: {
          action: 'chamameLoginOperator',
          token: params.token
        }
      })
      .done(function( r ){
        console.log( 'login', r );

        if ( r.status === 'failure' ) {
          self.abort( params.text.error );
          return;
        }

        self.pollConversation();
      })
      .fail( function( xhr ){
        console.log( 'login', xhr );
        self.abort( params.text.error );
      })
      .always();
    },
    self.joinConversation = function( conversation ) {
      console.log( self.processingJoinConversation);
      if ( self.processingJoinConversation || !self.enableControl() ) {
        return;
      }
      self.processingJoinConversation = true;

      self.stopPolling( self.conversationPolling );
      self.stopPolling( self.chatMessagePolling );

      if ( self.conversationsMeta[conversation.id] ) {
        self.conversationsMeta[conversation.id].read = true;
      } else {
        self.conversationsMeta[conversation.id] = {
          lastMessageId: 0,
          read: true
        }
      }

      self.lastReceivedMessageId = 0;
      self.chatMessages.removeAll();
      self.displayReplyForm( false );

      $.ajax({
        data: {
          action: 'chamameJoinConversation',
          token: params.token,
          conversationId: conversation.id
        }
      })
      .done(function( r ){
        console.log( 'joinConversation', r );

        if ( r.status === 'failure' ) {
          self.abort( params.text.error );
          return;
        }

        self.displayTerminal( true );

        self.activeConversationId( conversation.id );
        self.needMessageAlert = false;
        self.conversationAlert = false;
        self.pollConversation();
        self.pollChatMessage();

        self.displayReplyForm( true );
        self.focusReplyForm( true );
      })
      .fail( function( xhr ){
        console.log( 'joinConversation', xhr );
        self.abort( params.text.error );
      })
      .always(function() {
        setTimeout(function() {
          self.processingJoinConversation = false;
        }, 500 );
      });
    };
    self.leaveConversation = function() {
      self.stopPolling( self.chatMessagePolling );

      $.ajax({
        data: {
          action: 'chamameLeaveConversation',
          token: params.token
        }
      })
      .done(function( r ){
        console.log( 'leaveConversation', r );

        if ( r.status === 'failure' ) {
          // Ignore
        }
      })
      .fail( function( xhr ){
        console.log( 'leaveConversation', xhr );
      })
      .always(function() {
        self.activeConversationId( 0 );
        self.lastReceivedMessageId = 0;
        self.chatMessages.removeAll();
        self.displayTerminal( false );
        self.displayReplyForm( false );
        self.focusReplyForm( false );
      });
    };
    self.pollConversation = function() {
      if ( self.processingPollConversation ) {
        return;
      }
      self.processingPollConversation = true;

      var doAlert = false;

      $.ajax({
        data: {
          action: 'chamameGetConversations',
          token: params.token
        }
      })
      .done(function( r ){
        console.log( 'pollConversation', r );
        if ( r.status === 'failure' ) {
          self.abort( params.text.error );
          return;
        }

        self.conversations.removeAll();

        if ( r.data.length === 0 ) {
          self.conversationMissingCount += 1;
        } else {
          self.conversationMissingCount = 0;
          $.each( r.data, function( i, conversation ) {
            self.renderConversation( conversation );
            if ( self.conversationAlert && conversation.id > self.lastReceivedConversationId ) {
              doAlert = true;
            }
            self.lastReceivedConversationId = Math.max( self.lastReceivedConversationId, conversation.id );
          });
        }

        if ( doAlert ) {
          self.playConversationAlertSound();
        }

        self.conversationAlert = true;

        var intervalMs = 3000;
        if ( self.conversationMissingCount > 10 ) {
          intervalMs = 6000;
        }
        self.processingPollConversation = false;
        self.conversationPolling = setTimeout( self.pollConversation, intervalMs );
      })
      .fail(function( xhr ){
        console.log( 'pollConversation', xhr );
        self.abort( params.text.error );
        self.processingPollConversation = false;
      })
      .always(function(){});
    };
    self.renderConversation = function( conversation ) {
      var meta = self.conversationsMeta;

      if ( meta[conversation.id] ) {
        if ( meta[conversation.id].read && self.activeConversationId() != conversation.id ) {
          meta[conversation.id].read = conversation.lastMessageId <= meta[conversation.id].lastMessageId;
        }
        meta[conversation.id].lastMessageId = conversation.lastMessageId;
      } else {
        meta[conversation.id] = {
          lastMessageId: conversation.lastMessageId,
          read: false
        }
      }

      self.conversations.push({
        id: conversation.id,
        senderName: conversation.senderName,
        lastTimestamp: conversation.lastMessageTimestamp,
        lastMessage: conversation.lastMessageTruncated,
        lastSenderName: conversation.lastMessageSenderName,
        read: meta[conversation.id].read
      });
    };
    self.pollChatMessage = function() {
      if ( self.processingPollChatMessage ) {
        return;
      }
      self.processingPollChatMessage = true;

      $.ajax({
        data: {
          action: 'chamameGetUnreadMessage',
          token: params.token,
          lastReceivedMessageId: self.lastReceivedMessageId
        }
      })
      .done(function( r ){
        console.log( 'pollChatMessage', r );

        if ( r.status === 'failure' ) {
          if ( self.displayTerminal() ) {
            self.abort( params.text.error );
          } else {
            // Logged out
          }
          self.processingPollChatMessage = false;
          return;
        }

        if ( r.data.length === 0 ) {
          self.chatMessageMissingCount += 1;
        } else {
          self.chatMessageMissingCount = 0;
          $.each( r.data, function( i, message ) {
            var id = parseInt( message.id );
            if ( id !== self.lastReceivedMessageId ) {
              self.renderChatMessage( message );
            }
            if ( id > self.lastReceivedMessageId ) {
              self.lastReceivedMessageId = id;
            }
          });
          self.scrollTerminal();

          if ( self.needMessageAlert ) {
            self.playChatMessageAlertSound();
          }
          self.needMessageAlert = true;
        }

        var intervalMs = 1000;
        var count = self.chatMessageMissingCount;
        if ( count > 2 ) {
          intervalMs = 2000;
        }
        if ( count > 4 ) {
          intervalMs = 3000;
        }
        if ( count > 6 ) {
          intervalMs = 4000;
        }
        if ( count > 10 ) {
          intervalMs = 5000;
        }
        if ( count > 15 ) {
          intervalMs = 13000;
        }
        self.processingPollChatMessage = false;
        self.chatMessagePolling = setTimeout( self.pollChatMessage, intervalMs );
      })
      .fail( function( xhr ){
        console.log( 'pollChatMessage', xhr );
        self.abort( params.text.error );
        self.processingPollChatMessage = false;
      })
      .always( function(){});
    };
    self.renderChatMessage = function( message ) {
      self.chatMessages.push({
        message: message.message,
        senderName: message.senderName,
        timestamp: message.timestamp
      }); 
      self.existsChatMessage( true );
    };
    self.sendChatMessage = function() {
      if ( self.processingSendChatMessage ) {
        return;
      }
      self.processingSendChatMessage = true;

      self.stopPolling( self.conversationPolling );
      self.stopPolling( self.chatMessagePolling );

      var chatMessageToSend = self.chatMessageToSend();
      chatMessageToSend = $.trim( chatMessageToSend );
      if ( chatMessageToSend === '' ) {
        self.chatMessageToSend( '' );
        self.processingSendChatMessage = false;

        self.pollConversation();
        self.pollChatMessage();
        return;
      }

      $.ajax({
        data: {
          action: 'chamameSendMessage',
          token: params.token,
          chatMessage: chatMessageToSend
        }
      })
      .done(function( r ){
        console.log( 'sendChatMessage', r );

        if ( r.status === 'failure' ) {
          self.abort( params.text.error );
          return;
        }

        self.chatMessageToSend( '' );
        self.chatMessageMissingCount = 0;

        self.needMessageAlert = false;

        self.pollConversation();
        self.pollChatMessage();
      })
      .fail( function( xhr ){
        console.log( 'sendChatMessage', xhr );
        self.abort( params.text.error );
      })
      .always(function() {
        self.processingSendChatMessage = false;
      });
    };
    self.stopPolling = function( timer ) {
      clearTimeout( timer );
    };
    self.scrollTerminal = function() {
      var v = self.onRenderChatMessage();
      self.onRenderChatMessage( !v );
    };
    self.abort = function( $message ) {
      self.stopPolling( self.chatMessagePolling );
      self.stopPolling( self.conversationPolling );

      if ( ! self.unload ) {
        self.enableControl( false );
        self.systemMessage( $message );
      }
    };
    self.playChatMessageAlertSound = function() {
      var v = self.doMessageAlert();
      self.doMessageAlert( !v );
    };
    self.playConversationAlertSound = function() {
      var v = self.doConversationAlert();
      self.doConversationAlert( !v );
    }
  }

  $(function() {
    var chatAdmin = new ChatAdmin( chamameParams );
    chatAdmin.init();

    ko.applyBindings( ko.validatedObservable( chatAdmin ) );

  });

})(jQuery);

})( this );
