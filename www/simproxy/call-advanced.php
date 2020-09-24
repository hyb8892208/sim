<?php
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require("../inc/mysql_class.php");
include_once("../inc/wrcfg.inc");
include_once("../inc/aql.php");
include_once("../inc/define.inc");
include_once("../inc/language.inc");

$db = new mysql('simserver');
$tmp_db = new mysql();
$sql = "CREATE TABLE if not exists `tb_sim_abnormal_cnf` (                           
  `ob_abnormal_enable` tinyint(4) NOT NULL default '0',       
  `n_call_fail_count` smallint(5) unsigned default '10',       
  `b_sms_check_enable` tinyint(1) NOT NULL default '0',       
  `n_sms_send_fail_count` smallint(5) unsigned default '10',   
  `v_sms_send_phone` char(16) NOT NULL default '',             
  `v_sms_send_msg` varchar(980) NOT NULL default '',         
  `n_sms_result_type` smallint(5) NOT NULL default '0',     
  `b_register_fail_enable` tinyint(1) NOT NULL default '0',               
  `n_sim_register_fail_count` smallint(5) unsigned default '0',   
  PRIMARY KEY  (`ob_abnormal_enable`)                                 
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='tb_sim_abnormal_cnf'";
$db->query($sql);

function edit_advanced(){
	global $db;
	
	$res = $db->Get('tb_sim_abnormal_cnf', "*", '');
	$info = mysqli_fetch_array($res, MYSQLI_ASSOC);
	
	$abnormal_switch_val = $info['ob_abnormal_enable'];
	$abnormal_switch = '';
	if($abnormal_switch_val != 0){
		$abnormal_switch = 'checked';
	}
	
	$call_failed_count = $info['n_call_fail_count'];
	
	$sms_send_detection_switch_val = $info['b_sms_check_enable'];
	$sms_send_detection_switch = '';
	if($sms_send_detection_switch_val != 0){
		$sms_send_detection_switch = 'checked';
	}
	
	$sms_send_detection_count = $info['n_sms_send_fail_count'];
	$send_sms_number = $info['v_sms_send_phone'];
	$sms_message = $info['v_sms_send_msg'];
	
	if($info['n_sms_result_type'] == 1){
		$testing_sms_report = 'checked';
	}else{
		$testing_sms_report = '';
	}
	
	if($info['b_register_fail_enable'] == 1){
		$b_register_fail_enable = 'checked';
	}else{
		$b_register_fail_enable = '';
	}
	
	$n_sim_register_fail_count = $info['n_sim_register_fail_count'];
?>

<script type="text/javascript" src="/js/check.js"></script>
<script type="text/javascript" src="/js/jquery.ibutton.js"></script> 
<link type="text/css" href="/css/jquery.ibutton.css" rel="stylesheet" media="all" />
<form enctype="multipart/form-data" action="<?php echo get_self();?>" method="post">
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Sim Abnormal Settings');?></li>
		<li class="tb2">&nbsp;</li>
	</div>
	
	<table width="100%" class="tedit">
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Abnormal Switch');?>:
					<span class="showhelp">
					<?php echo language('Abnormal Switch help','Abnormal Switch');?>
					</span>
				</div>
			</th>
			<td>
				<input type="checkbox" name="abnormal_switch" id="abnormal_switch" <?php echo $abnormal_switch; ?> />
				<span id="cabnormal_switch"></span>
			</td>
		</tr>
		
		<tbody class="abnormal_content_tr">
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Call Failed Count');?>:
						<span class="showhelp">
						<?php echo language('Call Failed Count help','Call Failed Count');?>
						</span>
					</div>
				</th>
				<td>
					<input type="text" name="call_failed_count" id="call_failed_count" value="<?php echo $call_failed_count; ?>" />
					<span id="ccall_failed_count"></span>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('SMS Send Detection Switch');?>:
						<span class="showhelp">
						<?php echo language('SMS Send Detection Switch help','After opening, when the number of successive call failures reaches the set value, send short message to check whether the port is available; if the short message is sent successfully, clear the number of successive call failures; if the short message fails, limit the port\'s outgoing.');?>
						</span>
					</div>
				</th>
				<td>
					<input type="checkbox" name="sms_send_detection_switch" id="sms_send_detection_switch" <?php echo $sms_send_detection_switch; ?> />
				</td>
			</tr>
			
			<tr class="sms_send_detection_count_tr">
				<th>
					<div class="helptooltips">
						<?php echo language('SMS Send Detection Count');?>:
						<span class="showhelp">
						<?php echo language('SMS Send Detection Count help','SMS Send Detection Count');?>
						</span>
					</div>
				</th>
				<td>
					<input type="text" name="sms_send_detection_count" id="sms_send_detection_count" value="<?php echo $sms_send_detection_count; ?>" />
					<span id="csms_send_detection_count"></span>
				</td>
			</tr>
			
			<tr class="sms_send_detection_count_tr">
				<th>
					<div class="helptooltips">
						<?php echo language('Send Sms Number');?>:
						<span class="showhelp">
						<?php echo language('Send Sms Number help','Send Sms Number');?>
						</span>
					</div>
				</th>
				<td>
					<input type="text" name="send_sms_number" id="send_sms_number" value="<?php echo $send_sms_number; ?>" />
					<span id="csend_sms_number"></span>
				</td>
			</tr>
			
			<tr class="sms_send_detection_count_tr">
				<th>
					<div class="helptooltips">
						<?php echo language('Sms Message');?>:
						<span class="showhelp">
						<?php echo language('Sms Message help','Sms Message');?>
						</span>
					</div>
				</th>
				<td>
					<input type="text" name="sms_message" id="sms_message" value="<?php echo $sms_message; ?>" />
					<span id="csms_message"></span>
				</td>
			</tr>
			
			<tr class="sms_send_detection_count_tr">
				<th>
					<div class="helptooltips">
						<?php echo language('Testing SMS report');?>:
						<span class="showhelp">
						<?php echo language('Testing SMS report help', 'When closed, the successful sending of short message indicates that the port is available; when opened, the successful sending of short message and the receipt of short message report indicate that the port is available.');?>
						</span>
					</div>
				</th>
				<td>
					<input type="checkbox" name="testing_sms_report" id="testing_sms_report" <?php echo $testing_sms_report;?> />
				</td>
			</tr>
		</tbody>
	</table>
	
	<br/>

	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Sim Register Settings');?></li>
		<li class="tb2">&nbsp;</li>
	</div>
	
	<table width="100%" class="tedit">
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Register Failed Switch');?>:
					<span class="showhelp">
					<?php echo language('Register Failed Switch help','Register Failed Switch');?>
					</span>
				</div>
			</th>
			<td>
				<input type="checkbox" name="b_register_fail_enable" id="b_register_fail_enable" <?php echo $b_register_fail_enable; ?> />
				<span id="cb_register_fail_enable"></span>
			</td>
		</tr>
		
		<tbody class="register_failed_tr">
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Register Failed Counts');?>:
						<span class="showhelp">
						<?php echo language('Register Failed Counts help','Register Failed Counts');?>
						</span>
					</div>
				</th>
				<td>
					<input type="text" name="n_sim_register_fail_count" id="n_sim_register_fail_count" value="<?php echo $n_sim_register_fail_count; ?>" />
					<span id="cn_sim_register_fail_count"></span>
				</td>
			</tr>
		</tbody>
	</table>
	
	<br/>
	
	<table id="float_btn" class="float_btn">
		<tr id="float_btn_tr" class="float_btn_tr">
			<td>
				<input type="hidden" name="send" id="send" value="" />
				<input type="submit" style="margin-right:15px;" value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';return check();" />
			</td>
		</tr>
	</table>
</form>
<script>
$(function(){
	$("#abnormal_switch").iButton();
	$("#sms_send_detection_switch").iButton();
	$("#testing_sms_report").iButton();
	$("#b_register_fail_enable").iButton();
	
	var abnormal_switch = $("#abnormal_switch").attr("checked");
	if(abnormal_switch == 'checked'){
		$(".abnormal_content_tr").show();
	}else{
		$(".abnormal_content_tr").hide();
	}
	$("#abnormal_switch").change(function(){
		if($(this).attr('checked') == 'checked'){
			$(".abnormal_content_tr").show();
		}else{
			$(".abnormal_content_tr").hide();
		}
	});
	
	var sms_send_detection_switch = $("#sms_send_detection_switch").attr("checked");
	if(sms_send_detection_switch == 'checked'){
		$(".sms_send_detection_count_tr").show();
	}else{
		$(".sms_send_detection_count_tr").hide();
	}
	$("#sms_send_detection_switch").change(function(){
		if($(this).attr('checked') == 'checked'){
			$(".sms_send_detection_count_tr").show();
		}else{
			$(".sms_send_detection_count_tr").hide();
		}
	});
	
	var b_register_fail_enable = $("#b_register_fail_enable").attr('checked');
	if(b_register_fail_enable == 'checked'){
		$(".register_failed_tr").show();
	}else{
		$(".register_failed_tr").hide();
	}
	$("#b_register_fail_enable").change(function(){
		if($(this).attr('checked') == 'checked'){
			$(".register_failed_tr").show();
		}else{
			$(".register_failed_tr").hide();
		}
	});
});

function check(){
	var abnormal_switch = document.getElementById('abnormal_switch').checked;
	var sms_send_detection_switch = document.getElementById('sms_send_detection_switch').checked;
	
	var call_failed_count = document.getElementById('call_failed_count').value;
	var sms_send_detection_count = document.getElementById('sms_send_detection_count').value;
	
	if(abnormal_switch){
		document.getElementById('ccall_failed_count').innerHTML = '';
		if(check_number(call_failed_count)){
			document.getElementById('call_failed_count').focus();
			document.getElementById('ccall_failed_count').innerHTML = con_str('<?php echo language("js check integer")?>');
			return false;
		}
		
		if(sms_send_detection_switch){
			document.getElementById('csms_send_detection_count').innerHTML = '';
			if(check_number(sms_send_detection_count)){
				document.getElementById('sms_send_detection_count').focus();
				document.getElementById('csms_send_detection_count').innerHTML = con_str('<?php echo language("js check integer");?>');
				return false;
			}
		}
	}
}
</script>
<?php
}

function save_advanced(){
	global $db;
	
	if(isset($_POST['abnormal_switch'])){
		$abnormal_switch = 1;
	}else{
		$abnormal_switch = 0;
	}
	
	if($_POST['call_failed_count'] != ''){
		$call_failed_count = $_POST['call_failed_count'];
	}else{
		$call_failed_count = 0;
	}
	
	if(isset($_POST['sms_send_detection_switch'])){
		$sms_send_detection_switch = 1;
	}else{
		$sms_send_detection_switch = 0;
	}
	
	if($_POST['sms_send_detection_count'] != ''){
		$sms_send_detection_count = $_POST['sms_send_detection_count'];
	}else{
		$sms_send_detection_count = 0;
	}
	
	$send_sms_number = $_POST['send_sms_number'];
	
	$sms_message = $_POST['sms_message'];
	
	if(isset($_POST['testing_sms_report'])){
		$testing_sms_report = 1;
	}else{
		$testing_sms_report = 0;
	}
	
	if(isset($_POST['b_register_fail_enable'])){
		$b_register_fail_enable = 1;
	}else{
		$b_register_fail_enable = 0;
	}
	
	$n_sim_register_fail_count = $_POST['n_sim_register_fail_count'];
	
	//gsoap
	$params = array('oSimAbnormal' =>
		array('abnormalEnable'=>$abnormal_switch,
			'smsCheckEnable'=>$sms_send_detection_switch,
			'callFailCount'=>$call_failed_count,
			'smsSendCount'=>$sms_send_detection_count,
			'smsSendPhone'=>$send_sms_number,
			'smsSendMsg'=>$sms_message,
			'smsResultType'=>$testing_sms_report,
			'registercheckenable'=>$b_register_fail_enable,
			'registerfailcheckcount'=>$n_sim_register_fail_count
	));
	
	$xml = get_xml('SBKSimAbnormalConfigUpdate', $params);
	
	$wsdl = "http://127.0.0.1:8888/?wsdl";
	$client = new SoapClient($wsdl);
	$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
	
	//database
	$res = $db->Get('tb_sim_abnormal_cnf', "*", '');
	$info = mysqli_fetch_array($res, MYSQLI_ASSOC);
	
	if(!isset($info['ob_abnormal_enable']) || $info['ob_abnormal_enable'] == ''){
		$fields = "ob_abnormal_enable,n_call_fail_count,b_sms_check_enable,n_sms_send_fail_count,v_sms_send_phone,v_sms_send_msg,n_sms_result_type,b_register_fail_enable,n_sim_register_fail_count";
		$values = "'$abnormal_switch','$call_failed_count','$sms_send_detection_switch','$sms_send_detection_count','$send_sms_number','$sms_message','$testing_sms_report','$b_register_fail_enable','$n_sim_register_fail_count'";
		$db->Add('tb_sim_abnormal_cnf', $fields, $values);
	}else{
		$abnormal_switch_val = $info['ob_abnormal_enable'];
		$fields = "ob_abnormal_enable='$abnormal_switch',n_call_fail_count='$call_failed_count',b_sms_check_enable='$sms_send_detection_switch',n_sms_send_fail_count='$sms_send_detection_count',v_sms_send_phone='$send_sms_number',v_sms_send_msg='$sms_message',n_sms_result_type='$testing_sms_report',b_register_fail_enable='$b_register_fail_enable',n_sim_register_fail_count='$n_sim_register_fail_count'";
		$condition = "where ob_abnormal_enable = '$abnormal_switch_val'";
		$db->Set('tb_sim_abnormal_cnf', $fields, $condition);
	}
}

if($_POST){
	if(isset($_POST['send']) && $_POST['send'] == 'Save'){
		save_advanced();
		edit_advanced();
	}
}else{
	edit_advanced();
}

require("/opt/simbank/www/inc/boot.inc");
?>