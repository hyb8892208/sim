<?php
require('/mysql_class.php');
require('../inc/function.inc');
	sleep(420);
do{						//设备线路释放常驻进程	
	sleep(10);
	$simbank_data_release = check_simbank_release();
	if(!isset($simbank_data_release['ob_gw_seri']) || !isset($simbank_data_release['ob_sb_seri']) || $simbank_data_release['ob_sb_seri'] == ''|| $simbank_data_release['ob_gw_seri'] == ''){
		echo 'No Simbank To Release ';
		continue;
	}
	$gateway_data_release = check_gateway_release($simbank_data_release['ob_gw_seri'],$simbank_data_release['ob_gw_link_bank_nbr'],$simbank_data_release['ob_gw_link_slot_nbr']);
	if(!isset($gateway_data_release['ob_gw_seri']) || $gateway_data_release['ob_gw_seri'] == ''){
		echo 'No Gateway To Release';
		continue;
	}
	print_r($simbank_data_release);
	print_r($gateway_data_release);
	$message = array(
		'new_version' => 0001,
		'msgtype' => 0006,
		'msglen' => 74,
		'result' => 0,
		'reserve' => 0,
	);
	
	$pkt = pack("nnnnna10nnVnnH64a10nnVn", $message['new_version'], $message['msgtype'], $message['msglen'],$message['result'],$message['reserve'],$simbank_data_release['ob_sb_seri'],$simbank_data_release['ob_sb_link_bank_nbr'],$simbank_data_release['ob_sb_link_sim_nbr'],$simbank_data_release['n_sb_link_ip'],$simbank_data_release['n_sb_link_port'],$simbank_data_release['n_sb_link_atr_len'],$simbank_data_release['b_sb_link_atr'],$gateway_data_release['ob_gw_seri'],$gateway_data_release['ob_gw_link_bank_nbr'],$gateway_data_release['ob_gw_link_slot_nbr'],$gateway_data_release['n_gw_link_ip'],$gateway_data_release['n_gw_link_port']);
	$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	$len = strlen($pkt);
	socket_sendto($sock, $pkt, $len, 0, getLocalIp(), 5203);					//发送线路释放信息
	socket_close($sock);
	sleep(960);
} while (true);


function check_simbank_release()						//定期检测线路是否需要释放
{
	$db=new mysql();
	$condition = "WHERE n_sb_link_online=0 and n_sb_link_stat=1 ORDER BY n_sb_link_total_time DESC LIMIT 1";//web配置条件，根据什么条件检查线路信息表(1.用时最长)
	$fields = "*";
	$data = $db->Get("tb_simbank_link_info",$fields,$condition);
	$simbank_link_info = mysqli_fetch_array($data,MYSQLI_ASSOC); 
	return $simbank_link_info;
}

function check_gateway_release($ob_gw_seri = '',$ob_gw_link_bank_nbr = '',$ob_gw_link_slot_nbr = '')
{
	$db=new mysql();
	$condition = "LIMIT 1";							//web配置条件，根据什么条件检查线路信息表(1.用时最长)
	if($ob_gw_seri != ''){
		$condition = "where ob_gw_seri = \"$ob_gw_seri\" and ob_gw_link_bank_nbr = \"$ob_gw_link_bank_nbr\" and ob_gw_link_slot_nbr = \"$ob_gw_link_slot_nbr\" LIMIT 1";
	}
	$fields = "*";
	$data = $db->Get("tb_gateway_link_info",$fields,$condition);
	$gw_link_info = mysqli_fetch_array($data,MYSQLI_ASSOC); 
	return $gw_link_info;
}


?>