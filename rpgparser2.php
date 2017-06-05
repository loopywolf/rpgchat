<?php
$PLAYERS = array();
$GAMES = array();
$SCENES = array();
$DBL = mysqli_connect("alix", "i2d", "Dei5iapu", "i2d") or die("Could not connect for rpgchat");
echo "DBL="; print_r($DBL);


function rpgParse($received_text) {
  echo "rpg parse\n";  
  print_r($received_text);
  
	$tst_msg = json_decode($received_text); //json decode 
	$user_name = trim($tst_msg->name); //sender name
	$user_message = trim($tst_msg->message); //message text
  $user_game = trim($tst_msg->game);  //game name
	$user_color = trim($tst_msg->color); //color
  
  echo "<PRE>tst_msg ";
  print_r($tst_msg);
  echo "</PRE>"; 
  
  $thisChr = getChrInfo($user_name);
  $thisGame = getGameInfo($thisChr->gameId);
  
  //debug
  //$user_message .= $thisChr->id.$thisChr->gameId; 

  if($thisChr->welcome) {
    //this is the chr's arrival into the game/scene
    //want to welcome them, and send the game welcome message
    //how do I send multiple messages back?
    $welcome = "Welcome to $thisGame->name, $thisChr->name!<BR>$thisGame->announce<BR>";
 		$additional = mask(json_encode(array('type'=>'system', 'message'=>$welcome))); //prepare json data
		send_message($additional); //notify all users about new connection
    $thisChr->welcome = 0;
  }//if

  //very simplest functions
  if(substr($user_message,0,1)=='"') //quoted string
    $user_message .= "&#34;"; //add a trailing quote, is all
  else
  if(substr($user_message,0,1)=="+" && $user_name == $GAMES[$user_game]->gm) {
    //add xp etc. to a char's score - already checked plus and that the player is the GM - only gm can do this
    //addScore($user_message); - complex, too, we need to be able to send to specific player - bugger
  } else 
  if( substr($user_message,0,5)=="roll " )
    $user_message = doRoll( $user_message );
    
	return mask(json_encode(array('type'=>'usermsg', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color)));
}//F

function getChrInfo($u) {
  global $CHRS;
  global $DBL;
  
  //this attempts to find the info about the chr from the server (us)
  if(isset($CHRS[$u]))
    return $CHRS[$u];

  $query = 
"SELECT dc.id as id,dc.game_id as gameId 
FROM `dice_characters` dc 
WHERE name = '$u'";
  $result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());
  $row = mysqli_fetch_object($result);
  
  echo "chr="; print_r($row);
  
  $row->name = $u;
  $row->welcome = 1;
  $CHRS[$u] = $row;
  
  return $row;    
  //SELECT dc.id as chr_id,dc.game_id as game_id FROM `dice_characters` dc WHERE name = 'Loopy'
  //note: games should be collected in an array when server starts    
}//F
function getGameInfo($g) {
  global $GAMES;
  global $DBL;
  
  //this attempts to find the info about the chr from the server (us)
  if(isset($GAMES[$g]))
    return $GAMES[$g];

  $query = 
"SELECT gameName as name,gm,xprate,baseline,announce
FROM `dice_games`
WHERE id=$g";
  $result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());
  $row = mysqli_fetch_object($result);
  
  echo "game="; print_r($row);
  
  $GAMES[$u] = $row;
  
  return $row;    
  //SELECT dc.id as chr_id,dc.game_id as game_id FROM `dice_characters` dc WHERE name = 'Loopy'
  //note: games should be collected in an array when server starts    
}//F

function doRoll($msg) {
  //returns a message - we already know this begins with "roll "
  
  //if this is a dice-roll command, process it and store things ,etc
  //format: roll PER+5 [vs 6+4]
  //break it into words
  $words = explode(" ",$msg);
  if( count($words)!=4 && count($words)!=2 ) return $msg; //drop out

  $roll = $words[0]; //don't need to check
  $stat = $words[1]; 

  if( count($words)==4 ) {  
    $v = $words[2];
    $difficulty = $words[3];
  }//if
  
}//F
?>
