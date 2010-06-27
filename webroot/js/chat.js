$(function() {
	haveNewSession();
});

function bindLinks(sid)
{
	$("#chat_form_" + sid + " > a.sendMessage").click(
		function(event) {
			event.preventDefault();
			
			sendMessage($(this).attr('alt'));
			
			return false;
		}
	);
	
	$("#tabs").append('<li><a href="#chat_'+ sid +'">Sessão '+ sid +'</a></li>');
	
	$(".jbutton").button();
	
	$(".jclose").button({text:false, icons :{primary: 'ui-icon-closethick'}});
	
	$('#chats').tabs();
}

// verifica se há nova sessão aberta
function haveNewSession()
{	
	$.PeriodicalUpdater('/chat_sessions/haveNew', {
		method: 'post',				// method; get or post
		minTimeout: 1000,			// starting value for the timeout in milliseconds
		maxTimeout: 2000,			// maximum length of time between requests
		multiplier: 2,				// if set to 2, timerInterval will double each time the response hasn't changed (up to maxTimeout)
		maxCalls: 0,				// maximum number of calls. 0 = no limit.
		autoStop: 0					// automatically stop requests after this many returns of the same data. 0 = disabled.
	},
	function(data) {				// Handle the new data (only called when there was a change)
		if(data.status == true)
		{
			$.each(data.session_ids, openChatWindow);
		}
	});
}

// lê novas mensagens de uma sessão
function readMessages(sid)
{
	$.PeriodicalUpdater('/messages/read', {
		method: 'post',								// method; get or post
		data: mountReadStatments,					// array of values to be passed to the page - e.g. {name: "John", greeting: "hello"}
		dataArgument: sid,
		minTimeout: 1000,							// starting value for the timeout in milliseconds
		maxTimeout: 2000,							// maximum length of time between requests
		multiplier: 2,								// if set to 2, timerInterval will double each time the response hasn't changed (up to maxTimeout)
		maxCalls: 0,								// maximum number of calls. 0 = no limit.
		autoStop: 0									// automatically stop requests after this many returns of the same data. 0 = disabled.
	},
	function(data) {								// Handle the new data (only called when there was a change)
		if(data.status == true)
		{
			$.each(data.messages, displayMessage);
		}
	});
}

function mountReadStatments (sid)
{
	return {session_id: sid, last_message_id: $("#Message" + sid + "LastMessageId").val()};
}

function displayMessage(id, message)
{
	msg = "<p>" + message.author + ": " + message.message + "</p>";
	
	$("#chat_" + message.session + " > div.history").append(msg);
	
	if(id > 0)
	{
		$("#Message" + message.session + "LastMessageId").val(id);
	}
}

function openChatWindow(k, sid)
{
	$.ajax({
		url : '/chat_sessions/chat/' + sid,
		type : 'get',
		data : {type : 'html'},
		success : function(data) {
			$("#chats").append(data);
			bindLinks(sid);
			confirmOpened(sid);
		},
		error : messageError
	});
}

function confirmOpened(sid)
{
	$.ajax({
		url : '/chat_sessions/openedConfirm/' + sid,
		type : 'get',
		success : function(data) {
			if(data.status == true)
			{
				readMessages(sid);
			}
		},
		error : messageError
	});
}

// envia uma mensagem
function sendMessage(sid)
{
	container = $('#chat_form_' + sid);

	$.ajax({
		url : '/messages/send',
		type : 'post',
		data : container.serialize(),
		success : messageSended,
		error : messageError
	});
	
	message = {session: sid, message: $("#chat_form_" + sid + " .msg").val(), author: "eu"};
	
	displayMessage(0, message);
}

function messageSended(data)
{
	if(data.status != true)
	{
		msg = '<p class="error">Não foi possível entregar sua mensagem</p>';
		
		$("#chat_" + data.session + " > div.history").append(msg);
	}
}

function messageError(xhr, status, exception)
{
	alert('Não foi possível comunicar-se com servidor');
}