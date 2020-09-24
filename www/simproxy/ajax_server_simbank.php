<?php
//header('Content-type: text/html;charset=GB2312'); 
//header('Content-type: text/html;charset=utf8'); 
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

require_once("../inc/function.inc");
require('../inc/mysql_class.php');
//require("../inc/language.inc");
include_once("../inc/wrcfg.inc");
include_once("../inc/aql.php");
include_once("../inc/define.inc");
include_once("../inc/language.inc");

$language = get_web_language_cache('/tmp/web/language.cache');
if(isset($_GET['send_debug'])){
	if($_GET['send_debug']=='on'){
		debug_language('on');
	}else if($_GET['send_debug']=='off'){
		debug_language('off');
	}
}

if(file_exists('/tmp/web/language.debug')){
	$language_debug = 'true';
}else{
	$language_debug = 'false';
}


	$db=new mysql();

	//$condition = "where ob_pol_name = \"$file_name\"";
	$condition = "where n_sb_available=1 order by n_sb_online desc";
	$data = $db->Get("tb_simbank_info",'*',$condition);
	//$all_sim_info = mysqli_fetch_array($data,MYSQLI_ASSOC);
	$all_sim_info = mysqli_fetch_all($data, MYSQLI_ASSOC);
	$row = mysqli_num_rows($data);
	$db="";
	
	if(isset($_GET['action']) && $_GET['action']) {
		
		$led_idle="<img src='../../images/led_idle.png'  style='vertical-align:text-bottom;'>";
		$led_busy="<img src='../../images/led_busy.png' style='vertical-align:text-bottom;'>";
		$led_disable="<img src='../../images/led_disable.png' style='vertical-align:text-bottom;'>";
		$led_pending="<img src='../../images/led_pending.png' style='vertical-align:text-bottom;'>";
		$led_reload="<img src='../../images/led_reload.png' style='vertical-align:text-bottom;'>";
		$led_signal="<img src='../../images/led_signal.png' style='vertical-align:text-bottom;'>";
		$led_local_block="<img src='../../images/led_local_block.png' style='vertical-align:text-bottom;'>";
		$led_local_unused="<img src='../../images/led_local_unused.png' style='vertical-align:text-bottom;'>";
		$led_remote_block="<img src='../../images/led_remote_block.png' style='vertical-align:text-bottom;'>";
		$led_remote_unused="<img src='../../images/led_remote_unused.png' style='vertical-align:text-bottom;'>";
		$led_linkdown="<img src='../../images/led_linkdown.png' style='vertical-align:text-bottom;'>";
		$led_unequipped="<img src='../../images/led_unequipped.png' style='vertical-align:text-bottom;'>";
		$led_connect="<img src='../../images/led_busy.png' style='vertical-align:text-bottom;'>";
		$led_alerting="<img src='../../images/led_reload.png' style='vertical-align:text-bottom;'>";
		$led_unknown="<img src='../../images/led_pending.png' style='vertical-align:text-bottom;'>";
		//echo $_GET['action']."<br>";
		//echo $_GET['method'];
		
		//global $sim_count;
		//echo $sim_count;
		
		switch($_GET['action']) {
			case 'process_log':
				if ($_GET['log_type']=='SimProxySvr_log') {											
					$url='/tmp/log/SimProxySvr.log';								
				}else if ($_GET['log_type']=='socket_s_log'){
					$url='/opt/simbank/www/php/socket_s.log';
					
				}else if ($_GET['log_type']=='establish_log'){				
					$url='/tmp/log/establish.log';		
				}else if ($_GET['log_type']=='release_log'){
					$url='/opt/simbank/www/php/release.log';
				}else if ($_GET['log_type']=='SimRdrSvr_log'){
					$url='/tmp/log/SimRdrSvr.log';
				}
				//print_rr($_GET);
				$size=$_GET['size'];			
				$size=1024*50;
				$filesize=filesize($url);
				//echo $size."==";
				//echo $filesize;
				
				if ($size==0){
					$content=file_get_contents($url);
					echo $url;
					echo $content;
					$tmpsize=0;
					
				}else if ($filesize<$size) {
					$content=file_get_contents($url);
					$tmpsize="-".$filesize;
					
				}
				else{
					$content=file_get_contents($url,false,null,$size,$filesize-$size);
					$content=file_get_contents($url,false,null,$filesize-$size,$size);
					$tmpsize=$filesize;
					
				}
				
				
				
				if ($_GET['method']=='update'){
					if ($size==$filesize) {
						$content='';
					}				
					$content=$tmpsize."&".$content;
				}			
				//echo $_GET['method'];
				if ($_GET['method']=='clean'){
					
					if ($size==$filesize) {
						$content='';
					}											
					//echo $url;
					system("echo -n ''> ".$url);
					$content=$tmpsize."&".$content;
				}			
					
				//echo json_encode($content);
				echo $content;
				exit(0);
			break;
			case 'refresh_sim':
				
				//create $result
				
				$htmlstr .= '<select class="div_tab_title sim_index_tab" onchange="switch_tab(this)" >';
				for($j =0; $j < $row; $j++){
					$color = '';
					$selected = '';
					if($j == 0){
						$selected = 'selected';
						$color = "style='color:#ECFE82;'";
					}
					
					$htmlstr .= '<option value='.$all_sim_info[$j]['ob_sb_seri'].' '.$selected.'>'.$all_sim_info[$j]['ob_sb_alias'].'-'.$all_sim_info[$j]['ob_sb_seri'].'</option>';
				}
				$htmlstr .= '</select><div id="newline"></div>';
				
				for($j =0; $j < $row; $j++){
				$display = '';
				if($j != 0) $display = "style='display:none;'";
				$simbank_serial_num[$j] = $all_sim_info[$j]['ob_sb_seri'];
				$_SESSION['sim_count']= $all_sim_info[$j]['n_sb_links'];

				/*
				ob_sb_seri =simbankÐòºÅ
				ob_sb_link_bank_nbr =simbankµÄBankID
				ob_sb_link_sim_nbr=simbankµÄsim¿¨ºÅ
				ob_gw_seri=gatewayµÄÐòºÅ			
				ob_gw_link_bank_nbr=gatewayµÄ°å¿¨ºÅ
				ob_gw_link_slot_nbr=gatewayÍ¨µÀºÅ
				n_sb_link_stat=sim¿¨×´Ì¬
				
				*/
				//
				
				for ($i=0;$i<$_SESSION['sim_count'];$i++){
					$result[$i]['id']=$i;
					$result[$i]['ob_sb_seri']="";
					$result[$i]['ob_sb_link_bank_nbr']="";
					$result[$i]['ob_sb_link_sim_nbr']="";
					$result[$i]['ob_gw_seri']="";
					$result[$i]['ob_gw_link_bank_nbr']="";
					$result[$i]['ob_gw_link_slot_nbr']="";
					$result[$i]['n_sb_link_stat']="";
					$result[$i]['ob_gw_link_chn_nbr']="";
					$result[$i]['n_sb_link_call_rest_time'] ="";
					$result[$sim_index]['n_sim_no'] ="";
					
				}
				
				$db=new mysql();
				
				$condition = "where ob_sb_seri=\"$simbank_serial_num[$j]\"";
				
				$sim_fileds = "ob_sb_seri,ob_sb_link_bank_nbr,ob_sb_link_sim_nbr,b_sim_abnormal";
				$sim_data = $db->Get('tb_sim_info', $sim_fileds, $condition);
				while($sim_res = mysqli_fetch_array($sim_data,MYSQLI_ASSOC)){
					$sim_index=$sim_res['ob_sb_link_bank_nbr']*8+$sim_res['ob_sb_link_sim_nbr'];
					$result[$sim_index]['b_sim_abnormal'] = $sim_res['b_sim_abnormal'];
				}
				
				$fileds = "ob_sb_link_bank_nbr,ob_sb_seri,ob_sb_link_bank_nbr,ob_sb_link_sim_nbr,ob_gw_seri,ob_gw_link_bank_nbr,ob_gw_link_slot_nbr,n_sb_link_stat,ob_gw_link_chn_nbr,n_sb_link_call_rest_time,v_sim_phone_number,n_sim_balance";
				$data = $db->Get("tb_simbank_link_info",$fileds,$condition);
				while($sim = mysqli_fetch_array($data,MYSQLI_ASSOC)){
					$sim_index=$sim['ob_sb_link_bank_nbr']*8+$sim['ob_sb_link_sim_nbr'];				
					$result[$sim_index]['ob_sb_seri']=$sim['ob_sb_seri'];
					$result[$sim_index]['ob_sb_link_bank_nbr']=$sim['ob_sb_link_bank_nbr'];
					$result[$sim_index]['ob_sb_link_sim_nbr']=$sim['ob_sb_link_sim_nbr'];
					$result[$sim_index]['ob_gw_seri']=$sim['ob_gw_seri'];
					//$result[$sim_index]['ob_gw_link_bank_nbr']=$sim['ob_gw_link_bank_nbr'];
					//$result[$sim_index]['ob_gw_link_slot_nbr']=$sim['ob_gw_link_slot_nbr'];
					
					if($sim['ob_gw_seri'] != ''){
						$result[$sim_index]['n_sim_no'] = $sim['ob_gw_link_bank_nbr'] * 8 + $sim['ob_gw_link_slot_nbr'] + 1;
						$result[$sim_index]['ob_gw_link_chn_nbr']=$sim['ob_gw_link_chn_nbr'];
					}else{
						$result[$sim_index]['n_sim_no'] = '';
						$result[$sim_index]['ob_gw_link_chn_nbr'] = '';
					}
					
					$result[$sim_index]['n_sb_link_stat']=$sim['n_sb_link_stat'];
					$result[$sim_index]['n_sb_link_call_rest_time'] = $sim['n_sb_link_call_rest_time'];
					$result[$sim_index]['v_sim_phone_number'] = $sim['v_sim_phone_number'];
					if($sim['n_sim_balance'] == -95.27){
						$result[$sim_index]['n_sim_balance'] = '';
					}else{
						$result[$sim_index]['n_sim_balance'] = $sim['n_sim_balance'];
					}
				}
				$row_count=$_SESSION['sim_count']/16;			
				$row_index=1;
				$sim_index=0;
				
				$htmlstr.="<table width=\"100%\" class='tshow $simbank_serial_num[$j]' $display>";
				$htmlstr.="<tr class='index_center_tr'><th>$simbank_serial_num[$j]</th><th>Col 1</th><th>Col 2</th><th>Col 3</th><th>Col 4</th><th>Col 5</th><th>Col 6</th><th>Col 7</th><th>Col 8</th></tr>";			
				
				//tb_bank_state
				$data = $db->Get("tb_bank_state",'*','where ob_sb_seri="'.$simbank_serial_num[$j].'"');
				$tb_bank_info = mysqli_fetch_all($data, MYSQLI_ASSOC);
				
				for ($row_id=1;$row_id<=$row_count;$row_id++){
					
					$htmlstr.="<TR><TD ROWSPAN=2 style='text-align:center;'>$row_id</TD>";
					for ($i=1;$i<=16;$i++){					
						$sim_id = (16*($row_id-1)+$i);
						
						$tmp_bank = ceil($sim_id/(8));
						$style = "";
						for($k=0;$k<count($tb_bank_info);$k++){
							if($tb_bank_info[$k]['ob_sb_bank'] == $tmp_bank && $tb_bank_info[$k]['b_bank_stat'] == 0){
								$style = "style='color:red;text-decoration:line-through;'";
							}
						}
						
						$seri_name = $simbank_serial_num[$j];
						$htmlstr.="<td><span style='width:28px;display:inline-block;'>".$sim_id;
						$htmlstr .= "</span><div class='helptooltips'>";
						
						//LED					
						switch ($result[$sim_index]['n_sb_link_stat']){
						case '0': 
							$htmlstr.=$led_disable.'&nbsp&nbsp<span id="'.$seri_name.'_'.$sim_id.'">'.language('Empty').'</span>';
							break;
						case '1': 
							$htmlstr.=$led_reload.'&nbsp&nbsp<span id="'.$seri_name.'_'.$sim_id.'">'.language('Ready').'</span>';
							break;
						case '2': 
							$htmlstr.=$led_local_unused.'&nbsp&nbsp<span id="'.$seri_name.'_'.$sim_id.'">'.language('Sleep').'</span>';
							break;					
						case '3': 
							$htmlstr.=$led_idle.'&nbsp&nbsp<span id="'.$seri_name.'_'.$sim_id.'">'.language('Assigned').'</span>';
							break;
						case '4': 
							$htmlstr.=$led_connect.'&nbsp&nbsp<span id="'.$seri_name.'_'.$sim_id.'">'.language('Talking').'</span>';
							break;
						case '5':
							$htmlstr.=$led_signal.'&nbsp&nbsp<span id="'.$seri_name.'_'.$sim_id.'">'.language('Registering').'</span>';
							break;
						case '6':
							$htmlstr.=$led_linkdown.'&nbsp&nbsp<span id="'.$seri_name.'_'.$sim_id.'">'.language('Locked').'</span>';
							break;
						case '7':
							$htmlstr.=$led_local_block.'&nbsp&nbsp<span id="'.$seri_name.'_'.$sim_id.'">'.language('Idle').'</span>';
							break;
						default:
							if($style == ""){
								$htmlstr.=$led_disable.'&nbsp&nbsp<span id="'.$seri_name.'_'.$sim_id.'" >'.language('Empty').'</span>';
							}else{
								$htmlstr.=$led_disable.'&nbsp&nbsp<span id="'.$seri_name.'_'.$sim_id.'" '.$style.'>'.language('Empty Bank').'</span>';
							}
							break;
						}
						
						if($result[$sim_index]['b_sim_abnormal'] == 1){
							$htmlstr .= "<span class='abnormal' style='color:red;font-weight:bold;font-size:20px;position:absolute;margin-left:3px;'>!</span>";
						}
						
						//END OF LED
						//$htmlstr.="<br>".$result[$sim_index]['id'];
						$htmlstr.="<span class='showhelp'>";
						$htmlstr.='SimBank Index: '.$result[$sim_index]['ob_sb_seri']."<br>";
						$htmlstr.='SimBank ID: '.$result[$sim_index]['ob_sb_link_bank_nbr']."<br>";
						$htmlstr.='SimBank No.: '.$result[$sim_index]['ob_sb_link_sim_nbr']."<br>";
						$htmlstr.='Gateway Index: '.$result[$sim_index]['ob_gw_seri']."<br>";
						//$htmlstr.='Gateway Board No.: '.$result[$sim_index]['ob_gw_link_bank_nbr']."<br>";
						//$htmlstr.='Gateway Slot No.: '.$result[$sim_index]['ob_gw_link_slot_nbr']."<br>";
						$htmlstr.='Gateway Sim No.: '.$result[$sim_index]['n_sim_no']."<br>";
						$htmlstr.='Gateway Port No.: '.$result[$sim_index]['ob_gw_link_chn_nbr']."<br>";
						$htmlstr.='Phone No.: '.$result[$sim_index]['v_sim_phone_number']."<br>";
						$htmlstr.='Sim Balance: '.$result[$sim_index]['n_sim_balance']."<br>";
						$htmlstr.='Sim Rest Time:' . $result[$sim_index]['n_sb_link_call_rest_time']. " min" . "<br>";
						if($result[$sim_index]['b_sim_abnormal'] == 1){
							$htmlstr.="<span style='color:red;font-weight:bold;'><img src='/images/warning_1.png' style='width:18px;'/> ".language('Sim Abnormal')."</span>";
						}
						$htmlstr.="</span></div><input type='hidden' id='ob_sb_seri' value='".$result[$sim_index]['ob_sb_seri']."' /><input type='hidden' id='ob_sb_link_bank_nbr' value='".$result[$sim_index]['ob_sb_link_bank_nbr']."' /><input type='hidden' id='ob_sb_link_sim_nbr' value='".$result[$sim_index]['ob_sb_link_sim_nbr']."' /></td>";
						
						if ($i==8){
							$htmlstr.="</tr><tr>";	
						} 
						$sim_index++;
					}
					$htmlstr.="</tr>";
					}
					
				//$htmlstr.="<tr><td colspan='9'><div style='display:inline'>".$led_idle."</div>&nbsp;".language('Idle')."&nbsp;&nbsp;<div style='display:inline'>".$led_busy."</div>&nbsp;".language('Busy');
				$htmlstr.="</table>";
				//$htmlstr.="<div id=\"newline\"></div>";
				//print_rr($result);
				// create html table 
				}
				
				echo $htmlstr;
				exit(0);					
				break;
			case 'connection_status':
				if(file_exists('/tmp/OPlink.status')){
					$status = file_get_contents('/tmp/OPlink.status');
				}else{
					$status = '';
				}
				echo $status;
				break;
			case 'sync_time_from_ntp':
				sync_time_from_ntp();
				break;
			case 'sync_time_from_client':
				sync_time_from_client();
				break;
		}
	}

function sync_time_from_ntp(){
	$ntpserver[1] = $_GET['ntp_server1'];
	$ntpserver[2] = $_GET['ntp_server2'];
	$ntpserver[3] = $_GET['ntp_server3'];
	
	$final_show = '';

	$final_show  = '<font color=ff0000>NTP';
	$final_show .= language('Synchronize Failed');
	$final_show .= '</font>';

	for($i=1;$i<=3;$i++){
		if($ntpserver[$i] != '') {
			$ret = `/tools/ntpclient -h "$ntpserver[$i]" -s > /dev/null 2>&1 && echo -n 'ok'`;
			if($ret == 'ok') {
				$final_show  = "NTP: [".$ntpserver[$i]."] ";
				$final_show .= language("Synchronize Succeeded");
				
				system("hwclock -w");
				break;
			}
		}
	}
	sleep(20);
	echo $final_show;
}

function sync_time_from_client(){
	$final_show  = '<font color=ff0000>';
	$final_show .= language('Client Synchronize Failed');
	$final_show .= '</font>';

	if( isset($_POST['local_yea']) &&
		isset($_POST['local_mon']) &&
		isset($_POST['local_dat']) &&
		isset($_POST['local_hou']) &&
		isset($_POST['local_min']) &&
		isset($_POST['local_sec'])
	) {
		//date -s [MMDDhhmm[[CC]YY][.ss]]
		$ts = sprintf("%02d%02d%02d%02d%04d.%02d",$_POST['local_mon'],$_POST['local_dat'],$_POST['local_hou'],$_POST['local_min'],$_POST['local_yea'],$_POST['local_sec']);
		$cmd = "date \"$ts\"";
		$ret = `$cmd > /dev/null 2>&1 && echo -n 'ok'`;
		if($ret == 'ok') {
			$final_show  = language("Client Synchronize Succeeded");
			system("hwclock -w");
		}
	}
	
	echo $final_show;
}

function get_system_time()
{
        $all_time = `date "+%Y:%m:%d:%H:%M:%S"`;
        $item = explode(':', $all_time, 6); 
        if(isset($item[5])) {
                $year = $item[0];
                $month = $item[1];
                $date = $item[2];
                $hour = $item[3];
                $minute = $item[4];
                $second = $item[5];
        }
	return "$year-$month-$date $hour:$minute:$second";
}


