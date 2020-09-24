<?php
require('mysql_class.php');
require('../inc/function.inc');
//sleep(60);
do{						//设备线路建立常驻进程
	//sleep(5);
	$simbank_link_data = check_line_establish();
	if(!isset($simbank_link_data['ob_sb_seri']) || $simbank_link_data['ob_sb_seri'] == ''){
		//echo 'No Simbank To Establishment';
		continue;
	}
	$gateway_link_data = check_gateway_info();
	if(!isset($gateway_link_data['ob_gw_seri']) || $gateway_link_data['ob_gw_seri'] == ''){
		//echo 'No Gateway To Establishment';
		continue;
	}
	$message = array(
		"new_version"=> 0001,
		"msgtype" => 0005,
		"msglen" => 74,
		"result" => 0,
		"reserve" => 0
	);
	// mod by hlzheng20150915
	$len_tmp = $simbank_link_data['n_sb_link_vgsm_len'] * 2;
	$pkt = pack("nnnnna10nnVnnH64a10nnVnnH".$len_tmp, $message['new_version'], $message['msgtype'], $message['msglen'],$message['result'],$message['reserve'],$simbank_link_data['ob_sb_seri'],$simbank_link_data['ob_sb_link_bank_nbr'],$simbank_link_data['ob_sb_link_sim_nbr'],$simbank_link_data['n_sb_link_ip'],$simbank_link_data['n_sb_link_port'],$simbank_link_data['n_sb_link_atr_len'],$simbank_link_data['b_sb_link_atr'],$gateway_link_data['ob_gw_seri'],$gateway_link_data['ob_gw_link_bank_nbr'],$gateway_link_data['ob_gw_link_slot_nbr'],$gateway_link_data['n_gw_link_ip'],$gateway_link_data['n_gw_link_port'],$simbank_link_data['n_sb_link_vgsm_len'],$simbank_link_data['n_sb_link_vgsm']);

	$fp = fopen("pack_link_create.dat", "w");
        fwrite($fp, $pkt);
        fclose($fp);

	$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	$len = strlen($pkt);
	socket_sendto($sock, $pkt, $len, 0, getLocalIp(), 5203);					//发送线路建立信息
	socket_close($sock);
	sleep(360);
	
	checkRelease();
} while (true);

function checkRelease(){
	sleep(5);
	//线路释放代码
	$simbank_data_release = check_simbank_release();
	if(!isset($simbank_data_release['ob_gw_seri']) || !isset($simbank_data_release['ob_sb_seri']) || $simbank_data_release['ob_sb_seri'] == ''|| $simbank_data_release['ob_gw_seri'] == ''){
		echo 'No Simbank To Release ';
		checkRelease();
	}
	$gateway_data_release = check_gateway_release($simbank_data_release['ob_gw_seri'],$simbank_data_release['ob_gw_link_bank_nbr'],$simbank_data_release['ob_gw_link_slot_nbr']);
	if(!isset($gateway_data_release['ob_gw_seri']) || $gateway_data_release['ob_gw_seri'] == ''){
		echo 'No Gateway To Release';
		checkRelease();
	}
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
	sleep(30);
}

function check_line_establish()							//定期检测线路是否需要建立
{
	$db=new mysql();
	$condition = 'WHERE n_sb_link_online = 0 ORDER BY n_sb_link_total_time LIMIT 1';							//web配置条件，根据什么条件检查线路信息表(1.空闲 2.总时间最少)
	$fields = "*";
	$data = $db->Get("tb_simbank_link_info",$fields,$condition);
	$simbank_link_data = mysqli_fetch_array($data,MYSQLI_ASSOC); 
	return $simbank_link_data;
}

function check_gateway_info()
{
	$db=new mysql();
	$condition = 'where n_gw_link_stat = 0 and ob_gw_link_bank_nbr = 0 LIMIT 1';							//web配置条件
	$fields = "*";
	$data = $db->Get("tb_gateway_link_info",$fields,$condition);
	$gateway_link_data = mysqli_fetch_array($data,MYSQLI_ASSOC); 
	return $gateway_link_data;
}

function check_simbank_release()						//定期检测线路是否需要释放
{
	$db=new mysql();
	$condition = "where n_sb_link_stat=1 LIMIT 1";							//web配置条件，根据什么条件检查线路信息表(1.用时最长)
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
