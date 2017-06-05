var sceneNumber = 0;  //by default  
var PLAYERS_ON_MAP = [];

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

    //alert("msg.type="+msg.type);

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
      //alert("got map info");
      var l = document.getElementById("map-button");
      l.style.animation = "pulse0 2.5s infinite";
    } else
    if(msg.type=="playerlist") {
      var list = msg.list;  //the info came in as a single line of text and needs parsed
      var lines = list.split(","); //first, the lines
      var select = document.getElementById("player-tags");

      select.options.length = 0;
      var option = document.createElement('option');
      option.text = option.value = "none";
      select.add(option, 0);

      PLAYERS_ON_MAP.length = 0;  //to clear the array;
      for(i=0;i<lines.length;i++) {
        var data = lines[i].split("|");
        var newPlayer = new Object();
        newPlayer.name = data[0];
        newPlayer.mapX = data[1];
        newPlayer.mapY = data[2];
        PLAYERS_ON_MAP.push( newPlayer );
        //need to add it the global array with this info
        //alert("added player "+i)
        var option = document.createElement('option');
        option.text = option.value = newPlayer.name;
        select.add(option, 0);
      }//for
      //and that is all
      //we need to fill in that select box at the same time      
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
	
	websocket.onerror	= function(ev){$('#message_box').append("<div class=\"system_error\">Error Occurred - "+ev.data+"</div>");}; 
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

function showMap() {
  //showFeature('map');
  //now it's going to work via javascript
  var x = document.getElementById("main-map");
  x.style.visibility = 'visible';
  //now we need to set content
  //var y = document.getElementById("map-image");
  //y.src = window.map.url;
  //$("#map-image").attr('background-image', window.map.url);
  var y = document.getElementById("map-image");
  y.style.backgroundImage = "url('" + window.map.url + "')";
  var size = y.clientWidth * window.map.scale;
  y.style.backgroundPosition = window.map.x+"px "+window.map.y+"px";
  y.style.backgroundSize = size+"px";    
  //y.style.width = window.map.scale;
  //y.style.height = window.map.scale;

  showPlayersOnMap();
}//F
function hideMap() {
  var x = document.getElementById("main-map");
  x.style.visibility = 'hidden';
}
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
function gmMapPlus() {
	gmMap('+');
}//F
function gmMapMinus() {
	gmMap('-');
}//F
function gmMapLeft() {
	gmMap('L');
}//F
function gmMapRight() {
	gmMap('R');
}//F
function gmMapUp() {
	gmMap('U');
}//F
function gmMapDown() {
	gmMap('D');
}//F
function gmMap(c) {
  var MOVE_SIZE = 50;
  var SCALE_SIZE = 1.2;
  //var SCALE_SHRINK = 1 / SCALE_SIZE;

  //var size = y.clientWidth * window.map.scale;
  //y.style.backgroundPosition = window.map.x+"px "+window.map.y+"px";
  
  switch(c) {
    case 'L' : window.map.x = window.map.x - MOVE_SIZE; break;
    case 'R' : window.map.x = window.map.x + MOVE_SIZE; break;
    case 'U' : window.map.y = window.map.y - MOVE_SIZE; break;
    case 'D' : window.map.y = window.map.y + MOVE_SIZE; break;
    case '+' : window.map.scale = window.map.scale * SCALE_SIZE; break;
    case '-' : window.map.scale = window.map.scale * (1 / SCALE_SIZE); break;
  }//switch
  showMap();
}//F
function gmMapSave() {
  var mygame = window.GameName;
  var myname = $('#name').val(); //get user name

	var msg = {
		message: 'gm-change-map',
    game : mygame,
		name: myname,
		mapx : window.map.x,
    mapy : window.map.y,
    scale : window.map.scale
	};
	//convert and send data to server
	websocket.send(JSON.stringify(msg));
  //now we want to close this window
  hideMap();
}//F
function showPlayersOnMap() {
  //using the info in PLAYERS_ON_MAP we need to position the player tags
  //note: and make them visible, if applicable
  for(i=0;i<10;i++) {
    var p = document.getElementById("player"+i);
    if(i<PLAYERS_ON_MAP.length) {
      p.style.left = (PLAYERS_ON_MAP[i].mapX * window.map.scale) +"px";
      p.style.top = (PLAYERS_ON_MAP[i].mapY * window.map.scale) +"px";
      p.innerHTML = PLAYERS_ON_MAP[i].name;
    } else
      p.style.visibility = 'hidden';
    //alert("xx"+i);
  }//for
  //all those we do not set, must be hidden
}//F

$(function() {
    $("#map-image").click(function(e) {

      var offset = $(this).offset();
      var relativeX = (e.pageX - offset.left);
      var relativeY = (e.pageY - offset.top);

      alert("X: " + relativeX + "  Y: " + relativeY);

      var e = document.getElementById("player-tags");
      var p = e.options[e.selectedIndex].value;
      
      //so we need to give the new user player position as x - mapx
      var playerX = relativeX - window.map.x;
      var playerY = relativeY - window.map.y;

      var mygame = window.GameName;
      var myname = $('#name').val(); //get user name

      var msg = {
        message: 'player-pos',
        player: p,
        game : mygame,
        name: myname,
        new_x : playerX,
        new_y : playerY
      };
      //convert and send data to server
      websocket.send(JSON.stringify(msg));
      //now we want to close this window
      hideMap();   
    });
});
