<?php
//header('Content-type: text/html;charset=GB2312'); 
//header('Content-type: text/html;charset=utf8'); 
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

include_once("../inc/function.inc");
include_once("../inc/language.inc");
include_once("../inc/wrcfg.inc");
include_once("../inc/cluster.inc");
include_once("../inc/aql.php");
require("../inc/mysql_class.php");

$language = get_web_language_cache('/tmp/web/language.cache');

function get_linkname_by_portid($links,$portid){
	//echo $links."<br>";
	if (strstr($links,":".$portid)){	
		$links=explode(',',trim($links));
		for ($i=0;$i<count($links);$i++){
				$tmp=explode(':',$links[$i]);
				if ($tmp[1]==$portid){
					return $tmp[0];
				}
		}
	}else{
		return "";
	}
}

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
	
	
	switch($_GET['action']) {
		case 'refresh_callstatus':
			//得到全中继的呼叫状态		
			$result=get_full_channel_status();			
			//print_rr($result);
			// create html table 
			$htmlstr="<table width=\"100%\" class=\"tshow\">";
			
			$htmlstr.=	"<tr><th style='text-align:center'>".language("Channel")."</th>";		
			$htmlstr.="<th style='text-align:center'>".language("Status")."</th>";
			$htmlstr.="<th style='text-align:center'>".language("Direction")."</th>";
			$htmlstr.="<th style='text-align:center'>".language("CallerID")."</th>";
			$htmlstr.="<th style='text-align:center'>".language("CalleeID")."</th>";
			$htmlstr.="<th style='text-align:center'>".language("AnsweredTime")."</th>";
			$htmlstr.="<th style='text-align:center'>".language("Duration")."</th>";
			/*
			$htmlstr.="<th style='text-align:center'>".language("ConnectlineID")."</th>";
			$htmlstr.="<th style='text-align:center'>".language("Directbridge")."</th>";
			$htmlstr.="<th style='text-align:center'>".language("DestChan")."</th>";			
			*/
			$htmlstr.=	"</tr>";
			
			
			if (isset($result)){
				foreach ($result as $row){
					$htmlstr.="<tr>";
					$htmlstr.="<td align=center>".$row['id']."</td>";
					$htmlstr.="<td align=center>".$row['Status']."</td>";
					$htmlstr.="<td align=center>".$row['Direction']."</td>";
					$htmlstr.="<td align=center>".$row['CallerID']."</td>";
					$htmlstr.="<td align=center>".$row['CalleeID']."</td>";
					$htmlstr.="<td align=center>".$row['AnsweredTime']."</td>";
					$htmlstr.="<td align=center>".$row['Duration']."</td>";
					/*
					$htmlstr.="<td align=center>".$row['ConnectlineID']."</td>";				
					$htmlstr.="<td align=center>".$row['Directbridge']."</td>";
					$htmlstr.="<td align=center>".$row['DestChan']."</td>";
					*/
					$htmlstr.="</tr>";
				}
			}
			
			$htmlstr.="</table>";
			$html=$htmlstr;
			echo $html;
			//exit(0);					
			break;
		case 'refresh_channels':
			
			if (isset($_SESSION['all_interface'])){
				$_SESSION['all_interface']=get_interface_type(1);
			}			
			$interface=$_SESSION['all_interface'];
			
			$signal=$_SESSION['dahdi-channels'][1]['signalling'];
			
			
			//echo $_SESSION['ss7_mode'];
			if ($_SESSION['ss7_mode']=="yes"){
				$signal='ss7'; //无论是chan_ss7还是lib_ss7,这里都设置为SS7
				//GET schannel status
				$aql=new aql;
				
				
				//get port array
				if ($_SESSION['ss7_type']=='ss7'){
					$setok = $aql->set('basedir','/etc/asterisk');
					if (!$setok) {
						echo $aql->get_error();
						return;
					}
					$db=$aql->query("select * from ss7.conf where section like 'host-%'");
					$hostname="host-".get_hostname();
					$links=$db[$hostname]['links'];
					
					
					$db=$aql->query("select * from ss7.conf where section like 'link-%'");
					for ($spanid=1;$spanid<=$_SESSION['port_count'];$spanid++){
						$schannel_list['l'.$spanid]=$db['link-l'.$spanid]['schannel'];
					}					
				}else{ //$_SESSION['ss7_type']=='libss7'
					$setok = $aql->set('basedir','/etc/asterisk/gw');
					if (!$setok) {
						echo $aql->get_error();
						return;
					}
					
					for ($spanid=1;$spanid<=$_SESSION['port_count'];$spanid++){
						$schannel_list['l'.$spanid]="";						
					}
					
					
					$db=$aql->query("select * from dahdi-ss7-channels-mirror.conf where section like 'linkset-%'");
					$schannel_max=4;
					for ($schannelid=1;$schannelid<=$schannel_max;$schannelid++){
						if (isset($db["linkset-".$schannelid]['sigchan'])){							
							$spanid=get_spanid_by_channel($db["linkset-".$schannelid]['sigchan']);							
							$schannel_list['l'.$spanid]=$db["linkset-".$schannelid]['sigchan'];							
							//转变成为其在E1/T1内的本身中继内的时隙序号
							if ($interface=='e1') {	
								$schannel_list['l'.$spanid]=$db["linkset-".$schannelid]['sigchan']-31*($spanid-1);
							}else{
								$schannel_list['l'.$spanid]=$db["linkset-".$schannelid]['sigchan']-24*($spanid-1);
							}
							
						}
					}
					
				}
				
			}
			
			//echo	$signal;
			
			//create $result
			if ($signal=='mfcr2'){
				$command="mfcr2 show channels";
				$result = execute_astcmd($command);	
				$result=strstr($result,'Rx CAS');
				$result=strstr($result,'<br/>');
				$result=trim($result,'<br/>');
				$result=trim($result);		
				$result=explode('<br/>',$result);
				$itemcount=count($result);			
				for ($i=0;$i<=$itemcount;$i++){
					$result[$i]=explode(' ',$result[$i]);
					$subcount=count($result[$i]);					
					$new_result='';
					for ($ii=0;$ii<=$subcount;$ii++){
						if (trim($result[$i][$ii])<>''){
							$new_result[]=$result[$i][$ii];
						}
					}
					$result[$i]=$new_result;					
				}
				
			}else if ($signal=='ss7') { //这里的SS7是包括了chan-SS7和lib-SS7
				
				if ($_SESSION['ss7_type']=='ss7'){
					$command="ss7 linestat";
				}else{//libss7
					$command="ss7 show channels";
				}
				$result_raw = execute_astcmd($command);	
					if ($_SESSION['ss7_type']=='ss7'){
						for ($spanid=1;$spanid<=$_SESSION['port_count'];$spanid++){
							$link=get_linkname_by_portid($links,$spanid);						
							
							if ($link<>"") {
								$result=strstr($result_raw,$link);						
							}else{
								$result='';
							}
							//echo $result;
							if ($result){
								$result=substr($result,3);
								$result=explode('<br/>',$result);				
								
								$linkname=get_linkname_by_portid($links,$spanid);
								if ($schannel_list[$linkname]==""){
									$tmpint=1;								
								}else{
									$tmpint=0;
								}
								if ($interface=='e1') {
									for ($i=1;$i<=(30+$tmpint);$i++){
										$new_result[]=explode('|',$result[$i]);
									}
								}else{
									for ($i=1;$i<=(23+$tmpint);$i++){
										$new_result[]=explode('|',$result[$i]);
									}
								}
						
							}else{ //disable link
								
								$linkname=get_linkname_by_portid($links,$spanid);
								if ($schannel_list[$linkname]==""){
									$tmpint=1;								
								}else{
									$tmpint=0;
								}
								
								if ($interface=='e1') {
									for ($i=1;$i<=(30+$tmpint);$i++){
									$new_result[]="disable";
									}
								}else{
									for ($i=1;$i<=(23+$tmpint);$i++){
									$new_result[]="disable";
									}									
								}
								
							}
						}
						$result=$new_result;	
						
					}
					if ($_SESSION['ss7_type']=='libss7'){						
						$result_raw=strstr($result_raw,'Name');
						$result_raw=trim($result_raw,'Name');
						$result_raw=trim($result_raw);
						$result=explode("\n",$result_raw);						
						$itemcount=count($result);						
					
						
						//$new_result[]="";
						$index1=0;
						for ($spanid=1;$spanid<=$_SESSION['port_count'];$spanid++){
							$linkname='l'.$spanid;
							if ($schannel_list[$linkname]==""){
								$tmpint=1;								
							}else{
								$tmpint=0;
							}
							
							if ($interface=='e1') {
								for ($i=1;$i<=(30+$tmpint);$i++){
									$tmp_array=explode('|',$result[$index1]);
									$new_result[trim($tmp_array[1])]=$tmp_array;
									ksort($new_result);
									$index1++;
								}
							}else{
								for ($i=1;$i<=(23+$tmpint);$i++){
									$tmp_array=explode('|',$result[$index1]);
									$new_result[trim($tmp_array[1])]=$tmp_array;	
									//$new_result[]=explode('|',$result[$index1]);
									ksort($new_result);
									$index1++;									
								}
							}
							
						
						}						
						//
						$result=$new_result;
						
					}
					
				}
			else{		//pri 	
				$command="pri show channels";
				$result = execute_astcmd($command);	
				
				$result=strstr($result,'Call Name');
				$result=strstr($result,'<br/>');
				$result=trim($result,'<br/>');
				$result=trim($result);		
				$result=explode('<br/>',$result);
				
				$itemcount=count($result);
				for ($i=0;$i<=$itemcount;$i++){
					$result[$i]=explode(' ',$result[$i]);					
					$subcount=count($result[$i]);					
					$new_result='';
					for ($ii=0;$ii<=$subcount;$ii++){
						if (trim($result[$i][$ii])<>''){
							$new_result[]=$result[$i][$ii];
						}
					}
					$result[$i]=$new_result;
				}
				
			}
			
			//print_rr($result);
			
			// create html table 
			$htmlstr="<table width=\"100%\" class=\"tshow\">";
			
			
			
			////////////////////////////e1 mode///////////////////////
			if ($interface=='e1') {	
				$htmlstr.=	"<tr><th style='text-align:center'>".language("Port")."</th>";		
				for ($channelid=1;$channelid<=31;$channelid++){				
					$htmlstr.="<th style='text-align:center'>$channelid</th>";
				}						
				$htmlstr.=	"</tr>";	
				
				$index1=1;
				$showid=1;
				for ($spanid=1;$spanid<=$_SESSION['port_count'];$spanid++){
					
					$htmlstr.=	"<tr><td align=center>$spanid</td>";
					for ($channelid=1;$channelid<=31;$channelid++){
						$tmpstr='';
						
						//print_rr($result[$index1]);
						$linkname=get_linkname_by_portid($links,$spanid);	
						
						if ((($channelid==0) || ($channelid==$schannel_list[$linkname]) ) && ($_SESSION['ss7_type']=="ss7")) {
							$htmlstr.="<td align=center>$showid<br>".$led_signal."</td>";				
							//$index1++;
							$showid++;
						}else if (($schannel_list['l'.$spanid]==$channelid) && ($_SESSION['ss7_type']=="libss7")){							
							$htmlstr.="<td align=center>$showid<br>".$led_signal."</td>";							
							$index1++;
							$showid++;
						}else if ((($channelid==0) || ($channelid==16)) && ($_SESSION['ss7_mode']=="no")){
							$htmlstr.="<td align=center>$showid<br>".$led_signal."</td>";
							$showid++;
							
						}else{
							
							$index2=$channelid+($spanid-1)*31;														
							if ($signal=='mfcr2'){								
								//6=tx 7=rx
								if ((strstr($result[$index1-1][6],'0x')<>'') ||(strstr($result[$index1-1][7],'0x')<>'') || ($result[$index1-1][6]=='BLOCK') || ($result[$index1-1][7]=='BLOCK') ){									
									if (strstr($result[$index1-1][6],'0x')<>''){
										$tmpstr=$led_local_unused;
									}else if (strstr($result[$index1-1][7],'0x')<>''){
										$tmpstr=$led_remote_unused;
									}else if ($result[$index1-1][6]=='BLOCK'){
										$tmpstr=$led_local_block;
									}else if ($result[$index1-1][7]=='BLOCK'){
										$tmpstr=$led_remote_block;
									}else {						
										$tmpstr=$led_disable;
									}
								}else{
									if (($result[$index1-1][6]=='IDLE')&& ($result[$index1-1][7]=='IDLE')) {
										$tmpstr=$led_idle;
									}else if (!isset($result[$index1-1][6])){ // asterisk is down
										$tmpstr=$led_disable;
									}
									else{
										$tmpstr=$led_busy;
									}
								}
								$tmpstr="$showid<br>".$tmpstr;
								$htmlstr.="<td align=center>$tmpstr</td>";											
								$index1++;	
								$showid++;								
							}else if ($signal=='ss7'){// SS7
								if ($_SESSION['ss7_type']=='ss7'){
									
									if (trim($result[$index1-1][3])=='BLOCKED'){
										$tmpstr=$led_disable;
										
										if ((trim($result[$index1-1][4])=='Remote Maintenance') or (trim($result[$index1-1][4])=='Remote Hardware')){
											$tmpstr=$led_remote_block;
											
										}
										if ((trim($result[$index1-1][4])=='Local Maintenance') or (trim($result[$index1-1][4])=='Local Hardware')){
											$tmpstr=$led_local_block;	
											
										}
										if (trim($result[$index1-1][4])=='Local NoUse'){
											$tmpstr=$led_local_unused_unused;
										}
										if (trim($result[$index1-1][4])=='Link down'){
											$tmpstr=$led_linkdown;
										}
										if (trim($result[$index1-1][4])=='Unequipped CIC'){
											$tmpstr=$led_unequipped;
										}
										
										
									
									//	
										
									}else if (trim($result[$index1-1][2])=='Idle'){
										$tmpstr=$led_idle;				
																		
									}else if (trim($result[$index1-1][2])=='Idle Reset pending'){
										$tmpstr=$led_pending;																				
									}								
									else if (trim($result[$index1-1][2])==''){									
										$tmpstr=$led_disable;
									}else if ($result[$index1-1]=="disable"){									
										$tmpstr=$led_disable;
									}else if (trim($result[$index1-1][2])<>'Idle'){									
										$tmpstr=$led_busy;
									}								
									$tmpstr="$showid<br>".$tmpstr;
									$htmlstr.="<td align=center>$tmpstr</td>";	
									$showid++;
								}
								if ($_SESSION["ss7_type"]=="libss7"){
									/*
										第一列是该channel所在的链路集，哪一个linkset， 
										第二列是该channel的编号，哪一个通道，										
										如果第三列为Yes，第六列为Idle，则web上显示Idle。 										
										//如果第三列为Yes，第六列不为Idle，则web上显示Busy。										
										如果第三列为No， 第六列为Idle，则web上显示disable
										如果第三列为No， 第六列不为Idle，则web上显示busy
										第四列是该channel是否本地阻塞，“local blocked”，值为yes或者no。
										第五列是该channel是否远端阻塞，“remote blocked”，值为yes或者no。
										第六列是该channel通话状态，主要有：Idle，Allocated，Continuity，Setup，Proceeding，Alerting，Connect，Glare，Unknown等9种状态。
												 咱们E1网关的web页面只需要显示4种：Idle，Setup，Connect，Alerting, Unknown。
										第七列表示该channel是否收到过对方发送的SS7消息，值为yes或者no。
										第八列表示该channel的名称（呼叫状态需要用到）。
									*/
									
										//print_rr($result);
										
										if (trim($result[$index1][3])=="Yes"){										
											$tmpstr=$led_local_block;
										}else if (trim($result[$index1][4])=="Yes"){
											$tmpstr=$led_remote_block;
										}else if ((trim($result[$index1][2])=="Yes") and (trim($result[$index1][5])=="Idle") ){
											$tmpstr=$led_idle;
										}else if ((trim($result[$index1][2])=="No") and (trim($result[$index1][5])=="Alerting") ){
											$tmpstr=$led_alerting;
										}else if ((trim($result[$index1][2])=="No") and (trim($result[$index1][5])!="Idle") ){
											$tmpstr=$led_busy;
										}else if ((trim($result[$index1][2])=="No") and (trim($result[$index1][5])=="Idle") ){
											$tmpstr=$led_disable;
										}else{ 
										//echo $result[$index1][5]."<br>";
											switch (trim($result[$index1][5])){
												case "Idle":																									
													$tmpstr=$led_idle;
													break;
												case "Setup":
													$tmpstr=$led_pending;
													break;
												case "Connect":
													$tmpstr=$led_busy;
													break;
												case "Alerting":
													$tmpstr=$led_alerting;
													break;												
												default:
													$tmpstr=$led_unknown;
													
												
											}
										}
									
									
									$tmpstr="$showid<br>".$tmpstr;
									$htmlstr.="<td align=center>$tmpstr</td>";	
									//$htmlstr.="<td align=center>$tmpstr<br>".$result[$index1][1]."</td>";	
									
									$showid++;
								}
								$index1++;	
							
								
							}else{ // PRI
								////////
							    
								//print_rr($result[$index1]);
								
								$tmpstr=$result[$index1-1][3];
								if (($result[$index1-1][3]=='No') && ($result[$index1-1][4]=='Idle')){
									$tmpstr=$led_disable;								
								}else{
									$tmpstr=trim($result[$index1-1][4]);									
									if ($tmpstr=="Idle"){										
										//$tmpstr=$led_idle;
										//print_rr($_SESSION['pri_node_type']);
										if ($_SESSION['pri_status'][$result[$index1-1][0]] == 'Up')
											if ($_SESSION['pri_node_type'][$spanid]==false){//本段和对端有具有一样的类型									
												$tmpstr=$led_reload;
											}else {
												$tmpstr=$led_idle;											
											}
										else {
											$tmpstr=$led_disable;
										}
									}else if (!isset($result[$index1-1][4])){ // asterisk is down										
										$tmpstr=$led_disable;
									}else{										
										$tmpstr=$led_busy;
									}
								}
								
								$tmpstr="$showid<br>".$tmpstr;
								$htmlstr.="<td align=center>$tmpstr</td>";											
								$index1++;
								$showid++;
							}		
						}
					}						
					$htmlstr.=	"</tr>";
				}			
				$htmlstr.="<tr><td colspan='33'><div style='display:inline'>".$led_idle."</div>&nbsp;".language('Idle')."&nbsp;&nbsp;<div style='display:inline'>".$led_busy."</div>&nbsp;".language('Busy');
				
				if (($signal=='pri_net') or ($signal=='pri_cpe')) {
					$htmlstr.="&nbsp;&nbsp;<div style='display:inline'>".$led_reload."</div>&nbsp;".language('Same Node Type');
				}
				if ($signal=='mfcr2'){
				$htmlstr.="&nbsp;&nbsp;<div style='display:inline'>".$led_local_block."</div>&nbsp;".language('Local Blocked')."&nbsp;&nbsp;<div style='display:inline'>".$led_remote_block."</div>&nbsp;".language('Remode Blocked')."&nbsp;&nbsp;<div style='display:inline'>".$led_local_unused."</div>&nbsp;".language('Local Unavailable')."&nbsp;&nbsp;<div style='display:inline'>".$led_remote_unused."</div>&nbsp;".language('Remode Unavailable');
				}
				if ($signal=='ss7'){
					if ($_SESSION['ss7_type']=="ss7"){
						$htmlstr.="&nbsp;&nbsp;<div style='display:inline'>".$led_pending."</div>&nbsp;".language('Pending')."&nbsp;&nbsp;<div style='display:inline'>".$led_local_block."</div>&nbsp;".language('Local Blocked')."&nbsp;&nbsp;<div style='display:inline'>".$led_remote_block."</div>&nbsp;".language('Remode Blocked')."&nbsp;&nbsp;<div style='display:inline'>".$led_local_unused."</div>&nbsp;".language('Local NoUse')."&nbsp;&nbsp;<div style='display:inline'>".$led_linkdown."</div>&nbsp;".language('Link Down')."&nbsp;&nbsp;<div style='display:inline'>".$led_unequipped."</div>&nbsp;".language('Unequipped CIC');
					}else{
						$htmlstr.="&nbsp;&nbsp;<div style='display:inline'>".$led_alerting."</div>&nbsp;".language('Alerting')."&nbsp;&nbsp;<div style='display:inline'>".$led_local_block."</div>&nbsp;".language('Local Blocked')."&nbsp;&nbsp;<div style='display:inline'>".$led_remote_block."</div>&nbsp;".language('Remode Blocked')."&nbsp;&nbsp;<div style='display:inline'>".$led_unknown."</div>&nbsp;".language('Unknown')."&nbsp;&nbsp;<div style='display:inline'>"."</div>";
					}
					
					
				}
				$htmlstr.="&nbsp;&nbsp;<div style='display:inline'>".$led_disable."</div>&nbsp;".language('Disable')."&nbsp;&nbsp;<div style='display:inline'>".$led_signal."</div>&nbsp;".language('S channel')."</td></tr>";
			}else{
			//////////////T1////////////////////////
			
			//// Only 'PRI' and 'SS7' be available in T1 mode 
			////                                
			///////////////////////////////////////
			$htmlstr.=	"<tr><th style='text-align:center'>Port</th>";		
			for ($channelid=1;$channelid<=24;$channelid++){				
				$htmlstr.="<th style='text-align:center'>$channelid</th>";
			}						
			$htmlstr.=	"</tr>";	
			$index1=1;
			$showid=1;
			for ($spanid=1;$spanid<=$_SESSION['port_count'];$spanid++){
				$htmlstr.=	"<tr><td align=center>$spanid</td>";
					for ($channelid=1;$channelid<=24;$channelid++){
						$tmpstr='';
						$linkname=get_linkname_by_portid($links,$spanid);						
						if ((($channelid==0) || ($channelid==$schannel_list[$linkname])) && ($_SESSION['ss7_type']=="ss7")) {
							$htmlstr.="<td align=center>$showid<br>".$led_signal."</td>";
							$showid++;
							//$index1++;
						}else if (($schannel_list['l'.$spanid]==$channelid) && ($_SESSION['ss7_type']=="libss7")){							
							$htmlstr.="<td align=center>$showid<br>".$led_signal."</td>";							
							$showid++;
							$index1++;
						}else if ((($channelid==0) || ($channelid==24)) && ($_SESSION['ss7_mode']=="no")){
							$htmlstr.="<td align=center>$showid<br>".$led_signal."</td>";
							$showid++;
							
						}else{
							$index2=$channelid+($spanid-1)*24;							
							
							if ($signal=='ss7'){// SS7
								if ($_SESSION['ss7_type']=='ss7'){
									if (trim($result[$index1-1][3])=='BLOCKED'){
										$tmpstr=$led_disable;
										if ((trim($result[$index1-1][4])=='Remote Maintenance') or (trim($result[$index1-1][4])=='Remote Hardware')){
											$tmpstr=$led_remote_block;
											
										}
										if ((trim($result[$index1-1][4])=='Local Maintenance') or (trim($result[$index1-1][4])=='Local Hardware')){
											$tmpstr=$led_local_block;										
										}
										if (trim($result[$index1-1][4])=='Local NoUse'){
											$tmpstr=$led_local_unused_unused;
										}
										if (trim($result[$index1-1][4])=='Link down'){
											$tmpstr=$led_linkdown;
										}
										
									}else if (trim($result[$index1-1][2])=='Idle'){
										$tmpstr=$led_idle;				
									}else if (trim($result[$index1-1][2])=='Idle Reset pending'){
										$tmpstr=$led_pending;																				
																		
									}else if (trim($result[$index1-1][2])==''){									
										$tmpstr=$led_disable;
									}else if ($result[$index1-1]=="disable"){									
										$tmpstr=$led_disable;
									}else if (trim($result[$index1-1][2])<>'Idle'){									
										$tmpstr=$led_busy;
									}
									$tmpstr="$showid<br>".$tmpstr;
									$htmlstr.="<td align=center>$tmpstr</td>";	
									}
									if ($_SESSION["ss7_type"]=="libss7"){
										/*
										第一列是该channel所在的链路集，哪一个linkset， 
										第二列是该channel的编号，哪一个通道，										
										如果第三列为Yes，第六列为Idle，则web上显示Idle。 										
										//如果第三列为Yes，第六列不为Idle，则web上显示Busy。										
										如果第三列为No， 第六列为Idle，则web上显示disable
										如果第三列为No， 第六列不为Idle，则web上显示busy
										第四列是该channel是否本地阻塞，“local blocked”，值为yes或者no。
										第五列是该channel是否远端阻塞，“remote blocked”，值为yes或者no。
										第六列是该channel通话状态，主要有：Idle，Allocated，Continuity，Setup，Proceeding，Alerting，Connect，Glare，Unknown等9种状态。
												 咱们E1网关的web页面只需要显示4种：Idle，Setup，Connect，Alerting, Unknown。
										第七列表示该channel是否收到过对方发送的SS7消息，值为yes或者no。
										第八列表示该channel的名称（呼叫状态需要用到）。
										*/
										
										//$tmpstr=$led_disable."<br>".$result[$index1][1];
										if (trim($result[$index1][3])=="Yes"){										
											$tmpstr=$led_local_block;
										}else if (trim($result[$index1][4])=="Yes"){
											$tmpstr=$led_remote_block;
										}else if ((trim($result[$index1][2])=="Yes") and (trim($result[$index1][5])=="Idle") ){
											$tmpstr=$led_idle;
										}else if ((trim($result[$index1][2])=="No") and (trim($result[$index1][5])=="Alerting") ){	
											$tmpstr=$led_alerting;
										}else if ((trim($result[$index1][2])=="No") and (trim($result[$index1][5])!="Idle") ){
											$tmpstr=$led_busy;
										}else if ((trim($result[$index1][2])=="No") and (trim($result[$index1][5])=="Idle") ){
											$tmpstr=$led_disable;									
										}else{  
											//echo $result[$index1][5]."<br>";
											switch (trim($result[$index1][5])){
												case "Idle":												
												
													$tmpstr=$led_idle;
													break;
												case "Setup":
													$tmpstr=$led_pending;
													break;
												case "Connect":
													$tmpstr=$led_busy;
													break;
												case "Alerting":
													$tmpstr=$led_alerting;
													break;												
												default:
													$tmpstr=$led_unknown;
													
												
											}
										}
										
										
										$tmpstr="$showid<br>".$tmpstr;
										$htmlstr.="<td align=center>$tmpstr</td>";	
										//$htmlstr.="<td align=center>$tmpstr<br>".$result[$index1][1]."</td>";	
										
										//$showid++;
								}								
								$index1++;
								$showid++;
							}else{ //PRI
								$tmpstr=$result[$index1-1][3];
								if (($result[$index1-1][3]=='No') && ($result[$index1-1][4]=='Idle')){
									$tmpstr=$led_disable;								
								}else{
									$tmpstr=trim($result[$index1-1][4]);									
									if ($tmpstr=="Idle"){										
										//$tmpstr=$led_idle;
										//print_rr($_SESSION['pri_node_type']);
										if ($_SESSION['pri_status'][$result[$index1-1][0]] == 'Up')
											if ($_SESSION['pri_node_type'][$spanid]==false){//本段和对端有具有一样的类型									
												$tmpstr=$led_reload;
											}else {
												$tmpstr=$led_idle;											
											}
										else {
											$tmpstr=$led_disable;
										}
									}else if (!isset($result[$index1-1][4])){ // asterisk is down
										$tmpstr=$led_disable;
									}else{										
										$tmpstr=$led_busy;
									}
								}
								$tmpstr="$showid<br>".$tmpstr;
								$htmlstr.="<td align=center>$tmpstr</td>";											
								$index1++;	
								$showid++;								
							}
						}						
					}
					$htmlstr.=	"</tr>";
				}
				$htmlstr.="<tr><td colspan='33'><div style='display:inline'>".$led_idle."</div>&nbsp;".language('Idle')."&nbsp;&nbsp;<div style='display:inline'>".$led_busy."</div>&nbsp;".language('Busy');
				if (($signal=='pri_net') or ($signal=='pri_cpe')) {
					$htmlstr.="&nbsp;&nbsp;<div style='display:inline'>".$led_reload."</div>&nbsp;".language('Same Node Type');
				}
				if ($signal=='mfcr2'){
				$htmlstr.="&nbsp;&nbsp;<div style='display:inline'>".$led_local_block."</div>&nbsp;".language('Local Blocked')."&nbsp;&nbsp;<div style='display:inline'>".$led_remote_block."</div>&nbsp;".language('Remode Blocked')."&nbsp;&nbsp;<div style='display:inline'>".$led_local_unused."</div>&nbsp;".language('Local Unavailable')."&nbsp;&nbsp;<div style='display:inline'>".$led_remote_unused."</div>&nbsp;".language('Remode Unavailable');
				}
				if ($signal=='ss7'){
					if ($_SESSION['ss7_type']=="ss7"){
						$htmlstr.="&nbsp;&nbsp;<div style='display:inline'>".$led_pending."</div>&nbsp;".language('Pending')."&nbsp;&nbsp;<div style='display:inline'>".$led_local_block."</div>&nbsp;".language('Local Blocked')."&nbsp;&nbsp;<div style='display:inline'>".$led_remote_block."</div>&nbsp;".language('Remode Blocked')."&nbsp;&nbsp;<div style='display:inline'>".$led_local_unused."</div>&nbsp;".language('Local NoUse')."&nbsp;&nbsp;<div style='display:inline'>".$led_linkdown."</div>&nbsp;".language('Link Down')."&nbsp;&nbsp;<div style='display:inline'>".$led_unequipped."</div>&nbsp;".language('Unequipped CIC');
					}else{
						$htmlstr.="&nbsp;&nbsp;<div style='display:inline'>".$led_alerting."</div>&nbsp;".language('Alerting')."&nbsp;&nbsp;<div style='display:inline'>".$led_local_block."</div>&nbsp;".language('Local Blocked')."&nbsp;&nbsp;<div style='display:inline'>".$led_remote_block."</div>&nbsp;".language('Remode Blocked')."&nbsp;&nbsp;<div style='display:inline'>".$led_unknown."</div>&nbsp;".language('Unknown')."&nbsp;&nbsp;<div style='display:inline'>"."</div>";
					}
				}
				$htmlstr.="&nbsp;&nbsp;<div style='display:inline'>".$led_disable."</div>&nbsp;".language('Disable')."&nbsp;&nbsp;<div style='display:inline'>".$led_signal."</div>&nbsp;".language('S channel')."</td></tr>";
				
			}
			$htmlstr.="</table>";
			
			echo $htmlstr;
			
		
			
			exit(0);					
			break;
		
		
		case 'refresh_pri':
			
			$htmlstr="<table width='100%' class='tshow' id='table_pri'><tr>";
			for ($portindex=1;$portindex<=$_SESSION["port_count"];$portindex++){
				$htmlstr.="<th style='text-align:center'>";
				$htmlstr.="Port ".$portindex;
				$htmlstr.="</th>";	
			}
			$htmlstr.="</tr>";
			
			$htmlstr.="<tr align='center'>";
			
			for ($portindex=1;$portindex<=$_SESSION["port_count"];$portindex++){
				$htmlstr.="<td>";
				
				$tmp=get_pri_status($portindex);
				
				if ($tmp=="Up"){
					
					$htmlstr.=$led_idle;
				}
				else if ($tmp=="Down"){
					
					$htmlstr.=$led_busy;		
				}else {
					$htmlstr.=$led_reload;		
				}
				$htmlstr.="</td>";	
				}
			$htmlstr.="</tr></table>";
			
			$htmlstr.="</table>";
			echo $htmlstr;
			exit(0);					
			break;
		
		case 'refresh_spans':
			
			$htmlstr="<table width='100%' class='tshow' id='table_spans'><tr>";
			for ($portindex=1;$portindex<=$_SESSION["port_count"];$portindex++){
				$htmlstr.="<th style='text-align:center'>";
				$htmlstr.=language("Port").$portindex;
				$htmlstr.="</th>";	
			}
			$htmlstr.="</tr><tr align='center'>";
			for ($portindex=1;$portindex<=$_SESSION["port_count"];$portindex++){
				$htmlstr.="<td>";
				$tmp=get_span_status($portindex);
				if ($tmp=="OK"){
					
					$htmlstr.=$led_idle;					
				}
				else if ($tmp=="RED"){
					
					$htmlstr.=$led_busy;		
				}else {					
					$htmlstr.=$led_reload;
				}
				$htmlstr.="</td>";	
				}
			$htmlstr.="</tr>";
			
			
			$htmlstr.="<tr><td colspan='33'><div style='display:inline'>".$led_idle."</div>&nbsp;".language('OK')."&nbsp;&nbsp;<div style='display:inline'>".$led_busy."</div>&nbsp;".language('Down')."&nbsp;&nbsp;<div style='display:inline'>".$led_reload."</div>&nbsp;".language('Reload')."</td></tr>";	
			$htmlstr.="</table>";			
			echo $htmlstr;			
			exit(0);		
			break;
		case "process_log":
			if ($_GET['log_type']=='sys_log') {											
				$url='/data/user/log/eventlog.txt';				
			}else if ($_GET['log_type']=='ast_log'){
				$url='/var/log/ast_messages';
			}else if ($_GET['log_type']=='mfcr2_log'){				
				$portid=$_GET['port'];								
				
				exec("mfcr2log_split.sh ".$portid);
				
				//$url='/var/log/pri_messages';
				//$url='/var/log/asterisk/'.$portid;
				$url='/var/log/asterisk/mfcr2_'.$portid.'.log';
				
			}else if ($_GET['log_type']=='pri_log'){				
				$portid=$_GET['port'];								
				exec("prilog_split.sh ".$portid);
				
				//$url='/var/log/pri_messages';
				//$url='/var/log/asterisk/'.$portid;
				$url='/var/log/asterisk/'.$portid;
				
			}else if ($_GET['log_type']=='sip_log'){
				$url='/var/log/asterisk/sip';
			}else if ($_GET['log_type']=='iax_log'){
				$url='/var/log/asterisk/iax2';
			}else if ($_GET['log_type']=='ss7_log'){
				$url='/var/log/asterisk/ss7';
				
			}
			
			$size=$_GET['size'];
			
			$filesize=filesize($url);	
			
			if ($size==0){
				$content=file_get_contents($url);
				$tmpsize=0;
			}else if ($filesize<$size) {
				$content=file_get_contents($url);
				$tmpsize="-".$filesize;
			}
			else{
				$content=file_get_contents($url,false,null,$size,$filesize-$size);
				$tmpsize=$filesize;
			}						
					
			if ($_GET['method']=='update'){
				if ($size==$filesize) {
					$content='';
				}				
				$content=$tmpsize."&".$content;
			}			
			
			if ($_GET['method']=='clean'){
				if ($size==$filesize) {
					$content='';
				}								
				
				system("echo -n ''> ".$url);
				
				if ($_GET['log_type']=='pri_log'){
					$url='/var/log/pri_messages';
					system("echo -n ''> ".$url);
				}
				
				if ($_GET['log_type']=='mfcr2_log'){
					$url='/var/log/asterisk/mfcr2log';
					system("echo -n ''> ".$url);
				}
				
				//$portid=$_GET['port'];
				//exec("prilog_split.sh ".$portid." cleanup");
				$content=$tmpsize."&".$content;
			}			
				
			//echo json_encode($content);
			echo $content;
			exit(0);
			
			
			
			
		
			break;
		case "update_status":
			$echostr="";
			$echostr.=get_system_time().": Rececive data is: ".$_GET['mypara'];
			echo json_encode($echostr);
			exit(0);
			break;
			
		case 'change_language':
			change_language();
			break;
		case 'cancel_language':
			cancel_language();
			break;
		case 'reset_limit':
			reset_limit();
			break;
		case 'openvpn_status':
			get_openvpn_status();
			break;
		case 'get_ping_log':
			get_ping_log();
			break;
		case 'ping_and_traceroute':
			ping_and_traceroute();
			break;
		case 'sim_batch_get_channel_num':
			sim_batch_get_channel_num();
			break;
		case 'query_sim_balance':
			query_sim_balance();
			break;
		case 'end_query_sim_balance':
			end_query_sim_balance();
			break;
		case 'get_sim_balance_status':
			get_sim_balance_status();
			break;
	}
}

function query_sim_balance(){
	$params = array();
	
	$xml = get_xml('SBKCheckSimBalance',$params);
	$wsdl = "http://127.0.0.1:8888/?wsdl";
	$client = new SoapClient($wsdl);
	$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
	print_r($result);
}

function end_query_sim_balance(){
	$params = array();
	
	$xml = get_xml('SBKCheckSimBalanceEnd',$params);
	$wsdl = "http://127.0.0.1:8888/?wsdl";
	$client = new SoapClient($wsdl);
	$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
	print_r($result);
}

function get_sim_balance_status(){
	$params = array();
	
	$xml = get_xml('SBKCheckSimBalanceStatus',$params);
	echo $xml;
	$wsdl = "http://127.0.0.1:8888/?wsdl";
	$client = new SoapClient($wsdl);
	$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
	print_r($result);
}

function sim_batch_get_channel_num(){
	$tmp_db = new mysql();
	$db = new mysql('simserver');
	
	$gateway_seri = $_GET['gateway_seri'];
	
	$data = $tmp_db->Get("tb_gateway_info","n_gw_links","where ob_gw_seri='".$gateway_seri."'");
	$info = mysqli_fetch_array($data,MYSQLI_ASSOC);
	
	//在其他绑定sim中使用过的通道
	$mapping_arr = [];
	$mapping_data = $db->Get('tb_mapping_info','ob_gw_chnl','where ob_gw_seri="'.$gateway_seri.'"');
	while($mapping_info = mysqli_fetch_array($mapping_data,MYSQLI_ASSOC)){
		array_push($mapping_arr,$mapping_info['ob_gw_chnl']);
	}
	
	$arr = [];
	for($i=1;$i<=$info['n_gw_links'];$i++){
		if(in_array($i,$mapping_arr)){
			continue;
		}
		
		array_push($arr,$i);
	}

	$arr_str = json_encode($arr);
	
	echo $arr_str;
}

function get_openvpn_status(){
	$info = file_get_contents('/tmp/log/openvpn.log');
	if(strstr($info,'Initialization Sequence Completed')){
		echo 'OK';
	}else{
		echo "Failed to connect";
	}
}

function reset_limit(){
	$ob_pol_name = $_GET['ob_pol_name'];
	
	$params = array( 'policyname' => $ob_pol_name );
	$xml = get_xml('SBKPolicyMonthDateUpdate',$params);
	$wsdl = "http://127.0.0.1:8888/?wsdl";
	$client = new SoapClient($wsdl);
	$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
	echo 'success';
}

function change_language(){
	$conf_file = "/config/simbank/conf/web_language.conf";
	if(isset($_GET['language_type'])){
		$aql = new aql();
		$hlock = lock_file($conf_file);
		$aql->set('basedir','/config/simbank/conf/');
		if(!$aql->open_config_file($conf_file)){
			echo $aql->get_error();
			unlock_file($hlock);
			return -1;
		}
		
		$res = $aql->query("select * from web_language.conf");
		
		$language = $_GET['language_type'];
		if(isset($res['general']['language'])){
			$aql->assign_editkey('general','language',$language);
		}else{
			$aql->assign_append('general','language',$language);
		}
		
		if(!$aql->save_config_file('web_language.conf')){
			echo $aql->get_error();
			unlock_file($hlock);
			return false;
		}
		unlock_file($hlock);
	}
	exec("/tools/web_language_init >/dev/null 2>&1 &");
}

function cancel_language(){
	$conf_file = "/config/simbank/conf/web_language.conf";
	$aql = new aql();
	$hlock = lock_file($conf_file);
	$aql->set('basedir','/config/simbank/conf/');
	if(!$aql->open_config_file($conf_file)){
		echo $aql->get_error();
		unlock_file($hlock);
		return -1;
	}
	
	$res = $aql->query("select * from web_language.conf");
	
	if(isset($res['general']['flag'])){
		$aql->assign_editkey('general','flag',1);
	}else{
		$aql->assign_append('general','flag',1);
	}
	
	if(!$aql->save_config_file('web_language.conf')){
		echo $aql->get_error();
		unlock_file($hlock);
		return false;
	}
	unlock_file($hlock);
}

function get_span_status($span_index){
	
	$command="dahdi show status";
	$result = execute_astcmd($command);		
	$result=explode("<br/>",$result);
	$result=$result[$span_index];
	$result=explode("|",$result);
	$ret=trim($result[1]);

	if (($_SESSION['dahdi-channels'][$span_index]['signalling']=='pri_net') or ($_SESSION['dahdi-channels'][$span_index]['signalling']=='pri_cpe')) {
		$command="pri show span ".$span_index;		
		$result = execute_astcmd($command);	
		//print_rr($result);
		$result=explode("\n",$result);
		
		$local_type=explode(" ",$result[3]);
		$local_type=trim($local_type[1]);
		$remode_type=explode(" ",$result[4]);
		$remode_type=trim($remode_type[2]);
		//echo $local_type;
		//echo $remode_type."<br>";
		
		if ($local_type==$remode_type){
			$_SESSION['pri_node_type'][$span_index]=false;
		}else{
			$_SESSION['pri_node_type'][$span_index]=true;
		}
		$_SESSION['pri_status'][$span_index]=get_pri_status($span_index);
		
	}
	return  $ret;
}


function get_pri_status($span_index){
	$command="pri show span $span_index";
	$result = execute_astcmd($command);		
	if (strpos($result,'Up')!=false){
		return 'Up';
	}else if (strpos($result,'Alarm')!=false){
		return 'Down';
	}else if (strpos($result,'Down')!=false){
		return 'Down';
	}else return 'NA';
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


function get_full_channel_status(){
	
	$idle_status['Status']=language("IDLE");
	$idle_status['Direction']="";
	$idle_status['CallerID']="";
	$idle_status['CalleeID']="";
	$idle_status['AnsweredTime']="";
	$idle_status['Duration']="";
	$idle_status['ConnectlineID']="";
	$idle_status['Directbridge']="";	
	$idle_status['DestChan']="";
	
	
	$signal=$_SESSION['signalling'];
	//echo $signal;
	if ($signal=='ss7'){$command='core show channels';}//ss7就是chan_ss7
	if ($signal=='mfcr2'){$command='core show channels';}
	if ($signal=='libss7'){$command='ss7 show channels';}
	if ($signal=='pri'){$command='pri show channels';}
	
	$result = execute_astcmd($command);
	//print_rr($result);
	//echo 
	if ($signal=='libss7'){		
		$result=strstr($result,'Name');
		$result=ltrim($result,'Name');
		$result=trim($result);
		$result=explode('<br/>',$result);
		foreach($result as $line){
			if (isset($line)){				
				$tmp=explode('|',$line);
				//print_rr($tmp);
				$chid=trim($tmp[1]);
				
				if (($chid<$_SESSION['channel_array']['first'])  or  ($chid>$_SESSION['channel_array']['last'])) continue;
				
				if ($chid=="") continue;
				if (trim($tmp[7])<>""){//'name column'				
					$chan_name=trim($tmp[7]);
					$command_channel='core show callstatus '.$chan_name;
					$result_line=execute_astcmd($command_channel);
					$result_line=strstr($result_line,$chan_name);					
					$result_line=trim($result_line,$chan_name);
					$result_line=trim($result_line,"<br/>");
					$result_line=explode('|',$result_line);
					$result_new[$chid]['id']=$chid;
					$result_new[$chid]['Status']=trim($result_line[1]);
					$result_new[$chid]['Direction']=trim($result_line[2]);
					$result_new[$chid]['CallerID']=trim($result_line[3]);
					$result_new[$chid]['CalleeID']=trim($result_line[4]);
					$result_new[$chid]['AnsweredTime']=trim($result_line[5]);
					$result_new[$chid]['Duration']=trim($result_line[6]);
					$result_new[$chid]['ConnectlineID']=trim($result_line[7]);
					$result_new[$chid]['Directbridge']=trim($result_line[8]);	
					$result_new[$chid]['DestChan']=trim($result_line[9]);					
				}else{
					$result_new[$chid]=$idle_status;
					$result_new[$chid]['id']=$chid;
				}
			}
		}
	}
	
	
	if ($signal=='pri'){		
		$result=strstr($result,'Name');
		$result=ltrim($result,'Name');
		$result=trim($result);
		
		$result=explode('<br/>',$result);
		
		foreach($result as $line){
			if (isset($line)){				
				$tmp=explode(' ',$line);
				
				$subcount=count($tmp);					
				$new_result='';
				for ($i=0;$i<=$subcount;$i++){
					if (trim($tmp[$i])<>''){
						$new_result[]=$tmp[$i];
					}
				}
				$tmp=$new_result;
				
				$chid=trim($tmp[1]);
				if (($chid<$_SESSION['channel_array']['first'])  or  ($chid>$_SESSION['channel_array']['last'])) continue;
				if ($chid=="") continue;
				if (isset($tmp[6])){//'name column'				
					$chan_name=trim($tmp[6]);
					$command_channel='core show callstatus '.$chan_name;
					$result_line=execute_astcmd($command_channel);
					$result_line=strstr($result_line,$chan_name);					
					$result_line=trim($result_line,$chan_name);
					$result_line=trim($result_line,"<br/>");
					$result_line=explode('|',$result_line);
					$result_new[$chid]['id']=$chid;
					$result_new[$chid]['Status']=trim($result_line[1]);
					$result_new[$chid]['Direction']=trim($result_line[2]);
					$result_new[$chid]['CallerID']=trim($result_line[3]);
					$result_new[$chid]['CalleeID']=trim($result_line[4]);
					$result_new[$chid]['AnsweredTime']=trim($result_line[5]);
					$result_new[$chid]['Duration']=trim($result_line[6]);
					$result_new[$chid]['ConnectlineID']=trim($result_line[7]);
					$result_new[$chid]['Directbridge']=trim($result_line[8]);	
					$result_new[$chid]['DestChan']=trim($result_line[9]);					
				}else{
					$result_new[$chid]=$idle_status;
					$result_new[$chid]['id']=$chid;
				}
			}
		}
	}
	
	
	if ($signal=='mfcr2'){		
		
		
		if ($_SESSION['all_interface']=='t1'){
			$total_channel=24*$_SESSION['port_count'];
			$div=24;
			$sub=24;
		}else{
			$total_channel=31*$_SESSION['port_count'];
			$div=16;
			$sub=31;
		}		
		for ($i=1;$i<=$total_channel;$i++){
			if (($i<$_SESSION['channel_array']['first'])  or  ($i>$_SESSION['channel_array']['last'])) continue;
			
			$tmp=(int)($i/$sub);	
			if ((($i-$tmp*$sub)%$div==0) and ($i<$total_channel)) continue;
			$result_new[$i]=$idle_status;
			$result_new[$i]['id']=$i;			
		}
		
		
		$result=strstr($result,'(Data)');
		$result=ltrim($result,'(Data)');
		$result=trim($result);
		$result=explode('<br/>',$result);
		foreach($result as $line){
			if (isset($line)){				
				$tmp=explode(' ',$line);
				//print_rr($tmp);				
				$tmp=$tmp[0];
				if (strstr($tmp,'DAHDI')!=FALSE){				
					$chid=strstr($tmp,'-',true);
					$chid=strstr($chid,'/');
					$chid=trim($chid,'/');
					if (($chid<$_SESSION['channel_array']['first'])  or  ($chid>$_SESSION['channel_array']['last'])) continue;
				}else{
					continue;
				}					
				if ($chid=="") continue;						
				$chan_name=$tmp;
				$command_channel='core show callstatus '.$chan_name;
				$result_line=execute_astcmd($command_channel);
				$result_line=strstr($result_line,$chan_name);					
				$result_line=trim($result_line,$chan_name);
				$result_line=trim($result_line,"<br/>");
				$result_line=explode('|',$result_line);
				$result_new[$chid]['id']=$chid;
				$result_new[$chid]['Status']=trim($result_line[1]);
				$result_new[$chid]['Direction']=trim($result_line[2]);
				$result_new[$chid]['CallerID']=trim($result_line[3]);
				$result_new[$chid]['CalleeID']=trim($result_line[4]);
				$result_new[$chid]['AnsweredTime']=trim($result_line[5]);
				$result_new[$chid]['Duration']=trim($result_line[6]);
				$result_new[$chid]['ConnectlineID']=trim($result_line[7]);
				$result_new[$chid]['Directbridge']=trim($result_line[8]);	
				$result_new[$chid]['DestChan']=trim($result_line[9]);				
			}
		}
	}
	
	if ($signal=='ss7'){		
		
		if ($_SESSION['all_interface']=='t1'){
			$total_channel=24*$_SESSION['port_count'];
			$div=24;
			$sub=24;
		}else{
			$total_channel=31*$_SESSION['port_count'];
			$div=16;
			$sub=31;
		}
		$aql=new aql;
		$setok = $aql->set('basedir','/etc/asterisk');
		if (!$setok) {
			echo $aql->get_error();
			return;
		}
		$db=$aql->query("select * from ss7.conf where section like 'link-%'");
		for ($spanid=1;$spanid<=$_SESSION['port_count'];$spanid++){
			$schannel_list[]=$db['link-l'.$spanid]['schannel'];
		}
		
		
		for ($i=1;$i<=$total_channel;$i++){		
			if (in_array($i,$schannel_list)) continue; //不把信令通道放入数组里
			if (($i<$_SESSION['channel_array']['first'])  or  ($i>$_SESSION['channel_array']['last'])) continue;
			
			$result_new[$i]=$idle_status;
			$result_new[$i]['id']=$i;			
		}
		
		
		$result=strstr($result,'(Data)');
		$result=ltrim($result,'(Data)');
		$result=trim($result);
		$result=explode('<br/>',$result);
		foreach($result as $line){
			if (isset($line)){				
				$tmp=explode(' ',$line);
				//print_rr($tmp);				
				$tmp=$tmp[0];
				if (strstr($tmp,'SS7')!=FALSE){				
					$chan_name=$tmp;
					$chid=strstr($tmp,'/');
					$chid=trim($chid,'/');
					$tmp=explode('/',$chid);					
					$chid=$tmp[1];					
					
				}else{
					continue;
				}					
				if (($chid<$_SESSION['channel_array']['first'])  or  ($chid>$_SESSION['channel_array']['last'])) continue;
		
				if ($chid=="") continue;						
				
				$command_channel='core show callstatus '.$chan_name;
				$result_line=execute_astcmd($command_channel);
				$result_line=strstr($result_line,$chan_name);					
				$result_line=trim($result_line,$chan_name);
				$result_line=trim($result_line,"<br/>");
				$result_line=explode('|',$result_line);
				$result_new[$chid]['id']=$chid;
				$result_new[$chid]['Status']=trim($result_line[1]);
				$result_new[$chid]['Direction']=trim($result_line[2]);
				$result_new[$chid]['CallerID']=trim($result_line[3]);
				$result_new[$chid]['CalleeID']=trim($result_line[4]);
				$result_new[$chid]['AnsweredTime']=trim($result_line[5]);
				$result_new[$chid]['Duration']=trim($result_line[6]);
				$result_new[$chid]['ConnectlineID']=trim($result_line[7]);
				$result_new[$chid]['Directbridge']=trim($result_line[8]);	
				$result_new[$chid]['DestChan']=trim($result_line[9]);				
			}
		}
	}
	
	
	if (isset($result_new)){
		ksort($result_new);
	}
	//print_rr($result_new);
	return $result_new;
}

function ping_and_traceroute(){
	$type = $_POST['type'];
	$select_ip = $_POST['select_ip'];
	$ping_host = $_POST['hostname'];
	
	if($type == 'ping'){
		$cmd_temp = "ping -I $select_ip -c 4 $ping_host";
		$cmd = $cmd_temp." > /tmp/ping.log &";
	}else{
		$cmd_temp = "traceroute -s $select_ip $ping_host";
		$cmd = $cmd_temp." > /tmp/traceroute.log &";
	}
	
	exec($cmd);
	
	$Report = language('Report');
	$Result = language('Result');
	
	trace_output_start($Report, $cmd_temp);
	trace_output_newline();
	trace_output_newhead($Result);
	trace_output_end();
}

function get_ping_log(){
	$type = $_GET['type'];
	
	if($type == 'ping'){
		$content = file_get_contents("/tmp/ping.log");
		exec("pidof ping",$output);
	}else if($type == 'traceroute'){
		$content = file_get_contents("/tmp/traceroute.log");
		exec("pidof traceroute",$output);
	}
	
	if($output[0] == ""){
		echo "<input type='hidden' value='ping_success' >";
	}
	echo $content;
}


?>
