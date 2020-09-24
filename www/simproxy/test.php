<?php
phpinfo();
echo exec('whoami');
?>

<form action="index.html">
<table>
	<tr>
		<td>Please Select Portal</td>
	</tr>
	<tr>
		<td><input type="button" id="select_server" onclick="window.location.href='../simproxy/index.php'" value="Server"></td>
	</tr>
	<tr>
		<td><td><input type="button" id="select_server" onclick="window.location.href='../simproxy/index.php';'" value="Client"></td></td>
	</tr>
</table>
</form>

