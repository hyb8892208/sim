<?php
require_once('/opt/simbank/www/inc/mysql_class.php');
require_once('/opt/simbank/www/inc/function.inc');
require_once('/opt/simbank/www/inc/config.inc');
$group_name = "";
$release_time = 300;
if ($argc > 1) {
	$group_name = $argv[1];
}

$strategy_conf = get_strategy_conf();

simbank_release_handler();


function get_strategy_conf()
{
	$ret = get_config("/opt/simbank/www/strategy/strategy.conf", NULL);
	return $ret["strategy"];
}

function simbank_release_handler()						//定期检测线路是否需要释放
{
	global $group_name;
	global $release_time;
	global $strategy_conf;

	$db=new mysql();
	$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	
	$time_curr = time();
	$condition = "";
	if ($strategy_conf["mode"] == 1)
	{
		$timeout = $strategy_conf["duration_online"];
		$condition = "WHERE n_sb_link_stat=3 AND n_sb_link_call_time >= $timeout";
	}
	else
	{
		$cnt = $strategy_conf["counts"];
		$condition = "WHERE n_sb_link_stat=3 AND n_sb_link_call_counts >= $cnt";
	}
	
	$fields = "*";
	$data = $db->Get("tb_simbank_link_info",$fields,$condition);
	while($simbank_data_release = mysqli_fetch_array($data,MYSQLI_ASSOC)) { 
		if(!isset($simbank_data_release['ob_gw_seri']) || !isset($simbank_data_release['ob_sb_seri']) || $simbank_data_release['ob_sb_seri'] == ''|| $simbank_data_release['ob_gw_seri'] == ''){
			//echo "No Simbank To Release\n";
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

		
		$sb_seri = $simbank_data_release["ob_sb_seri"];
		$sb_bank = $simbank_data_release["ob_sb_link_bank_nbr"];
		$sb_sim  = $simbank_data_release["ob_sb_link_sim_nbr"];
		$gw_seri = $gateway_data_release["ob_gw_seri"];
		$gw_bank = $gateway_data_release["ob_gw_link_bank_nbr"];
		$gw_slot = $gateway_data_release["ob_gw_link_slot_nbr"];
		printf("[%s]Release: Send linkRelease(gateway[%s-%02d-%02d] <--> simbank[%s-%02d-%02d]) msg to SimProxySvr\n", \
		date("Y-m-d H:i:s"), $gw_seri, $gw_bank, $gw_slot,$sb_seri, $sb_bank, $sb_sim);
				
		
		/*
		$sb_seri = $simbank_data_release["ob_sb_seri"];
		$sb_bank = $simbank_data_release["ob_sb_link_bank_nbr"];
		$sb_sim  = $simbank_data_release["ob_sb_link_sim_nbr"];
		$gw_seri = $gateway_data_release["ob_gw_seri"];
		$gw_bank = $gateway_data_release["ob_gw_link_bank_nbr"];
		$gw_slot = $gateway_data_release["ob_gw_link_slot_nbr"];
		//获取总使用时间，开始使用时间和最后使用时间 
		$condition_time = "WHERE ob_sb_seri = \"$sb_seri\" and ob_sb_link_bank_nbr = $sb_bank and ob_sb_link_sim_nbr = $sb_sim LIMIT 1"; 
		$fields_time = "n_sb_link_total_time,n_sb_link_start_time,n_sb_link_last_time"; 
		$data_time = $db->Get("tb_simbank_link_info",$fields_time,$condition_time); 
		$time_info = mysql_fetch_array($data_time); 
		 
		//更新simbank线路信息表 
		$last_time = strtotime(date("Y-m-d H:i:s")); 
		$total_time = 0; 
		if (isset($time_info) && isset($time_info['n_sb_link_total_time']) && isset($time_info['n_sb_link_start_time'])) { 
			$total_time = $time_info['n_sb_link_total_time'] + $last_time - $time_info['n_sb_link_start_time']; 
		} 
		//$condition_sb_link = "WHERE ob_sb_seri = \"$sb_seri\" and ob_gw_seri=\"$gw_seri\" and ob_gw_link_bank_nbr=\"$gw_bank\" and ob_gw_link_slot_nbr=\"$gw_slot\"";			 
		$condition_sb_link = "WHERE ob_sb_seri = \"$sb_seri\" and ob_sb_link_bank_nbr = $sb_bank and ob_sb_link_sim_nbr = $sb_sim";			 
		$fields_sb_link = "n_sb_link_last_time=\"$last_time\",n_sb_link_total_time=\"$total_time\",n_sb_link_stat=2"; 
		$data = $db->Set("tb_simbank_link_info",$fields_sb_link,$condition_sb_link); 
		 
		//更新gateway线路信息表 
		$condiction_gw_link="WHERE ob_gw_seri=\"$gw_seri\" and ob_gw_link_bank_nbr=$gw_bank and ob_gw_link_slot_nbr=$gw_slot"; 
		$data_gw=$db->Set("tb_gateway_link_info","n_gw_link_stat=1,n_gw_link_last_time = \"$last_time\"",$condiction_gw_link); // gateway's channel not sleep
		 
		//更新日志信息 
		$n_sb_link_atr_len = $simbank_data_release['n_sb_link_atr_len']; 
		$b_sb_link_atr = $simbank_data_release['b_sb_link_atr']; 
		$d_sb_op_timestamp = $last_time; 
		$fields = "ob_sb_seri,ob_sb_link_bank_nbr,ob_sb_link_sim_nbr,n_sb_link_atr_len,b_sb_link_atr,n_sb_op_type,d_sb_op_timestamp,ob_gw_seri,ob_gw_link_bank_nbr,ob_gw_link_slot_nbr,v_log_desc"; 
	    $values = "\"$sb_seri\",\"$sb_bank\",\"$sb_sim\",\"$n_sb_link_atr_len\",\"$b_sb_link_atr\",\"2\",\"$d_sb_op_timestamp\",\"$gw_seri\",\"$gw_bank\",\"$gw_slot\",\"link release\""; 
	    $db->Add("tb_simbank_link_log",$fields,$values); 
	    */


		/*
		// update simbank's sim status
		$setval = "n_sb_link_stat=2,n_sb_link_call_counts=0";
		$seri = $simbank_data_release['ob_sb_seri'];
		$bank = $simbank_data_release['ob_sb_link_bank_nbr'];
		$slot = $simbank_data_release['ob_sb_link_sim_nbr'];
		$ucond = "where ob_sb_seri=\"$seri\" and ob_sb_link_bank_nbr=\"$bank\" and ob_sb_link_sim_nbr=\"$slot\"";
		$db->Set("tb_simbank_link_info", $setval, $ucond);
		// update gateway's channel status
		$setval = "n_gw_link_stat=1";
		
		$seri = $gateway_data_release['ob_gw_seri'];
		$bank = $gateway_data_release['ob_gw_link_bank_nbr'];
		$slot = $gateway_data_release['ob_gw_link_slot_nbr'];
		$ucond = "where ob_gw_seri=\"$seri\" and ob_gw_link_bank_nbr=\"$bank\" and ob_gw_link_slot_nbr=\"$slot\"";
		$db->Set("tb_gateway_link_info", $setval, $ucond);
		
		//echo $gateway_data_release['ob_sb_seri'];
		//echo $gateway_data_release['ob_sb_link_bank_nbr'];
		//echo $gateway_data_release['ob_sb_link_sim_nbr'];
		//$udcon = "where ob_sb_seri=\"".$gateway_data_release['ob_sb_seri']."\" and ob_sb_link_bank_nbr=\"".$gateway_data_release['ob_sb_link_bank_nbr']."\" and ob_sb_link_sim_nbr=\"".$gateway_data_release['ob_sb_link_sim_nbr']."\"";
		//$db->Set("tb_simbank_link_info", "ob_gw_seri=\"\",ob_gw_link_bank_nbr=\"\",ob_gw_link_slot_nbr=\"\"", $udcon);
		*/
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
