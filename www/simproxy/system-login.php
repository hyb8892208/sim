<?php
include_once("../inc/network_factory.inc");
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require('../inc/mysql_class.php');
//require("../inc/language.inc");
include_once("../inc/wrcfg.inc");
include_once("../inc/aql.php");
include_once("../inc/define.inc");
include_once("../inc/language.inc");
?>

<?php
$DENY_PORT_LIST="5038,5039,5040,5041,5042,5060,12345,12346,12347,12348,12349,8000,56888"
?>

<script type="text/javascript" src="/js/jquery.ibutton.js"></script> 
<link type="text/css" href="/css/jquery.ibutton.css" rel="stylesheet" media="all" />
<script type="text/javascript" src="/js/functions.js"></script>
<script type="text/javascript" src="/js/check.js"></script>
<script type="text/javascript" src="/js/float_btn.js"></script>

<script type="text/javascript">

function check()
{
	var login_mode = document.getElementById("login_mode").value;
	var user = document.getElementById("user").value;
	var pw1 = document.getElementById("pw1").value;
	var pw2 = document.getElementById("pw2").value;
	var web_server_port = parseInt(document.getElementById("web_server_port").value);
	var web_server_https_port = parseInt(document.getElementById("web_server_https_port").value);
	
	document.getElementById("cuser").innerHTML = '';
	document.getElementById("cpw1").innerHTML = '';
	document.getElementById("cpw2").innerHTML = '';
	document.getElementById("cweb_server_port").innerHTML = '';
	document.getElementById("cweb_server_https_port").innerHTML = '';
	
	if(user != "" || pw1 != "" || pw2 != "") {
		if(!check_diyname(user)) {
			var rstr = "<?php echo language('js check diyname','Allowed character must be any of [-_+.<>&0-9a-zA-Z],1 - 32 characters.');?>";
			document.getElementById("cuser").innerHTML = con_str(rstr);
			return false;
		}

		if(!check_diypwd(pw1)) {
			var rstr = "<?php echo language('js check diypwd','Allowed character must be any of [-_+.<>&0-9a-zA-Z],4 - 32 characters.');?>";
			document.getElementById("cpw1").innerHTML = con_str(rstr);
			return false;
		}

		if(pw1 !== pw2) {
			document.getElementById("cpw2").innerHTML = con_str('<?php echo language('Confirm Password warning@system-login web','This password must match the password above.');?>');
			return false;
		}
	}
	
	var deny_port_list = new Array(<?php echo $DENY_PORT_LIST;?>);
	var allow = true;
	for (var i in deny_port_list){
		if(web_server_port == deny_port_list[i]){
			allow = false;
			break;
		}
	}
	

	if((web_server_port < 1024 && web_server_port != 80 ) || web_server_port > 65535 || allow == false){
		document.getElementById("cweb_server_port").innerHTML = con_str('<?php echo language('js webserver http port help','Range: 1024-65535, HTTP Default 80. Some ports are forbidding.'); ?>');
		return false;
	}
	if((web_server_https_port < 1024 && web_server_https_port != 443 ) || web_server_https_port > 65535 || allow == false){
		document.getElementById("cweb_server_https_port").innerHTML = con_str('<?php echo language('js webserver https port help','Range: 1024-65535, HTTPS Default 443. Some ports are forbidding.'); ?>');
		return false;
	}
	
	return true;
}
</script>

<?php

function save2webserver()
{
/*
#web_server.conf
[general]
username=admin
password=admin
port=80
*/
	$aql = new aql();
	$setok = $aql->set('basedir','/config/simbank/conf');
	if (!$setok) {
		echo $aql->get_error();
		return false;
	}
        
	$conf_path = '/config/simbank/conf/web_server.conf';
	$hlock = lock_file($conf_path);
        
	if(!file_exists($conf_path)) {
		fclose(fopen($conf_path,"w"));
	}
        
	if(!$aql->open_config_file($conf_path)){
		echo $aql->get_error();
		unlock_file($hlock);
		return false;
	}
        
	$exist_array = $aql->query("select * from web_server.conf");

	if(!isset($exist_array['general'])) {
		$aql->assign_addsection('general','');
	}
	
	if(isset($_POST['user']) && isset($_POST['pw1']) && isset($_POST['pw2'])
		&& trim($_POST['user']) != '' && trim($_POST['pw1']) != '' && trim($_POST['pw2']) != ''
	&& $_POST['pw1']==$_POST['pw2'] ) {
		
	
		$username = trim($_POST['user']);
		$password = trim($_POST['pw1']);

		if(isset($exist_array['general']['username'])) {
			$aql->assign_editkey('general','username',$username);
		} else {
			$aql->assign_append('general','username',$username);
		}

		if(isset($exist_array['general']['password'])) {
			$aql->assign_editkey('general','password',$password);
		} else {
			$aql->assign_append('general','password',$password);
		}
			
	}
	
	if(isset($_POST['web_server_port'])){
		$port = trim($_POST['web_server_port']);
		if($port >= 1 && $port <= 65535){
			if(isset($exist_array['general']['port'])) {
				$aql->assign_editkey('general','port',$port);
			} else {
				$aql->assign_append('general','port',$port);
			}
		}
	} else {
		if(isset($exist_array['general']['port'])) {
			$aql->assign_editkey('general','port',80);
		} else {
			$aql->assign_append('general','port',80);
		}		
	}
	
	if(isset($_POST['web_server_https_port'])){
		$https_port = trim($_POST['web_server_https_port']);
		if($https_port >= 1 && $https_port <= 65535){
			if(isset($exist_array['general']['https_port'])) {
				$aql->assign_editkey('general','https_port',$https_port);
			} else {
				$aql->assign_append('general','https_port',$https_port);
			}
		}
	} else {
		if(isset($exist_array['general']['https_port'])) {
			$aql->assign_editkey('general','https_port',443);
		} else {
			$aql->assign_append('general','https_port',443);
		}		
	}
	
	if(isset($_POST['login_mode'])){
		$login_mode = trim($_POST['login_mode']);
		if(isset($exist_array['general']['login_mode'])){
			$aql->assign_editkey('general','login_mode',$login_mode);
		}else{
			$aql->assign_append('general','login_mode',$login_mode);
		}
	}else{
		if(isset($exist_array['general']['login_mode'])){
			$aql->assign_editkey('general','login_mode','http_https');
		}else{
			$aql->assign_append('general','login_mode','http_https');
		}
	}
	
	if (!$aql->save_config_file('web_server.conf')) {
		echo $aql->get_error();
		unlock_file($hlock);
		return false;
	}
	
	unlock_file($hlock);
	
	return true;
	
}

$aql = new aql();
$aql->set('basedir','/config/simbank/conf');
$res = $aql->query("select * from web_server.conf");
$web_server_port = '80';
$web_server_https_port = '443';
if(isset($res['general']['port']))	$web_server_port = trim($res['general']['port']);
if(isset($res['general']['https_port'])) $web_server_https_port = trim($res['general']['https_port']);

if(!is_numeric($web_server_port)) {
	$web_server_port = '80';
}
if(!is_numeric($web_server_https_port)){
	$web_server_https_port = '443';
}

$login_mode = '';
if(isset($res['general']['login_mode'])) $login_mode = trim($res['general']['login_mode']);

?>

<form enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Web Login Settings');?></li>
		<li class="tb2">&nbsp;</li>
	</div>

	<table width="100%" class="tedit" >
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('User Name');?>:
					<span class="showhelp">
					<?php
						$help = <<<EOF
					NOTES: Your gateway doesn't have administration role. <br/>
					All you can do here is defining the username and password to manage your gateway.<br/>
					And it has all privileges to operate your gateway.<br/>
					User Name: Allowed characters "-_+.<>&0-9a-zA-Z".Length: 1-32 characters.
EOF;
						echo language('User Name help@system-login web', $help);
					?>
					</span>
				</div>
			</th>
			<td>
				<input id="user" type="text" name="user" style="width: 250px;" value="" /><span id="cuser"></span>
			</td>
		</tr>
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Password');?>:
					<span class="showhelp">
					<?php
						echo htmlentities(language('js check diypwd',"Allowed character must be any of [0-9a-zA-Z`~!@$%^&*()_+{}|<>?-=[],./],4 - 32 characters."));
					?>
					</span>
				</div>
			</th>
			<td>
				<input id="pw1" type="password" name="pw1" style="width: 250px;" value="" /><span id="cpw1"></span>
			</td>
		</tr>
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Confirm Password');?>:
					<span class="showhelp">
					<?php echo language('Confirm Password help@system-login web',"Please input the same password as 'Password' above.");?>
					</span>
				</div>
			</th>
			<td>
				<input id="pw2" type="password" name="pw2" style="width: 250px;" value="" /><span id="cpw2"></span>
			</td>
		</tr>				  
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Login Mode');?>:
					<span class="showhelp">
					<?php echo language('Login Mode help',"Select the mode of login.");?>
					</span>
				</div>
			</th>
			<td>
				<select id="login_mode" name="login_mode" onchange="login_mode_change();">
					<option value="http_https" <?php if ($login_mode == "http_https"){echo "selected";} ?>>http and https</option>
					<option value="https" <?php if ($login_mode == "https"){echo "selected";} ?>>only https</option>
					<option value="http" <?php if ($login_mode == "http"){echo "selected";} ?>>only http</option>
				</select>
			</td>
		</tr>
		<tr>                                                                                                                      
			<th>                                                                                                                             
				<div class="helptooltips">                                                                                                              
					<?php echo language('HTTP Port');?>:                                                                                                     
					<span class="showhelp">                                                                                                             
					<?php echo language('web server port help',"Specify the web server port number.");?>                                                
					</span>                                                                                                                             
				</div>                                                                                                                                      
			</th>                                                                                                                                               
			<td>                                                                                                                                                
				<input id="web_server_port" type="text" maxlength=5 name="web_server_port" style="width: 50px;" value="<?php echo $web_server_port ?>"      
						oninput="this.value=this.value.replace(/[^\d]*/g,'')" onkeyup="this.value=this.value.replace(/[^\d]*/g,'')"/>                       
				<span id="cweb_server_port"></span>                                                                                                         
			</td>                                                                                                                                               
		</tr>
		<tr>                                                                                                                      
			<th>                                                                                                                             
				<div class="helptooltips">                                                                                                              
					<?php echo language('HTTPS Port');?>:                                                                                                     
					<span class="showhelp">                                                                                                             
					<?php echo language('web server port help',"Specify the web server port number.");?>                                                
					</span>                                                                                                                             
				</div>                                                                                                                                      
			</th>                                                                                                                                               
			<td>                                                                                                                                                
				<input id="web_server_https_port" type="text" maxlength=5 name="web_server_https_port" style="width: 50px;" value="<?php echo $web_server_https_port ?>"      
						oninput="this.value=this.value.replace(/[^\d]*/g,'')" onkeyup="this.value=this.value.replace(/[^\d]*/g,'')"/>                       
				<span id="cweb_server_https_port"></span>                                                                                                         
			</td>                                                                                                                                               
		</tr>
	</table>
	
	<br>
	
	<input type="hidden" name="send" id="send" value="" />
	<div id="float_btn" class="float_btn">
		<div id="float_btn_tr" class="float_btn_tr">
				<input type="submit"   value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';return check();" />
		</div>
	</div>
	<table id="float_btn2" style="border:none;" class="float_btn2">
		<tr id="float_btn_tr2" class="float_btn_tr2">
			<td>
				<input type="submit" id="float_button_1" class="float_short_button" value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';return check();" />
			</td>
		</tr>
	</table>
</form>

<?php 
if($_POST && isset($_POST['send']) && $_POST['send'] == 'Save') {
	if(save2webserver()){
		save_webserver_to_lighttpd();
		wait_apply("exec", "/etc/init.d/lighttpd restart > /dev/null 2>&1 &");
		header("Location: ".$_SERVER['PHP_SELF']);
	}	
}
?>
	
<script type="text/javascript">
$(function(){
	login_mode_change();
});

function login_mode_change()
{
	if (document.getElementById("login_mode").value == "https") {
		document.getElementById("web_server_port").disabled=true;
		document.getElementById("web_server_port").style.backgroundColor="#E0E0E0";
		document.getElementById("web_server_https_port").disabled=false;
		document.getElementById("web_server_https_port").style.backgroundColor='';
	}else if(document.getElementById("login_mode").value == "http") {
		document.getElementById("web_server_https_port").disabled=true;
		document.getElementById("web_server_https_port").style.backgroundColor="#E0E0E0";
		document.getElementById("web_server_port").disabled=false;
		document.getElementById("web_server_port").style.backgroundColor='';
	} else {
		document.getElementById("web_server_https_port").disabled=false;
		document.getElementById("web_server_https_port").style.backgroundColor='';
		document.getElementById("web_server_port").disabled=false;
		document.getElementById("web_server_port").style.backgroundColor='';
	}
}
</script>

<div id="float_btn1" class="sec_float_btn1">
</div>
<div  class="float_close" onclick="close_btn()">
</div>

<?php require("../inc/boot.inc");?>