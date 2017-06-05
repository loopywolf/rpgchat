<STYLE>
body {
  font-family:calibri;
  background:#3a6763;
  color:#d6d676;
}
.high {
  color:#f7ff86;
  font-weight:bold;
}
</STYLE>
<?php
include "db_config.php";

//tool for gm to search and update his notes
$GAME = $_REQUEST['game'];
if($GAME=="") die("Error:Game not specified.");
if(isset($_REQUEST['search'])) {
  $SEARCH = $_REQUEST['search'];
} else {
  ?>
  <FORM>
    search <INPUT name=search type=text><INPUT type=submit>
    <INPUT type=hidden name=game value=<?php echo $GAME; ?>>
  </FORM>
  <?php
  die();
}//if

echo "Search: $SEARCH<P>";
$SEARCH = strtolower($SEARCH);

$query = 
"SELECT * 
FROM dice_database 
WHERE lower(keyword) like '%$SEARCH%'";

$result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());
while($row = mysqli_fetch_object($result)){
  echo "<DIV><span class=high>$row->keyword</span> : $row->value</DIV>";
}//while

$query = 
"SELECT * 
FROM dice_database 
WHERE lower(value) like '%$SEARCH%'";

$result = mysqli_query($DBL,$query) or die("failed ".__FILE__."@".__LINE__." $query ".mysql_error());
while($row = mysqli_fetch_object($result)){
  $line = preg_replace("/\w*?".preg_quote($SEARCH)."\w*/i", "<span class=high>$0</span>", $row->value);
  echo "<DIV>$row->keyword : $line</DIV>";
}//while

echo "<P><A href='notes.php?game=$GAME'>Back</A>";   
?>