<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link type="text/css" rel="stylesheet" href="rpg.css">
  <link href="https://fonts.googleapis.com/css?family=Exo+2" rel="stylesheet">
</head>
<body>
<script src="jquery-3.1.1.js"></script>
<?php
include 'rpgchat-lib.php'; 
include 'db_config.php';

// the main page should be a login page (standard PHP)
$PROPER_PASSWORD = "game";
$GM_PASSWORD = "gmInTheHouse";
$GM_MODE_ON = false;
//$USERNAME = $_REQUEST['username'];
$GAME_BLURB = "World of Wonders and Fables - Fantasy RPG"; //"Shrimps make Fools of Dragons";
$GAME_NAME = $_REQUEST['game'];
if($GAME_NAME=="")
  $GAME_NAME = "wowf"; //sfd";

//first section is the login
if(!isset($_REQUEST['username'])||!isset($_REQUEST['password'])) {
  echo "<DIV id=login-box>
    <FORM method=get>
      <DIV id=login-header>Welcome to dStory RPG</DIV>
      <TABLE><TR>
        <TD>Select Game</TD><TD><SELECT class=grey-input name=game>";
  //login screen - show list of games, ask for player name and password
  if(isset($_REQUEST['game']))
    $GAME = $_REQUEST['game'];
  $query = 
"SELECT gameName
FROM i2d.dice_games";
  $result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());
  while($row = mysqli_fetch_object($result)) {
    if($row->gameName==$GAME)
      $s = " selected ";
    else
      $s = "";
    echo "<OPTION class=grey-input value='$row->gameName' $s>$row->gameName</OPTION>";
  }//while
  if(isset($_REQUEST['username']))
    $USERNAME = $_REQUEST['username'];
  else
    $USERNAME = "";
?>
    </SELECT></TD>
    <TR>
      <TD>
      User Name </TD><TD><INPUT class=grey-input type=text name=username value='<?php echo $USERNAME; ?>'></TD></TR>
    <TR><TD>Password</TD><TD><INPUT class=grey-input type=password name=password></TD></TR>
    <TR><TD></TD><TD><INPUT id=join-button type=submit value=Join></TD></TR>
  </TABLE>
  </FORM></DIV> 
  <?php //post later
  die();
}//if

$USERNAME = $_REQUEST['username'];
$PASSWORD = $_REQUEST['password'];
$GAME = $_REQUEST['game'];//'WOWF';  //?hard cocded?
if($USERNAME==$GAME) {
  if($PASSWORD!=$GM_PASSWORD) die("error: you cannot log in as GM without the proper password.");
  $GM_MODE_ON = true;
} else { 
  if($PASSWORD!=$PROPER_PASSWORD) die("wrong password");
}//if

/*if(isset($_REQUEST['scene'])) { //if the player moved to a scene, we need to change the scene
  $SCENE = $_REQUEST['scene'];
  if($SCENE>0) {
    echo ' 
<script language="javascript" type="text/javascript">
  var sceneNumber = '.$SCENE.';
</script> 
    ;
  }//if
}//if */ 

$colours = array('007AFF','FF7000','FF7000','15E25F','CFC700','CFC700','CF1100','CF00BE','F00');
//$user_colour = array_rand($colours);
$user_colour = getUserColor($USERNAME);

function sceneChangeMenu() {
  global $USERNAME;
  global $PROPER_PASSWORD;
  
  echo "<DIV id=scene-menu>";
  $flash = "";
  for($i=0;$i<5;$i++) { //how does the client know how many scenes there are? How is it updateD? how about sceneactivity update
    //when this page is loaded, the player will always be in scene 1, tho it can change
    $j = $i + 1;
    /* if($i==0)
      $flash = "";
    else
      $flash = " scene-$i-flash "; */
    //echo "<A title='switch to scene $j' id='scene$i' class='scene-button scene-all scene-$i $flash' href='index.php?username=$USERNAME&password=$PROPER_PASSWORD&scene=$i'>$j</A>"; //security?
    //echo "<A title='switch to scene $j' id='scene$i' class='scene-button scene-all scene-$i $flash' >$j</A>"; //security?
    echo "<button id='scene$i' class='button scene-button scene-$i'>$j</button>";
  }//for
  echo "</DIV>";
}//F
function getUserColor($s) {
  global $colours;
  
  $total = 0;
  for($i=0;$i<strlen($s) && $i<3;$i++) {
    $c = ord(substr($s,$i,1));
    $total += ord($c);
  }//for

  //echo "DEBUG: color=".$total % count($colours);
  
  return $total % count($colours);
}//F

echo '<script language="javascript" type="text/javascript">';
echo 'window.GameName = "'.$GAME.'";';
echo 'var Arrival_Message = "'.$ARRIVAL_MESSAGE.'";';
echo '</script>';

?>

<!--- it was here --->
<script language="javascript" type="text/javascript">
var sceneNumber = 0;  //by default  

$(document).ready(function(){
	//create a new WebSocket object.
	//var wsUri = "ws://localhost:9000/demo/server.php"; 	
  //var wsUri = "ws://wolfwares.ca:9000/rpgchat/server.php";
  var wsUri = "ws://wolfwares.ca:12345/rpgchat/server.php";
	websocket = new WebSocket(wsUri); 
	
	websocket.onopen = function(ev) { // connection is open 
		$('#message_box').append("<div class=\"system_msg\">Connected!</div>"); //notify user
    //AP let's try to send a communication on message open
    //this one could be just to identify myself and so on
		var myname = $('#name').val(); //get user name
    var mygame = window.GameName;

    var msg = {
		message: Arrival_Message, //"lavirra9",
    game : mygame,
		name: myname, //myname is not defined at this point - why
		color : "FFF"
		};
		//convert and send data to server
		websocket.send(JSON.stringify(msg));
    //alert("sent that first one");
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
    
    $('#message').val(''); //reset text - THIS is where this belongs
	});
	
	//#### Message received from server?
	websocket.onmessage = function(ev) {
		var msg = JSON.parse(ev.data); //PHP sends Json data
		//var type = msg.type; //message type
		//var umsg = msg.message; //message text
		//var uname = msg.name; //user name
		//var ucolor = msg.color; //color

    //deal with the various returns - HERE
		if(msg.type == 'usermsg') {
      if(msg.name != null && msg.message != null)
			//$('#message_box').append("<div><span class=\"user_name\" style=\"color:#"+ucolor+"\">"+uname+" : <span class=\"user_message\">"+umsg+"</span></div>");
        $('#message_box').append("<div><span class=\"user_name\" style=\"color:#"+msg.color+"\">"+msg.name+" <SPAN class=spacer>&nbsp;</SPAN><SPAN class=main-text> "+msg.message+"</span></SPAN></div>");
      else
        console.error("null message"+msg.name);
		}
    else
		if(msg.type == 'system') {
			$('#message_box').append("<div class=\"system_msg\">"+msg.message+"</div>");    
    }
    else
    if(msg.type == "sceneupdate") {
      //the index is in sn
      //want that scene to begin flashing
      var l = document.getElementById("scene"+msg.sn);
      l.style.animation = "pulse"+msg.sn+" 2.5s infinite";
      //alert("did something sn="+sn);
    }
    else
    if(msg.type=="mapdate") {
      var mapInfo = new Object();      
      mapInfo.url = msg.url;
      mapInfo.x = msg.x;
      mapInfo.y = msg.y;
      mapInfo.scale = msg.scale;
      window.map = mapInfo; //will this work?
      //alert("got info");
      var l = document.getElementById("map-button");
      l.style.animation = "pulse0 2.5s infinite";
    } else 
    if(msg.type=="imagemsg") {
      var imgLink = "<A target=_new href=\"" + msg.message + "\" class=image-link-in-chat><IMG class=image-in-chat src=\"" + msg.message + "\" title='click to expand in a new window'></A>";
      //alert( "imgLink=" + imgLink );
      //$('#message_box').append("<div><table class=image-layout-table><TR><TD class='cell-left user_name' style=\"color:#" +msg.color + "\">" + msg.name + "</TD><TD class='cell-right'>" + imgLink + "</TD></TR></TABLE></div>");
      $('#message_box').append("<div><span class=user_name style=\"color:#"+msg.color+"\">"+msg.name+" <SPAN class=spacer>&nbsp;</SPAN> " + imgLink + "</div>");
    } else {
      $('#message_box').append("<div class=\"system_msg\">error: unknown message type - notify your admin</div>");
    }//if
		
		//$('#message').val(''); //reset text - why the hell?
		
		var objDiv = document.getElementById("message_box");
		objDiv.scrollTop = objDiv.scrollHeight;
	};

  //AP attempt to make a click function for scene number
	$('#scene0').click(function(){ //use clicks message send button
    sceneChange(0);
  });
	$('#scene1').click(function(){ //use clicks message send button
    sceneChange(1);
  });
	$('#scene2').click(function(){ //use clicks message send button
    sceneChange(2);
  });
	$('#scene3').click(function(){ //use clicks message send button
    sceneChange(3);
  });
	$('#scene4').click(function(){ //use clicks message send button
    sceneChange(4);
  });
  
  function sceneChange(sn) {
    //alert("scene"+sn+"!");
    var l = document.getElementById("scene"+sceneNumber);
    l.style.opacity = "1.0";
    
    sceneNumber = sn; //that lets this script know we're now in scene 2

    var l = document.getElementById("scene"+sn);
    l.style.opacity = "0.3";

    $('#message_box').html("");//val('');
    $('#message_box').append("scene change "+sn);  //erases the message box
    //next: tell server, and refill from log
    //alert("i got this far");

    var myname = $('#name').val(); //get user name
    var mygame = window.GameName;
        
    var msg = {
      name: myname,
      game: mygame,
  		message: "scenechange",
      param: sn
		};
		//convert and send data to server
		websocket.send(JSON.stringify(msg));
    
    //turn off the scene-notifier, because we're now up to date (theoretically)
    var l = document.getElementById("scene"+sn);
    //l.style.animation = ""; //stops animation now
    l.style.removeProperty('animation');
  }//F
	
	websocket.onerror	= function(ev) {
    if(typeof ev.data == 'undefined'){ //typeof ev.data == 'undefined') {
      $('#message_box').append("<div class=\"system-down\">Thanks for visiting but our <SPAN class='scene-1-flash small-caps'>server is not running</SPAN> at the moment</B><BR>Please click the game info on the right for more help!</div>");
    } else
    $('#message_box').append("<div class=\"system_error\">Error Occurred - "+ev.data+"</div>");
  }; 
	websocket.onclose 	= function(ev){$('#message_box').append("<div class=\"system_msg\">Connection Closed</div>");}; 
});

/* //we need to detect that sceneNumber is not 0, and if so, tell the server
if(sceneNumber>0) {
		var msg = {
		message: 'scene',
    scene: sceneNumber,
    game : mygame,
		name: myname,
		color : '<?php echo $colours[$user_colour]; ?>'
		};
		//convert and send data to server
		websocket.send(JSON.stringify(msg));   
}//if */

function showPicker() {
  showFeature('picker');
}//F
function showNotes() {
  showFeature('notes')
}//F
function showFeature(fn) {
  //alert('map '+window.GameName);
  //open new window for game
  var url = fn+'.php?game='+window.GameName;
  //var bfn = upper(1,fn)+mid(fn);
  window.open(url, window.GameName+' '+fn, "height=600,width=900,top=200,left=200");
}//F
</script>

<TABLE class=t1>
  <TR class=tr>
    <TD id=c-chat>
      <div class=chat_wrapper>
        <div class="message_box" id="message_box"></div>
      </div>
    </TD>
    <TD id=c-menu rowspan=2>
      <DIV id=menu-box>
        <DIV class=menu-title><?php echo $GAME; ?></DIV>
        <DIV class=detail><?php echo $GAME_BLURB; ?></DIV>
        <DIV class=menu-title>Scenes</DIV>
        <?php sceneChangeMenu(); ?>
        <DIV class=menu-title>Commands</DIV>
        <DIV class=menu-button-box>
          <a target=_new href='<?php echo "http://monfur.ca/dstory/sheet.php?name=$USERNAME"; ?>' class='menu-title menu-button'>Sheet</A>
          <a href='http://monfur.ca/dstory/wowf/' target=_new class='menu-title menu-button'>Game Info</A>
          <a href='https://app.roll20.net/campaigns/details/3337537/wowf' target=_new class='menu-title menu-button'>Map</A>
          <a href='https://www.pinterest.ca/loopywolf/world-of-wonders-and-fables-rpg/' target=_new class='menu-title menu-button' title='remember to follow!'>Pinterest</A>
          <a href=help.html target=_new class='menu-title menu-button'>Help</A>
<?php
        echo "<a target=_new class='menu-title menu-button' href='logs.php?game=$GAME'>Logs</A> ";
        echo "<a class='menu-title menu-button' href='index.php?game=$GAME&username=$USERNAME' title='log onto a different character (chr)'>Relog</A> ";
  if($GM_MODE_ON){ 
    echo "<a href='javascript:showPicker()' class='menu-title menu-button'>Picker</A> ";
    echo "<a href='javascript:showNotes()' class='menu-title menu-button'>Notes</A> ";
  }//if
?>
        </DIV>
        <DIV class=menu-title>Players</DIV>
    </DIV>
  </TD></TR>
  <TR>
    <TD id=c-chat-2>
      <div class="panel">
        <input type="text" name="name" id="name" placeholder="Your Name" <?php echo "value='$USERNAME'"; ?> maxlength="15" style="display:none" />
        <input tabindex=1 type="text" class=main-input name="message" id="message" placeholder="Message" maxlength="5000" onkeydown = "if (event.keyCode == 13)document.getElementById('send-btn').click()"  />
        <button id="send-btn" class=button>SEND</button>
      </div>
    </TD>
  </TR>
</TABLE>
</body>
</html>

<!---- history http://stackoverflow.com/questions/25928762/js-text-input-history-on-up-arrow --->

<!--- http://monfur.ca/dstory/sheet.php?name=Loopy --->