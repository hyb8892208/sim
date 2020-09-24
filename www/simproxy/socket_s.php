#!/usr/bin/php
<?php 
require_once('mysql_class.php'); 
require_once('../inc/function.inc');
declare (ticks = 1); 
 
//最大进程数, 必须大于2 
//一个用于组策略脚本，另一个用于处理消息 
$max_child = 1;  
$message_queue_key = ftok(__FILE__, 'a'); 
$strategy_path = "../strategy"; 
 
echo "running simbank_php_daemon, localip:".getLocalIp()."\n"; 
simbank_php_daemon(); 
 
function debug_time_print() 
{ 
	$time = explode(" ", microtime());   
	$time = $time[1] . $time[0];   
	return $time; 
} 
 
$report_line_count = 0; 
 
function debug_msg_print($pkt)  
{ 
	$message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/C*",$pkt); 
	 
	if(!check_pkt($message,'packet')) { 
		echo 'sock_daemon: Invalid message packet\n'; 
		continue; 
	}
	echo "version: ".$message['version'].",  msgtype: ".$message['msgtype'].",  msglen: ".$message['msglen'].",  result: ".$message['result'].",  reserve: ".$message['reserve']."\n";
	/*if ($message['msgtype'] == 4) { 
		global $report_line_count; 
		$report_line_count++; 
		$message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/C10device_serial_num/n1device_bank_num/n1device_slot_nbr/V1device_ip/n1device_port/n1device_atr_long/H64device_line_atr",$pkt);	 
		echo "[" . debug_time_print() . "] recv report line message [" . $message['device_bank_num'] . "-" . $message['device_slot_nbr'] . "][" . $report_line_count . "]\n";	 
	} */
} 
 
function simbank_php_daemon() 
{ 
	global $message_queue_key; 
	//echo "key=".$message_queue_key."\n";
	//$message_queue = msg_get_queue($message_queue_key, 0666); 
	$socket = socket_create( AF_INET, SOCK_DGRAM, SOL_UDP); 
	if (!$socket) { 
		die("$errstr ($errno)"); 
	} 
	 
	$ok = socket_bind($socket, getLocalIp(), 12346); 
	//$ok = socket_bind($socket, 'localhost', 12346);
	if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1)) { 
  	  echo 'Unable to set option on socket: '. socket_strerror(socket_last_error()) . PHP_EOL; 
	} 
	 
	create_child_process($socket); 
	do {
		//socket_recvfrom($socket, $pkt, 8192, 0, $from, $port); 
		socket_recvfrom($socket, $pkt, 65535, 0, $from, $port);
		if(!$pkt){ 
			 echo "sock_daemon: recvieve error!\n"; 
			 continue; 
		} 
		 
		//debug_msg_print($pkt);
		$message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/C*",$pkt);
		$msg_len = 10 + $message['msglen'] + 2;
		//echo "simbank_php_daemon: receive package[len:".$msg_len."] header: [".$message['version']."-".$message['msgtype']."-".$message['msglen']."-".$message['result']."-".$message['reserve']."]\n";
		//msg_send($message_queue, 1, $pkt);
		$message_queue = msg_get_queue($message_queue_key, 0666);
		if (!msg_send ($message_queue, 1, $pkt, true, false, $msg_err))
				echo "Msg not sent because $msg_err\n";
		
		echo "simbank_php_daemon: msg_send return\n"; 		
	} while (true); 
} 
 
 
function create_child_process($socket) 
{ 
	global $max_child; 
 
	if ($max_child < 2) { 
		$max_child = 2; 
	} 
 
	for ($i = 0; $i < $max_child; $i++) { 
		$pid = pcntl_fork(); 
		if ($pid == 0) { 
			if ($i == 0) { 
				strategy_daemon(); 
			} 
			else { 
				handle_message($socket,getLocalIp(),5203); 
				//handle_message($socket,'localhost',5203);
			} 
		} 
	} 
} 
 
function handle_message($socket,$from,$port) 
{
	global $message_queue_key; 
	$db=new mysql(getLocalIp(),'root','','simserver',"gbk","pconn"); 
	//$db=new mysql('localhost','root','','simserver',"gbk","pconn");
	do {
		$message_queue = msg_get_queue($message_queue_key, 0666); 
		msg_receive($message_queue, 0, $message_type, 65535, $pkt, true, 0, $msg_error); 
		//msg_receive($message_queue, 0, $message_type, 8192, $pkt, true, 0, $msg_error); 
		if (!$pkt || $pkt == "") {
			echo "handle_message: msg_receive return NULL\n";
			continue; 
		}
		$message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/C*",$pkt); 
		 
		if(!check_pkt($message,'packet')) { 
			echo 'sock_daemon: Invalid message packet\n'; 
			continue; 
		}
		$msg_len = 10 + $message['msglen'] + 2;
		echo "handle_message: receive package[len:".$msg_len."] header: [".$message['version']."-".$message['msgtype']."-".$message['msglen']."-".$message['result']."-".$message['reserve']."]\n";
		$respong_pkt = ""; 
		switch($message['msgtype']){ 
			case 0x0001:
				if(register_user($db, $pkt)){     	//true为注册成功，false为注册失败
					echo "sock_daemon: Equipment registration success\n"; 
					$respong_pkt = registration_responds($pkt,'success'); 
				} else { 
					echo "sock_daemon: Equipment registration failed\n"; 
					$respong_pkt = registration_responds($pkt,'failed'); 
				} 
				break; 
			case 0x0002: 
				if(loginout_user($db, $pkt)){     	//true为注销成功，false为注销失败 
					echo "sock_daemon: Equipment login out successfully\n"; 
					$respong_pkt = loginout_responds($pkt,'success'); 
				} else { 
					echo "sock_daemon: Equipment login out failed\n"; 
					$respong_pkt = loginout_responds($pkt,'failed'); 
				} 
				break; 
			case 0x0003: 
				if(heartbeat_detection($db, $pkt)){     	//true为更新心跳时间戳成功，false为失败 
					echo "sock_daemon: Equipment update hearbeat successfully\n"; 
					$respong_pkt = heartbeat_responds($pkt,'success'); 
				} else { 
					echo "sock_daemon: Equipment update hearbeat failed\n"; 
					$respong_pkt = heartbeat_responds($pkt,'failed'); 
				} 
				break; 
			case 0x0004:
				if(report_line($db, $pkt)){     	//true为线路上报成功，false为线路上报失败 
					//echo "sock_daemon: Equipment line report successfully\n"; 
					$respong_pkt = report_line_responds($pkt,'success'); 
				} else { 
					//echo "sock_daemon: Equipment line report failed\n"; 
					$respong_pkt = report_line_responds($pkt,'failed'); 
				} 
				break; 
			case 0x0201: 
				if(disconnect_line($db, $pkt)){     	//线路断开 
					echo "sock_daemon: Equipment line disconnect successfully\n"; 
					$respong_pkt = disconnect_line_responds($pkt,"success"); 
				} else { 
					echo "sock_daemon: Equipment line disconnect failed\n"; 
					$respong_pkt = disconnect_line_responds($pkt,"failed"); 
				}
				run_strategy('release');
				run_strategy('establish'); 
				break; 
			case 0x0211: 
				if(interrupt_line($db, $pkt)){     	//线路中断 
					echo "sock_daemon: Equipment line interrupt successfully\n"; 
					$respong_pkt = interrupt_line_responds($pkt,"success"); 
				} else { 
					echo "sock_daemon: Equipment line interrupt failed\n"; 
					$respong_pkt = interrupt_line_responds($pkt,"failed"); 
				}					 
				run_strategy('release');
				run_strategy('establish'); 
				break; 
			case 0x1005:		 
				//建立线路 
				if($message['result'] == 0) { 
					$up_sb_info = update_simbank_info_establish($db, $pkt);     //若成功，更新日志和simbank信息 
					if($up_sb_info){ 
						echo "sock_daemon: line establish success\n"; 
					} else { 
						echo "sock_daemon: line establish failed...\n"; 
					} 
				} else { 
					echo "sock_daemon: line establish failed\n"; 
				} 
				break; 
			case 0x1006:														//释放线路消息 
				if($message['result'] == 0) { 
					$up_sb_info = update_simbank_info_release($db, $pkt);     //若成功，更新日志和simbank信息 
					if($up_sb_info){ 
						echo "sock_daemon: line release success\n"; 
					} else { 
						echo "sock_daemon: line release failed...\n"; 
					} 
				} else { 
					echo "sock_daemon: line release failed\n"; 
				} 
				run_strategy('release');
				break; 
			case 0x0101:													//开始呼叫消息 
				if (start_call_handler($pkt)) { 
					echo "sock_daemon: start call handler success!\n"; 
				} 
				else { 
					echo "sock_daemon: start call handler failed!\n"; 
				} 
				break; 
			case 0x0102:													//结束呼叫消息 
				if (end_call_handler($pkt)) { 
					echo "sock_daemon: end call handler success!\n"; 
				} 
				else { 
					echo "sock_daemon: end call handler failed!\n"; 
				}		 
				run_strategy('release');
				break; 
			default: 
				break; 
		} 
		$len = strlen($respong_pkt); 
		if ($len > 0) { 
			socket_sendto($socket, $respong_pkt, $len, 0, $from, $port); 
		} 
	}while(true); 
} 
 
function check_pkt($message,$packet='',$register='',$loginout='',$heartbeat='',$report_line='',$line_establish='',$line_release=''){ 
	/* 
	if($packet == 'packet'){ 
		if(isset($message['msgtype']) && isset($message['version']) && isset($message['msglen'])){ 
			return true; 
		} else { 
			return false; 
		} 
	} 
	if($register == 'register' || $loginout == 'loginout' || $heartbeat == 'heartbeat'){ 
		if(isset($message['device_type']) && isset($message['device_serial_num']) && isset($message['device_bank_num'])&& isset($message['device_lines_num'])){ 
			return true; 
		} else { 
			return false; 
		} 
	} 
	if($report_line == 'reportLine'){ 
		if(isset($message['ob_sb_seri']) && isset($message['device_serial_num']) && isset($message['ob_sb_link_sim_nbr'])){ 
			return true; 
		} else { 
			return false; 
		} 
	} 
	*/ 
	return true; 
} 
 
function register_user($db, $pkt)									//设备注册消息体 
{ 
    $message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/C10device_serial_num/n1device_bank_num/n1device_lines_num/C64device_password",$pkt); 
    if(!check_pkt($message,'','register')) { 
    	echo 'Invalid message packet\n'; 
    	return; 
    } 
	$deviceSerialNum = ''; 
	for($serial_num = 1 ; $serial_num < 11 ; $serial_num++){ 
		$deviceSerialNum .= chr($message["device_serial_num$serial_num"]);   //将ASCII码转换成字符串 
	} 
	$deviceSerialNum = trim($deviceSerialNum); 
	$devicePassword = ''; 
	for($password_num = 1 ; $password_num < 65 ; $password_num++){ 
		$devicePassword .= chr($message["device_password$password_num"]);    //将ASCII码转换成字符串 
	} 
	$devicePassword = trim($devicePassword); 
	 
	/* 
	*tb_simbank_info数据表:ob_sb_seri,n_sb_links,v_sb_passwd,obj_grp_name,n_sb_available,n_sb_online,v_sb_desc 
	*tb_gateway_info数据表:ob_gw_seri,n_gw_links,v_gw_passwd,ob_grp_name,ob_loc_id,n_gw_online,v_gw_desc 
	*/ 
 
	$data = '';		     
	$dbs=array(); 
	$k = 0; 
	$condition = '';
	//echo "device_type: " . $message['device_type']; 
    switch($message['device_type']){ 
    	case 1: 
    		$fields = "n_gw_online"; 
    		$condition = "where v_gw_passwd = '".$devicePassword."' and n_gw_links = ".$message['device_lines_num']." and ob_gw_seri = '".$deviceSerialNum."'"; 
  			$data = $db->Get("tb_gateway_info",$fields,$condition); 
			echo $condition . "\n"; 
			if (!empty($data)) { 
				$row = mysqli_fetch_array($data,MYSQLI_ASSOC);       //取得tb_gateway_info数据表信息 
				if (isset($row['n_gw_online']))
				{
					if ($row['n_gw_online'] == 0)
					{
						$db->Set("tb_gateway_info","n_gw_online = 1",$condition);
					}
					else
					{
						$cond = "where bo_gw_seri = '" . $deviceSerialNum . "'";
						$db->Del("tb_gateway_link_info", $cond);
						$db->Set("tb_gateway_info","n_gw_online = 1",$condition);
					}
					return true;
				}
				else
				{
					echo "register_user: gateway[".$deviceSerialNum."-".$message['device_bank_num']."-".$message['device_lines_num']."] not found!\n";
                                        return false;
				}
				
				/*if(isset($row['n_gw_online']) && $row['n_gw_online'] == 0){         		//判断是否注册成功 
					$db->Set("tb_gateway_info","n_gw_online = 1",$condition);			//修改为在线状态 
					echo "register_user: gateway[".$deviceSerialNum."-".$message['device_bank_num']."-".$message['device_lines_num']."] register succ.\n";
					return true; 
				} else { 
					echo "register_user: gateway[".$deviceSerialNum."-".$message['device_bank_num']."-".$message['device_lines_num']."] register fail.\n";
					return false; 
				}*/
			}
			else
			{
				return false;
			}
	    	break; 
    	case 2: 
    		$fields = "n_sb_online"; 
    		$condition = "where  v_sb_passwd = '".$devicePassword."' and n_sb_links = ".$message['device_lines_num']." and ob_sb_seri = '".$deviceSerialNum."'"; 
		$data = $db->Get("tb_simbank_info",$fields,$condition); 
			if (!empty($data)) { 
				$row = mysqli_fetch_array($data,MYSQLI_ASSOC);       						//取得tb_gateway_info数据表信息 
				if(isset($row['n_sb_online']) && $row['n_sb_online'] == 0){									//判断是否注册成功 
					$db->Set("tb_simbank_info","n_sb_online = 1",$condition);            //修改为在线状态 
					echo "register_user: simbank[".$deviceSerialNum."-".$message['device_lines_num']."] register succ.\n";
					return true; 
				} else { 
					echo "register_user: simbank[".$deviceSerialNum."-".$message['device_bank_num']."-".$message['device_lines_num']."] register fail.\n";
					return false; 
				} 
			}
			else
			{
				return false;
			}
	    	break; 
    	default: 
	    	echo 'register_user: Device Type Error.\n'; 
    		return false; 
	    	break; 
    } 
} 
 
function  registration_responds($pkt,$status)                //设备注册响应消息体 
{                   
	$message = unpack("n1new_version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/C10/n1device_bank_num/n1device_lines_num/C64password",$pkt); 
	 
    if($status == 'success') { 
    	$message['result'] = 0; 
	} elseif($status == 'failed') { 
    	$message['result'] = 1; 
	} 
	$message['msgtype'] = 4097; 
	$message['msglen'] = 16; 
	 
  	$respong_pkt = pack("nnnnnnccccccccccnn", $message['new_version'], $message['msgtype'], $message['msglen'],$message['result'], $message['reserve'], $message['device_type'],$message['1'],$message['2'],$message['3'],$message['4'],$message['5'],$message['6'],$message['7'],$message['8'],$message['9'],$message['10'],$message['device_bank_num'], $message['device_lines_num']); 
	 
	$cksum = checksum($respong_pkt); 
	$respong_pkt = pack("a*n", $respong_pkt, $cksum); 
	 
	return $respong_pkt; 
} 
 
function loginout_user($db, $pkt)											//设备注销消息体 
{						 
    $message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/C10device_serial_num/n1device_bank_num/n1device_lines_num",$pkt); 
    if(!check_pkt($message,'','','loginout')) { 
    	echo 'loginout_user: Invalid message logout packet\n'; 
    	return; 
    } 
	 
	$deviceSerialNum = ''; 
	for($serial_num = 1 ; $serial_num < 11 ; $serial_num++){ 
		$deviceSerialNum .= chr($message["device_serial_num$serial_num"]);   //将ASCII码转换成字符串 
	} 
	$deviceSerialNum = trim($deviceSerialNum); 
	 
	$data = '';		     
	$dbs=array(); 
	$k = 0; 
	$condition = ''; 
    switch($message['device_type']){ 
    	case 1: 
    		$fields = "n_gw_online"; 
    		$device_lines_num = $message['device_lines_num']; 
    		$condition = "where n_gw_links = \"$device_lines_num\" and ob_gw_seri = \"$deviceSerialNum\""; 
  			$condition_link = "ob_gw_seri = \"$deviceSerialNum\""; 
			$condition_sb_link = "where ob_gw_seri = \"$deviceSerialNum\" and n_sb_link_stat = 1"; 
  			$data = $db->Get("tb_gateway_info",$fields,$condition); 
			$row = mysqli_fetch_array($data,MYSQLI_ASSOC);       //取得tb_gateway_info数据表信息 
			if(isset($row['n_gw_online']) && $row['n_gw_online'] == 1){         		//判断是否能注销成功 
				$db->Set("tb_simbank_link_info","n_sb_link_stat = 0,ob_gw_seri='',ob_gw_link_bank_nbr=0,ob_gw_link_slot_nbr=0",$condition_sb_link);			//修改网关线路对应的simbank线路状态 
				$db->Del("tb_gateway_link_info",$condition_link);									//删除设备线路信息表相关记录 
				$db->Set("tb_gateway_info","n_gw_online = 0",$condition);			//修改为离线状态 						
				echo "longinout_user: gateway[".$deviceSerialNum."-".$message['device_bank_num']."-".$message['device_lines_num']."] longinout succ.\n";
				return true; 
			} else {
				echo "longinout_user: gateway[".$deviceSerialNum."-".$message['device_bank_num']."-".$message['device_lines_num']."] longinout fail.\n"; 
				return false; 
			} 
	    	break; 
    	case 2: 
    		$fields = "n_sb_online"; 
    		$device_lines_num = $message['device_lines_num']; 
    		$condition = "where  n_sb_links = \"$device_lines_num\" and ob_sb_seri = \"$deviceSerialNum\""; 
  			$condition_link = "ob_sb_seri = \"$deviceSerialNum\""; 
			//$condition_gw_link = "where A.ob_sb_seri=\"$deviceSerialNum\" and A.ob_gw_seri=B.ob_gw_seri and A.ob_gw_link_bank_nbr=B.ob_gw_link_bank_nbr and A.ob_gw_link_slot_nbr=B.ob_gw_link_slot_nbr"; 
  			$condition_gw_link="where ob_sb_seri=\"$deviceSerialNum\"";
			//$condition_gw_link = "where A.ob_sb_seri=\"$deviceSerialNum\" and A.ob_gw_seri=B.ob_gw_seri and A.ob_gw_link_bank_nbr=B.ob_gw_link_bank_nbr and A.ob_gw_link_slot_nbr=B.ob_gw_link_slot_nbr"; 
  			
			$data = $db->Get("tb_simbank_info",$fields,$condition); 
			$row = mysqli_fetch_array($data,MYSQLI_ASSOC);       						//取得tb_gateway_info数据表信息 
			if(isset($row['n_sb_online']) && $row['n_sb_online'] == 1){									//判断是否能注销成功 
				//$db->Set("tb_simbank_link_info AS A,tb_gateway_link_info AS B","B.n_gw_link_stat = 0",$condition_gw_link); 
				$db->Set("tb_gateway_link_info","n_gw_link_stat = 0,ob_sb_seri='',ob_sb_link_bank_nbr=0,ob_sb_link_sim_nbr=0",$condition_gw_link);   //修改与该simbank线路已建立对应关系的网关线路状态 
				$db->Del("tb_simbank_link_info",$condition_link);									//删除设备线路信息表相关记录 
				$db->Set("tb_simbank_info","n_sb_online = 0",$condition);            //修改为离线状态 				
				echo "longinout_user: simbank[".$deviceSerialNum."-".$message['device_bank_num']."-".$message['device_lines_num']."] longinout succ.\n";
				return true; 
			} else { 
				echo "longinout_user: simbank[".$deviceSerialNum."-".$message['device_bank_num']."-".$message['device_lines_num']."] longinout fail.\n";
				return false; 
			} 
	    	break; 
    	default:
		echo "longinout_user: unkown device type.\n"; 
    		return false; 
	    	break; 
    } 
} 
 
function  loginout_responds($pkt,$status)                //设备注销响应消息体 
{                   
	$message = unpack("n1new_version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/C10/n1device_bank_num/n1device_lines_num",$pkt); 
	 
    if($status == 'success') { 
    	$message['result'] = 0; 
	} elseif($status == 'failed') { 
    	$message['result'] = 1; 
	} 
	$message['msgtype'] = 4098; 
	$message['msglen'] = 26; 
  	$respong_pkt = pack("nnnnnnccccccccccnn", $message['new_version'], $message['msgtype'], $message['msglen'],$message['result'], $message['reserve'], $message['device_type'],$message['1'],$message['2'],$message['3'],$message['4'],$message['5'],$message['6'],$message['7'],$message['8'],$message['9'],$message['10'],$message['device_bank_num'], $message['device_lines_num']); 
	 
	$cksum = checksum($respong_pkt); 
	$respong_pkt = pack("a*n", $respong_pkt, $cksum); 
	 
	return $respong_pkt; 
} 
 
function heartbeat_detection($db, $pkt)									//心跳消息体 
{ 
    $message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/C10device_serial_num/n1device_bank_num/n1device_lines_num",$pkt); 
    if(!check_pkt($message,'','','','heartbeat')) { 
    	echo 'Invalid heartbeat message packet\n'; 
    	return; 
    } 
 
	$deviceSerialNum = ''; 
	for($serial_num = 1 ; $serial_num < 11 ; $serial_num++){ 
		$deviceSerialNum .= chr($message["device_serial_num$serial_num"]);   //将ASCII码转换成字符串 
	} 
	$deviceSerialNum = trim($deviceSerialNum); 
	$data = '';		     
	$dbs=array(); 
	$condition = ''; 
	$time = ''; 
	$time = date('Y-m-d H:i:s'); 
    switch($message['device_type']){ 
    	case 1: 
    		$fields = "ob_grp_name"; 
    		$device_lines_num = $message['device_lines_num']; 
    		$condition = "where n_gw_links = \"$device_lines_num\" and ob_gw_seri = \"$deviceSerialNum\""; 
  			$data = $db->Get("tb_gateway_info",$fields,$condition); 
			$row = mysqli_fetch_array($data,MYSQLI_ASSOC);       //取得tb_gateway_info数据表信息 
			if(isset($row['ob_grp_name'])){         		//判断是否存在相应设备 
				$db->Set("tb_gateway_info","d_heartbeat_time = \"$time\"",$condition);			//更新时间戳 
				return true; 
			} else { 
				return false; 
			} 
	    	break; 
    	case 2: 
    		$fields = "ob_grp_name"; 
    		$device_lines_num = $message['device_lines_num']; 
    		$condition = "where  n_sb_links = \"$device_lines_num\" and ob_sb_seri = \"$deviceSerialNum\""; 
  			$data = $db->Get("tb_simbank_info",$fields,$condition); 
			$row = mysqli_fetch_array($data,MYSQLI_ASSOC);       						//取得tb_gateway_info数据表信息 
			if(isset($row['ob_grp_name'])){									//判断是否存在相应设备 
				$db->Set("tb_simbank_info","d_heartbeat_time = \"$time\"",$condition);            //更新设备时间戳 
				return true; 
			} else { 
				return false; 
			} 
	    	break; 
    	default: 
	    	echo 'Device Type Error.\n'; 
    		return false; 
	    	break; 
    } 
} 
 
function  heartbeat_responds($pkt,$status)                //设备心跳响应消息体 
{                   
	$message = unpack("n1new_version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/C10/n1device_bank_num/n1device_lines_num",$pkt); 
	 
    if($status == 'success') { 
    	$message['result'] = 0; 
	} elseif($status == 'failed') { 
    	$message['result'] = 1; 
	} 
	$message['msgtype'] = 4099; 
	$message['msglen'] = 16; 
  	$respong_pkt = pack("nnnnnnc10nn", $message['new_version'], $message['msgtype'], $message['msglen'],$message['result'], $message['reserve'], $message['device_type'],$message['1'],$message['2'],$message['3'],$message['4'],$message['5'],$message['6'],$message['7'],$message['8'],$message['9'],$message['10'],$message['device_bank_num'], $message['device_lines_num']); 
	 
	$cksum = checksum($respong_pkt); 
	$respong_pkt = pack("a*n", $respong_pkt, $cksum); 
	 
	return $respong_pkt; 
} 
$line_report_count = 0; 
 
function report_line($db, $pkt)													//设备线路上报消息体 
{ 
	//echo "[" . getmypid() . "][" . debug_time_print() . "] simbank report_line\n";
	$message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/C10device_serial_num/n1device_usb_num/n1device_bank_num/n1device_slot_nbr/V1device_ip/n1device_port/n1device_atr_long/H64device_line_atr",$pkt);
	/*$message_new = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/C10device_serial_num/n1device_usb_num/n1device_bank_num/n1device_slot_nbr/V1device_ip/n1device_port/n1device_atr_long/H64device_line_atr/n1link_vgsm_len",$pkt);
	$length_tmp=$message_new['link_vgsm_len']*2;
	$message= unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/C10device_serial_num/n1device_usb_num/n1device_bank_num/n1device_slot_nbr/V1device_ip/n1device_port/n1device_atr_long/H64device_line_atr/n1link_vgsm_len/H".$length_tmp."link_vgsm",$pkt);*/

	/*$fp = fopen("vgsm.dat", "w"); 
        fwrite($fp, $message['link_vgsm']);
        fclose($fp);*/

	/*
	$fp = fopen("report_line.txt", "w"); 
	fwrite($fp, $message['version']."\r\n"); 
	fwrite($fp, $message['msgtype']."\r\n"); 
	fwrite($fp, $message['result']."\r\n"); 
	fwrite($fp, $message['reserve']."\r\n"); 
	fwrite($fp, $message['device_type']."\r\n"); 
	fwrite($fp, $message['device_serial_num']."\r\n"); 
	fwrite($fp, $message['device_usb_num']."\r\n"); 
	fwrite($fp, $message['device_bank_num']."\r\n"); 
	fwrite($fp, $message['device_slot_nbr']."\r\n"); 
	fwrite($fp, $message['device_ip']."\r\n"); 
	fwrite($fp, $message['device_port']."\r\n"); 
	fwrite($fp, $message['device_atr_long']."\r\n"); 
	fwrite($fp, $message['device_line_atr']."\r\n"); 
	fwrite($fp, $message['link_vgsm_len']."\r\n"); 
	fwrite($fp, $message['link_vgsm']."\r\n"); 	
	fclose($fp); 
	*/
	
	//echo "report_line: after unpack msg from bank, link_vgsm_len: " . link_vgsm_len;
    if(!check_pkt($message,'','','','','reportLine')) { 
    	echo 'report_line: Invalid line report packet\n'; 
    	return; 
    } 
	//echo "[" . getmypid() . "][" . debug_time_print() . "] simbank unpack\n"; 
	$deviceSerialNum = ''; 
	for($serial_num = 1 ; $serial_num < 11 ; $serial_num++){ 
		$deviceSerialNum .= chr($message["device_serial_num$serial_num"]);   //将ASCII码转换成字符串 
	} 
	 
	$deviceLineAtr = $message["device_line_atr"]; 
	//echo "[" . getmypid() . "][" . debug_time_print() . "] simbank new mysql\n"; 
	$data = ''; 
    switch($message['device_type']){ 
    	case 1: 
    		$device_serial_num = $deviceSerialNum; 
    		$device_bank_num = $message['device_bank_num']; 
    		$device_slot_nbr = $message['device_slot_nbr']; 
    		$device_ip = $message['device_ip']; 
    		$device_port = $message['device_port']; 
    		 
    		$check_con = "where ob_gw_seri = \"$device_serial_num\" and ob_gw_link_bank_nbr = \"$device_bank_num\" and ob_gw_link_slot_nbr = \"$device_slot_nbr\""; 
    		$check_line = 	$db->Get("tb_gateway_link_info","ob_gw_seri",$check_con);		//判断线路是否已上报 
			$check_line_data = mysql_fetch_array($check_line);  
    		if(isset($check_line_data['ob_gw_seri']) && $check_line_data['ob_gw_seri'] != ''){ 
    			//2014-12-12: reset line state for accidental power down, added by Ferom;
				$setval="n_gw_link_stat=0";
				$db->set("tb_gateway_link_info",$setval,$check_con);
				echo "report_line: Equipment line had reported\n"; 
    			return true; 
    		} 
    		 
    		$fields = "ob_gw_seri,ob_gw_link_bank_nbr,ob_gw_link_slot_nbr,n_gw_link_ip,n_gw_link_port,n_gw_link_stat,v_gw_link_desc"; 
    		$values = "\"$device_serial_num\",\"$device_bank_num\",\"$device_slot_nbr\",\"$device_ip\",\"$device_port\",0,''"; 
    		$data = $db->Add("tb_gateway_link_info",$fields,$values); 
    		//echo $data; 
			if($data == 1){         		//判断是否更新线路成功 
				echo "report_line: gateway[".$device_serial_num."-".$message['device_usb_num']."-".$message['device_bank_num']."-".$message['device_slot_nbr']."] report succ.\n";
				return true; 
			} else { 
				echo "report_line: gateway[".$device_serial_num."-".$message['device_usb_num']."-".$message['device_bank_num']."-".$message['device_slot_nbr']."] report fail.\n";
				return false; 
			} 
	    	break; 
    	case 2: 
		$message_new = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/C10device_serial_num/n1device_usb_num/n1device_bank_num/n1device_slot_nbr/V1device_ip/n1device_port/n1device_atr_long/H64device_line_atr/n1link_vgsm_len",$pkt);
                $length_tmp=$message_new['link_vgsm_len']*2;
                $message= unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/C10device_serial_num/n1device_usb_num/n1device_bank_num/n1device_slot_nbr/V1device_ip/n1device_port/n1device_atr_long/H64device_line_atr/n1link_vgsm_len/H".$length_tmp."link_vgsm",$pkt);
    		$device_serial_num = $deviceSerialNum; 
		$device_usb_num = $message['device_usb_num']; 
    		$device_bank_num = $message['device_bank_num']; 
    		$device_slot_nbr = $message['device_slot_nbr']; 
    		$device_atr_long = $message['device_atr_long']; 
    		$device_line_atr = $deviceLineAtr; 
    		$device_ip = $message['device_ip']; 
    		$device_port = $message['device_port'];
		$link_vgsm_len = $message['link_vgsm_len'];
		$link_vgsm = $message['link_vgsm']; 
    		//echo "[" . getmypid() . "][" . debug_time_print() . "] simbank search start\n"; 
    	    $check_con = "where ob_sb_seri = \"$device_serial_num\" and ob_sb_link_usb_nbr=\"$device_usb_num\" and ob_sb_link_bank_nbr=\"$device_bank_num\" and ob_sb_link_sim_nbr=\"$device_slot_nbr\""; 
    		$check_line = 	$db->Get("tb_simbank_link_info","ob_sb_seri",$check_con)	;	//判断线路是否已上报			 
			if (!empty($check_line)) { 
				$check_line_data = mysql_fetch_array($check_line); 
				if(isset($check_line_data['ob_sb_seri']) && $check_line_data['ob_sb_seri'] != ''){ 
					//2014-12-12: reset line state for accidental power down, added by Ferom;
					$setval="n_sb_link_online=0,n_sb_link_stat=0";
					$db->set("tb_simbank_link_info",$setval,$check_con);
					
					echo "report_line: Equipment line had reported\n"; 
					return true; 
				} 
			}   
			//echo "[" . getmypid() . "][" . debug_time_print() . "] simbank search end\n"; 
    		$fields = "ob_sb_seri,ob_sb_link_usb_nbr,ob_sb_link_bank_nbr,ob_sb_link_sim_nbr,n_sb_link_atr_len,b_sb_link_atr,n_sb_link_vgsm_len,b_sb_link_vgsm,n_sb_link_ip,n_sb_link_port,n_sb_link_bind,n_sb_link_total_time,n_sb_link_start_time,n_sb_link_last_time,n_sb_link_online,ob_gw_seri,ob_gw_link_bank_nbr,ob_gw_link_slot_nbr,n_sb_link_stat,v_sb_link_desc"; 
    		$values = "\"$device_serial_num\",\"$device_usb_num\",\"$device_bank_num\",\"$device_slot_nbr\",\"$device_atr_long\",\"$device_line_atr\",\"$link_vgsm_len\",\"$link_vgsm\",\"$device_ip\",\"$device_port\",\"\",\"\",\"\",\"\",\"0\",\"\",\"\",\"\",\"0\",\"\""; 
    		 
    		$data = $db->Add("tb_simbank_link_info",$fields,$values); 
			//echo "[" . getmypid() . "][" . debug_time_print() . "] simbank add end \n"; 
    		//	echo $data; 
			if($data == 1){         		//判断是否更新线路成功 
				echo "report_line: simbank[".$device_serial_num."-".$message['device_usb_num']."-".$message['device_bank_num']."-".$message['device_slot_nbr']."] report succ.\n";
				return true; 
			} else { 
				echo "report_line: simbank[".$device_serial_num."-".$message['device_usb_num']."-".$message['device_bank_num']."-".$message['device_slot_nbr']."] report fail.\n";
				return false; 
			} 
	    	break; 
    	default: 
	    	echo 'Device Type Error.\n'; 
    		return false; 
	    	break; 
    } 
	 
} 
 
function  report_line_responds($pkt,$status)                //设备线路上报响应消息体 
{                   
	$message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/C10/n1device_usb_num/n1device_bank_num/n1device_slot_nbr/V1device_ip/n1device_port/n1device_atr_long/H32device_line_atr",$pkt); 
	 
	echo "[" . getmypid() . "][" . debug_time_print() . "] responds report line message [" . $message['device_usb_num'] . $message['device_bank_num'] . "-" . $message['device_slot_nbr'] . "]\n";	 
	 
    if($status == 'success') { 
    	$message['result'] = 0; 
	} elseif($status == 'failed') { 
    	$message['result'] = 1; 
	} 
	$message['msgtype'] = 4100; 
	$message['msglen'] = 18; 
  	$respong_pkt = pack("nnnnnnccccccccccnnn", $message['version'], $message['msgtype'], $message['msglen'],$message['result'], $message['reserve'], $message['device_type'],$message['1'],$message['2'],$message['3'],$message['4'],$message['5'],$message['6'],$message['7'],$message['8'],$message['9'],$message['10'],$message['device_usb_num'],$message['device_bank_num'], $message['device_slot_nbr']); 
	 
	$cksum = checksum($respong_pkt); 
	$respong_pkt = pack("a*n", $respong_pkt, $cksum); 
	 
	return $respong_pkt; 
} 
 
function disconnect_line($db, $pkt)													//设备线路上报消息体 
{ 
    $message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/a10device_serial_num/n1device_usb_num/n1device_bank_num/n1device_slot_nbr",$pkt); 
	$ob_sb_seri = $message['device_serial_num']; 
	$ob_sb_link_bank_nbr = $message['device_bank_num']; 
	$ob_sb_link_sim_nbr = $message['device_slot_nbr']; 
	$condition = "ob_sb_seri=\"$ob_sb_seri\" AND ob_sb_link_bank_nbr=$ob_sb_link_bank_nbr AND ob_sb_link_sim_nbr=$ob_sb_link_sim_nbr"; 
	 
	echo "[" . $condition . "]\n"; 
	 
	$db->Del("tb_simbank_link_info",$condition);
	echo "========= DELETE FROM tb_gateway_link_info WHERE $condition";
	// upate gateway link stat in tb_gateway_info if the link being create
	$db->Set("tb_gateway_link_info", "n_gw_link_stat=0", "WHERE $condition");
	echo "========= UPDATE tb_gateway_link_info SET n_gw_link_stat=0 WHERE $condition";
	
	return true; 
} 
 
function disconnect_line_responds($pkt,$status)  
{ 
	$message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/a10device_serial_num/n1device_usb_num/n1device_bank_num/n1device_slot_nbr",$pkt); 
    if($status == 'success') { 
    	$message['result'] = 0; 
	} elseif($status == 'failed') { 
    	$message['result'] = 1; 
	} 
	$message['msgtype'] = 0x1202; 
	$message['msglen'] = 16; 
	$respong_pkt = pack("nnnnna10nnn", $message['version'], $message['msgtype'], $message['msglen'],$message['result'], $message['reserve'],$message['device_serial_num'],$message['device_usb_num'],$message['device_bank_num'], $message['device_slot_nbr']); 
	 
	$cksum = checksum($respong_pkt); 
	$respong_pkt = pack("a*n", $respong_pkt, $cksum);	 
	return $respong_pkt; 
}	 
 
function interrupt_line($db, $pkt)													//设备线路中断 
{ 
    $message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/a10device_serial_num/n1device_usb_num/n1device_bank_num/n1device_slot_nbr",$pkt); 
	$ob_sb_seri = $message['device_serial_num']; 
	$ob_sb_link_bank_nbr = $message['device_bank_num']; 
	$ob_sb_link_sim_nbr = $message['device_slot_nbr']; 
	$condition = "WHERE ob_sb_seri=\"$ob_sb_seri\" AND ob_sb_link_bank_nbr=\"$ob_sb_link_bank_nbr\" AND ob_sb_link_sim_nbr=\"$ob_sb_link_sim_nbr\""; 
	 
	//echo "[" . $condition . "]\n"; 
	$data = $db->Get("tb_simbank_link_info","*",$condition); 
	$simbank_info = mysqli_fetch_array($data,MYSQLI_ASSOC); 
	 
	if (!isset($simbank_info['ob_sb_seri']) || !isset($simbank_info['ob_gw_seri'])){ 
		return false; 
	} 
	if (!isset($simbank_info['ob_gw_link_bank_nbr']) || !isset($simbank_info['ob_gw_link_slot_nbr'])  
			|| ($simbank_info['ob_gw_link_bank_nbr'] == "") || ($simbank_info['ob_gw_link_slot_nbr'] == "")) { 
		return false; 
	} 
 
	//获取总使用时间，开始使用时间和最后使用时间 
	$condition_time = "WHERE ob_sb_seri = \"$ob_sb_seri\" and ob_sb_link_bank_nbr = \"$ob_sb_link_bank_nbr\" and ob_sb_link_sim_nbr = \"$ob_sb_link_sim_nbr\" LIMIT 1"; 
	$fields_time = "n_sb_link_total_time,n_sb_link_start_time,n_sb_link_last_time"; 
	$data_time = $db->Get("tb_simbank_link_info",$fields_time,$condition_time); 
	$time_info = mysql_fetch_array($data_time); 
	 
	//更新simbank线路信息表 
	$last_time = strtotime(date("Y-m-d H:i:s")); 
	$total_time = 0; 
	if (isset($time_info) && isset($time_info['n_sb_link_total_time']) && isset($time_info['n_sb_link_start_time'])) { 
		$total_time = $time_info['n_sb_link_total_time'] + $last_time - $time_info['n_sb_link_start_time']; 
	}		 
	$condition_sb_link = "WHERE ob_sb_seri=\"$ob_sb_seri\" AND ob_sb_link_bank_nbr=\"$ob_sb_link_bank_nbr\" AND ob_sb_link_sim_nbr=\"$ob_sb_link_sim_nbr\""; 
	$fields_sb_link = "n_sb_link_last_time=\"$last_time\",n_sb_link_total_time=\"$total_time\",n_sb_link_stat=0,n_sb_link_online=0"; 
	$data = $db->Set("tb_simbank_link_info",$fields_sb_link,$condition_sb_link);	 
	 
	//更新gateway线路信息表 
	$ob_gw_seri = $simbank_info['ob_gw_seri']; 
	$ob_gw_link_bank_nbr = $simbank_info['ob_gw_link_bank_nbr']; 
	$ob_gw_link_slot_nbr = $simbank_info['ob_gw_link_slot_nbr']; 
	$condiction_gw_link="WHERE ob_gw_seri=\"$ob_gw_seri\" and ob_gw_link_bank_nbr=\"$ob_gw_link_bank_nbr\" and ob_gw_link_slot_nbr=\"$ob_gw_link_slot_nbr\""; 
	$data_gw=$db->Set("tb_gateway_link_info","n_gw_link_stat=0,n_gw_link_last_time = \"$last_time\"",$condiction_gw_link); 
		 
	return true; 
} 
 
function interrupt_line_responds($pkt,$status)  
{ 
	$message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/a10device_serial_num/n1device_usb_num/n1device_bank_num/n1device_slot_nbr",$pkt); 
    if($status == 'success') { 
    	$message['result'] = 0; 
	} elseif($status == 'failed') { 
    	$message['result'] = 1; 
	} 
	$message['msgtype'] = 0x1212; 
	$message['msglen'] = 16; 
	$respong_pkt = pack("nnnnna10nnn", $message['version'], $message['msgtype'], $message['msglen'],$message['result'], $message['reserve'],$message['device_serial_num'],$message['device_usb_num'],$message['device_bank_num'], $message['device_slot_nbr']); 
	 
	$cksum = checksum($respong_pkt); 
	$respong_pkt = pack("a*n", $respong_pkt, $cksum);	 
	return $respong_pkt; 
} 
 
 
function update_simbank_info_establish($db, $pkt) 
{ 
	$message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/a10ob_sb_seri/n1ob_sb_link_usb_nbr/n1ob_sb_link_bank_nbr/n1ob_sb_link_sim_nbr/V1n_sb_link_ip/n1n_sb_link_port/n1n_sb_link_atr_len/H64b_sb_link_atr/a10ob_gw_seri/n1ob_gw_link_bank_nbr/n1ob_gw_link_slot_nbr/V1n_gw_link_ip/n1n_gw_link_port",$pkt); 
	 
	//更新simbank线路信息表 
	$start_time = strtotime(date("Y-m-d H:i:s")); 
	$ob_sb_seri = $message['ob_sb_seri']; 
	$ob_sb_link_bank_nbr = $message['ob_sb_link_bank_nbr']; 
	$ob_sb_link_sim_nbr = $message['ob_sb_link_sim_nbr']; 
	$ob_gw_seri = $message['ob_gw_seri']; 
	$ob_gw_link_bank_nbr = $message['ob_gw_link_bank_nbr']; 
	$ob_gw_link_slot_nbr = $message['ob_gw_link_slot_nbr']; 
	$condition = "WHERE ob_sb_seri = \"$ob_sb_seri\" AND ob_sb_link_bank_nbr = \"$ob_sb_link_bank_nbr\" AND ob_sb_link_sim_nbr = \"$ob_sb_link_sim_nbr\" "; 
	$set_val = "ob_gw_seri=\"$ob_gw_seri\",ob_gw_link_bank_nbr=\"$ob_gw_link_bank_nbr\",ob_gw_link_slot_nbr=\"$ob_gw_link_slot_nbr\",n_sb_link_start_time=\"$start_time\",n_sb_link_call_time=0,n_sb_link_stat=1"; 
	$data = $db->Set("tb_simbank_link_info",$set_val,$condition); 
 
 
	echo "create link Simbank = [" . $ob_sb_seri  . "-" . $ob_sb_link_bank_nbr . "-" . $ob_sb_link_sim_nbr . "]" . "   Gateway=[" . $ob_gw_seri . "-" . $ob_gw_link_bank_nbr . "-" . $ob_gw_link_slot_nbr . "]\n"; 
 
	//更新gateway线路信息表 
	$condition = "WHERE ob_gw_seri = \"$ob_gw_seri\" and ob_gw_link_bank_nbr=\"$ob_gw_link_bank_nbr\" and ob_gw_link_slot_nbr=\"$ob_gw_link_slot_nbr\""; 
	$data_gw_link =$db->Set("tb_gateway_link_info", "n_gw_link_stat=1,ob_sb_seri=\"$ob_sb_seri\",ob_sb_link_bank_nbr=\"$ob_sb_link_bank_nbr\",ob_sb_link_sim_nbr=\"$ob_sb_link_sim_nbr\"", $condition);  
	 
	//更新日志信息 
	$ob_sb_link_bank_nbr = $message['ob_sb_link_bank_nbr']; 
	$ob_sb_link_sim_nbr = $message['ob_sb_link_sim_nbr']; 
	$n_sb_link_atr_len = $message['n_sb_link_atr_len']; 
	$b_sb_link_atr = $message['b_sb_link_atr']; 
	$d_sb_op_timestamp = $start_time; 
	$ob_gw_seri = $message['ob_gw_seri']; 
	$ob_gw_link_bank_nbr = $message['ob_gw_link_bank_nbr']; 
	$ob_gw_link_slot_nbr = $message['ob_gw_link_slot_nbr']; 
	$fields = "ob_sb_seri,ob_sb_link_bank_nbr,ob_sb_link_sim_nbr,n_sb_link_atr_len,b_sb_link_atr,n_sb_op_type,d_sb_op_timestamp,ob_gw_seri,ob_gw_link_bank_nbr,ob_gw_link_slot_nbr"; 
    $values = "\"$ob_sb_seri\",\"$ob_sb_link_bank_nbr\",\"$ob_sb_link_sim_nbr\",\"$n_sb_link_atr_len\",\"$b_sb_link_atr\",\"1\",\"$d_sb_op_timestamp\",\"$ob_gw_seri\",\"$ob_gw_link_bank_nbr\",\"$ob_gw_link_slot_nbr\""; 
    $data_log = $db->Add("tb_simbank_link_log",$fields,$values); 
    if(isset($data) && isset($data_log)){ 
    	return true; 
    } else { 
    	return false; 
    } 
} 
 
function update_simbank_info_release($db, $pkt) 
{ 
	$message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/a10ob_sb_seri/n1ob_sb_link_usb_nbr/n1ob_sb_link_bank_nbr/n1ob_sb_link_sim_nbr/V1n_sb_link_ip/n1n_sb_link_port/n1n_sb_link_atr_len/H64b_sb_link_atr/a10ob_gw_seri/n1ob_gw_link_bank_nbr/n1ob_gw_link_slot_nbr/V1n_gw_link_ip/n1n_gw_link_port",$pkt); 
	 
	$ob_sb_seri = $message['ob_sb_seri']; 
	$ob_sb_link_bank_nbr = $message['ob_sb_link_bank_nbr']; 
	$ob_sb_link_sim_nbr = $message['ob_sb_link_sim_nbr']; 
	$ob_gw_seri = $message['ob_gw_seri']; 
	$ob_gw_link_slot_nbr = $message['ob_gw_link_slot_nbr']; 
	$ob_gw_link_bank_nbr = $message['ob_gw_link_bank_nbr']; 
	 
	//获取总使用时间，开始使用时间和最后使用时间 
	$condition_time = "WHERE ob_sb_seri = \"$ob_sb_seri\" and ob_sb_link_bank_nbr = \"$ob_sb_link_bank_nbr\" and ob_sb_link_sim_nbr = \"$ob_sb_link_sim_nbr\" LIMIT 1"; 
	$fields_time = "n_sb_link_total_time,n_sb_link_start_time,n_sb_link_last_time"; 
	$data_time = $db->Get("tb_simbank_link_info",$fields_time,$condition_time); 
	$time_info = mysql_fetch_array($data_time); 
	 
	//更新simbank线路信息表 
	$last_time = strtotime(date("Y-m-d H:i:s")); 
	$total_time = 0; 
	if (isset($time_info) && isset($time_info['n_sb_link_total_time']) && isset($time_info['n_sb_link_start_time'])) { 
		$total_time = $time_info['n_sb_link_total_time'] + $last_time - $time_info['n_sb_link_start_time']; 
	} 
	$condition_sb_link = "WHERE ob_sb_seri = \"$ob_sb_seri\" and ob_gw_seri=\"$ob_gw_seri\" and ob_gw_link_bank_nbr=\"$ob_gw_link_bank_nbr\" and ob_gw_link_slot_nbr=\"$ob_gw_link_slot_nbr\" and n_sb_link_online=0";			 
	$fields_sb_link = "n_sb_link_last_time=\"$last_time\",n_sb_link_total_time=\"$total_time\",n_sb_link_stat=0"; 
	$data = $db->Set("tb_simbank_link_info",$fields_sb_link,$condition_sb_link); 
	 
	//更新gateway线路信息表 
	$condiction_gw_link="WHERE ob_gw_seri=\"$ob_gw_seri\" and ob_gw_link_bank_nbr=\"$ob_gw_link_bank_nbr\" and ob_gw_link_slot_nbr=\"$ob_gw_link_slot_nbr\""; 
	$data_gw=$db->Set("tb_gateway_link_info","n_gw_link_stat=0,n_gw_link_last_time = \"$last_time\"",$condiction_gw_link); 
	 
	//更新日志信息 
	$ob_sb_seri = $message['ob_sb_seri']; 
	$ob_sb_link_bank_nbr = $message['ob_sb_link_bank_nbr']; 
	$ob_sb_link_sim_nbr = $message['ob_sb_link_sim_nbr']; 
	$n_sb_link_atr_len = $message['n_sb_link_atr_len']; 
	$b_sb_link_atr = $message['b_sb_link_atr']; 
	$d_sb_op_timestamp = $last_time; 
	$ob_gw_seri = $message['ob_gw_seri']; 
	$ob_gw_link_bank_nbr = $message['ob_gw_link_bank_nbr']; 
	$ob_gw_link_slot_nbr = $message['ob_gw_link_slot_nbr']; 
	$fields = "ob_sb_seri,ob_sb_link_bank_nbr,ob_sb_link_sim_nbr,n_sb_link_atr_len,b_sb_link_atr,n_sb_op_type,d_sb_op_timestamp,ob_gw_seri,ob_gw_link_bank_nbr,ob_gw_link_slot_nbr"; 
    $values = "\"$ob_sb_seri\",\"$ob_sb_link_bank_nbr\",\"$ob_sb_link_sim_nbr\",\"$n_sb_link_atr_len\",\"$b_sb_link_atr\",\"2\",\"$d_sb_op_timestamp\",\"$ob_gw_seri\",\"$ob_gw_link_bank_nbr\",\"$ob_gw_link_slot_nbr\""; 
    $data_log = $db->Add("tb_simbank_link_log",$fields,$values); 
    if(isset($data) && isset($data_log)){ 
    	return true; 
    } else { 
    	return false; 
    } 
} 
 
function start_call_handler($pkt) 
{ 
	$message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/a10device_serial_num/n1device_bank_num/n1device_slot_nbr",$pkt); 
	$start_time = strtotime(date("Y-m-d H:i:s")); 
	$device_type = $message['device_type']; 
	$ob_gw_seri = $message['device_serial_num']; 
	$ob_gw_link_bank_nbr = $message['device_bank_num']; 
	$ob_gw_link_slot_nbr = $message['device_slot_nbr']; 
	 
	echo "recv start call [" . $ob_gw_seri . "][" . $ob_gw_link_bank_nbr . "][" . $ob_gw_link_slot_nbr . "]\n"; 
	 
	$setval = "n_sb_link_online=1,n_sb_link_call_start_time=\"$start_time\""; 
	$condition = "WHERE ob_gw_seri=\"$ob_gw_seri\" AND ob_gw_link_bank_nbr=\"$ob_gw_link_bank_nbr\" AND ob_gw_link_slot_nbr=\"$ob_gw_link_slot_nbr\" AND n_sb_link_stat=1 AND n_sb_link_online=0"; 
 
	 
	switch($device_type){ 
	case 1:				//gateway 
		$db=new mysql(); 
		$fields = "*"; 
		//查找符合条件的simbank线路 
		$data = $db->Get("tb_simbank_link_info",$fields,$condition); 
		echo "----[" . $condition . "]----\n"; 
		$simbank_link_info = mysqli_fetch_array($data,MYSQLI_ASSOC); 
		if (!isset($simbank_link_info) || $simbank_link_info == ""){ 
			echo "1111111111111111111111\n"; 
			return false; 
		} 
		//echo "2222222222222222222222\n"; 
		$ob_sb_seri = $simbank_link_info['ob_sb_seri']; 
		$ob_sb_link_bank_nbr = $simbank_link_info['ob_sb_link_bank_nbr']; 
		$ob_sb_link_sim_nbr = $simbank_link_info['ob_sb_link_sim_nbr']; 
				 
		$condition_set = "WHERE ob_sb_seri=\"$ob_sb_seri\" AND ob_sb_link_bank_nbr=\"$ob_sb_link_bank_nbr\" AND ob_sb_link_sim_nbr=\"$ob_sb_link_sim_nbr\""; 
		//设置simbank线路使用状态 
		$data = $db->Set("tb_simbank_link_info",$setval,$condition_set); 
		if (!isset($data)){ 
			echo "3333333333333333333\n"; 
			return false; 
		} 
		break; 
	case 2:				//simbank 
		break; 
	default: 
		break; 
	} 
	 
	return true;	 
} 
 
function end_call_handler($pkt) 
{ 
	$message = unpack("n1version/n1msgtype/n1msglen/n1result/n1reserve/n1device_type/a10device_serial_num/n1device_bank_num/n1device_slot_nbr",$pkt); 
	$device_type = $message['device_type']; 
	$ob_gw_seri = $message['device_serial_num']; 
	$ob_gw_link_bank_nbr = $message['device_bank_num']; 
	$ob_gw_link_slot_nbr = $message['device_slot_nbr']; 
	 
	echo "recv end call [" . $ob_gw_seri . "][" . $ob_gw_link_bank_nbr . "][" . $ob_gw_link_slot_nbr . "]\n"; 
	 
	$condition = "WHERE ob_gw_seri=\"$ob_gw_seri\" AND ob_gw_link_bank_nbr=\"$ob_gw_link_bank_nbr\" AND ob_gw_link_slot_nbr=\"$ob_gw_link_slot_nbr\" AND n_sb_link_online=1"; 
	 
	switch($device_type){ 
	case 1:				//gateway 
		$db=new mysql(); 
		$fields = "*"; 
		//查找符合条件的simbank线路 
		$data = $db->Get("tb_simbank_link_info",$fields,$condition); 
		$simbank_link_info = mysqli_fetch_array($data,MYSQLI_ASSOC); 
		if (!isset($simbank_link_info) || $simbank_link_info == ""){ 
			return false; 
		} 
		$ob_sb_seri = $simbank_link_info['ob_sb_seri']; 
		$ob_sb_link_bank_nbr = $simbank_link_info['ob_sb_link_bank_nbr']; 
		$ob_sb_link_sim_nbr = $simbank_link_info['ob_sb_link_sim_nbr']; 
		 
		//获取总使用时间，开始使用时间和最后使用时间 
		$condition_time = "WHERE ob_sb_seri = \"$ob_sb_seri\" and ob_sb_link_bank_nbr = \"$ob_sb_link_bank_nbr\" and ob_sb_link_sim_nbr = \"$ob_sb_link_sim_nbr\" LIMIT 1"; 
		$fields_time = "n_sb_link_call_total_time,n_sb_link_call_time,n_sb_link_call_start_time,n_sb_link_call_last_time"; 
		$data_time = $db->Get("tb_simbank_link_info",$fields_time,$condition_time); 
		$time_info = mysql_fetch_array($data_time); 
		$last_time = strtotime(date("Y-m-d H:i:s")); 
		//自线路上报以来（包含N次线路建立与释放），总通话时间 
		$total_time = $time_info['n_sb_link_call_total_time']; 
		//本次线路建立以来，总通话时间 
		$link_calls_time = $time_info['n_sb_link_call_time']; 
		if (isset($time_info['n_sb_link_call_start_time'])) { 
			if ($time_info['n_sb_link_call_start_time'] == '0') { 
				$total_time = $time_info['n_sb_link_call_total_time']; 
			} 
			else { 
				$total_time = $time_info['n_sb_link_call_total_time'] + $last_time - $time_info['n_sb_link_call_start_time']; 
			} 
			 
			if ($time_info['n_sb_link_call_start_time'] == '0') { 
				$link_calls_time = $time_info['n_sb_link_call_time']; 
			} 
			else { 
				$link_calls_time = $time_info['n_sb_link_call_time'] + $last_time - $time_info['n_sb_link_call_start_time']; 
			}			 
			 
		} 
		$setval = "n_sb_link_online=0,n_sb_link_call_last_time=\"$last_time\",n_sb_link_call_total_time=\"$total_time\",n_sb_link_call_time=\"$link_calls_time\""; 
				 
		$condition_set = "WHERE ob_sb_seri=\"$ob_sb_seri\" AND ob_sb_link_bank_nbr=\"$ob_sb_link_bank_nbr\" AND ob_sb_link_sim_nbr=\"$ob_sb_link_sim_nbr\""; 
		//设置simbank线路使用状态 
		$data = $db->Set("tb_simbank_link_info",$setval,$condition_set); 
		if (!isset($data)){ 
			return false; 
		} 
		break; 
	case 2:				//simbank 
		break; 
	default: 
		break; 
	} 
	 
	return true; 
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
 
function strategy_daemon() 
{ 
	//$strategy_mode = 'global'; 
	$strategy_mode = 'group'; 
	 
	if ($strategy_mode == 'global') { 
		global_strategy(); 
	} 
	else { 
		group_strategy(); 
	} 
} 
 
/* 
组策略模式： 
1、循环拉起每个组对应的建立策略和释放策略 
2、本组策略只负责本组线路的检测 
3、每个循环周期策略本身只运行一次 
*/ 
function group_strategy() 
{ 
	$pid2 = pcntl_fork(); 
	if ($pid2) { 
		echo "running establish strategy\n"; 
		$tick_count = 0.0; 
		while(true) { 
			
			run_strategy('establish'); 
			//echo "\n\n\n"; 
			if ($tick_count > 30.0) { 
				sleep(1); 
			} 
			else { 
				usleep(500000); 
				$tick_count += 0.5; 
			} 
		} 
	} 
	else { 
		echo "running release strategy\n";	 
		while(true) { 
			sleep(10); 
			run_strategy('release'); 
			//echo "\n\n\n"; 
		} 
	}	 
} 
 
function run_strategy($type) 
{ 
	$db=new mysql(); 
	 
	$data = $db->Get("tb_group_info",'*',''); 
	$all_group=array(); 
	$k = 0; 
	while($row = mysqli_fetch_array($data,MYSQLI_ASSOC)) { 
		$all_group[$k] = $row; 
		$k += 1; 
	} 
	global $strategy_path; 
	foreach($all_group as $group) { 
		if ($type == 'establish') { 
			if (strstr($group['v_grp_create_policy'], ".php")) { 
				$strategy_cmd = "/usr/bin/php " . $strategy_path . "/" . $group['v_grp_create_policy'] . " " . $group['ob_grp_name']; 
				//echo "establish: " . $strategy_cmd . "\n"; 
			} 
		} 
		elseif ($type == 'release') { 
			if (strstr($group['v_grp_release_policy'], ".php")) { 
				$strategy_cmd = "/usr/bin/php " . $strategy_path . "/" . $group['v_grp_release_policy'] . " " . $group['ob_grp_name']; 
				//echo "release: " . $strategy_cmd . "\n"; 
			} 
		} 
		system($strategy_cmd); 
	} 
} 
 
/* 
全局策略模式： 
1、只拉起一个策略，负责全局各个线路的建立和释放 
2、策略本身循环检测各个组符合建立和释放条件的线路 
*/ 
function global_strategy() 
{ 
	$pid1 = pcntl_fork(); 
	if ($pid1) { 
		echo "running establish_simbank_s.php\n"; 
		system("/usr/bin/php ../strategy/establish_simbank_s.php"); 
	} 
	else { 
		echo "running release_s.php\n"; 
		system("/usr/bin/php ../strategy/release_s.php"); 
	} 
} 
 
?> 
