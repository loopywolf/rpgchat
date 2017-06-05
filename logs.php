<STYLE>
body {
  font-family:calibri;
  background:#262229;
  color:#d6d676;
}
.high {
  color:#f7ff86;
  font-weight:bold;
}
#t1 {
  width: 100%;
  border: 2px solid grey;
  border-radius: 7px;
}
.c1 {
  color:green;
}
.c2 {
  width: 300px;
  border-left: 1px grey solid;
}
.log-link {
  text-decoration: none;
  color:#d6d676;
}
.log-link:hover {
  background:white;
  color:black;
}
.title {
  background:#e6e6e3;
  color: black;
  font-family: chicago;
  font-size: 18px;
  margin-top: 3px;
  margin-bottom: 3px;
  padding: 1px 3px;
  border-radius: 4px;
}
#row {
  vertical-align:top;
}
.cell {
  padding:2px 4px;
}
#page-title {
  background:#e6e6e3;
  color: black;
  font-family: chicago;
  font-size: 18px;
  margin-top: 3px;
  margin-bottom: 3px;
  padding: 1px 3px;
  border-radius: 4px;
  width:100%;
}
.player {
  color: #48af48;
}
#log-header {
  background: #185418;
  border-radius: 3px;
  color: #48af48;
  padding: 2px 4px;
}
.found-date {
  border: 1px solid grey;
  border-radius: 4px;
  padding: 2px 4px;
  color: white;
  margin-top: 2px;
  background:#3c3737;
}
.found-line {
  color: green;
  margin-left: 28px;
  padding: 2px 4px;
  margin-top: 2px;
}
.found-high {
  color:#48af48;
}
</STYLE>
<?php
include "db_config.php";

function fileToDate($file) {
  global $GAME;
  
  if(substr($file,-4)!=".log")
    return "";
  $file = substr($file,0,strlen($file)-4);
  $bits = explode("-",$file);
  $game = $bits[0];
  $date = $bits[1];
  $scene = $bits[2];
  $year = substr($date,0,4);
  $month = substr($date,4,2);
  $day = substr($date,6,2);
  
  $result = new Stdclass();
  $result->game = $game;
  $result->date = "$year-$month-$day";
  $result->scene = $scene;
  $result->dateCode = $year.$month.$day;
  
  //echo "fileDetails <PRE>";print_r($result);echo "</PRE>";
  
  return $result;        
}//F
function dateToFile($game,$date,$scene) {
  $date = date("Ymd",strtotime($date));
  
  return "$game-$date-$scene.log";
}//F










$GAME = $_REQUEST['game'];
if($GAME=="") die("Error:Game not specified.");
$blankLines = 0;

echo "<DIV id=page-title>Game Logs - $GAME</DIV>";
?>
<TABLE id=t1>
  <TR id=row><TD class='cell c1'>
<?php
if(isset($_REQUEST['search'])) {
  $SEARCH = $_REQUEST['search'];
  echo "<DIV id=log-header>Searching for keyword $SEARCH...</DIV>";
  //search all the appropriate log files
  $dir = "logs";
  $dh = opendir($dir);
  while (($file = readdir($dh)) !== false) {
    //echo "file $file | ";
    if(substr($file,-4)!=".log") continue;
    //echo "file ok $file | ";
    $fileDetails = fileToDate($file);    
    if($fileDetails->game !=$GAME) continue;
    //echo "checking file [$file]";
    $fd = fopen("logs/$file","r"); 
    while(!feof($fd)){
      $line = trim(fgets($fd,4096));
      if(stripos($line,$SEARCH)!==FALSE) {
        $results[ $file ] = $line;        
      }//if       
    }//while    
  }//while
  closedir($dh);
  
  krsort($results);
  
  foreach($results as $file => $line) {
    $fi = fileToDate($file);
    //print_r($fi);
    echo "<DIV class=found-date>On $fi->date [$fi->scene]</DIV>";
    $line = preg_replace("/\w*?".preg_quote($SEARCH)."\w*/i", "<span class=found-high>$0</span>", $line);
    echo "<DIV class=found-line>$line</DIV>";
  }//for
  
} else 
if(isset($_REQUEST['date'])) {
  $DATE = $_REQUEST['date'];
  echo "<DIV id=log-header>Date $DATE</DIV>";
  $fd = fopen("logs/$GAME-$DATE.log","r");
  while(!feof($fd)) {
    $line = trim(fgets($fd,4096));
    if($line=="") {
      $blankLines++;
      continue;
    }
    $element = explode("|",$line);
    if(count($element)<2){    
      $blankLines++;
      continue;
    }
    $e0 = $element[0];
    $e1 = $element[1];
    if(trim($e0)!="" && trim($e1)!="")    
      echo "<DIV class=log-line><SPAN class=player>$e0</SPAN> $e1</DIV>";
    else
      $blankLines++;
  }//while
  fclose($fd); 
}//if date  
?>
</TD><TD class='cell c2'>
<DIV class=title>Search</DIV>
<FORM>Search <INPUT name=search type=text><INPUT type=submit>
<INPUT type=hidden name=game value='<?php echo $GAME; ?>'>
</FORM><BR>
<DIV class=title>Calendar</DIV>
<?php
  //////////////////////  CALENDAR //////////////////////////////////
  $dir = "logs";
  $dh = opendir($dir);
  while (($file = readdir($dh)) !== false) {
      if(substr($file,-4)!=".log") continue;
      $info = fileToDate($file);
      if($info->game!=$GAME) continue;
      $choices[] = $file;
      //echo "<DIV>filename: $file date=$date</DIV>";
  }//while
  closedir($dh);
  
  rsort($choices);
  
  foreach($choices as $c) {
    $info = fileToDate($c);
    $hint = date("jS F,Y",strtotime($info->date));
    //echo "<DIV title='$hint - scene $scene'>$c $year-$month-$day [$scene]</DIV>";
    echo "<DIV><A class=log-link href='logs.php?game=$GAME&date=$info->dateCode-$info->scene' title='$hint'>$info->date [$info->scene]</A></DIV>";
  }//for
?></TD></TR>
</TABLE>
<?php

if(isset($blanklines))
  echo "<DIV id=error>Found $blankLines blank lines in file - this should not be so.</DIV>";
/*$query = 
"SELECT * 
FROM dice_database 
WHERE lower(value) like '%$SEARCH%'";

$result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());
while($row = mysqli_fetch_object($result)){
  echo "<DIV>$row->keyword : $line</DIV>";
}//while


$search_pattern = "text to find";
$output = array();
$result = exec("/path/to/grep -l " . escapeshellarg($search_pattern) . " /path/to/directory/*", $output);

print_r($output);*/
?>