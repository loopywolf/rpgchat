<HTML>
	<BODY>
	<H1>Developer's Notes</H3>
	<?php
		$fd = fopen('developer.sif',"r");
		while(!feof($fd)) {
			$line = trim(fgets($fd,4000));
			echo "<BR>$line";
		}//while
	?>
	</BODY>
</HTML>