<?php
require_once('../php/mysql_class.php');
$group_name = "";
$release_time = 300;
if ($argc > 1) {
	$group_name = $argv[1];
}
simbank_release_handler();

function simbank_release_handler()						//定期检测线路是否需要释放
{
	global $group_name;
	global $release_time;
	$db=new mysql();
	$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	if ($group_name == "") {
		$condition = "WHERE n_sb_link_online=0 AND n_sb_link_stat=1 AND n_sb_link_call_time>" . $release_time;//web配置条件，根据什么条件检查线路信息表(1.本次通话时间超过某个数值)
	}
	else {
		$condition = "WHERE ob_sb_seri IN 
	(SELECT ob_sb_seri FROM tb_simbank_info WHERE ob_grp_name='$group_name') AND  n_sb_link_online=0 AND n_sb_link_stat=1 AND n_sb_link_call_time>" . $release_time;
	}
	$fields = "*";
	$data = $db->Get("tb_simbank_link_info",$fields,$condition);
	
	while($simbank_data_release = mysqli_fetch_array($data,MYSQLI_ASSOC)) { 
		if(!isset($simbank_data_release['ob_gw_seri']) || !isset($simbank_data_release['ob_sb_seri']) || $simbank_data_release['ob_sb_seri'] == ''|| $simbank_data_release['ob_gw_seri'] == ''){
			echo "No Simbank To Release\n";
			continue;
		}
		$gateway_data_release = check_gateway_release($db, $simbank_data_release['ob_gw_seri'],$simbank_data_release['ob_gw_link_bank_nbr'],$simbank_data_release['ob_gw_link_slot_nbr']);
		if(!isset($gateway_data_release['ob_gw_seri']) || $gateway_data_release['ob_gw_seri'] == ''){
			echo "No Gateway To Release\n";
			continue;
		}
		$message = array(
			'new_version' => 0001,
			'msgtype' => 0006,
			'msglen' => 76,
			'result' => 0,
			'reserve' => 0,
		);
		
		$pkt = pack("nnnnna10nnnVnnH64a10nnVn", $message['new_version'], $message['msgtype'], $message['msglen'],$message['result'],$message['reserve'],$simbank_data_release['ob_sb_seri'],$simbank_data_release['ob_sb_link_usb_nbr'],$simbank_data_release['ob_sb_link_bank_nbr'],$simbank_data_release['ob_sb_link_sim_nbr'],$simbank_data_release['n_sb_link_ip'],$simbank_data_release['n_sb_link_port'],$simbank_data_release['n_sb_link_atr_len'],$simbank_data_release['b_sb_link_atr'],$gateway_data_release['ob_gw_seri'],$gateway_data_release['ob_gw_link_bank_nbr'],$gateway_data_release['ob_gw_link_slot_nbr'],$gateway_data_release['n_gw_link_ip'],$gateway_data_release['n_gw_link_port']);
			
		$cksum = checksum($pkt);
		$pkt = pack("a*n", $pkt, $cksum);
		$len = strlen($pkt);
		
		socket_sendto($sock, $pkt, $len, 0, getLocalIp(), 5203);					//发送线路释放信息
	}
	socket_close($sock);
}

function check_gateway_release($db, $ob_gw_seri = '',$ob_gw_link_bank_nbr = '',$ob_gw_link_slot_nbr = '')
{
	$condition = "LIMIT 1";	
	if($ob_gw_seri != ''){
		$condition = "where ob_gw_seri = \"$ob_gw_seri\" and ob_gw_link_bank_nbr = \"$ob_gw_link_bank_nbr\" and ob_gw_link_slot_nbr = \"$ob_gw_link_slot_nbr\" LIMIT 1";
	}
	$fields = "*";
	$data = $db->Get("tb_gateway_link_info",$fields,$condition);
	$gw_link_info = mysqli_fetch_array($data,MYSQLI_ASSOC); 
	return $gw_link_info;
}

function checksum($buf)
{
	$cksum = 0;
	$data = unpack("S*", $buf);
	
	foreach($data as $value)
	{
		$cksum += $value;
	}
	while($cksum >> 16)
		$cksum = ($cksum >> 16) + ($cksum & 0xffff);
	return (~$cksum);
}
?>