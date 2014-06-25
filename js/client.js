;(function( window, undefined ) {

(function( $ ) {

  if ( ! window.console ) {
    window.console = { log: function(){} };
  }

  // Custom bindings
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
    update: function( element, valueAccessor ) {
      var v = valueAccessor();
      var e = element;
      ko.utils.unwrapObservable( v ) ? $( e ).fadeIn() : $( e ).fadeOut();
    }
  };

  ko.bindingHandlers.slideVisible = {
    init: function( element, valueAccessor ) {
      $( element ).toggle( ko.utils.unwrapObservable( valueAccessor() ) );
    },
    update: function( element, valueAccessor ) {
      var v = valueAccessor();
      var e = element;
      ko.utils.unwrapObservable( v ) ? $( e ).slideDown() : $( e ).slideUp();
    }
  };

  ko.validation.init({
    insertMessages: false,
    decorateInputElement: true
  });

  ko.validation.rules['looseEmail'] = {
    validator: function( value, args ) {
      var pattern = new RegExp( "^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$" );
      return pattern.test( value );
    }
  };
  ko.validation.registerExtenders();

  var ChatClient = function( params ) {
    var self = this;

    self.userName = ko.observable( '' )
      .extend({
        throttle: 300
      })
      .extend({
        required: { message: params.text.nameRequired }
      });

    self.userEmail = ko.observable( '' )
      .extend({
        throttle: 300
      })
      .extend({
        required: { message: params.text.emailRequired }
      })
      .extend({
        looseEmail: { message: params.text.emailFormat }
      });

    self.chatMessageToSend = ko.observable();
    self.chatMessages = ko.observableArray();

    self.display = ko.observable( false );
    self.displayContainer = ko.observable( false );
    self.displayLoginForm = ko.observable( true );
    self.displayTerminal = ko.observable( false );
    self.systemMessage = ko.observable( '' );
    self.existsChatMessage = ko.observable( false );
    self.scroll = ko.observable( false );
    self.focusLoginForm = ko.observable( false );
    self.focusReplyForm = ko.observable( false );
    self.enableControl = ko.observable( true );

    self.polling;
    self.lastReceivedMessageId = 0;
    self.missingCount = 0;
    self.processingSendChatMessage = false;
    self.processingPollChatMessage = false;
    self.processingLoginOrLogout = false;

    self.init = function() {
      $.ajaxSetup({
        cache : false,
        type: 'post',
        dataType: 'json',
        url: chamameParams.ajaxUrl
      });

      self.display( true );

      if ( params.loggedIn ) {
        self.restore();
      }
    };
    self.toggleContainer = function() {
      var current = self.displayContainer();
      self.displayContainer( ! current );
      self.focusLoginForm( ! current );
    };
    self.login = function() {
      if ( self.processingLoginOrLogout ) {
        return;
      }
      self.processingLoginOrLogout = true;

      if ( !self.isValid() ) {
        self.errors.showAllMessages();
        self.processingLoginOrLogout = false;
        return;
      }

      var userName = self.userName();
      var userEmail = self.userEmail();

      // Login
      $.ajax({
        data: {
          action: 'chamameLoginVisitor',
          token: params.token,
          userName: userName,
          userEmail: userEmail
        }
      })
      .done(function( r ){
        console.log( 'login', r );

        if ( r.status === 'failure' ) {
          self.abort( params.text.error );
          self.processingLoginOrLogout = false;
          return;
        }

        self.displayLoginForm( false );
        self.displayTerminal( true );

        self.enableControl( true );
        self.focusReplyForm( true );
        self.pollChatMessage();
        self.processingLoginOrLogout = false;
      })
      .fail(function( xhr ){
        console.log( 'login', xhr );
        self.abort( params.text.error );
      })
      .always();
    };
    self.logout = function( data, event ) {
      if ( self.processingLoginOrLogout ) {
        return;
      }
      self.processingLoginOrLogout = true;

      self.stopPolling();

      $.ajax({
        data: {
          action: 'chamameLogout',
          token: params.token
        }
      })
      .done(function( r ){
        console.log( 'logout', r );
        if ( r.status === 'failure' ) {
          // Ignore
        }

        self.focusReplyForm( false );
        self.displayTerminal( false );
        self.displayLoginForm( true );
        self.enableControl( true );
        self.toggleContainer();
        self.chatMessageToSend( '' );
        self.chatMessages.removeAll();
        self.missingCount = 0;
        self.lastReceivedMessageId = 0;
        self.processingSendChatMessage = false;
        self.processingPollChatMessage = false;
        self.systemMessage( '' );
      })
      .fail(function( xhr ){
        console.log( 'logout', xhr );
      })
      .always(function(){
        setTimeout(function() {
          self.processingLoginOrLogout = false;
        }, 1000 );
      });
    };
    self.restore = function() {

      self.displayContainer = ko.observable( true );
      self.displayLoginForm = ko.observable( false );
      self.displayTerminal = ko.observable( true );

      $.ajax({
        data: {
          action: 'chamameGetUnreadMessage',
          token: params.token,
          lastReceivedMessageId: self.lastReceivedMessageId
        }
      })
      .done(function( r ){
        console.log( 'restore', r );

        if ( r.status === 'failure' ) {
          self.abort( params.text.error );
          return;
        }

        if ( r.data.length > 0 ) {
          $.each( r.data, function( i, message ) {
            if ( message.id !== self.lastReceivedMessageId ) {
              self.renderChatMessage( message );
            }
            if ( message.id > self.lastReceivedMessageId ) {
              self.lastReceivedMessageId = message.id;
            }
          });
        }
        self.scrollTerminal();
        self.pollChatMessage();
      })
      .fail(function( xhr ){
        console.log( 'restore', xhr );
        self.abort( params.text.error );
      })
      .always();
    },
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
            // logged out
          }
          self.processingPollChatMessage = false;
          return;
        }

        if ( r.data.length === 0 ) {
          self.missingCount += 1;
        } else {
          self.missingCount = 0;
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
        }

        var intervalMs = 1000;
        if ( self.missingCount > 2 ) {
          intervalMs = 2000;
        }
        if ( self.missingCount > 4 ) {
          intervalMs = 3000;
        }
        if ( self.missingCount > 6 ) {
          intervalMs = 4000;
        }
        if ( self.missingCount > 10 ) {
          intervalMs = 5000;
        }
        if ( self.missingCount > 15 ) {
          intervalMs = 13000;
        }

        self.polling = setTimeout( self.pollChatMessage, intervalMs );
        self.processingPollChatMessage = false;
      })
      .fail(function( xhr ){
        console.log( 'pollChatMessage', xhr );
        self.abort( params.text.error );
      })
      .always();
      
    };
    self.stopPolling = function() {
      clearTimeout( self.polling );
    };
    self.sendChatMessage = function() {
      if ( self.processingSendChatMessage ) {
        return;
      }
      self.processingSendChatMessage = true;
      self.stopPolling();

      var chatMessageToSend = self.chatMessageToSend();
      chatMessageToSend = $.trim( chatMessageToSend );
      if ( chatMessageToSend === '' ) {
        self.chatMessageToSend( '' );
        self.pollChatMessage();
        self.processingSendChatMessage = false;
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
        self.missingCount = 0;
        self.scrollTerminal();
      })
      .fail(function( xhr ){
        console.log( xhr );
        self.abort( params.text.error );
      })
      .always(function() {
        self.processingSendChatMessage = false;
        self.pollChatMessage();
      });
    };
    self.renderChatMessage = function( message ) {
      self.chatMessages.push({
        message: message.message,
        senderName: message.senderName,
        timestamp: message.timestamp
      }); 
      self.existsChatMessage( true );
    };
    self.scrollTerminal = function() {
      v = self.scroll();
      self.scroll( !v );
    };
    self.abort = function( message ) {
      self.stopPolling();
      self.enableControl( false );
      self.systemMessage( message );
    }
  };




  $(function() {


    var chatClient = new ChatClient( chamameParams );
    chatClient.init();

    ko.applyBindings( ko.validatedObservable( chatClient ) );
  });

})(jQuery);

})( this );
