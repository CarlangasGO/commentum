{!! HTML::script('/js/stanzaio.bundle.min.js') !!}
<script>

	/**
	* Nicer logging output for chat-related shit
	*/
	var chatLog = function(message)
	{
		console.log('<<CHAT>> ' + message);
	}


	/**
	 * Connect with the XMPP server
	 * using WebSockets.
	 */
	var client;
	var roster;
	var loggedIn = false;

	var connectClient = function()
	{
		client = XMPP.createClient(
		{
		    jid: '{{ Auth::user()->username }}@commentum.io',
		    password: '{{ Auth::user()->xmpp_password }}',
		    transport: 'websocket',
		    wsURL: 'wss://chat.commentum.io:8443/websocket'
		});

		client.on('session:started', function ()
		{
			chatLog('Session started -- getting roster & sending presence...');

		    client.getRoster().then(function(data)
		    {
		    	roster = data.roster;

		    	$('#roster').html("");

		    	$.each(roster.items, function(index, user)
		    	{
		    		$('#roster').append('<li><span class="indicator"><i class="ion-record"></i></span> ' + user.jid.local + '</li>');
		    	});

		    	chatLog('Roster retrieved...')
		    });

		    client.sendPresence();
		    $('#userStatusIndicator').removeClass('error').addClass('online');

		    loggedIn = true;
		    chatLog('Initial presence sent... logged in!');
		});

		client.on('chat', function (msg)
		{
			chatLog('Received chat message: ' + msg);

			$('#chatMessages').append('<li>' + msg.body + '</li>');
			var cmb = $("#chatMessagesWindow");
			cmb.animate({ scrollTop: cmb.prop("scrollHeight") - cmb.height() }, 1);
		});

		client.on('message:sent', function (msg)
		{
			$('#chatMessages').append('<li class="green">' + msg.body + '</li>');
			var cmb = $("#chatMessagesWindow");
			cmb.animate({ scrollTop: cmb.prop("scrollHeight") - cmb.height() }, 1);

			chatLog('Sent chat message: ' + msg);
		});

		client.on('auth:failed', function()
		{
			chatLog('Authentcation failed!')
			$('#roster').html("<li class='error-li'>Couldn't connect to the chat server.<br><br><a class='btn success medium' id='connectToChat'>Try again</a></li>");
		});

		client.on('subscribed', function(data)
		{
			chatLog("Received 'subscribe' event: " + data);
		});

		client.on('roster:update', function(data)
		{
			chatLog("Received 'roster:update' event: " + data);
		});

		client.connect();
	}

	/**
	 * Disable new line insert
	 * on enter in the chat input
	 * area.
	 */
	$('#chatInput').keydown(function(e)
	{
		if (e.keyCode === 13)
			return false;
	});

	/**
	 * Send message on enter,
	 * if the chat input is not
	 * empty.
	 */
	$('#chatInput').keyup(function(e)
	{
		var keyCode = e.keyCode;

		if (keyCode === 13)
		{
			var input = $(this).val();

			if (input != "")
			{
				client.sendMessage(
				{
					to: currentUser + "@commentum.io",
					from: "{{ Auth::user()->username }}@commentum.io",
					body: input
				});

				$(this).val("");
			}
		}
	});

	if ($.cookie('chatPadding') != undefined)
	{
		$('.chat-bar').toggleClass('open');
		$('#chatChevron').toggleClass('ion-chevron-up ion-chevron-down');
		$('body').addClass('chat-fixed');
	}

	/**
	 * Show/hide the chat list
	 * sidebar.
	 */
	$('#chatToggler').click(function()
	{
		if ($('#chatbox').hasClass('open'))
		{
			$('#chatbox').removeClass('open');

			setTimeout(function()
			{
				$('.chat-bar').toggleClass('open');
				$('#chatChevron').toggleClass('ion-chevron-up ion-chevron-down');
				$('body').removeClass('chat-fixed');
				$.removeCookie('chatPadding');
			}, 200);
		}
		else
		{
			if ($.cookie('chatPadding') != undefined)
				$.removeCookie('chatPadding');
			else
				$.cookie('chatPadding', true);

			$('.chat-bar').toggleClass('open');
			$('#chatChevron').toggleClass('ion-chevron-up ion-chevron-down');
			$('body').toggleClass('chat-fixed');
		}
	});

	/**
	 * Show/hide the chatbox and
	 * give the user feedback of
	 * with whom he/she is chatting.
	 */
	var currentUser = null;

	$('.chat-list').on('click', 'li', function()
	{
		var item = $(this),
			user = item.text(),
			chbx = $('#chatbox');

		if (item.hasClass('error-li'))
		{
			return false;
		}

		if (user == currentUser)
		{
			currentUser = null;
			chbx.removeClass('open');
		}
		else
		{
			currentUser = user;

			$('#chatHeader').text('Chatting with /u/' + currentUser);

			if (chbx.hasClass('open'))
			{
				chbx.removeClass('open');
				setTimeout(function()
				{
					chbx.addClass('open');
				}, 200);
			}
			else
			{
				chbx.addClass('open');
			}
		}
	});

	/**
	 * Try to connect to the server
	 * if it failed.
	 */
	$('.chat-list').on('click', '.error-li', function()
	{
		$('#roster').html('<br><center><img src="{{ url('/img/loader.svg') }}" width="30px"></center>');
		connectClient();
	});

	/**
	 * Handle click on #addUserBtn
	 */
	$('#addUserBtn').click(function()
	{
		var userInput = $('#addUserInput').val();

		if (userInput == "")
		{
			$('#addUserInput').focus();
			return false;
		}

		if (loggedIn == false)
		{
			alert("Can't connect to the chat server.");
			return false;
		}

		client.subscribe(userInput + '@commentum.io');
		$('#addUserInput').val("");
	});

	/**
	 * Connect the fucking client.
	 */
	connectClient();
</script>