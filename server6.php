<?php
include "rpgparser.php";
include "rpgchat-lib.php";

$host = 'localhost'; //host
$port = '12345'; //port
$null = NULL; //null var

$SCENE_SIZE = 20;

initGames();

//Create TCP/IP sream socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//reuseable port
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
//bind socket to specified host
socket_bind($socket, 0, $port);
print_r($socket);                                                
//listen to port
socket_listen($socket);

//create & add listning socket to the list
//AP clients is a list of all people connected to the server
$Clients = array($socket);
$PlayerSockets = array($socket);

//start endless loop, so that our script doesn't stop
while (true) {
	//manage multipal connections
	$changed = $Clients;
	//returns the socket resources in $changed array
	socket_select($changed, $null, $null, 0, 10);
	
	//check for new socket
	if (in_array($socket, $changed)) {
		$socket_new = socket_accept($socket); //accpet new socket
    echo "accept new socket\n";
		$Clients[] = $socket_new; //add socket to client array
    
    $PlayerSockets[] = $socket_new; //keep copy in players list, so we can have persistent index
		
		$header = socket_read($socket_new, 1024); //read data sent by the socket
		perform_handshaking($header, $socket_new, $host, $port); //perform websocket handshake
		
		socket_getpeername($socket_new, $ip); //get ip address of connected socket
		$response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' connected'))); //prepare json data
		send_all_message($response); //notify all users about new connection
    //HERE is where I could send the initial log throw-back
    
		//make room for new socket
		$found_socket = array_search($socket, $changed);
		unset($changed[$found_socket]);
	}
	
	//loop through all connected sockets
	foreach ($changed as $changed_socket) {	

    echo "changed=".intval($changed); print_r($changed);
		
		//check for any incomming data
		while(socket_recv($changed_socket, $buf, 1024, 0) >= 1)
		{
			$received_text = unmask($buf); //unmask data
			$tst_msg = json_decode($received_text); //json decode 
			/* $user_name = $tst_msg->name; //sender name
			$user_message = $tst_msg->message; //message text
      $user_game = $tst_msg->game;  //game name
			$user_color = $tst_msg->color; //color 
      
      /* echo "<PRE>tst_msg ";
      print_r($tst_msg);
      echo "</PRE>"; */
      //special processing for initial message

      $playerId = getPlayer( $changed_socket,$tst_msg->name ); //gives (or sets up) the player object, including the socket if needed
      if($tst_msg->message==$ARRIVAL_MESSAGE) continue; //we don't want to display this
      rpgParse($playerId,$tst_msg); //if we should need it
      //it might send messages - this last call
			
			//prepare data to be sent to client
			//$response_text = mask(json_encode(array('type'=>'usermsg', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color)));
      
			//send_all_message($response_text); //send data
			//$response_text = mask(json_encode(array('type'=>'usermsg', 'name'=>$user_name, 'message'=>"22".$user_message, 'color'=>$user_color)));
			//send_message($response_text);
			break 2; //exist this loop
		}
		
		$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
		if ($buf === false) { // check disconnected client
			// remove client for $clients array
			$found_socket = array_search($changed_socket, $Clients);
			socket_getpeername($changed_socket, $ip);
			unset($Clients[$found_socket]);
			
			//notify all users about disconnected connection
			$response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' disconnected')));
			send_all_message($response);
		}
	}
}//while true
// close the listening socket
socket_close($socket);

function send_all_message($msg)
{
	global $Clients;
  global $PlayerSockets;
  
  echo "DEBUG send_all msg "; print_r($msg); echo "\n";
  
	foreach($Clients as $changed_socket)
	{
    $index = array_search($changed_socket,$PlayerSockets);
    echo "index=$index\n"; //i don't care
		@socket_write($changed_socket,$msg,strlen($msg));
	}
	return true;
}//F

//Unmask incoming framed message
function unmask($text) {
	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}
	elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}
	else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}//F

//Encode message for transfer to client.
function mask($text)
{
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);
	
	if($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);
	elseif($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);
	return $header.$text;
}//F

//handshake new client.
function perform_handshaking($receved_header,$client_conn, $host, $port)
{
	$headers = array();
	$lines = preg_split("/\r\n/", $receved_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
		{
			$headers[$matches[1]] = $matches[2];
		}
	}

	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	//hand shaking header
	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
	"Upgrade: websocket\r\n" .
	"Connection: Upgrade\r\n" .
	"WebSocket-Origin: $host\r\n" .
	"WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
	"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
  
  //I'm going to take a chance on this one 
  
	socket_write($client_conn,$upgrade,strlen($upgrade));
}//F

//$playerId = getPlayer( $changed_socket ); //gives (or sets up) the player object, including the socket if needed
function getPlayer($socket,$user) {
  global $PlayerSockets;
  global $Players;
  global $GAMES;
  
  echo "getPlayer name=$user\n";
  
  $index = array_search($socket,$PlayerSockets);
  echo " socket_i=$index\n";
  
  if( isset($Players[$index]) ) 
    return $index;
  
  echo "adding new player name=$user\n";
  
  //$new_player_index = count($Players)-1;
  //otherwise, we need to create this new player object
  $new_player = new StdClass;
  $new_player->socket = $socket;
  $new_player->user = $user;
  $new_player->sceneId = 0; //default scene for this game
  $new_player->id = $index; 

  //send welcome message to player
  //1. will need to get the character info
  $chr = getCharacter($user);
  echo "chr=$user "; print_r($chr);
  $game = $GAMES[ $chr->gameId ];
  echo "gameId=$chr->gameId "; print_r($game);
  $welcome = "Welcome to $game->name<BR>$game->announce";
  //DEBUG special reminder feature for client development
  $game->announce .= "<BR>NEXT STEP: You are sending player positions to server.. need to update the database then re-send back to client";
  $msg = mask(json_encode(array('type'=>'usermsg', 'name'=>$user, 'message'=>$welcome, 'color'=>'#FFFFFF')));
  echo "DEBUG send welcome "; print_r($msg); echo "\n";
  echo "DEBUG socket="; print_r($socket); echo "\n";
  @socket_write($socket,$msg,strlen($msg));
  
  $new_player->gameId = $chr->gameId; //why not
  $new_player->game = $GAMES[ $chr->gameId ];  //why not give direct link?
    
  readBackSceneToPlayer(0,$new_player);
  
  $Players[$index] = $new_player;
 
  echo "Players "; print_r($Players);
  echo "player=$user stored at $index\n";
  
  //you should also send map update
  sendMapUpdateToPlayer($game,$socket);
  //this sends a map update to this player only

  //this is a convenient place to update the players (next) //TODO
  $msg = getPlayerList($game);
  echo "DEBUG players list"; print_r($msg); echo "\n";
  @socket_write($socket, $msg, strlen($msg));
  
  return $index;    
}//F

//NOTE: this is called when the player first logs on, to get a list of the current players and their locations on map
//it can be sent again, when a player's location is updated
function getPlayerList($game) {
  global $DBL;

  //get the list of players
  $query = 
"SELECT dc.name,dc.map_x,dc.map_y
FROM i2d.dice_characters dc
LEFT JOIN i2d.dice_games dg ON (dc.game_id = dg.id)
LEFT JOIN i2d.dice_stats ds ON (ds.char_id = dc.id)
WHERE dg.gameName = '$game->name'
and ds.statName = 'XP'";

  echo "DEBUG: playersList query=$query\n";

  $result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());

  while($row = mysqli_fetch_object($result)){
  	if($row->map_x==0 && $row->map_y==0) { } // do nothing
  	else {
  		//$row->name = strtoupper($row->name);
    	$player[] = "$row->name|$row->map_x|$row->map_y";
  	}//else
  }//while

  $result = implode(",",$player);
  //transmit it to the game client
  //need to send it back in digestible format

  $msg = mask(json_encode(array('type'=>'playerlist', 'list'=>$result)));

  return $msg;
}//F

?>