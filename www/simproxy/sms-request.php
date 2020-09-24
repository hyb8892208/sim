<?php
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require('../inc/mysql_class.php');
//require("../inc/language.inc");
include_once("../inc/wrcfg.inc");
include_once("../inc/aql.php");
include_once("../inc/define.inc");
include_once("../inc/language.inc");
?>

<?php 
function show_sms($res){
	$db=new mysql();
	$condition = "where n_sb_online=1";
	$data = $db->Get("tb_simbank_info",'*',$condition);
	$all_sim_info = mysqli_fetch_all($data, MYSQLI_ASSOC);
	$row = mysqli_num_rows($data);
?>
<form action="<?php echo get_self();?>" method="post" >
	<table width="100%" class="tedit">
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Select Simbank');?>
					<span class="showhelp">
					<?php echo language('Select Simbank help','Select Simbank');?>
					</span>
				</div>
			</th>
			<td>
				<select id="select_simbank" name="select_simbank">
				<?php for($i=1;$i<=$row;$i++){ 
				?>
					<option value="<?php echo $all_sim_info[$i-1]['ob_sb_seri'];?>" <?php if($_POST['select_simbank'] == $all_sim_info[$i-1]['ob_sb_seri']) echo 'selected';?>><?php echo $all_sim_info[$i-1]['ob_sb_seri']?></option>
				<?php } ?>
				</select>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Port');?>
					<span class="showhelp">
					<?php echo language('Port help@sms-request','Select the interface to send SMS.');?>
					</span>
				</div>
			</th>
			<td>
				<table cellpadding="0" cellspacing="0" class="port_table">
				<?php for($i=1;$i<=$row;$i++){ ?>
				
					<tr id="port<?php echo $all_sim_info[$i-1]['ob_sb_seri'];?>" class="_port" style="display:none;">
					
					<?php 
						$condition = "where ob_sb_seri=\"".$all_sim_info[$i-1]['ob_sb_seri']."\" order by ((ob_sb_link_bank_nbr*8)+ob_sb_link_sim_nbr+1)";
						$fileds = "ob_sb_link_bank_nbr,ob_sb_link_sim_nbr,n_sb_link_stat";
						$data = $db->Get("tb_simbank_link_info",$fileds,$condition);
						
						while($sim = mysqli_fetch_array($data,MYSQLI_ASSOC)){
							$disabled = '';
							$font_color = '';
							if(!($sim['n_sb_link_stat'] == '3' || $sim['n_sb_link_stat'] == '4')) {
								$font_color = 'style="color:darkgrey;"';
								$disabled='disabled';
							}
							$sim_index=$sim['ob_sb_link_bank_nbr']*8+$sim['ob_sb_link_sim_nbr']+1;
							
							$select_simbank = $_POST['select_simbank'];
							$spans = $_POST['spans'];
							$checked = isset($spans[$select_simbank][$sim_index]) ? 'checked' : '';
							
					?>
						<td class="sms_port" >
							<input type="checkbox" name="spans[<?php echo $all_sim_info[$i-1]['ob_sb_seri'];?>][<?php echo $sim_index;?>]" class="port_sel_class" <?php echo $disabled.' '.$checked; ?> />
							<span <?php echo $font_color;?>><?php echo "sim-".$sim_index;?><span>
						</td>
					<?php } ?>
					
					</tr>
					
				<?php } ?>
				
					<tr style="border:none;">
						<td style="padding-left:0;">
							<input type="checkbox" id="select_all" />
							<?php echo language('All');?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Load numbers from text file');?>:
					<span class="showhelp">
					<?php echo language('Load numbers from text file help');?>
					</span>
				</div>
			</th>
			<td >
				<input type="file" id="number_book" name="number_book" style="" onchange="readfiles();" value="yes"/>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Destination Number');?>:
					<span class="showhelp">
					<?php echo language('Destination Number help',"
						The number will receive the message.<br/>
						You will be able to separate each number by these symbols: '\r', '\n', space character, semicolon, comma.<br/>
						If you have more than one destination numbers.");
					?>
					</span>
				</div>
			</th>
			<td >

<?php
			 if(isset($_POST['dest_num'])) {
				 $value = str_replace("\"","&quot;",$_POST['dest_num']);
			 } else {
				 $value = "";
			 }
			 echo "<textarea id=\"dest_num\" name=\"dest_num\" width=\"100%\" rows=\"5\" cols=\"80\" >$value</textarea>";
?>
			<br><h4><?php echo language('Destination Number help2',"\"; semicolon\" , \"| vertical Bar\" , \" , comma \" , \"   blank \" , \" : colon \" , \" . dot \" were treated as separators in Destination Number List");?></h4>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Message');?>:
					<span class="showhelp">
					<?php echo language('Message help','The SMS content to be sent out.');?>
					</span>
				</div>
			</th>
			<td >
				<textarea id="msg" name="msg" rows="18" cols="80" width="100%"><?php if(isset($_POST['msg'])) echo $_POST['msg'];?></textarea>
			</td>
		</tr>
		
		<tr>
			<th><?php echo language('Action');?>:</th>
			<td>
				<input type="hidden" name="send" id="send" value="" />
				<input type="submit" value="<?php echo language('Send');?>" onclick="document.getElementById('send').value='Send';return check();"/>
			</td>
		</tr>
	</table>
</form>

<br/>

<?php 
	if(isset($res)){
		sms_statistics($res);
		sms_detail_reports($res);
	}
?>

<script>
//select simbank
var sim_sel = document.getElementById('select_simbank').value;
$("#port"+sim_sel).show();
$("#select_simbank").change(function(){
	var _sim_sel = $(this).val();
	$("._port").hide();
	$("#port"+_sim_sel).show();
});


//select_all
$("#select_all").click(function(){
	var that = this;
	$(".port_sel_class").each(function(){
		if($(this).attr('disabled') != 'disabled'){
			$(this).attr("checked",$(that).is(':checked'));
		}
	});
});
$(".port_sel_class").click(function(){
	if(!$(this).is(':checked')) {
		$("#select_all").attr("checked", false);
	 }
});

function readfiles(){
	if(typeof window.ActiveXObject != 'undefined') {
		return;
	}else{
		var files = document.getElementById('number_book').files;
		if (!files.length) {
			alert('Please select a file!');
			return;
		}
		var file = files[0];
		var reader = new FileReader();

		reader.onloadend = function(evt) {
			if (evt.target.readyState == FileReader.DONE) {
				document.getElementById('byte_content').textContent = evt.target.result;
			}
		};

		reader.readAsBinaryString(file);
		reader.onload=function(e){
			document.getElementById("dest_num").value=this.result;
		}
		return;
	}
}

function check(){
	var port_sel = 0;
	$(".port_sel_class").each(function(){
		if($(this).attr('checked') == 'checked'){
			port_sel = 1;
		}
	});
	
	if(port_sel == 0){
		alert("<?php echo language('select ports','Please select ports');?>");
		return false;
	}
	
	var dest_num = document.getElementById('dest_num').value;
	if(dest_num == ''){
		alert('Destination Number alert','Please input destination number!');
		return false;
	}
	
	return true;
}
</script>

<?php 
}


function Send(){
	$db=new mysql();
	$condition = "where ob_sb_seri=\"".$_POST['select_simbank']."\"";
	$fileds = "ob_sb_link_bank_nbr,ob_sb_seri,ob_sb_link_bank_nbr,ob_sb_link_sim_nbr,ob_gw_seri,ob_gw_link_bank_nbr,ob_gw_link_slot_nbr,n_sb_link_stat,ob_gw_link_chn_nbr,n_sb_link_call_rest_time,v_sim_phone_number,n_sim_balance";
	$data = $db->Get("tb_simbank_link_info",$fileds,$condition);
	
	$i=0;
	while($sim = mysqli_fetch_array($data,MYSQLI_ASSOC)){
		$sms_type = 3;
		$seri = $sim['ob_gw_seri'];//ob_gw_seri
		$sbbanknbr = $sim['ob_sb_link_bank_nbr'];//ob_sb_link_bank_nbr
		$sbslotnbr = $sim['ob_sb_link_sim_nbr'];//ob_sb_link_sim_nbr
		$chnnbr = $sim['ob_gw_link_chn_nbr'];//ob_gw_link_chn_nbr
		$gwbanknbr = $sim['ob_gw_link_bank_nbr'];//ob_gw_link_bank_nbr
		$gwslotnbr = $sim['ob_gw_link_slot_nbr'];//ob_gw_link_slot_nbr
		$dstnum_str = $_POST['dest_num'];//发送号码
		$dstnum_str=str_replace(' ',',',$dstnum_str);
		$dstnum_str=str_replace(';',',',$dstnum_str);
		$dstnum_str=str_replace('|',',',$dstnum_str);
		$dstnum_str=str_replace('.',',',$dstnum_str);
		$dstnum_str=str_replace(':',',',$dstnum_str);
		$dstnum_str=str_replace('&nbsp;',',',$dstnum_str);
		$dstnum_str=str_replace(PHP_EOL,',',$dstnum_str);
		$dstnum_temp = explode(',',$dstnum_str);
		
		$spans = $_POST['spans'];
		$sim_index = $sbbanknbr*8+$sbslotnbr+1;
		$j=0;
		$index_flag = 0;
		foreach($spans[$_POST['select_simbank']] as $key => $val){
			if($sim_index == $key){
				$index_flag = $j;
			}
			$j++;
		}
		
		$dstnum = $dstnum_temp[$index_flag];
		
		$srcnum = $dstnum;//发送号码
		$matchstr = '';//空
		$sendmsg = $_POST['msg'];//短信内容
		
		if(!isset($spans[$_POST['select_simbank']][$sim_index])) continue;
		if($dstnum_temp[$index_flag] == '') continue;
		
		$client = new SoapClient("http://127.0.0.1:8888/?wsdl");
		
		$params = array('smsreqbuff' => 
			array('smstype'=>$sms_type,
				'seri'=>$seri,
				'sbbanknbr'=>$sbbanknbr,
				'sbslotnbr'=>$sbslotnbr,
				'chnnbr'=>$chnnbr,
				'gwbanknbr'=>$gwbanknbr,
				'gwslotnbr'=>$gwslotnbr,
				'dstnum'=>$dstnum,
				'srcnum'=>$srcnum,
				'matchstr'=>$matchstr,
				'sendmsg'=>$sendmsg
		));
		
		$xml = get_xml('SBKSMSReq',$params);
		$wsdl = "http://127.0.0.1:8888/?wsdl";
		$client = new SoapClient($wsdl);
		$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
		
		$res[$i]['seri'] = $seri;
		$res[$i]['port'] = 'sim-'.($sbbanknbr*8+$sbslotnbr+1);
		$res[$i]['dstnum'] = $dstnum;
		$res[$i]['sendmsg'] = $sendmsg;
		$res[$i]['result'] = $result;
		$i++;
	}
	
	return $res;
}

function sms_statistics($res){
	$_count = count($res);
	$succeed_num = 0;
	for($i=0;$i<$_count;$i++){
		if(strstr($res[$i]['result'],"<result>240</result>")){
			$succeed_num++;
		}
	}
	$failed_num = $_count - $succeed_num;
	$str.= '<b>'.language('Statistics Report').'</b>';
	$str.= '<table style="width:100%;font-size:12px;border:1px solid rgb(59,112,162);margin-bottom:20px;">';
	$str.= '<tbody><tr style="background-color:#D0E0EE;height:26px;"><th style="width:30%">'.language('Total').'</th><th style="width:30%">'.language('Success').'</th><th style="width:30%">'.language('Failed').'</th></tr>';
	$str.= '<tr align="center" style="background-color: rgb(232, 239, 247);"><td style="width:30%">'.$_count.'</td><td style="width:30%">'.$succeed_num.'</td><td style="width:30%">'.$failed_num.'</td></tr></tbody></table>';
	echo $str;
}

function sms_detail_reports($res){
	$str.= '<b>Detail</b>';
	$str.= '<table style="width:100%;font-size:12px;border:1px solid rgb(59,112,162);">';
	$str.= '<tbody><tr style="background-color:#D0E0EE;height:26px;"><th style="width:12%">'.language('Serial Number').'</th><th>'.language('Port').'</th><th style="width:30%;word-break:break-all;">'.language('Message').'</th><th style="width:15%">'.language('Destination Number').'</th><th style="width:8%">'.language('Result').'</th></tr>';
	for($i=0;$i<count($res);$i++){
		$str.= '<tr align="center" style="background-color: rgb(232, 239, 247);">';
		$str.= '<td style="width:12%">'.$res[$i]['seri'].'</td>';
		$str.= '<td style="width:15%">'.$res[$i]['port'].'</td>';
		$str.= '<td align="left" style="width:30%;word-break:break-all;">'.$res[$i]['sendmsg'].'</td>';
		$str.= '<td style="width:15%">'.$res[$i]['dstnum'].'</td>';
		if(strstr($res[$i]['result'],"<result>240</result>")){
			$str.= '<td style="width:8%">'.language('Success').'</td></tr>';
		}else{
			$str.= '<td style="width:8%">'.language('Failed').'</td></tr>';
		}
	}
	$str.= '</tbody></table>';
	echo $str;
}

if($_POST){
	$res = Send();
	show_sms($res);
}else{
	show_sms();
}

require("/opt/simbank/www/inc/boot.inc");
?>