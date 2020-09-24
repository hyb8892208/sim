<!---
通过物理正式目录转simbank，这样的好处是以后维护只需要拷贝整个www目录就好，不需要配置httpd.conf
-->

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Openvox SimBank Administration Console</title>
<style type="text/css">
	*{color:#CCC;background:#000;font-family:Verdana,Helvetica,sans-serif}
	p{position:absolute;width:99%;top:50%;margin-top:-3em;line-height:3em;text-align:center}
</style>
</head>
<script type="text/javascript">
	window.location.href='system-monitor_sim.php';
	/*if(navigator.userAgent.toLowerCase().indexOf("chrome") != -1){
		window.location.href='cgi-bin/php/system-status.php';
	}else{
		document.write('Make sure to use Chrome web browerss');
	}*/
</script>
<body>
<center>
<?php
	$fal = 32321;
	echo md5($fal);

?>
	<h3><a href="system-monitor_sim.php">Entry</a></h3>
</center>
</body>
</html>