<?php 
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require('../inc/mysql_class.php');
include_once("../inc/wrcfg.inc");
include_once("../inc/aql.php");
include_once("../inc/define.inc");
include_once("../inc/language.inc");
?>

<?php
function show_toolkit($res){
	$db=new mysql();
	$condition = "where n_sb_online=1";
	$data = $db->Get("tb_simbank_info",'*',$condition);
	$all_sim_info = mysqli_fetch_all($data, MYSQLI_ASSOC);
	$row = mysqli_num_rows($data);
	
	$aql = new aql();
	$aql->set('basedir','/config/simbank/conf/');
	$file_res = $aql->query('select * from sms_send.conf');

	if(isset($file_res['sms']['select_simbank'])){
		$select_simbank = $file_res['sms']['select_simbank'];
	}else{
		$select_simbank = '';
	}

	if(isset($file_res['sms']['smstype'])){
		$smstype = $file_res['sms']['smstype'];
	}else{
		$smstype = '';
	}

	if(isset($file_res['sms']['dstnum'])){
		$dstnum = $file_res['sms']['dstnum'];
	}else{
		$dstnum = '';
	}

	if(isset($file_res['sms']['srcnum'])){
		$srcnum = $file_res['sms']['srcnum'];
	}else{
		$srcnum = '';
	}

	if(isset($file_res['sms']['matchstr'])){
		$matchstr = $file_res['sms']['matchstr'];
	}else{
		$matchstr = '';
	}

	if(isset($file_res['sms']['sendmsg'])){
		$sendmsg = $file_res['sms']['sendmsg'];
	}else{
		$sendmsg = '';
	}

	if(isset($file_res['sms']['scheduled_type'])){
		$scheduled_type = $file_res['sms']['scheduled_type'];
	}else{
		$scheduled_type = '';
	}

	if(isset($file_res['sms']['day'])){
		$day = $file_res['sms']['day'];
	}else{
		$day = '';
	}

	if(isset($file_res['sms']['hour'])){
		$hour = $file_res['sms']['hour'];
	}else{
		$hour = '';
	}

	if(isset($file_res['sms']['spans'])){
		$spans = json_decode($file_res['sms']['spans'],true);
	}else{
		$spans = '';
	}
	
	$type_selected['by_day'] = '';
	$type_selected['by_hour'] = '';
	if($scheduled_type == 'by_day'){
		$type_selected['by_day'] = 'selected';
	}else if($scheduled_type == 'by_hour'){
		$type_selected['by_hour'] = 'selected';
	}
	
?>
<form action="<?php echo get_self();?>" method="post">
	<table width="100%" class="tedit">
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Select Simbank');?>:
					<span class="showhelp">
					<?php echo language('Select Simbank help','');?>
					</span>
				</div>
			</th>
			<td>
				<select id="select_simbank" name="select_simbank">
				<?php for($i=1;$i<=$row;$i++){ 
				?>
					<option value="<?php echo $all_sim_info[$i-1]['ob_sb_seri'];?>" <?php if($select_simbank == $all_sim_info[$i-1]['ob_sb_seri']) echo 'selected';?>><?php echo $all_sim_info[$i-1]['ob_sb_seri']?></option>
				<?php } ?>
				</select>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Port');?>:
					<span class="showhelp">
					<?php echo language('Port help@sms-request','');?>
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
							
							$select_simbank = $select_simbank;
							$checked = isset($spans[$select_simbank][$sim_index]) ? 'checked' : '';
					?>
						<td class="sms_port" >
							<input type="checkbox" name="spans[<?php echo $all_sim_info[$i-1]['ob_sb_seri'];?>][<?php echo $sim_index;?>]" class="port_sel_class" <?php echo $disabled.' '.$checked; ?> />
							<span <?php echo $font_color;?>><?php echo "sim-".$sim_index;?></span>
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
					<?php echo language('SMS Type');?>:
					<span class="showhelp">
					<?php echo language('SMSTYPE help','Choose send type content.');?>
					</span>
				</div>
			</th>
			<td >
				<select id="smstype" name="smstype">
					<option  value="2" <?php if($smstype == 2) echo 'selected';?> ><?php echo language('SIM Balance');?></option>
					<option  value="1" <?php if($smstype == 1) echo 'selected';?> ><?php echo language('phone number');?></option>
				</select>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Destination Number');?>:
					<span class="showhelp">
					<?php echo language('Destination help','This is the destination number which you want to send.');?>
					</span>
				</div>
			</th>
			<td >
				<input id="dstnum" type="text" name="dstnum" value="<?php if(isset($dstnum)) echo $dstnum; ?>" onkeyup="this.value=this.value.replace(/\D/g,'')" onafterpaste="this.value=this.value.replace(/\D/g,'')"/>
				<span class="error_report" id="dstnum_r"></span>
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
				<input id="sendmsg" type="text" name="sendmsg" value="<?php if(isset($sendmsg)) echo $sendmsg;?>" />
				<span class="error_report" id="sendmsg_r"></span>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Match Number');?>:
					<span class="showhelp">
					<?php echo language('Match number help','This number will receive the message.');
					?>
					</span>
				</div>
			</th>
			<td >
				<input id="srcnum" type="text" name="srcnum" value="<?php if(isset($srcnum)) echo $srcnum; ?>" onkeyup="this.value=this.value.replace(/\D/g,'')" onafterpaste="this.value=this.value.replace(/\D/g,'')"/>
				<span class="error_report" id="srcnum_r"></span>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('The Matching Characters');?>:
					<span class="showhelp">
					<?php echo language('characters help','Here is the key to find the phone number or SIM balance.For example, if you want to query the phone number,and receive message is "your phone number is xxxxxx",You should fill in "number".<br>If you want to send other message,you can ignore it.');?>
					</span>
				</div>
			</th>
			<td >
				<input id="matchstr" type="text" name="matchstr" value="<?php if(isset($matchstr)) echo $matchstr;?>" />
				<span class="error_report" id="matchstr_r"></span>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Scheduled send type');?>:
					<span class="showhelp">
						<?php echo language('Scheduled send type help','Set the time to send SMS by day or hour. If the corresponding value is set to 0, the time to send SMS will not be enabled.<br/>Note: there is a risk of blocking the card if the balance is queried too frequently.');?>
					</span>
				</div>
			</th>
			<td>
				<select id="scheduled_type" name="scheduled_type" onchange="typechange()">
					<option value="by_day" <?php echo $type_selected['by_day'];?>><?php echo language('By Day@sms','By Day');?></option>
					<option value="by_hour" <?php echo $type_selected['by_hour'];?>><?php echo language('By Hour');?></option>
				</select>
			</td>
		</tr>
		
		<tr id="by_day_table">
			<th>
				<div class="helptooltips">
					<?php echo language('Day');?>:
					<span class="showhelp">
						<?php echo language('Day help','Send SMS every corresponding days, 0 means not to send.');?>
					</span>
				</div>
			</th>
			<td>
				<select id="day" name="day" style="text-align:center;width:50px;">
					<?php 
					for($i=0;$i<=30;$i++){
						if($i == $day){
							$day_selected = 'selected';
						}else{
							$day_selected = '';
						}
						echo "<option value='$i' $day_selected >$i</option>";
					}
					?>
				</select>
			</td>
		</tr>
		
		<tr id="by_hour_table" style="display:none;">
			<th>
				<div class="helptooltips">
					<?php echo language('Hour');?>:
					<span class="showhelp">
						<?php echo language('Hour help','Send SMS every corresponding hours, 0 means not to send.');?>
					</span>
				</div>
			</th>
			<td>
				<select id="hour" name="hour" style="text-align:center;width:50px;">
					<?php
					for($i=0;$i<=23;$i++){
						if($i == $hour){
							$hour_selected = 'selected';
						}else{
							$hour_selected = '';
						}
						echo "<option value='$i' $hour_selected >$i</option>";
					}
					?>
				</select>
			</td>
		</tr>
		
	</table>
	
	<div id="newline"></div>
	
	<table id="float_btn" class="float_btn">
		<tr id="float_btn_tr" class="float_btn_tr">
			<td>
				<input type="hidden" name="send" id="send" value="" />
				<input style="margin-right:15px;" type="submit" value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';"/>
				<input style="margin-right:15px;" type="submit" value="<?php echo language('Send');?>" onclick="document.getElementById('send').value='Send';return check();"/>
			</td>
		</tr>
	</table>
	
	<div id="newline"></div>
</form>

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
	if(!$(this).is(':checked')){
		$("#select_all").attr("checked", false);
	}
});

function mbStringLength(s) {
	var totalLength = 0;
	var i;
	var charCode;
	for (i = 0; i < s.length; i++) {
		charCode = s.charCodeAt(i);
		if (charCode < 0x007f) {
			totalLength = totalLength + 1;
		} else if ((0x0080 <= charCode) && (charCode <= 0x07ff)) {
			totalLength += 2;
		} else if ((0x0800 <= charCode) && (charCode <= 0xffff)) {
			totalLength += 3;
		}
	}
	return totalLength;
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
	
	var dstnum_len = mbStringLength($("#dstnum").val());
	if(dstnum == 0){
		$("#dstnum_r").text("<?php echo language('sms_destination_tip','*Destination Number cannot be empty.'); ?>");
		return false;
	}
	if(dstnum > 15){
		$("#dstnum_r").text("<?php echo language('sms_character_tip','*Allow character must be less than 15 characters.'); ?>");
		return false;
	}
	
	var srcnum_len = mbStringLength($("#srcnum").val());
	if(srcnum_len == 0){
		$("#srcnum_r").text("<?php echo language('sms_receive_number_tip','*Receive Number cannot be empty.'); ?>");
		return false;
	}
	if(srcnum > 15){
		$("#srcnum_r").text("<?php echo language('sms_character_tip','*Allow character must be less than 15 characters.'); ?>");
		return false;
	}
	
	var sendmsg_len = mbStringLength($("#sendmsg").val());
	if(sendmsg_len == 0){
		$("#sendmsg_r").text("<?php echo language('sms_message_tip', '*Message cannot be empty.'); ?>");
		return false;
	}
	if(sendmsg_len > 127){
		$("#sendmsg_r").text("<?php echo language('sms_character_tip2', '*Allow character must be less than 127 characters.'); ?>");
		return false;
	}
	
	return true;
}

function typechange(){
	var type = document.getElementById('scheduled_type').value;
	
	if(type == 'by_day'){
		$("#by_day_table").show();
		$("#by_hour_table").hide();
	}else if(type == 'by_hour'){
		$("#by_day_table").hide();
		$("#by_hour_table").show();
	}
}

typechange();
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
		$sms_type = $_POST['smstype'];//短信类型
		$seri = $sim['ob_gw_seri'];//ob_gw_seri
		$sbbanknbr = $sim['ob_sb_link_bank_nbr'];//ob_sb_link_bank_nbr
		$sbslotnbr = $sim['ob_sb_link_sim_nbr'];//ob_sb_link_sim_nbr
		$chnnbr = $sim['ob_gw_link_chn_nbr'];//ob_gw_link_chn_nbr
		$gwbanknbr = $sim['ob_gw_link_bank_nbr'];//ob_gw_link_bank_nbr
		$gwslotnbr = $sim['ob_gw_link_slot_nbr'];//ob_gw_link_slot_nbr
		$dstnum = $_POST['dstnum'];//发送号码
		$srcnum = $_POST['srcnum'];//短信接收号码
		$matchstr = $_POST['matchstr'];//匹配内容
		$sendmsg = $_POST['sendmsg'];//短信内容
		
		$spans = $_POST['spans'];
		if(!isset($spans[$_POST['select_simbank']][$sbbanknbr*8+$sbslotnbr+1])) continue;
		
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
		if($sms_type == 2){
			$sms_type_temp = language('SIM Balance');
		}else{
			$sms_type_temp = language('phone number');
		}
		$res[$i]['smstype'] = $sms_type_temp;
		$res[$i]['sendmsg'] = $sendmsg;
		$res[$i]['dstnum'] = $dstnum;
		$res[$i]['result'] = $result;
		if($_POST['smstype'] == 1){
			$res[$i]['smstype'] == 'phone number';
		}else if($_POST['smstype'] == 2){
			$res[$i]['smstype'] == 'SMS balance';
		}
		$i++;
	}
	
	save_data();
	
	return $res;
}

function save_data(){
	$conf_path = '/config/simbank/conf/sms_send.conf';
	if(!file_exists($conf_path)){
		fclose(fopen($conf_path,"w"));
	}
	
	$aql = new aql();
	
	$aql->set('basedir','/config/simbank/conf/');
	$hlock = lock_file($conf_path);
	if(!$aql->open_config_file($conf_path)){
		echo $aql->get_error();
		unlock_file($hlock);
		return false;
	}
	
	$res = $aql->query("select * from sms_send.conf");
	
	if(!isset($res['sms'])){
		$aql->assign_addsection('sms','');
	}
	
	if(isset($res['sms']['select_simbank'])){
		$aql->assign_editkey('sms','select_simbank',$_POST['select_simbank']);
	}else{
		$aql->assign_append('sms','select_simbank',$_POST['select_simbank']);
	}
	
	if(isset($res['sms']['smstype'])){
		$aql->assign_editkey('sms','smstype',$_POST['smstype']);
	}else{
		$aql->assign_append('sms','smstype',$_POST['smstype']);
	}
	
	if(isset($res['sms']['dstnum'])){
		$aql->assign_editkey('sms','dstnum',$_POST['dstnum']);
	}else{
		$aql->assign_append('sms','dstnum',$_POST['dstnum']);
	}
	
	if(isset($res['sms']['srcnum'])){
		$aql->assign_editkey('sms','srcnum',$_POST['srcnum']);
	}else{
		$aql->assign_append('sms','srcnum',$_POST['srcnum']);
	}
	
	if(isset($res['sms']['matchstr'])){
		$aql->assign_editkey('sms','matchstr',$_POST['matchstr']);
	}else{
		$aql->assign_append('sms','matchstr',$_POST['matchstr']);
	}
	
	if(isset($res['sms']['sendmsg'])){
		$aql->assign_editkey('sms','sendmsg',$_POST['sendmsg']);
	}else{
		$aql->assign_append('sms','sendmsg',$_POST['sendmsg']);
	}
	
	if(isset($res['sms']['scheduled_type'])){
		$aql->assign_editkey('sms','scheduled_type',$_POST['scheduled_type']);
	}else{
		$aql->assign_append('sms','scheduled_type',$_POST['scheduled_type']);
	}
	
	if(isset($res['sms']['day'])){
		$aql->assign_editkey('sms','day',$_POST['day']);
	}else{
		$aql->assign_append('sms','day',$_POST['day']);
	}
	
	if(isset($res['sms']['hour'])){
		$aql->assign_editkey('sms','hour',$_POST['hour']);
	}else{
		$aql->assign_append('sms','hour',$_POST['hour']);
	}
	
	$spans = json_encode($_POST['spans']);
	if(isset($res['sms']['spans'])){
		$aql->assign_editkey('sms','spans',$spans);
	}else{
		$aql->assign_append('sms','spans',$spans);
	}
	
	if (!$aql->save_config_file('sms_send.conf')) {
		echo $aql->get_error();
		unlock_file($hlock);
		return false; 
	}
	unlock_file($hlock);
	
	//crontab
	$write = '';
	$sms_schedule_send = "/tools/sms_schedule_send.php";
	if($_POST['scheduled_type'] == 'by_day' && $_POST['day'] != 0){
		$minute = trim(`date "+%M"`);
		$hour = trim(`date "+%H"`);
		$day = $_POST['day'];
		
		$write = "$minute $hour */$day * * root $sms_schedule_send";
	}else if($_POST['scheduled_type'] == 'by_hour' && $_POST['hour'] != 0){
		$minute = trim(`date "+%M"`);
		$hour = $_POST['hour'];
		
		$write = "$minute */$hour * * * root $sms_schedule_send";
	}
	
	$file_path = "/etc/crontab";
	$hlock = lock_file($file_path);
	exec("sed -i '/\/tools\/sms_schedule_send.php/d' \"$file_path\" 2> /dev/null");
	if($write != '') exec("echo \"$write\" >> $file_path");
	unlock_file($hlock);
	
	wait_apply("exec", "sh /etc/init.d/cron restart > /dev/null 2>&1 &");
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
	$str.= '<b>'.language('Detail').'</b>';
	$str.= '<table style="width:100%;font-size:12px;border:1px solid rgb(59,112,162);">';
	$str.= '<tbody><tr style="background-color:#D0E0EE;height:26px;"><th style="width:12%">'.language('Serial Number').'</th><th style="width:12%">'.language('Port').'</th><th style="width:15%;">'.language('SMS Type').'</th><th style="width:30%;word-break:break-all;">'.language('Message').'</th><th style="width:15%">'.language('Destination Number').'</th><th style="width:8%">'.language('Result').'</th></tr>';
	for($i=0;$i<count($res);$i++){
		$str.= '<tr align="center" style="background-color: rgb(232, 239, 247);">';
		$str.= '<td style="width:12%">'.$res[$i]['seri'].'</td>';
		$str.= '<td style="width:12%">'.$res[$i]['port'].'</td>';
		$str.= '<td style="width:15%;">'.$res[$i]['smstype'].'</td>';
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
	if($_POST['send'] == 'Send'){
		$res = Send();
	}else if($_POST['send'] == 'Save'){
		save_data();
	}
	show_toolkit($res);
}else{
	show_toolkit();
}
?>

<?php require("/opt/simbank/www/inc/boot.inc"); ?>