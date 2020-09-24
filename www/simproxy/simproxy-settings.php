<?php
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require('../inc/mysql_class.php');
include_once("../inc/wrcfg.inc");
include_once("../inc/aql.php");
include_once("../inc/define.inc");
include_once("../inc/language.inc");
?>
<link type="text/css" href="../css/jquery.ibutton.css" rel="stylesheet" media="all" />

<script type="text/javascript" src="../js/jquery.ibutton.js"></script> 
<script type="text/javascript" src="../js/functions.js"></script>
<script type="text/javascript" src="../js/check.js"></script>
<script type="text/javascript" src="/js/float_btn.js"></script>


<?php
//my
$ipaddr = '';
$emuRes = array();
$ipaddr = get_lan_addr();
$localip = getLocalIp();

if($_POST && isset($_POST['send']) && $_POST['send'] == 'Save') {
	save_to_SimProxySvr_conf();
	//echo '<script type="text/javascript">window.location.href = "simproxy-settings.php"</script>';
}
$emuRes = read_SimProxySvr_conf();

function save_to_SimProxySvr_conf(){

	$content = '';
	$file = '/opt/simbank/SimProxySvr/SimProxySvr.conf';
	
	if( !file_exists($file) ){
		echo 'file \''.$file.'\' does not exists.<br /><br />';
		require("../inc/boot.inc");
		die();
	}
	
	
	$aql=new aql;
 	$setok = $aql->set('basedir','/opt/simbank/SimProxySvr');
	if (!$setok) {
 		echo $aql->get_error();
 		return;
 	}
	$hlock = lock_file($file);
	if (!$aql->open_config_file($file)){
		echo $aql->get_error();
		unlock_file($hlock);
		return false;
	}
	
	$db=$aql->query("select * from SimProxySvr.conf" );

	/*
	if( isset($_POST['local_ip']) && ($_POST['local_ip'] != '' ) ){
		$post    = '';
		$post    = trim($_POST['local_ip']);
		$aql->assign_editkey('SimProxySvr','local_ip',$post);		
	}
	//*/

	if( isset($_POST['server_ip']) && ($_POST['server_ip'] != '' ) ){
		$post    = '';
		$post    = trim($_POST['server_ip']);
		$aql->assign_editkey('SimProxySvr','server_ip',$post);		
	}
	//*/
	if( isset($_POST['server_port']) && ($_POST['server_port'] != '' ) ){
		$post    = '';
		$post    = trim($_POST['server_port']);
		$aql->assign_editkey('SimProxySvr','server_port',$post);		
	}
	
	if( isset($_POST['net_hdl_port_php']) && ($_POST['net_hdl_port_php'] != '' ) ){
		$post    = '';
		$post    = trim($_POST['net_hdl_port_php']);
		$aql->assign_editkey('SimProxySvr','net_hdl_port_php',$post);		
	}
	
	if( isset($_POST['php_hdl_ip']) && ($_POST['php_hdl_ip'] != '' ) ){
		$post    = '';
		$post    = trim($_POST['php_hdl_ip']);
		$aql->assign_editkey('PHP','php_hdl_ip',$post);		
	}
	
	if( isset($_POST['php_hdl_port']) && ($_POST['php_hdl_port'] != '' ) ){
		$post    = '';
		$post    = trim($_POST['php_hdl_port']);
		$aql->assign_editkey('PHP','php_hdl_port',$post);		
	}
	
	if (!$aql->save_config_file('SimProxySvr.conf')) {
			echo $aql->get_error();
			unlock_file($hlock);
			return false;
		}
	unlock_file($hlock);
}



function get_lan_addr(){
	$ipaddr = '';
	$aql=new aql;
 	$setok = $aql->set('basedir','/opt/simbank/SimProxySvr');
	if (!$setok) {
 		echo $aql->get_error();
 		return;
 	}
	
	$db=$aql->query("select * from SimProxySvr.conf" );

	
	if(!isset($db['SimProxySvr']['server_ip'])) {
				$aql->assign_append('SimProxySvr','server_ip','');
			} else {
				$ipaddr=$db['SimProxySvr']['server_ip'];
			}
	$aql='';
	return $ipaddr;	
}

function read_SimProxySvr_conf(){
	$content = '';
	$file = '/opt/simbank/SimProxySvr/SimProxySvr.conf';
	
	if( !file_exists($file) ){
		echo 'file \''.$file.'\' does not exists.<br /><br />';
		require("../inc/boot.inc");
		die();
	}
	$aql=new aql;
 	$setok = $aql->set('basedir','/opt/simbank/SimProxySvr');
	if (!$setok) {
 		echo $aql->get_error();
 		return;
 	}
	
	$db=$aql->query("select * from SimProxySvr.conf" );
	
	/*
	if(!isset($db['SimProxySvr']['local_ip'])) {
				//$aql->assign_append('SimProxySvr','local_ip','');
				$aql->assign_append('SimProxySvr','local_ip',getLocalIp());
			} else {
				//$Res['local_ip']=$db['SimProxySvr']['local_ip'];
				$Res['local_ip']=getLocalIp();
			}
	//*/

	
	if(!isset($db['SimProxySvr']['server_ip'])) {
				//$aql->assign_append('SimProxySvr','server_ip','');
				$aql->assign_append('SimProxySvr','server_ip',getLocalIp());
			} else {
				//$Res['server_ip']=$db['SimProxySvr']['server_ip'];
				$Res['server_ip']=getLocalIp();
			}
	//*/
	
	if(!isset($db['SimProxySvr']['server_port'])) {
				$aql->assign_append('SimProxySvr','server_port','6200');
			} else {
				$Res['server_port']=$db['SimProxySvr']['server_port'];
			}
	
	if(!isset($db['SimProxySvr']['net_hdl_port_php'])) {
				$aql->assign_append('SimProxySvr','net_hdl_port_php','6200');
			} else {
				$Res['net_hdl_port_php']=$db['SimProxySvr']['net_hdl_port_php'];
			}
	
	if(!isset($db['PHP']['php_hdl_ip'])) {
				//$aql->assign_append('PHP','php_hdl_ip','127.0.0.1');
				$aql->assign_append('PHP','php_hdl_ip',getLocalIp());
			} else {
				//$Res['php_hdl_ip']=$db['PHP']['php_hdl_ip'];
				$Res['php_hdl_ip']=getLocalIp();
			}
		
	if(!isset($db['PHP']['php_hdl_port'])) {
				$aql->assign_append('PHP','php_hdl_port','5204');
			} else {
				$Res['php_hdl_port']=$db['PHP']['php_hdl_port'];
			}
	
	if (!$aql->save_config_file('SimProxySvr.conf')) {
		echo $aql->get_error();		
		return;
	}	
	exec("kill -9 `pidof SimProxySvr`");	
	return $Res;
	
	
}
?>

	<form enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post" id="emuForm">
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('SimProxy Options');?></li>
		<li class="tb2">&nbsp;</li>
	</div>

	<table width="100%" class="tedit">
		<table width="100%" class="tedit">
		<!--
			<tr>
				<th border="0" style="border:none;">
					<div class="helptooltips">
						<?php echo language('Local IP');?>:
						<span class="showhelp">
						<?php echo language('Local IP'); ?>
						</span>
					</div>
				</th>
				<td border="0" style="border:none;">
					<input type="text" name="local_ip" id="local_ip" value="<?php echo isset($emuRes['local_ip'])?$emuRes['local_ip']:'';?>" />
					<span>本机IP地址</span>
				</td>
			</tr>
		-->
			<tr>
				<th border="0" style="border:none;">
					<div class="helptooltips">
						<?php echo language('SimProxy Server IP');?>:
						<span class="showhelp">
						<?php echo language('SimProxy Server IP'); ?>
						</span>
					</div>
				</th>
				<td border="0" style="border:none;">
					<input type="text" name="server_ip" id="server_ip" value="<?php echo isset($emuRes['server_ip'])?$emuRes['server_ip']:'';?>" />
					<!--<span>SimProxySvr IP Address</span>-->
				</td>
			</tr>
			
			<tr>
				<th border="0" style="border:none;">
					<div class="helptooltips">
						<?php echo language('SimProxy Server Port');?>:
						<span class="showhelp">
							<?php echo language('SimProxy Server Port'); ?>
						</span>
					</div>
				</th>
				<td border="0" style="border:none;">
					<input type="text" name="server_port" id="server_port" value="<?php echo isset($emuRes['server_port'])?$emuRes['server_port']:'';?>" />
					<span>default 6201</span>
				</td>
			</tr>
			<!--<tr>
				<th border="0" style="border:none;">
					<div class="helptooltips">
						<?php echo language('net_hdl_port_php');?>:
						<span class="showhelp">
							<?php echo language('net_hdl_port_php'); ?>
						</span>
					</div>
				</th>
				<td border="0" style="border:none;">
					<input type="text" name="net_hdl_port_php" id="net_hdl_port_php" value="<?php echo isset($emuRes['net_hdl_port_php'])?$emuRes['net_hdl_port_php']:'';?>" />
					<span>SimProxySvr和PHP通讯的端口</span>
				</td>
			</tr>-->	
			
			<!--<tr>
				<th border="0" style="border:none;">
					<div class="helptooltips">
						<?php echo language('php_hdl_ip');?>:
						<span class="showhelp">
							<?php echo language('php_hdl_ip'); ?>
						</span>
					</div>
				</th>
				<td border="0" style="border:none;">
					<input type="text" name="php_hdl_ip" id="php_hdl_ip" value="<?php echo isset($emuRes['php_hdl_ip'])?$emuRes['php_hdl_ip']:'';?>" />
					<span></span>
				</td>
			</tr>-->
			
			<!--<tr>
				<th border="0" style="border:none;">
					<div class="helptooltips">
						<?php echo language('php_hdl_port');?>:
						<span class="showhelp">
							<?php echo language('php_hdl_port'); ?>
						</span>
					</div>
				</th>
				<td border="0" style="border:none;">
					<input type="text" name="php_hdl_port" id="php_hdl_port" value="<?php echo isset($emuRes['php_hdl_port'])?$emuRes['php_hdl_port']:'';?>" />
					<span></span>
				</td>
			</tr>-->
				
		</table>
	</table>
	<br>

	
	<input type="hidden" name="send" id="send" value="" />
	
	<table id="float_btn" class="float_btn">
		<tr id="float_btn_tr" class="float_btn_tr" style="padding-left: 0px;">
			<td>
				<input type="submit"   value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';return check();"/>
			</td>	
			<td>
				<input type=button  value="<?php echo language('Cancel');?>" onclick="window.location.href='<?php echo get_self();?>'" />
			</td>
		</tr>
	</table>
	
	<table id="float_btn2" style="border:none;" class="float_btn2">
		<tr id="float_btn_tr2" class="float_btn_tr2" style="padding-left: 15px;">
			<td style="width:50px">
				<input type="submit" id="float_button_1" class="float_short_button" value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';return check();" />
			</td>
			<td>
				<input type="button" id="float_button_2" class="float_short_button" value="<?php echo language('Cancel');?>" onclick="window.location.href='<?php echo get_self();?>'"/>
			</td>
		</tr>
	</table>
	
	</form>

<?php require("../inc/boot.inc");?>

<script type="text/javascript">
//enable_ami_change();
function enable_ami_change(){
	var sw = document.getElementById('advanced_chkbox').checked;
	if(sw) {
		set_visible('advanced_div', true);		
	} else {
		set_visible('advanced_div', false);
		//document.write("ByeBye World!");			
	}
}

</script>

<?php
	$check_float=1;
	if($check_float == 1){
?>
	<div id="float_btn1" class="sec_float_btn1">
	</div>
	<div  class="float_close" onclick="close_btn()" >
	</div>
<?php	
	}
?>


<script type="text/javascript">
$(document).ready(
	function (){
		//$("#E1_internal_timing").iButton();
		//$("#port1").iButton();
		//$("#port2").iButton();
		$(":checkbox").iButton();

	}
);
</script>
