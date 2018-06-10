<link type="text/css" rel="stylesheet" href="rpg.css">
<link href="https://fonts.googleapis.com/css?family=Exo+2" rel="stylesheet">
<HEAD>
</HEAD>
<BODY>
<TABLE class=t1>
	<TR class=tr>
		<TD id=c-chat>
		  <div class=chat_wrapper>
		    <div class="message_box" id="message_box"></div>
		  </div>
		</TD>
	<TD rowspan=2 id=c-menu rowspan=2>
		<DIV id=menu-box>
		<DIV class=menu-title><?php echo $GAME; ?></DIV>
		<DIV class=detail><?php echo $GAME_BLURB; ?></DIV>
		<DIV class=menu-title>Scenes</DIV>
		sceneChangeMenu
		<DIV class=menu-title>Commands</DIV>
		<DIV class=menu-button-box>
		  <a target=_new href='<?php echo "http://monfur.ca/dstory/sheet.php?name=$USERNAME"; ?>' class='menu-title menu-button'>Sheet</A>
		  <a href=game.php class='menu-title menu-button'>Game Info</A>
		  <a href='javascript:showMap()' id=map-button class='menu-title menu-button' title='click to view map'>Map</A>
		  <a href='<?php echo $PINTEREST; ?>' class='menu-title menu-button' title='remember to follow!'>Pinterest</A>
		  <a href=help.php class='menu-title menu-button'>Help</A>
		</DIV>
		<DIV class=menu-title>Players</DIV>
	</TD>
	</TR>
  <TR id=bottom-row>
    <TD id=c-chat-2>
      <div class="panel">
        <input type="text" name="name" id="name" placeholder="Your Name" <?php echo "value='$USERNAME'"; ?> maxlength="15" style="display:none" />
        <input tabindex=1 type="text" class=main-input name="message" id="message" placeholder="Message" maxlength="5000" onkeydown = "if (event.keyCode == 13)document.getElementById('send-btn').click()"  />
        <button id="send-btn" class=button>SEND</button>
      </div>
    </TD>
  </TR>
</TABLE>
</BODY>
