<?php
include_once("../inc/network_factory.inc");
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require('../inc/mysql_class.php');
include_once("../inc/wrcfg.inc");
include_once("../inc/aql.php");
include_once("../inc/define.inc");
include_once("../inc/language.inc");
?>


<script type="text/javascript" src="/js/functions.js"></script>
<script type="text/javascript" src="/js/check.js"></script>

<?php 
function save_OPlink(){
	$aql = new aql();
	$setok = $aql->set('basedir', '/mnt/config/simbank/network/');
	if(!$setok){
		echo $aql->get_error();
		return false;
	}
	
	$lan_conf_path = "/mnt/config/simbank/network/lan.conf";
	
	if(!file_exists($lan_conf_path)){
		fclose(fopen($lan_conf_path));
	}
	
	$hlock = lock_file($lan_conf_path);
	
	if(!$aql->open_config_file($lan_conf_path)){
		echo $aql->get_error();
		unlock_file($hlock);
		return false;
	}
	
	$res = $aql->query("select * from lan.conf");
	
	if(!isset($res['OPlink'])){
		$aql->assign_addsection('OPlink', '');
	}
	
	if(isset($_POST['switch'])){
		$switch = 'on';
	}else{
		$switch = 'off';
	}
	
	if(isset($res['OPlink']['switch'])){
		$aql->assign_editkey('OPlink', 'switch', $switch);
	}else{
		$aql->assign_append('OPlink', 'switch', $switch);
	}
	
	if(isset($res['OPlink']['node_id'])){
		$aql->assign_editkey('OPlink', 'node_id', '1');
	}else{
		$aql->assign_append('OPlink', 'node_id', '1');
	}
	
	if(isset($res['OPlink']['seri'])){
		$aql->assign_editkey('OPlink', 'seri', $_POST['simbank_serial_number']);
	}else{
		$aql->assign_append('OPlink', 'seri', $_POST['simbank_serial_number']);
	}
	
	if($_POST['node_servers'] == 'china'){
		$node_servers = 'oplink-cn01.openvox.cn:1024';
	}else if($_POST['node_servers'] == 'america'){
		$node_servers = 'oplink-us01.openvox.cn:1024';
	}else if($_POST['node_servers'] == 'europe'){
		$node_servers = 'oplink-eur01.openvox.cn:1024';
	}else{
		$customize_server = $_POST['customize_server'];
		if(strstr($customize_server, '://')){
			$temp = explode("://", $customize_server);
			$temp = explode('/' ,$temp[1]);
			$node_servers = $temp[0];
		}else{
			$temp = explode('/' ,$customize_server);
			$node_servers = $temp[0];
		}
		$node_servers = trim($node_servers);
	}
	if(isset($res['OPlink']['node_servers'])){
		$aql->assign_editkey('OPlink', 'node_servers', $node_servers);
	}else{
		$aql->assign_append('OPlink', 'node_servers', $node_servers);
	}
	
	$aql->save_config_file('lan.conf');
	
	unlock_file($hlock);
	
	save_to_simrdrsvr_conf();
	clean_up_OPlink_status();
	
	wait_apply("exec", "sh /etc/init.d/OPlink restart > /dev/null 2>&1 &");
}

function save_to_simrdrsvr_conf(){
	$aql = new aql();
	$setok = $aql->set('basedir', '/mnt/config/simbank/network/');
	if(!$setok){
		echo $aql->get_error();
		return false;
	}

	$lan_res = get_conf("/mnt/config/simbank/network/lan.conf");
	
	$ipv4_ip = $lan_res['ipv4']['ipaddr'];
	
	if(!isset($_POST['switch'])){
		$aql=new aql;
		$file = '/mnt/config/simbank/conf/SimRdrSvr.conf';
		$setok = $aql->set('basedir','/mnt/config/simbank/conf');
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
		
		$db=$aql->query("select * from SimRdrSvr.conf" );
		
		if(isset($db['SimRdrSvr']['net_mode'])){
			$aql->assign_editkey('SimRdrSvr','net_mode','normal');
		}else{
			$aql->assign_append('SimRdrSvr','net_mode','normal');
		}
		
		if(isset($db['SimRdrSvr']['local_ip'])){
			$aql->assign_editkey('SimRdrSvr','local_ip',$ipv4_ip);
		}else{
			$aql->assign_append('SimRdrSvr','local_ip',$ipv4_ip);
		}
		
		$aql->save_config_file('SimRdrSvr.conf');
		
		unlock_file($hlock);
	}
}

function clean_up_OPlink_status(){
	if(file_exists('/tmp/OPlink.status')){
		file_put_contents('/tmp/OPlink.status', '');
	}
}

if(isset($_POST['send']) && $_POST['send'] == 'Save'){
	save_OPlink();
}



$aql = new aql();
$setok = $aql->set('basedir', '/mnt/config/simbank/network/');
if(!$setok){
	echo $aql->get_error();
	return false;
}

$res = get_conf("/mnt/config/simbank/network/lan.conf");

if(isset($res['OPlink']['switch']) && $res['OPlink']['switch'] == 'on'){
	$switch = 'checked';
}else{
	$switch = '';
}

if(isset($res['OPlink']['node_servers'])){
	$node_servers = $res['OPlink']['node_servers'];
}else{
	$node_servers = '';
}
if($node_servers == 'oplink-cn01.openvox.cn:1024'){
	$server_mode = 'china';
}else if($node_servers == 'oplink-us01.openvox.cn:1024'){
	$server_mode = 'america';
}else if($node_servers == 'oplink-eur01.openvox.cn:1024'){
	$server_mode = 'europe';
}else{
	$server_mode = 'customize';
}

$aql=new aql;
$setok = $aql->set('basedir','/mnt/config/simbank/conf');
if (!$setok) {
	echo $aql->get_error();
	return;
}

$db=$aql->query("select * from SimRdrSvr.conf" );
	
if(isset($db['SimRdrSvr']['seri'])) {
	$simbank_serial_number = $db['SimRdrSvr']['seri'];
} else {
	$simbank_serial_number = '';
}
?>
<script type="text/javascript" src="/js/jquery.ibutton.js"></script> 
<link type="text/css" href="/css/jquery.ibutton.css" rel="stylesheet" media="all" />

<form enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">

	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('OPlink Settings');?></li>
		<li class="tb2">&nbsp;</li>
	</div>
	
	<table width="100%" class="tedit" >	
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Switch');?>:
					<span class="showhelp">
					<?php echo language('Switch help','');?>
					</span>
				</div>
			</th>
			<td>
				<input type="checkbox" name="switch" id="switch" <?php echo $switch;?> />
			</td>
		</tr>
		
		<tr id="field_lan_ipaddr" class="switch_show">
			<th>
				<div class="helptooltips">
					<?php echo language('Node ID');?>:
					<span class="showhelp">
					<?php echo language('Node ID help','');?>
					</span>
				</div>
			</th>
			<td >
				1
			</td>
		</tr>
		
		<tr class="switch_show">
			<th><?php echo language('Simbank Serial Number');?>:</th>
			<td >
				<?php echo $simbank_serial_number;?>
				<input type="hidden" name="simbank_serial_number" value="<?php echo $simbank_serial_number;?>" />
			</td>
		</tr>
		
		<tr class="switch_show">
			<th><?php echo language('OPlink Server Node');?>:</th>
			<td >
				<select name="node_servers" id="node_servers" onchange="server_change()">
					<option value="china" <?php if($server_mode == 'china') echo 'selected';?>><?php echo language('China');?></option>
					<option value="america" <?php if($server_mode == 'america') echo 'selected';?>><?php echo language('America');?></option>
					<option value="europe" <?php if($server_mode == 'europe') echo 'selected';?>><?php echo language('Europe');?></option>
					<option value="customize" <?php if($server_mode == 'customize') echo 'selected';?>><?php echo language('Customize');?></option>
				</select>
				<input style="margin-left:10px;" size="30px" type="text" name="customize_server" id="customize_server" value="<?php echo $node_servers;?>" />
			</td>
		</tr>
	</table>

	<br/>
	
	<div class="switch_show">
		<div id="tab">
			<li class="tb1">&nbsp;</li>
			<li class="tbg">
				<div class="helptooltips">
					<?php echo language('Connection Status');?>
					<span class="showhelp">
					<?php echo language('Connection Status help','');?>  
					</span>
				</div>
			</li>
			<li class="tb2">&nbsp;</li>
		</div>
			
		<table width="100%" class="tedit" >
			<tr>
				<th><?php echo language('Connection Status');?>:</th>
				<td id="connection_status">
				
				</td>
			</tr>
		</table>
	</div>
	
	<br/>
	
	<input type="hidden" name="send" id="send" value="" />
	<table id="float_btn" class="float_btn">
		<tr id="float_btn_tr" class="float_btn_tr">
			<td>
				<input type="submit"   value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';return check();" />
			</td>
		</tr>
	</table>
</form>

<script>
$("#switch").iButton();

if(document.getElementById('switch').checked){
	$(".switch_show").show();
}else{
	$(".switch_show").hide();
}

$("#switch").change(function(){
	if($(this).attr('checked') == 'checked'){
		$(".switch_show").show();
	}else{
		$(".switch_show").hide();
	}
});

var time = 0;
function connection_status(){
	$.ajax({
		type:'GET',
		url: '/simproxy/ajax_server_simbank.php?action=connection_status',
		success: function(data){
			<?php if(isset($_POST['send']) && $_POST['send'] == 'Save'){?>
			if(data.indexOf('success') != -1){
				$("#connection_status").html("<span style='color:green'><?php echo language('Connected');?></span>");
			}else if(data == '' && time < 10){
				setTimeout('connection_status()',1000);
				time++;
				$("#connection_status").html('<img src="/images/loading.gif"/>');
			}else if(time >= 10){
				$("#connection_status").html("<span style='color:red'><?php echo language('Connect Timeout');?></span>");
			}else{
				$("#connection_status").html("<span style='color:red'><?php echo language('Connect Failed');?></span>");
			}
			<?php }else{?>
			if(data.indexOf('success') != -1){
				$("#connection_status").html("<span style='color:green'><?php echo language('Connected');?></span>");
			}else{
				$("#connection_status").html("<span style='color:red'><?php echo language('Connect Failed');?></span>");
			}
			<?php }?>
		}
	});
}

connection_status();

function check(){
	var ipv4 = '<?php echo $res['ipv4']['ipaddr']; ?>';
	var netmask = '<?php echo $res['ipv4']['netmask']; ?>';
	
	var ipv4_temp = ipv4.split('.');
	var netmask_temp = netmask.split('.');
	
	var ipv4_netmask_str = (ipv4_temp[0]&netmask_temp[0])+'.'+(ipv4_temp[1]&netmask_temp[1])+'.'+(ipv4_temp[2]&netmask_temp[2]);
	var default_netmask_str = (10&netmask_temp[0])+'.'+(150&netmask_temp[1])+'.'+(210&netmask_temp[2]);
	
	if(document.getElementById('switch').checked){
		if(ipv4_netmask_str == default_netmask_str){
			alert("<?php echo language('Connect OPlink help','The current network environment does not support OPlink. Please modify the IP segment and subnet mask before using it.');?>");
			return false;
		}
	}
	
	return true;
}

function server_change(){
	var server_mode = $("#node_servers").val();
	var old_server_mode = "<?php echo $server_mode; ?>";
	var node_servers = "<?php echo $node_servers; ?>";
	
	if(server_mode != 'customize'){
		$("#customize_server").hide();
	}else{
		if(old_server_mode == 'customize'){
			$("#customize_server").val(node_servers);
		}else{
			$("#customize_server").val('');
		}
		$("#customize_server").show();
	}
}
server_change();
</script>
		
<?php require("../inc/boot.inc");?>