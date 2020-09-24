<?php
/* get language cache */
require_once("/opt/simbank/www/inc/language.inc");
require_once("/opt/simbank/www/inc/define.inc");

//load redis database;
if(!$cloudSimProxy){
	require_once("/opt/simbank/www/inc/redis.inc");
}

$filename = basename($_SERVER['REQUEST_URI']);

$title_array = array(
	"simbank_manage.php" => "simbank",
	"gateway_manage.php" => "gateway",
	"group_manage.php" => "group",
	"local_manage.php" => "location",
	"strategy_manage.php" => "strategy",
	"user_manage.php" => "user",
	"simemu-settings.php" => "simemu-settings"
);

$language = get_web_language_cache('/tmp/web/language.cache');
if(isset($_GET['send_debug'])){
	if($_GET['send_debug']=='on'){
		debug_language('on');
	}else if($_GET['send_debug']=='off'){
		debug_language('off');
	}
}

if(file_exists('/tmp/web/language.debug')){
	$language_debug = 'true';
}else{
	$language_debug = 'false';
}


if(isset($title_array[$filename]))
	$title = $title_array[$filename];
else
	$title = "";
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $title;?></title>
	<!--
	<link rel="icon" href="./images/logo.ico" type="image/x-icon">
	-->
	<link rel="shortcut icon" href="./images/logo.ico" type="image/x-icon">
	<link rel="stylesheet"  href="/css/style.css?version=0.1.6" />
	</head>
	<body>
	<script src="/js/js.js"></script>
	<script src="/js/jquery.js"></script>
	<script type="text/javascript" src="/js/jquery-ui-1.10.2.custom.all.min.js"></script>
	<script type="text/javascript" src="/js/jquery.qrcode.min.js"></script>
	<?php //echo "language:";print_r($language);?>
