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

<script type="text/javascript" src="/js/jquery.ibutton.js"></script> 
<link type="text/css" href="/css/jquery.ibutton.css" rel="stylesheet" media="all" />
<?php
//AQL
$aql = new aql();

$setok = $aql->set('basedir','/config/simbank/network');
if (!$setok) {
	echo __LINE__.' '.$aql->get_error();
	exit;
}
?>

<?php
if($_POST && isset($_POST['send']) && $_POST['send'] == 'Save') {
	save_to_lan_conf();
	save_to_dns_conf();
	//exec("asterisk -rx \"dnsmgr refresh\" > /dev/null 2>&1 &");
}

function save_to_lan_conf()
{
	if(isset($_POST['lan_type'])) {
		$_type = $_POST['lan_type'];
	} else {
		$_type = 'factory';
	}
	
	if($_type == 'factory'){
		global $__FACTORY_LAN_IP__;
		global $__FACTORY_LAN_MASK__;
		global $__FACTORY_LAN_GW__;
		global $__FACTORY_LAN_MAC__;
		
		//$factory_mac = trim(file_get_contents('/tmp/.lanfactorymac'));
		if(isset($_POST['lan_mac'])) {
			$_mac = $_POST['lan_mac'];
		} else {
			$_mac = $__FACTORY_LAN_MAC__;
		}
		
		//$factory_mac = $_POST["lan_mac"];
		$slot_num=1;
		//$slot_num = get_slotnum();
		
		$factory_ip = str_replace('X',$slot_num,$__FACTORY_LAN_IP__);
		$factory_mask = $__FACTORY_LAN_MASK__;
		$factory_gw = $__FACTORY_LAN_GW__;
	
		//$_mac = $factory_mac;
		$_ipaddr = $factory_ip;
		$_netmask = $factory_mask;
		$_gateway = $factory_gw;
	}else{
		if(isset($_POST['lan_ipaddr'])) {
			$_ipaddr = $_POST['lan_ipaddr'];
		} else {
			$_ipaddr = '';
		}

		if(isset($_POST['lan_netmask'])) {
			$_netmask = $_POST['lan_netmask'];
		} else {
			$_netmask = '';
		}

		if(isset($_POST['lan_gateway'])) {
			$_gateway = $_POST['lan_gateway'];
		} else {
			$_gateway = '';
		}
	}
	
	if(isset($_POST['reserved_sw'])) {
		$_reserved_sw = 'on';
	} else {
		$_reserved_sw = 'off';
	}
	
	exec("ifconfig |grep HWaddr|awk '{print $5}' |head -1", $_mac);            
	$_mac = $_mac[0];

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
	
	if(!isset($res['general'])){
		$aql->assign_addsection('general', '');
	}
	
	if(isset($res['general']['type'])){
		$aql->assign_editkey('general', 'type', $_type);
	}else{
		$aql->assign_append('general', 'type', $_type);
	}
	
	if(isset($res['general']['mac'])){
		$aql->assign_editkey('general', 'mac', "");
	}else{
		$aql->assign_append('general', 'mac', "");
	}
	
	if(!isset($res['ipv4'])){
		$aql->assign_addsection('ipv4', '');
	}
	
	if(isset($res['ipv4']['ipaddr'])){
		$aql->assign_editkey('ipv4', 'ipaddr', $_ipaddr);
	}else{
		$aql->assign_append('ipv4', 'ipaddr', $_ipaddr);
	}
	
	if(isset($res['ipv4']['netmask'])){
		$aql->assign_editkey('ipv4', 'netmask', $_netmask);
	}else{
		$aql->assign_append('ipv4', 'netmask', $_netmask);
	}
	
	if(isset($res['ipv4']['gateway'])){
		$aql->assign_editkey('ipv4', 'gateway', $_gateway);
	}else{
		$aql->assign_append('ipv4', 'gateway', $_gateway);
	}
	
	if(!isset($res['reserved'])){
		$aql->assign_addsection('reserved', '');
	}
	
	if(isset($res['reserved']['switch'])){
		$aql->assign_editkey('reserved', 'switch', $_reserved_sw);
	}else{
		$aql->assign_append('reserved', 'switch', $_reserved_sw);
	}
	
	$aql->save_config_file('lan.conf');
	
	unlock_file($hlock);
	
	//gatway
	//$result=shell_exec("ip route add default via $_gateway dev eth0");
	$cmd="sudo ip route add default via $_gateway dev eth0";
	exec($cmd);
	
	//重启网络
	wait_apply("exec", "/etc/init.d/lan start > /dev/null 2>&1 &");
	//exec("sudo /etc/init.d/networking restart > /dev/null 2>&1 &");	
	//exec("sudo sh /usr/sbin/network_restart.sh");
	
}

function save_to_dns_conf()
{
	global $__BRD_HEAD__;
	global $__BRD_SUM__;

	if( isset($_POST['dns1']) &&
		isset($_POST['dns2']) &&
		isset($_POST['dns3']) &&
		isset($_POST['dns4'])
		) { //Save data to config file
		
		$_dns1 = trim($_POST['dns1']);
		$_dns2 = trim($_POST['dns2']);
		$_dns3 = trim($_POST['dns3']);
		$_dns4 = trim($_POST['dns4']);

		$write = "[general]\n";
		$write .= "dns1=$_dns1\n";
		$write .= "dns2=$_dns2\n";
		$write .= "dns3=$_dns3\n";
		$write .= "dns4=$_dns4\n";

		$cfg_file = "/config/simbank/network/dns.conf";
		$hlock = lock_file($cfg_file);
		$fh=fopen($cfg_file,"w");
		fwrite($fh,$write);
		fclose($fh);
		unlock_file($hlock);

		$write = "";
		if($_dns1 != "") $write .= "nameserver $_dns1\n";
		if($_dns2 != "") $write .= "nameserver $_dns2\n";
		if($_dns3 != "") $write .= "nameserver $_dns3\n";
		if($_dns4 != "") $write .= "nameserver $_dns4\n";

		//print_rr($write);
		$cfg_file = "/etc/resolv.conf";
		$hlock = lock_file($cfg_file);
		$fh=fopen($cfg_file,"w");
		fwrite($fh,$write);
		fclose($fh);
		unlock_file($hlock);

		}
	
	//wait_apply("exec", "/etc/init.d/asterisk restart > /dev/null 2>&1 &");	
}
?>

<?php
$type['dhcp'] = "";
$type['static'] = "";
$type['factory'] = "";

$reserved_sw = '';

/*
$hlock = lock_file('/config/simbank/network/lan.conf');
$res=$aql->query("select * from lan.conf");
unlock_file($hlock);
*/
$res = get_conf("/config/simbank/network/lan.conf");

exec("ifconfig |grep HWaddr|awk '{print $5}' |head -1", $cf_mac);            
$cf_mac = $cf_mac[0];

if(isset($res['general']['type'])) {
	$cf_type=trim($res['general']['type']);
	$type["$cf_type"] = 'selected';
} else {
	$cf_type="";
	$type['factory'] = 'selected';
}

if(isset($res['ipv4']['ipaddr'])) {
	$cf_ip=trim($res['ipv4']['ipaddr']);
} else {
	$cf_ip="";
}

if(isset($res['ipv4']['netmask'])) {
	$cf_mask=trim($res['ipv4']['netmask']);
} else {
	$cf_mask="";
}

if(isset($res['ipv4']['gateway'])) {
	$cf_gw=trim($res['ipv4']['gateway']);
} else {
	$cf_gw="";
}

if(isset($res['reserved']['switch'])) {
	if(is_true(trim($res['reserved']['switch']))) {
		$reserved_sw = 'checked';
	}
}

exec("ifconfig |grep HWaddr|awk '{print $5}' |head -1", $_mac);
$factory_mac = $_mac[0];
$slot_num = get_slotnum();
$factory_ip = str_replace('X',$slot_num,$__FACTORY_LAN_IP__);
$factory_mask = $__FACTORY_LAN_MASK__;
$factory_gw = $__FACTORY_LAN_GW__;

$reserved_ip = str_replace('X',$slot_num,$__RESERVED_LAN_IP__);
$reserved_mask = $__RESERVED_LAN_MASK__;

/* Get DNS data */
$hlock = lock_file('/config/simbank/network/dns.conf');
$res=$aql->query("select * from dns.conf");
unlock_file($hlock);

if(isset($res['general']['dns1'])) {
	$cf_dns1=trim($res['general']['dns1']);
} else {
	$cf_dns1="";
}

if(isset($res['general']['dns2'])) {
	$cf_dns2=trim($res['general']['dns2']);
} else {
	$cf_dns2="";
}

if(isset($res['general']['dns3'])) {
	$cf_dns3=trim($res['general']['dns3']);
} else {
	$cf_dns3="";
}

if(isset($res['general']['dns4'])) {
	$cf_dns4=trim($res['general']['dns4']);
} else {
	$cf_dns4="";
}
?>

<script type="text/javascript" src="/js/functions.js"></script>
<script type="text/javascript" src="/js/check.js"></script>
<script type="text/javascript">

function typechange()
{	
	var type = document.getElementById('lan_type').value;

	if(type == 'factory') {
		set_visible('field_lan_ipaddr', true);
		set_visible('field_lan_netmask', true);
		set_visible('field_lan_gateway', true);
		
		var traget=document.getElementById('div_ip');  
		traget.style.display="";

		obj = document.getElementById('lan_mac');
		obj.disabled = 'disabled';
		//obj.value = "<?php echo $factory_mac;?>";
		obj.value = "<?php if($cf_mac)echo $cf_mac;else echo $factory_mac;?>";
		
		obj = document.getElementById('lan_ipaddr');
		obj.disabled = 'disabled';
		obj.value = "<?php echo $factory_ip;?>";

		obj = document.getElementById('lan_netmask');
		obj.disabled = 'disabled';
		obj.value = "<?php echo $factory_mask;?>";

		obj = document.getElementById('lan_gateway');
		obj.disabled = 'disabled';


		obj.value = "<?php echo $factory_gw;?>";
	} else if (type == 'static') {
		//set_visible('div_ip', false);
		var traget=document.getElementById('div_ip');  
		traget.style.display="";
		
		set_visible('field_lan_ipaddr', true);
		set_visible('field_lan_netmask', true);
		set_visible('field_lan_gateway', true);

		obj = document.getElementById('lan_mac');
		obj.disabled = 'disabled';
		//obj.value = "<?php echo $factory_mac;?>";
		obj.value = "<?php if($cf_mac)echo $cf_mac;else echo $factory_mac;?>";
		
		/*
		obj = document.getElementById('lan_mac');
		obj.readOnly = false;
		obj.disabled = '';
		obj.value = "<?php if($cf_mac)echo $cf_mac;else echo $factory_mac;?>";
		*/
		
		obj = document.getElementById('lan_ipaddr');
		obj.readOnly = false;
		obj.disabled = '';
		obj.value = "<?php echo $cf_ip;?>";

		obj = document.getElementById('lan_netmask');
		obj.readOnly = false;
		obj.disabled = '';
		obj.value = "<?php echo $cf_mask;?>";

		obj = document.getElementById('lan_gateway');
		obj.readOnly = false;
		obj.disabled = '';
		obj.value = "<?php echo $cf_gw;?>";
	} else {//DHCP
	/*
		//set_visible('div_ip', false);		
		//set_visible('field_lan_ipaddr', false);
		//set_visible('field_lan_netmask', false);
		//0set_visible('field_lan_gateway', false);
		
		var traget=document.getElementById('div_ip');  
		traget.style.display="none";  

		
		
		obj = document.getElementById('lan_mac');
		obj.readOnly = 'true';
		obj.style.backgroundColor="#E0E0E0";
		obj.style.display="none";  
		//obj.value = "<?php echo $factory_mac;?>";
		obj.value = "<?php if($cf_mac)echo $cf_mac;else echo $factory_mac;?>";
		
		// obj = document.getElementById('lan_mac');
		// obj.readOnly = false;
		// obj.disabled = '';
		// obj.value = "<?php if($cf_mac)echo $cf_mac;else echo $factory_mac;?>";
		
		obj = document.getElementById('lan_ipaddr');
		obj.disabled = 'disabled';
		obj.value = "<?php echo $factory_ip;?>";

		obj = document.getElementById('lan_netmask');
		obj.disabled = 'disabled';
		obj.value = "<?php echo $factory_mask;?>";

		obj = document.getElementById('lan_gateway');
		obj.disabled = 'disabled';
		obj.value = "<?php echo $factory_gw;?>";*/
	}
}

function reservedswchange()
{
	var sw = document.getElementById('reserved_sw').checked;

	if(sw) {
		$("#lan_adv").slideDown();
	} else {
		$("#lan_adv").slideUp();
	}
}

function onload_func()
{
	typechange();
	reservedswchange();
}

function check()
{
	var lan_type = document.getElementById("lan_type").value;

	var lan_mac = document.getElementById("lan_mac").value;
	var lan_ipaddr = document.getElementById("lan_ipaddr").value;
	var lan_netmask = document.getElementById("lan_netmask").value;
	var lan_gateway = document.getElementById("lan_gateway").value;

	if(lan_type == 'static') {
		if(!check_ip(lan_ipaddr)) {
			document.getElementById("clan_ipaddr").innerHTML = con_str('<?php echo language('js check ip','Please input a valid IP address');?>');
			return false;
		} else {
			document.getElementById("clan_ipaddr").innerHTML = '';
		}

		if(!check_ip(lan_netmask)) {
			document.getElementById("clan_netmask").innerHTML = con_str('<?php echo language('js check ip','Please input a valid IP address');?>');
			return false;
		} else {
			document.getElementById("clan_netmask").innerHTML = '';
		}
	
		if(lan_gateway!="" && !check_ip(lan_gateway)) {
			document.getElementById("clan_gateway").innerHTML = con_str('<?php echo language('js check ip','Please input a valid IP address');?>');
			return false;
		} else {
			document.getElementById("clan_gateway").innerHTML = '';
		}
	}

	if(!checkDNS()){
		return false;
	}

	return true;
}

function checkDNS()
{
	var dns1 = document.getElementById("dns1").value;
	var dns2 = document.getElementById("dns2").value;
	var dns3 = document.getElementById("dns3").value;
	var dns4 = document.getElementById("dns4").value;

	if(dns1 != '') {
		if(!check_ip(dns1)) {
			document.getElementById("cdns1").innerHTML = con_str('<?php echo language('js check ip','Please input a valid IP address');?>');
			return false;
		} else {
			document.getElementById("cdns1").innerHTML = '';
		}
	}

	if(dns2 != '') {
		if(!check_ip(dns2)) {
			document.getElementById("cdns2").innerHTML = con_str('<?php echo language('js check ip','Please input a valid IP address');?>');
			return false;
		} else {
			document.getElementById("cdns2").innerHTML = '';
		}
	}

	if(dns3 != '') {
		if(!check_ip(dns3)) {
			document.getElementById("cdns3").innerHTML = con_str('<?php echo language('js check ip','Please input a valid IP address');?>');
			return false;
		} else {
			document.getElementById("cdns3").innerHTML = '';
		}
	}

	if(dns4 != '') {
		if(!check_ip(dns4)) {
			document.getElementById("cdns4").innerHTML = con_str('<?php echo language('js check ip','Please input a valid IP address');?>');
			return false;
		} else {
			document.getElementById("cdns4").innerHTML = '';
		}
	}

	return true;
}

</script>

	<form enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">

	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('LAN IPv4');?></li>
		<li class="tb2">&nbsp;</li>
	</div>

	<table width="100%" class="tedit" >
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Interface');?>:
					<span class="showhelp">
					<?php echo language('Interface help','The name of network interface.');?>
					</span>
				</div>
			</th>
			<td >
				eth0							
			</td>
		</tr>
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Type');?>:
					<span class="showhelp">
					<?php echo language('Type help@network-lan','
						The method to get IP.<br/>
						Factory: Getting IP address by Slot Number(System-->Information to check slot number).<br/>
						Static: manually set up your gateway IP.');
					?>
					</span>
				</div>
			</th>
			<td >
				<select id="lan_type" name="lan_type" onchange="typechange()">
					<option  value="factory" <?php echo $type['factory'];?> ><?php echo language('Factory');?></option>
					<option  value="static" <?php echo $type['static'];?> ><?php echo language('Static');?></option>
					<!--<option  value="dhcp" <?php echo $type['dhcp'];?> ><?php echo language('DHCP');?></option>-->
				</select>
			</td>
		</tr>				
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('MAC');?>:
					<span class="showhelp">
					<?php echo language('MAC help','Physical address of your network interface.');?>
					</span>
				</div>
			</th>
			<td >
				<input id="lan_mac" type="text" name="lan_mac" value="<?php echo $cf_mac;?>" />
				<span id="clan_mac"></span>
			</td>
		</tr>
	</table>

	<br>
	<div id="div_ip">
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('IPv4 Settings');?></li>
		<li class="tb2">&nbsp;</li>
	</div>
	<table width="100%" class="tedit" >
		<tr id="field_lan_ipaddr" >
			<th>
				<div class="helptooltips">
					<?php echo language('Address');?>:
					<span class="showhelp">
					<?php echo language('Address help','The IP address of your simbank.');?>
					</span>
				</div>
			</th>
			<td >
				<input id="lan_ipaddr" type="text" name="lan_ipaddr" value="<?php echo $cf_ip;?>" /><span id="clan_ipaddr"></span>
			</td>
		</tr>
		<tr id="field_lan_netmask" >
			<th>
				<div class="helptooltips">
					<?php echo language('Netmask');?>:
					<span class="showhelp">
					<?php echo language('Netmask help','The subnet mask of your gateway.');?>
					</span>
				</div>
			</th>
			<td >
				<input id="lan_netmask" type="text" name="lan_netmask" value="<?php echo $cf_mask;?>" /><span id="clan_netmask"></span>
			</td>
		</tr>
		<tr id="field_lan_gateway" >
			<th>
				<div class="helptooltips">
					<?php echo language('Default Gateway');?>:
					<span class="showhelp">
					<?php echo language('Default Gateway help','Default gateway IP addrress.');?>
					</span>
				</div>
			</th>
			<td >
				<input id="lan_gateway" type="text" name="lan_gateway" value="<?php echo $cf_gw;?>" /><span id="clan_gateway"></span>
			</td>
		</tr>
	</table>
	</div>
	<br>

	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg">
			<div class="helptooltips">
				<?php echo language('DNS Servers');?>
				<span class="showhelp">
				<?php echo language('DNS Servers help','
					A list of DNS IP address. <br/>
					Basically this info is from your local network service provider. ');
				?>  
				</span>
			</div>
		</li>
		<li class="tb2">&nbsp;</li>
	</div>

	<table width="100%" class="tedit" >
		<tr>
			<th><?php echo language('DNS Server');?> 1:</th>
			<td >
				<input type="text" name="dns1" id="dns1" value="<?php echo $cf_dns1;?>" /><span id="cdns1"></span>
			</td>
		</tr>
		<tr>
			<th><?php echo language('DNS Server');?> 2:</th>
			<td >
				<input type="text" name="dns2" id="dns2" value="<?php echo $cf_dns2;?>" /><span id="cdns2"></span>
			</td>
		</tr>
		<tr>
			<th><?php echo language('DNS Server');?> 3:</th>
			<td >
				<input type="text" name="dns3" id="dns3" value="<?php echo $cf_dns3;?>" /><span id="cdns3"></span>
			</td>
		</tr>
		<tr>
			<th><?php echo language('DNS Server');?> 4:</th>
			<td >
				<input type="text" name="dns4" id="dns4" value="<?php echo $cf_dns4;?>" /><span id="cdns4"></span>
			</td>
		</tr>
	</table>

	<br>
	
	<div id="tab" style="height:32px">
		<li class="tb1">&nbsp;</li>
		<li class="tbg">
			<div class="helptooltips">
				<?php echo language('Reserved Access IP');?>
				<span class="showhelp">
				<?php echo language('Reserved Access IP help','
					A reserved IP address to access in case your gateway IP is not available.<br/>
					Remember to set a similar network segment with the following address of your local PC.');
				?>
				</span>
			</div>
		</li>
		<li class="tb2">&nbsp;</li>
	</div>
	
	<div width="100%" class="div_setting_c">
		<div class="divc_setting_v">
			<table width='100%' class="tedit" style="border:none">
				<tr>
					<th>
						<div class="helptooltips">
							<?php echo language('Enable');?>:
							<span class="showhelp">
							<?php echo language('Enable help@network-lan','
								A switch to enable the reserved IP address or not.<br/>
								On(enabled),Off(disabled)');
							?>
							</span>
						</div>
					</th>
					<td >
						<input type="checkbox" id="reserved_sw" name="reserved_sw" onchange="reservedswchange()" <?php echo $reserved_sw ?> />
					</td>
				</tr>
			</table>
		</div>
		
		<div id='lan_adv' class='div_setting_d' style="position:relative;top:-2px;">
			<table width='100%' class="tedit" style="border:none">
				<tr id="reserved_ip_tr">
					<th>
						<div class="helptooltips">
							<?php echo language('Reserved Address');?>:
							<span class="showhelp">
							<?php echo language('Reserved Address help','The reserved IP address for this gateway.');?>
							</span>
						</div>
					</th>
					<td >
						<input id="reserved_ip" type="text" name="reserved_ip" value="<?php echo $reserved_ip;?>" readOnly disabled />
					</td>
				</tr>
				
				<tr id="reserved_mask_tr">
					<th>
						<div class="helptooltips">
							<?php echo language('Reserved Netmask');?>:
							<span class="showhelp">
							<?php echo language('Reserved Netmask help','The subnet mask of the reserved IP address.');?>
							</span>
						</div>
					</th>
					<td >
						<input id="reserved_mask" type="text" name="reserved_mask" value="<?php echo $reserved_mask;?>" readOnly disabled />
					</td>
				</tr>
			</table>
		</div>
	</div>
	
	<br>

	<input type="hidden" name="send" id="send" value="" />
	<table id="float_btn" class="float_btn">
		<tr id="float_btn_tr" class="float_btn_tr">
			<td>
				<input type="submit"   value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';return check();" />
			</td>
		</tr>
	</table>
	<table id="float_btn2" style="border:none;" class="float_btn2">
		<tr id="float_btn_tr2" class="float_btn_tr2">
			<td width="20px">
				<input type="submit" id="float_button_1" class="float_short_button" value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';return check();" />
			</td>
		</tr>
	</table>
</form>


<script type="text/javascript"> 
$(document).ready(function (){ 
	$(":checkbox").iButton();
	onload_func();
});
</script>

<?php require("../inc/boot.inc");?>
<!--
<div id="float_btn1" class="float_btn1 sec_float_btn1">
</div>
-->
<div  class="float_close" onclick="close_btn()">
</div>
