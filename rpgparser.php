<?php
$Players = array();
$GAMES = array();
$LOG_FILES = array();
$LOG_COUNTER = 0;
$LOG_LIMIT = 10;
include "db_config.php";
//$DBL = mysqli_connect("alix", "i2d", "Dei5iapu", "i2d") or die("Could not connect for rpgchat");
//echo "DBL="; print_r($DBL);

function rpgParse($playerId,$jsonMsg) {
  global $Players;
  
  echo "rpg parse: playerId=$playerId\n";  
  
	/* $tst_msg = json_decode($received_text); //json decode 
	$user_name = trim($tst_msg->name); //sender name
	$user_message = trim($tst_msg->message); //message text
  $user_game = trim($tst_msg->game);  //game name
	$user_color = trim($tst_msg->color); //color */
  
  echo "DEBUG: rpgParse jsonMsg ";
  print_r($jsonMsg);

  //we need to broadcast this message to everybody in your same scene
  $sceneId = $Players[ $playerId ]->sceneId;
  echo "DEBUG: sceneId=$sceneId\n";    //DEBUG  
  $gameId = $Players[ $playerId ]->gameId;

  echo "parse:\n";

  //MAIN CONTROLS  
  //$response_text = mask(json_encode(array('type'=>'usermsg', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color)));
//  if($jsonMsg->message=="lavirra9") {
    //arrival message, don't even print this
//  } else
  if($jsonMsg->message=='player-pos') {
    updatePlayerPos($jsonMsg);
  } else
  //can I put the gm map change here?
  if($jsonMsg->message=="gm-change-map") {
    //message attempting to change map
    echo "Attempting to change map\n";
    if($jsonMsg->name!=$jsonMsg->game)
      echo "ERROR: only GM can change map\n";
    else
      adjustMapParameters($jsonMsg,$gameId);
  } else
  if(substr(trim($jsonMsg->message),-1)=="=") { //only GM?
    echo "calculator\n";
    $r = calculator($jsonMsg->message);
    echo "result=$r\n";
    $jsonMsg->message = $r;
    message_to_scene($sceneId,$gameId,$jsonMsg); //player( $Players[ $playerId ],$r);
    echo "done calc\n";
  } else
  if(substr(trim($jsonMsg->message),0,5)=="roll ") {
    //echo "DEBUG: dicehandler call\n";
    diceHandler($jsonMsg,$sceneId,$gameId,$Players[$playerId]);  //we will add params as needed
  } else
  if($jsonMsg->message=="scenechange") {
    $sn = $jsonMsg->param;
    //the player just changed scenes
    $Players[ $playerId ]->sceneId = $sn;
    echo "player $playerId just changed scene $sn\n";
    //server must read and send the last x lines to the user
    readBackSceneToPlayer($sn,$Players[ $playerId] );
  } else
  if(substr($jsonMsg->message,0,1)=='"') {//quoted string
    $jsonMsg->message .= "&#34;"; //add a trailing quote, is all
    message_to_scene($sceneId,$gameId,$jsonMsg);
  } else 
    message_to_scene($sceneId,$gameId,$jsonMsg);
  
  /* //very simplest functions
  if(substr($user_message,0,1)=="+" && $user_name == $GAMES[$user_game]->gm) {
    //add xp etc. to a char's score - already checked plus and that the player is the GM - only gm can do this
    //addScore($user_message); - complex, too, we need to be able to send to specific player - bugger
  } else 
  if( substr($user_message,0,5)=="roll " )
    $user_message = doRoll( $user_message ); */    
    
  echo "DEBUG:done\n";
    
	//return mask(json_encode(array('type'=>'usermsg', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color)));
}//F

function message_to_scene($id,$gameId,$jsonMsg) {
  global $Players;
  
  $msg = mask(json_encode(array('type'=>'usermsg', 'name'=>$jsonMsg->name, 'message'=>$jsonMsg->message, 'color'=>$jsonMsg->color)));
  $sceneNotify = mask(json_encode(array('type'=>'sceneupdate', 'sn'=>$id))); 
  foreach($Players as $p) {
    $socket = $p->socket;
    if( $p->sceneId==$id ) { //then this player is in the scene I want to message
      echo "DEBUG messaging $p->user in scene $p->sceneId"; print_r($msg); echo "\n";
      @socket_write($socket,$msg,strlen($msg)); 
    } else {
      //if players are NOT in scene, they must receive a scene-notification
      echo "DEBUG notifying $p->user of scene $p->sceneId changed"; print_r($msg); echo "\n";
      @socket_write($socket,$sceneNotify,strlen($sceneNotify)); 
    }//if
  }//for
  
  //log
  $line = "$jsonMsg->name|$jsonMsg->message";
  if(trim($jsonMsg->name).trim($jsonMsg->message)=="") return;  
  logScene($gameId,$id,$line);
}//F

function initGames() {
  global $GAMES;
  global $DBL;

  $query = 
"SELECT id,gameName as name,gm,announce,map_image,map_x,map_y,map_scale
FROM `dice_games`";
  $result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());
  while($row = mysqli_fetch_object($result)){
    $GAMES[ $row->id ] = $row;
  }//while

  //DEBUG print_r($GAMES);  
}//F

//$chr = getCharacter($user);  
function getCharacter($user){
  global $DBL;
  global $CHARACTERS;
  
  if(isset($CHARACTERS[$user]))
    return $CHARACTERS[$user];
    
  $query = 
"SELECT dc.id as id,dc.game_id as gameId 
FROM `dice_characters` dc 
WHERE name = '$user'";
  $result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());
  $row = mysqli_fetch_object($result);
  $row->levels = array();//may cause trouble
  
  $CHARACTERS[$user] = $row;
  
  return $row;    
}//F

//  logScene($gameId,$id,"$jsonMsg->name  $jsonMsg->message");
function logScene($gameId,$sceneId,$line) {
  global $LOG_COUNTER;
  global $LOG_LIMIT;

  $fe = getPersistentLogDescriptor($gameId,$sceneId);       
  fwrite($fe,$line."\n");
  echo "DEBUG: logging to file $gameId.$sceneId - $line\n";
  
  $LOG_COUNTER++;
  if($LOG_COUNTER>$LOG_LIMIT) {
    $LOG_COUNTER = 0;
    foreach($LOG_FILES as $logsByGame)
      foreach($logsByGame as $lbg) 
        fflush($lbg);
    echo "DEBUG:flushing logs\n";
  }//if
}//F

function getPersistentLogDescriptor($gameId,$sceneId) {
  global $LOG_FILES;
  global $GAMES;
  
  if( isset($LOG_FILES[$gameId][$sceneId]))
    return $LOG_FILES[$gameId][$sceneId];

  $gameName = $GAMES[$gameId]->name;
  $dateTime = new DateTime("now",new DateTimeZone('America/New_York'));
  $date = $dateTime->format("Ymd");
  $filename = "logs/$gameName-$date-$sceneId.log";     
  $fe = fopen($filename,"a");
  $LOG_FILES[$gameId][$sceneId] = $fe; //done
  echo "DEBUG: created new log file @ $filename\n";
  
  return $fe;
}//F

function readBackSceneToPlayer($sn,$player) {
  global $SCENE_SIZE;
  global $GAMES;
  //global $PLAYERS;
  //reads the current log file and gets the last X lines and then sends them to the player(s) in that scene

  //$player = $PLAYERS[$playerId];
  //$gameId = $player->gameId;
  $game = $player->game;
  echo "DEBUG: sn=$sn player="; print_r($player);  
  
  $dateTime = new DateTime("now",new DateTimeZone('America/New_York'));
  $date = $dateTime->format("Ymd");
  $filename = "logs/$game->name-$date-$sn.log";  
  echo "DEBUG:read back filename $filename\n";
  //die();     
  $fd = fopen($filename,"r");
  if($fd===false) { 
    //if there is no file by that name, there is nothing to send back
    echo "DEBUG: nothing to send back on $filename\n";
    return;
  }//if 
  $current = 0;
  while(!feof($fd)){
    $line[ $current % $SCENE_SIZE ] = trim(fgets($fd,4096));    
    echo "DEBUG read back line ".$line[ $current % $SCENE_SIZE ]." current=$current\n";
    $current++;   
  }//while
  fclose($fd);
  print_r($line);
  echo "current=$current\n";
  for($i=0;$i<$SCENE_SIZE;$i++) {
    if(!isset($line[ ($current+$i) % $SCENE_SIZE ])) continue;
    $l = $line[ ($current+$i) % $SCENE_SIZE ];
    if($l=="") continue;
    echo "DEBUG: read back replay line=$l\n";   
    $bits = explode("|",$l);
    echo "DEBUG read back bits "; print_r($bits);
    $name = $bits[0];
    $l = $bits[1];    
    $msg = mask(json_encode(array('type'=>'usermsg', 'name'=>$name, 'message'=>$l, 'color'=>'bd9f70' ))); //playr doesn't have color atm
    message_to_player($player,$msg); //$line[ $current++ % $SCENE_SIZE ]);
  }//for
  //simple as that  
}//F

function message_to_player($player,$msg) {
   echo "DEBUG read back send "; print_r($msg); echo "\n";
  //$msg = mask(json_encode(array('type'=>'usermsg', 'name'=>$jsonMsg->name, 'message'=>$jsonMsg->message, 'color'=>$jsonMsg->color))); //playr doesn't have color atm
  //$socket = $player->socket;
  @socket_write($player->socket,$msg,strlen($msg)); 
}//F

//diceHandler($jsonMsg);  //we will add params as needed
/*function diceHandler2($msg,$sceneId,$gameId,$player) {
  //we already know the first 5 chrs are "roll "
  /* 		message: mymessage,
    game : mygame,
		name: myname,
		color : 
  $params = substr($msg->message,5); //clip off "roll "
  $handled = false; 
  
  //echo "DEBUG: dicehandler params=$params\n"; 
  
  //simple case roll 3 v 7 or we also handle AGL vs 5
  $bits = explode(" ",$params);
  if(count($bits)==3 && ($bits[1]=="vs")||($bits[1]=="v")) {
    $skill = trim($bits[0]);
    $difficulty = trim($bits[2]);
    $whatYouRolled = "$skill vs $difficulty";
    //is skill a number, or a stat?
    if(!is_numeric($skill)) {
      //look it up as if it were a stat, if it does not resolve, nothing we can do
      $stat = getLevel($msg->name,$skill);
      if($stat=="") { //fail
        $msg = mask(json_encode(array('type'=>'usermsg', 'name'=>$msg->name, 'message'=>"You have no level for $skill, please..", 'color'=>"gray" ))); //playr doesn't have color atm
        message_to_player($player,$msg); //$line[ $current++ % $SCENE_SIZE ]);
        return;
      } else {  //we found stat and can proceed
        $whatYouRolled = "$skill($stat) vs $difficulty";
        $skill = $stat; //and proceed
      }//if stat=="" 
    }//if
    $diceRoll = mt_rand(0,99);     
    $r = round( ($diceRoll * ($skill + $difficulty))/100 - $difficulty , 0); //default supposedly , $PHP_ROUND_HALF_UP);
    //echo "DEBUG: dicehandler skill=$skill difficulty=$difficulty r=$r\n";
    if(strlen($diceRoll)<2) $diceRoll = "0$diceRoll";
    $msg->message = " rolled $diceRoll% of $whatYouRolled and got $r";
    //$msg->name = "game";  //a special return value that I want to display in a special way
    message_to_scene($sceneId,$gameId,$msg); //we'll start with this, later a dice-result in proper format
    $handled = true;
  }//if
  
  if(!$handled) {
    message_to_scene($sceneId,$gameId,$msg);
  }//if       
}//F
*/

function getLevel($chrName,$ability) {
  global $DBL;
  global $CHARACTERS;
  
  if( isset($CHARACTERS[$chrName])) {
    $c = $CHARACTERS[$chrName];
    if(isset($c->levels[$ability])) 
      return $c->levels[$ability];
    else {
      //we must retrieve it from the database and store it here
      $query = 
"SELECT statName,statValue FROM 
dice_stats ds 
LEFT JOIN dice_characters dc ON (dc.id = ds.char_id) 
WHERE dc.name = '$chrName'
AND ds.statName = '$ability'";
      $result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());
      $row = mysqli_fetch_object($result);
      
      $CHARACTERS[ $chrName ]->levels[ $ability ] = $row->statValue;
      
      return $row->statValue;
    }//if found
  }//if chr set - what if not found?

}//F 

function calculator($msg) {
  $VALID = "01234567890+-*/";
  //a message of the style 5+6/7*3= 
  //$msg = substr($msg,0,strlen($msg)-1); //strip off equals
  //next, we must make sure it contains only numbers and +/*-
  //$operators = explode("+=x*/:%=",$msg);
  $tokens = "-+=x*/:%=";
  $op = strtok($msg,$tokens);
  while($op!==false) {
    $operator[] = $op;
    $op = strtok($tokens);
  }
  echo "operators: "; print_r($operator);
  $opCount = 1;
  if(count($operator)<2) return "error: Too few numbers $msg";
  $result = $operator[0];
  for($i=0;$i<strlen($msg);$i++) {
    $c = substr($msg,$i,1);
    echo "c=$c result=$result\n";
    switch($c) {
      case "+" : $result = $result + $operator[$opCount++];
                 break;
      case "-" : $result = $result - $operator[$opCount++];
                 break;
      case "x" :
      case "*" : $result = $result * $operator[$opCount++];
                 break;
      case "/" : $result = $result / $operator[$opCount++];
                 break;
      case ":" : $result = $result / $operator[$opCount++] * 100;
                 break;
      case "%" : $result = $result * $operator[$opCount++] / 100;
                 break;
      case "0":
      case "1":
      case "2":
      case "3":
      case "4":
      case "5":
      case "6":
      case "7":
      case "8":
      case "9":
      case "=":
                break;
      default:   return "unknown operator: $c in $msg";
    }//switch    
  }//for
  return $msg.$result;
}//F

function diceHandler($msg,$sceneId,$gameId,$player) { //more complex version - when this works, remove the other (2)
  //we already know the first 5 chrs are "roll "
  /* 		message: mymessage,
    game : mygame,
		name: myname,
		color : */
  $params = substr($msg->message,5); //clip off "roll "
  $handled = false; 
  
  //echo "DEBUG: dicehandler params=$params\n"; 
  
  //simple case roll 3 v 7 or we also handle AGL vs 5
  $bits = explode(" ",$params);
  if(count($bits)==3 && ($bits[1]=="vs")||($bits[1]=="v")) {
    $skill = trim($bits[0]);
    $difficulty = trim($bits[2]);
    //
    //must resolve skill and difficulty into numbers    
    $skill2 = computeStatValue($skill,$msg->name,$player);
    $difficulty2 = computeStatValue($difficulty,$msg->name,$player);
    $diceRoll = mt_rand(0,99);     
    $r = round( ($diceRoll * ($skill2 + $difficulty2))/100 - $difficulty2 , 0); //default ROUND UP supposedly
    //echo "DEBUG: dicehandler skill=$skill difficulty=$difficulty r=$r\n";
    if(strlen($diceRoll)<2) $diceRoll = "0$diceRoll";
    $whatYouRolled = $skill;
    if($skill<>$skill2)
      $whatYouRolled .= "($skill2)";
    $whatYouRolled .= " vs $difficulty";
    if($difficulty<>$difficulty2)
      $whatYouRolled .= "($difficulty2)";    
    //$whatYouRolled = "$skill vs $difficulty";
    $msg->message = " rolled $diceRoll% of $whatYouRolled and got $r";
    //$msg->name = "game";  //a special return value that I want to display in a special way
    message_to_scene($sceneId,$gameId,$msg); //we'll start with this, later a dice-result in proper format
    $handled = true;
  }//if
  
  if(!$handled) {
    message_to_scene($sceneId,$gameId,$msg);
  }//if       
}//F

function computeStatValue($s,$chrName,$player) {//converts an expression into a number e.g. PER+3+2 or even Lalaith.Archery (next)
  //first, is it a simple number?
  if(is_numeric($s)) {
    return $s;
  }//numeric
  //else it's a complex expression, so break it down
  $bits = explode("+",$s);
  //for each bit, get a value
  $total = 0;
  foreach($bits as $b) {
    echo "bit=$b "; //DEBUG
    //each bit will either be a number, or a stat/expression
    $v = getStatValue($b,$chrName,$player);
    echo "bit=$b v=$v "; //DEBUG
    if($v==-1) return -1; //abort! abort! abort!
    $total += $v;
  }//for
  return $total;
}//F

function getStatValue($s,$chrName,$player) {
  if(is_numeric($s)) {
    return $s;
  }//numeric
  //otherwise, it might be a statname eg Archery
  $stat = getLevel($chrName,$s);
  echo "stat=$stat "; //DEBUG
  if($stat=="") { //fail
    $msg = mask(json_encode(array('type'=>'usermsg', 'name'=>$chrName, 'message'=>"You have no level for $skill, please..", 'color'=>"gray" ))); //playr doesn't have color atm
    message_to_player($player,$msg); //$line[ $current++ % $SCENE_SIZE ]);
    return -1;
  }//if
  return $stat;  
}//F
function adjustMapParameters($msg,$gameId) {
  global $DBL;
  global $GAMES;

  echo "DEBUG:amp\n";

  $newX = $msg->mapx;
  $newY = $msg->mapy;
  $newScale = $msg->scale;

  $query =
"UPDATE i2d.dice_games
SET map_x = $newX,
map_y = $newY,
map_scale = $newScale
WHERE id=$gameId
LIMIT 1";
  $result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());

  echo "DEBUG: amp query=$query ";

  //we need to notify all players to update their maps...
  $game = $GAMES[ $gameId ];
  //oops we forgot to update the game object in server memory
  //$game->map_x = $newX;
  //$game->map_y = $newY;
  //$game->map_scale = $newScale;

  //sendMapUpdate($game,$socket);

  //$msg = mapUpdate($game);
  //e//cho "DEBUG map update "; print_r($msg); echo "\n";
  //@socket_write($socket,$msg,strlen($msg));
}//F

function sendMapUpdateToPlayer($game,$socket) {
  //I want you to re-read the game info from dB (refresh)
  //store to game object in local memory
  //then transmit to the game client(s)
  global $DBL;
  global $GAMES;

  $query =
"SELECT map_x,map_y,map_scale,map_image
FROM i2d.dice_games
WHERE id=$game->id";
  $result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());
  $row = mysqli_fetch_object($result);
  
  $GAMES[ $game->id ]->map_x = $row->map_x;
  $GAMES[ $game->id ]->map_y = $row->map_y;
  $GAMES[ $game->id ]->map_scale = $row->map_scale;
  $GAMES[ $game->id ]->map_image = $row->map_image;
  $game = $GAMES[ $game->id ];

  $msg = mask(json_encode(array('type'=>'mapdate', 'url'=>$game->map_image, 'x'=>$game->map_x, 'y'=>$game->map_y, 'scale'=>$game->map_scale)));
  
  echo "DEBUG map update "; print_r($msg); echo "\n";
  echo "DEBUG socket="; print_r($socket); echo "\n";

  @socket_write($socket,$msg,strlen($msg));
}//F

function updatePlayerPos($msg) {
  global $DBL;

  echo "updating player positions\n";
  /* msg 
  (
    [message] => player-pos
    [player] => PEQ
    [game] => WOWF
    [name] => WOWF
    [new_x] => 1430
    [new_y] => 975.5
  ) */
  $player = $msg->player;
  $mapX = $msg->new_x;
  $mapY = $msg->new_y;  

  $query =
  "UPDATE i2d.dice_characters
  SET map_x = $mapX,
  map_y = $mapY
  WHERE name = '$player'
  LIMIT 1";

  $result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());

  echo "DEBUG player=$player mapX=$mapX mapY=$mapY query=$query\n";
}//F

?>