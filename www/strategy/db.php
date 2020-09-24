<?php
require_once('../php/mysql_class.php');
require('../inc/function.inc');
require_once('../inc/config.inc');

$group_name = "";
if ($argc > 1) {
	$group_name = $argv[1];
}
//
$strategy_conf = get_strategy_conf();
//
$db = new mysql();
//
wakeup_line($db);
// get simbank's sim to be create
$data_sb = get_simbank_establish($db);
//
$data_gw = get_gateway_establish($db);
//
$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
// send create pack to SimProxy
while ($rec_sb = mysql_fetch_assoc($data_sb))
{
	$sb_seri = $rec_sb["ob_sb_seri"];
	$sb_bank = $rec_sb["ob_sb_link_bank_nbr"];
	$sb_sim = $rec_sb["ob_sb_link_sim_nbr"];
	printf("%s-%02d-%02d\n", $sb_seri, $sb_bank, $sb_sim);
}


// update simCard & channel status
//$time_curr = time();
// simbank
//$setval = "n_sb_link_stat=3,n_sb_link_start_time=$time_curr";
//$condition = "where ob_sb_seri=\"$simbank_link_data['ob_sb_seri']\" and ob_sb_link_bank_nbr=$simbank_link_data['ob_sb_link_bank_nbr'] and ob_sb_link_sim_nbr=$simbank_link_data['ob_sb_link_sim_nbr']";
//$db->Set("tb_simbank_link_info", $setval, $condition);
// gateway
//$setval = "n_gw_link_stat=3";
//$condition = "where ob_gw_seri=\"$gateway_link_data['ob_gw_seri']\" and ob_gw_link_bank_nbr=\"$gateway_link_data['ob_gw_link_bank_nbr']\" and ob_gw_link_sim_nbr=\"$gateway_link_data['ob_gw_link_sim_nbr']\"";
//$db->Set("tb_gateway_link_info", $setval, $condition);

/*
// update simbank table
$sb_seri = $simbank_link_data["ob_sb_seri"];
$sb_bank = $simbank_link_data["ob_sb_link_bank_nbr"];
$sb_sim = $simbank_link_data["ob_sb_link_sim_nbr"];
$gw_seri = $gateway_link_data["ob_gw_seri"];
$gw_bank = $gateway_link_data["ob_gw_link_bank_nbr"];
$gw_slot = $gateway_link_data["ob_gw_link_slot_nbr"];
$time_start = strtotime(date("Y-m-d H:i:s"));
$cond = "where ob_sb_seri=\"$sb_seri\" and ob_sb_link_bank_nbr=$sb_bank and ob_sb_link_sim_nbr=$sb_sim";
$setval = "ob_gw_seri=\"$gw_seri\",ob_gw_link_bank_nbr=$gw_bank,ob_gw_link_slot_nbr=$gw_slot,n_sb_link_start_time=\"$time_start\",n_sb_link_call_time=0,n_sb_link_call_counts=0,n_sb_link_stat=3";
$db->Set("tb_simbank_link_info", $setval, $cond);

// update gateway table
$cond = "where ob_gw_seri=\"$gw_seri\" and ob_gw_link_bank_nbr=$gw_bank and ob_gw_link_slot_nbr=$gw_slot";
$setval = "ob_sb_seri=\"$sb_seri\",ob_sb_link_bank_nbr=$sb_bank,ob_sb_link_sim_nbr=$sb_sim,n_gw_link_stat=3";
$db->Set("tb_gateway_link_info", $setval, $cond);

// update log table
$str_len = $simbank_link_data['n_sb_link_atr_len'];
$atr = $simbank_link_data['b_sb_link_atr'];
$fields = "ob_sb_seri,ob_sb_link_bank_nbr,ob_sb_link_sim_nbr,n_sb_link_atr_len,b_sb_link_atr,n_sb_op_type,d_sb_op_timestamp,ob_gw_seri,ob_gw_link_bank_nbr,ob_gw_link_slot_nbr,v_log_desc"; 
$values = "\"$sb_seri\",\"$sb_bank\",\"$sb_sim\",\"$str_len\",\"$atr\",\"1\",\"$time_start\",\"$gw_seri\",\"$gw_bank\",\"$gw_slot\",\"link create\""; 
$data_log = $db->Add("tb_simbank_link_log",$fields,$values); 
*/ 


/*
function update_establish_status($db, $simbank, $gateway)
{
	// update simbank table
	$time_start = strtotime(date("Y-m-d H:i:s"));
	$cond = "where ob_sb_seri=\"$simbank['ob_sb_seri']\" and ob_sb_bank_nbr=\"$simbank['ob_sb_bank_nbr']\" and ob_sb_sim_nbr=\"$simbank['ob_sb_sim_nbr']\"";
	$setval = "ob_gw_seri=\"$gateway['ob_gw_seri']\",ob_gw_link_bank_nbr=\"$gateway['ob_gw_link_bank_nbr']\",ob_gw_link_slot_nbr=\"$gateway['ob_gw_link_slot_nbr']\",n_sb_link_start_time=\"$time_start\",n_sb_link_call_time=0,n_sb_link_stat=3";
	$db->Set("tb_simbank_link_info", $setval, $cond);

	// update gateway table
	$cond = "where ob_gw_seri=\"$gateway['ob_gw_seri']\" and ob_gw_bank_nbr=$gateway['ob_gw_bank_nbr'] and ob_gw_slot_nbr=$gateway['ob_gw_slot_nbr']";
	$setval = "ob_sb_seri=\"$simbank['ob_sb_seri']\",ob_sb_link_bank_nbr=\"$simbank['ob_sb_link_bank_nbr']\",ob_sb_link_sim_nbr=\"$simbank['ob_sb_link_sim_nbr']\",n_gw_link_stat=3";
	$db->Set("tb_simbank_link_info", $setval, $cond);

	// update log table
	$fields = "ob_sb_seri,ob_sb_link_bank_nbr,ob_sb_link_sim_nbr,n_sb_link_atr_len,b_sb_link_atr,n_sb_op_type,d_sb_op_timestamp,ob_gw_seri,ob_gw_link_bank_nbr,ob_gw_link_slot_nbr"; 
    $values = "\"$simbank['ob_sb_seri']\",\"$simbank['ob_sb_link_bank_nbr']\",\"$simbank['ob_sb_link_sim_nbr']\",\"$simbank['n_sb_link_atr_len']\",\"$simbank['b_sb_link_atr']\",\"1\",\"$simbank['d_sb_op_timestamp']\",\"$gateway['ob_gw_seri']\",\"$gateway['ob_gw_link_bank_nbr']\",\"$gateway['ob_gw_link_slot_nbr']\""; 
    $data_log = $db->Add("tb_simbank_link_log",$fields,$values); 
}*/

function get_strategy_conf()
{
	$ret = get_config("/opt/simbank/www/strategy/strategy.conf", NULL);
	return $ret["strategy"];
}
function wakeup_line($db)
{
	global $strategy_conf;
	$time_curr = time();
	$time_sleep = $strategy_conf["duration_sleep"];
	if ($time_sleep <= 0)
	{
		printf("!!! time_sleep is %d, now change to 60\n", $time_sleep);
		$time_sleep = 60;
	}
	// 遍历tb_sb_link_info表，查找处于休眠状态下的sim通道记录集。
	// 对比当前时间和链路断开时间，如果超过休眠时间，则设置该sim通道的状态为就绪(未分配)。
	$result = $db->query("select * from tb_simbank_link_info where n_sb_link_stat=2");
	while ($row = mysql_fetch_assoc($result))
	{
		//printf("curr:%d - last:%d = %d, timeout:%d\n", $time_curr, $row["n_sb_link_last_time"], ($time_curr - $row["n_sb_link_last_time"]), $time_sleep);
		if (($time_curr - $row["n_sb_link_last_time"]) > $time_sleep)
		{
			$seri = $row["ob_sb_seri"];
			$bank = $row["ob_sb_link_bank_nbr"];
			$slot = $row["ob_sb_link_sim_nbr"];
			$setval = "n_sb_link_stat=1";
			$condition = "where ob_sb_seri=\"$seri\" and ob_sb_link_bank_nbr=\"$bank\" and ob_sb_link_sim_nbr=\"$slot\"";
			$db->Set("tb_simbank_link_info", $setval, $condition);
			printf("[%s]establish: wakeup simbank[%s-%02d-%02d], sleep_time[%d], sleep_timeout[%d]\n", \
				date("Y-m-d H:i:s"), $seri, $bank, $slot, $time_curr - $row["n_sb_link_last_time"], $time_sleep);
		}
	}
}
function check_line_establish($db)							//定期检测线路是否需要建立
{
	global $group_name;
	$condition = "";
	//web配置条件，根据什么条件检查线路信息表(1.空闲 2.总时间最少)
	$condition = "WHERE n_sb_link_stat = 1 ORDER BY n_sb_link_total_time LIMIT 1";
	$fields = "*";
	$data = $db->Get("tb_simbank_link_info",$fields,$condition);
	$simbank_link_data = mysqli_fetch_array($data,MYSQLI_ASSOC); 
	return $simbank_link_data;
}
function get_simbank_establish($db)							//定期检测线路是否需要建立
{
	//web配置条件，根据什么条件检查线路信息表(1.空闲 2.总时间最少)
	$result = $db->query("select * from tb_simbank_link_info where n_sb_link_stat = 1 order by n_sb_link_call_total_time desc");
	return $result;
}
function get_gateway_establish($db)							//定期检测线路是否需要建立
{
	//web配置条件，根据什么条件检查线路信息表(1.空闲 2.总时间最少)
	$condition = "WHERE n_gw_link_stat = 1 ORDER BY n_gw_link_last_time";
	$fields = "*";
	return $db->Get("tb_gateway_link_info",$fields,$condition);
}
?>

