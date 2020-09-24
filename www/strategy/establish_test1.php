<?php
require_once('../php/mysql_class.php');
require('../inc/function.php');
$group_name = "";
if ($argc > 1) {
	$group_name = $argv[1];
}

$db=new mysql();//('172.16.6.103','root','','simserver',"gbk","pconn");
$simbank_link_data = check_line_establish($db);

echo "\n\n\n";
echo "groupname=".$group_name."\n";
echo "simbank_link_data['ob_sb_seri']=[" . $simbank_link_data['ob_sb_seri'] . "]\n";
echo "simbank_link_data['ob_gw_seri']=[" . $simbank_link_data['ob_gw_seri'] . "]\n";

if(!isset($simbank_link_data['ob_sb_seri']) || $simbank_link_data['ob_sb_seri'] == ''){
	echo "No Simbank To Establishment\n\n\n";
	return;
}

$gateway_link_data = match_strategy($db, $simbank_link_data);
if(!isset($gateway_link_data['ob_gw_seri']) || $gateway_link_data['ob_gw_seri'] == ''){
	echo "No Gateway To Establishment\n\n\n";
	return;
}
$message = array(
	"new_version"=> 0001,
	"msgtype" => 0005,
	"msglen" => 76,
	"result" => 0,
	"reserve" => 0
);
$pkt = pack("nnnnna10nnnVnnH64a10nnVn", $message['new_version'], $message['msgtype'], $message['msglen'],$message['result'],$message['reserve'],$simbank_link_data['ob_sb_seri'],$simbank_link_data['ob_sb_link_usb_nbr'],$simbank_link_data['ob_sb_link_bank_nbr'],$simbank_link_data['ob_sb_link_sim_nbr'],$simbank_link_data['n_sb_link_ip'],$simbank_link_data['n_sb_link_port'],$simbank_link_data['n_sb_link_atr_len'],$simbank_link_data['b_sb_link_atr'],$gateway_link_data['ob_gw_seri'],$gateway_link_data['ob_gw_link_bank_nbr'],$gateway_link_data['ob_gw_link_slot_nbr'],$gateway_link_data['n_gw_link_ip'],$gateway_link_data['n_gw_link_port']);

$cksum = checksum($pkt);
$pkt = pack("a*n", $pkt, $cksum);
$len = strlen($pkt);
$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
socket_sendto($sock, $pkt, $len, 0, getLocalIp(), 5203);					//发送线路建立信息
socket_close($sock);

function check_line_establish($db)							//定期检测线路是否需要建立
{
	global $group_name;
	$condition = "";
	//web配置条件，根据什么条件检查线路信息表(1.空闲 2.总时间最少)
	if ($group_name == "") {
		$condition = "WHERE n_sb_link_stat = 0 ORDER BY n_sb_link_total_time LIMIT 1";
	}
	else {
		$condition = "WHERE ob_sb_seri IN 
	(SELECT ob_sb_seri FROM tb_simbank_info WHERE ob_grp_name='$group_name') AND n_sb_link_stat = 0 ORDER BY n_sb_link_total_time LIMIT 1";							
	}
	$fields = "*";
	$data = $db->Get("tb_simbank_link_info",$fields,$condition);
	$simbank_link_data = mysqli_fetch_array($data,MYSQLI_ASSOC); 
	return $simbank_link_data;
}

function check_gateway_info($db, $condition = '')
{
	$fields = "*";
	$data = $db->Get("tb_gateway_link_info",$fields,$condition);
	$gateway_link_data = mysqli_fetch_array($data,MYSQLI_ASSOC); 
	return $gateway_link_data;
}

function match_strategy($db,$simbank_link_data = '')
{	
	$ob_gw_seri = $simbank_link_data['ob_gw_seri'];
	$ob_sb_seri = $simbank_link_data['ob_sb_seri'];
	
	//echo "need establish sb_seri=" . $ob_sb_seri . "   " . "gw_seri=" . $ob_gw_seri . "\n";
	
	if ($ob_gw_seri != '') //simbank线路被配对过
	{
		/* 
		array("//第一步 优先查询配对过的网关线路不同位置不同组的网关线路",
			  "//第二步 查询配对过的网关线路同位置的其他组的网关线路",
			  "//第三步 查询配对过的网关线路同位置的同组的网关线路");
		*/
		$condition = array("WHERE ob_gw_seri IN (SELECT A.ob_gw_seri FROM tb_gateway_info AS A,(SELECT ob_grp_name,ob_loc_id FROM tb_gateway_info WHERE ob_gw_seri='$ob_gw_seri')B WHERE A.ob_grp_name != B.ob_grp_name AND A.ob_loc_id != B.ob_loc_id) AND n_gw_link_stat=0 LIMIT 1",
						   "WHERE ob_gw_seri IN (SELECT A.ob_gw_seri FROM tb_gateway_info AS A,(SELECT ob_grp_name,ob_loc_id FROM tb_gateway_info WHERE ob_gw_seri='$ob_gw_seri')B WHERE A.ob_grp_name != B.ob_grp_name AND A.ob_loc_id = B.ob_loc_id) AND n_gw_link_stat=0 LIMIT 1",
						   "WHERE ob_gw_seri IN (SELECT A.ob_gw_seri FROM tb_gateway_info AS A,(SELECT ob_grp_name,ob_loc_id FROM tb_gateway_info WHERE ob_gw_seri='$ob_gw_seri')B WHERE A.ob_grp_name = B.ob_grp_name AND A.ob_loc_id = B.ob_loc_id) AND n_gw_link_stat=0 LIMIT 1");
	}
	else				  //simbank线路未被配对过,处于初始状态
	{
		/* 
		array("//第一步 查询与simbank线路同组的网关线路",
			  "//第二步 查询与simbank线路所在组位置相同的其他组的网关线路",
			  "//第三步 查询与simbank线路所在组位置不同的网关线路");
		*/	
		$condition = array("WHERE ob_gw_seri IN (SELECT ob_gw_seri FROM tb_gateway_info WHERE ob_grp_name = (SELECT ob_grp_name FROM tb_simbank_info WHERE ob_sb_seri='$ob_sb_seri')) AND n_gw_link_stat = 0 LIMIT 1",
						   "WHERE ob_gw_seri IN (SELECT ob_gw_seri FROM tb_gateway_info WHERE ob_grp_name != (SELECT ob_grp_name FROM tb_simbank_info WHERE ob_sb_seri='$ob_sb_seri')) AND n_gw_link_stat = 0 LIMIT 1",
						   "WHERE n_gw_link_stat = 0 LIMIT 1");
	}
	$len = count($condition);
	for ($x = 0; $x < $len; $x++)
	{
		$gateway_link_data = check_gateway_info($db,$condition[$x]);
		//echo "condition=" . $condition[$x] . "\n\n";
		if(isset($gateway_link_data['ob_gw_seri']) && $gateway_link_data['ob_gw_seri'] != '')
		{
			echo "match gw_seri=" . $gateway_link_data['ob_gw_seri'] . "\n";
			return $gateway_link_data;
		}
	}
	
	return $gateway_link_data;
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
