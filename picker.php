<?php
include "db_config.php";

//tool for gm to find and pick random lists
$GAME = $_REQUEST['game'];
if($GAME=="") die("Error:Game not specified.");
$PICKED = $_REQUEST['picked'];

//show list of possible picks
if(!isset($PICKED)) {
  $query = 
  "SELECT keyword FROM dice_picks";
  $result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());
  while($row = mysqli_fetch_object($result)){
    echo "<DIV><A href='picker.php?game=$GAME&picked=$row->keyword'>$row->keyword</A></DIV>";
  }//while
  die();
}//if

//otherwise, show the picked
$query = 
"SELECT value FROM dice_picks where keyword='$PICKED'";
$result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());
$row = mysqli_fetch_object($result);

$choices = explode("\n",$row->value);

//print_r($choices);

$n = count($choices) - 1;
$x = mt_rand(0,$n);

echo "<H2>".$choices[$x]."</H2>";

echo "<P><A href='picker.php?game=$GAME'>Back</A>";   
?>