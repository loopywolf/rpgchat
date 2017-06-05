<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link type="text/css" rel="stylesheet" href="rpg.css">
  <link href="https://fonts.googleapis.com/css?family=Exo+2" rel="stylesheet">
</head>
<body>	

<?php 
// the main page should be a login page (standard PHP)

$colours = array('007AFF','FF7000','FF7000','15E25F','CFC700','CFC700','CF1100','CF00BE','F00');
$user_colour = array_rand($colours);
$GAME = 'WOWF';
$GAME_BLURB = "World of Wonders and Fables - Fantasy RPG";

function sceneChangeMenu() {
  echo "<DIV id=scene-menu>";
  $flash = "";
  for($i=0;$i<5;$i++) { //how does the client know how many scenes there are? How is it updateD? how about sceneactivity update
    $j = $i + 1;
    echo "<A title='switch to scene $j' class='scene-button scene-all scene-$i $flash' href='index.php?scene=$i'>$j</A>";
  }//for
  echo "</DIV>";
}//F

echo '<script language="javascript" type="text/javascript">window.GameName = "'.$GAME.'";</script>';

?>

<script src="jquery-3.1.1.js"></script>
<script language="javascript" type="text/javascript">  
$(document).ready(function(){
	//create a new WebSocket object.
	//var wsUri = "ws://localhost:9000/demo/server.php"; 	
  //var wsUri = "ws://wolfwares.ca:9000/rpgchat/server.php";
  var wsUri = "ws://wolfwares.ca:12345/rpgchat/server.php";
	websocket = new WebSocket(wsUri); 
	
	websocket.onopen = function(ev) { // connection is open 
		$('#message_box').append("<div class=\"system_msg\">Connected!</div>"); //notify user
	}

	$('#send-btn').click(function(){ //use clicks message send button	
		var mymessage = $('#message').val(); //get message text
		var myname = $('#name').val(); //get user name
    var mygame = window.GameName;
    //alert(mygame); alert(window.GameName);
		
		if(myname == ""){ //empty name?
			alert("Enter your Name please!");
			return;
		}
		if(mymessage == ""){ //emtpy message?
			alert("Enter Some message Please!");
			return;
		}
		document.getElementById("name").style.visibility = "hidden";
		
		var objDiv = document.getElementById("message_box");
		objDiv.scrollTop = objDiv.scrollHeight;
		//prepare json data
		var msg = {
		message: mymessage,
    game : mygame,
		name: myname,
		color : '<?php echo $colours[$user_colour]; ?>'
		};
		//convert and send data to server
		websocket.send(JSON.stringify(msg));
	});
	
	//#### Message received from server?
	websocket.onmessage = function(ev) {
		var msg = JSON.parse(ev.data); //PHP sends Json data
		var type = msg.type; //message type
		var umsg = msg.message; //message text
		var uname = msg.name; //user name
		var ucolor = msg.color; //color

		if(type == 'usermsg') 
		{
			//$('#message_box').append("<div><span class=\"user_name\" style=\"color:#"+ucolor+"\">"+uname+" : <span class=\"user_message\">"+umsg+"</span></div>");
      $('#message_box').append("<div><span class=\"user_name\" style=\"color:#"+ucolor+"\">"+uname+" <SPAN class=spacer>&nbsp;</SPAN><SPAN class=main-text> "+umsg+"</span></SPAN></div>");
		}
		if(type == 'system')
		{
			$('#message_box').append("<div class=\"system_msg\">"+umsg+"</div>");
		}
		
		$('#message').val(''); //reset text
		
		var objDiv = document.getElementById("message_box");
		objDiv.scrollTop = objDiv.scrollHeight;
	};
	
	websocket.onerror	= function(ev){$('#message_box').append("<div class=\"system_error\">Error Occurred - "+ev.data+"</div>");}; 
	websocket.onclose 	= function(ev){$('#message_box').append("<div class=\"system_msg\">Connection Closed</div>");}; 
});




</script>
<TABLE class=t1>
  <TR><TD id=c-chat>
    <div class=chat_wrapper>
      <div class="message_box" id="message_box"></div>
      <div class="panel">
        <input type="text" name="name" id="name" placeholder="Your Name" maxlength="15" />
        <input type="text" name="message" id="message" placeholder="Message" maxlength="80" onkeydown = "if (event.keyCode == 13)document.getElementById('send-btn').click()"  />
        <button id="send-btn" class=button>SEND</button>
      </div>
    </div>
  </TD>
  <TD id=c-menu>
    <DIV id=menu-box>
      <DIV class=menu-title><?php echo $GAME; ?></DIV>
      <DIV class=detail><?php echo $GAME_BLURB; ?></DIV>
      <DIV class=menu-title>Scenes</DIV>
      <?php sceneChangeMenu(); ?>
      <DIV class=menu-title>Commands</DIV>
      <DIV class=menu-button-box>
        <a href=sheet.php class='menu-title menu-button'>Sheet</A>
        <a href=game.php class='menu-title menu-button'>Game Info</A>
        <a href=help.php class='menu-title menu-button'>Help</A>
      </DIV>
      <DIV class=menu-title>Players</DIV>
    </DIV>
  </TD></TR>
</TABLE>

</body>
</html>