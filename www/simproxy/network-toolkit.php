<?php 
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require_once("../inc/wrcfg.inc");
?>

<?php 
$aql = new aql();

$res = get_conf("/mnt/config/simbank/network/lan.conf");

$ip = [];
if(isset($res['ipv4']['ipaddr'])){
	array_push($ip,$res['ipv4']['ipaddr']);
}

if(isset($res['p2p']['switch']) && $res['p2p']['switch'] == 'on'){
	array_push($ip,$res['p2p']['ipaddr']);
}

array_push($ip,'192.168.99.1');
?>

<table width="100%" class="tedit">
	<tr class="ttitle">
		<td>
			<div class="helptooltips">
				IP
				<span class="showhelp">
					<?php echo language('Network Toolkit IP help');?>
				</span>
			</div>
			<select id="select_ip" name="select_ip">
				<?php 
				for($i=0;$i<count($ip);$i++){
				?>
				<option value="<?php echo $ip[$i];?>"><?php echo $ip[$i];?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<input type="text" id="ping_hostname" name="ping_hostname" value="<?php echo "google.com";?>" />
			<input type="button" value="<?php echo language('Ping');?>" onclick="get_ping_traceroute_content('ping');"/>
		</td>
	</tr>
	<tr>
		<td>
			<input type="text" id="tracert_hostname" name="tracert_hostname" value="<?php echo "google.com";?>" />
			<input type="button" value="<?php echo language('Traceroute');?>" onclick="get_ping_traceroute_content('traceroute');"/>
		</td>
	</tr>
</table>
<br>

<div id="ping_back"></div>

<script>
function checkValue(value){
	if(value == "") {
		alert("Please input a domain or IP address\n");
		return false;
	}

	return true;
}

function get_ping_traceroute_content(type){
	if(type == 'ping'){
		var hostname = document.getElementById('ping_hostname').value;
		var ok_info = "<font color=008800>Successfully ping [ "+hostname+" ] .</font>";
	}else{
		var hostname = document.getElementById('tracert_hostname').value;
		var ok_info = "<font color=008800>Successfully traceroute [ "+hostname+" ] .</font>";
	}
	
	var select_ip = document.getElementById('select_ip').value;
	
	if(checkValue(hostname)){
		$.ajax({
			url: "/simproxy/ajax_server_new.php?action=ping_and_traceroute",
			type: "POST",
			data: {
				'type':type,
				'select_ip':select_ip,
				'hostname':hostname
			},
			success:function(data){
				$("#ping_back").html(data);
				get_ping_log(type,ok_info);
				$("#ping_back tr:eq(3) td").html("<img src='/images/mini_loading.gif' />");
			}
		});
	}
}

function get_ping_log(type,ok_info){
	$.ajax({
		url: "/simproxy/ajax_server_new.php?action=get_ping_log&type="+type,
		type: "GET",
		success:function(data1){
			$("#ping_back tr:eq(1) td").html('<pre>'+data1+'</pre>');
			
			if(data1.indexOf("ping_success") != -1){
				$("#ping_back tr:eq(3) td").html(ok_info);
			}else{
				setTimeout(get_ping_log,2000,type,ok_info);
			}
		},
		error:function(data){
			$("#ping_back tr:eq(1) td").html('error');
		}
	});
}
</script>

<?php require("../inc/boot.inc"); ?>