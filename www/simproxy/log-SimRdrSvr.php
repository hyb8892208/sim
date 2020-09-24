<?php
include_once("../inc/network_factory.inc");


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


function Download_file()
{
	$file_name="SimRdrSvr_log.tar.gz";
	$log_name="SimRdrSvr.log";
	$bank_dir="/tmp/log/bank/";
	$file_path="/tmp/log/";
	exec("tar -cvf ".$file_path.$file_name." ".$file_path.$log_name." ".$bank_dir);
	$file_path=$file_path.$file_name;
	
	if(!file_exists($file_path)) {
		//echo "</br>$file_name";
		echo language("Can not find $file_name");
		return;
	}

	//打开文件  
	$file = fopen ($file_path, "r" ); 
	$size = filesize($file_path) ;
	
	//输入文件标签 
	header('Content-Encoding: none');
	header('Content-Type: application/force-download');
	header('Content-Type: application/octet-stream');
	header('Content-Type: application/download');
	header('Content-Description: File Transfer');  
	header('Accept-Ranges: bytes');  
	header("Accept-Length:".$size);  
	header('Content-Transfer-Encoding: binary' );
	header("Content-Disposition: attachment; filename=".$file_name); 
	header('Pragma: no-cache');
	header('Expires: 0');
	//输出文件内容   
	//读取文件内容并直接输出到浏览器
    ob_clean();
	flush();
	echo fread($file, $size);	
	fclose ($file);
	unlink($file_path);
	
}


function echo_contents()
{	


?>
<script type="text/javascript">	
	$.ajax({
		url :'../simproxy/ajax_server_simbank.php?nocache='+Math.random(),
		type: 'GET',
		dataType: 'text', 
		data: {
			'action':'process_log',
			'log_type':'SimRdrSvr_log',
			'method':'reload'			,
			'size':0
		},
		success: function(log_astinfo){			
			document.getElementById("showlog").value = log_astinfo;
			document.getElementById("size").value = log_astinfo.length;
		},
	});
</script>

<?php
}

function Log_setting(){
	$aql = new aql();
	$log_logmonitor_path = "/mnt/config/simbank/conf/logfile_monitor.conf";
	$hlock = lock_file($log_logmonitor_path);
	if (!file_exists($log_logmonitor_path)) {
		fclose(fopen($log_logmonitor_path,"w"));
	}
	$aql->set('basedir','/mnt/config/simbank/conf');
	if(!$aql->open_config_file($log_logmonitor_path)){
		echo $aql->get_error();
		unlock_file($hlock);
		return -1;
	}
	
	if(!file_exists("/mnt/config/simbank/conf/logfile_monitor.conf")){
		exec("touch /mnt/config/simbank/conf/logfile_monitor.conf");
	}
	
	$res = $aql->query("select * from logfile_monitor.conf");
	
	if(isset($_POST['rdr_log_class']) && $_POST['rdr_log_class'] != ''){
		$rdr_log_class = $_POST['rdr_log_class'];
	}else{
		$rdr_log_class = '';
	}
	
	if(isset($_POST['rdr_log_autoclean']) && $_POST['rdr_log_autoclean'] == 'on'){
		$rdr_log_autoclean = $_POST['rdr_log_autoclean'];
		$rdr_log_maxsize = $_POST['rdr_log_maxsize'];
	}else{
		$rdr_log_autoclean = 'off';
		$rdr_log_maxsize = '';
	}
	
	if(isset($_POST['usb_data_logs']) && $_POST['usb_data_logs'] == 'on'){
		$usb_data_logs = 1;
	}else{
		$usb_data_logs = 0;
	}
	
	if(isset($_POST['usb_log_autoclean']) && $_POST['usb_log_autoclean'] == 'on'){
		$usb_log_autoclean = $_POST['usb_log_autoclean'];
		$usb_log_maxsize = $_POST['usb_log_maxsize'];
	}else{
		$usb_log_autoclean = 'off';
		$usb_log_maxsize = '';
	}
	
	if(!isset($res['rdr_log'])){
		$aql->assign_addsection('rdr_log','');
	}
	
	if($rdr_log_class != ''){
		if(isset($res['rdr_log']['rdr_log_class'])){
			$aql->assign_editkey('rdr_log', 'rdr_log_class', $rdr_log_class);
		}else{
			$aql->assign_append('rdr_log', 'rdr_log_class', $rdr_log_class);
		}
	}
	
	if(isset($res['rdr_log']['autoclean'])){
		$aql->assign_editkey('rdr_log', 'autoclean', $rdr_log_autoclean);
	}else{
		$aql->assign_append('rdr_log', 'autoclean', $rdr_log_autoclean);
	}
	
	if($rdr_log_maxsize != ''){
		if(isset($res['rdr_log']['maxsize'])){
			$aql->assign_editkey('rdr_log', 'maxsize', $rdr_log_maxsize);
		}else{
			$aql->assign_append('rdr_log', 'maxsize', $rdr_log_maxsize);
		}
	}
	
	if(!isset($res['usb_data_log'])){
		$aql->assign_addsection('usb_data_log','');
	}
	
	if(isset($res['usb_data_log']['usb_data_log_switch'])){
		$aql->assign_editkey('usb_data_log', 'usb_data_log_switch', $usb_data_logs);
	}else{
		$aql->assign_append('usb_data_log', 'usb_data_log_switch', $usb_data_logs);
	}
	
	if(isset($res['usb_data_log']['autoclean'])){
		$aql->assign_editkey('usb_data_log', 'autoclean', $usb_log_autoclean);
	}else{
		$aql->assign_append('usb_data_log', 'autoclean', $usb_log_autoclean);
	}
	
	if($usb_log_maxsize != ''){
		if(isset($res['usb_data_log']['maxsize'])){
			$aql->assign_editkey('usb_data_log', 'maxsize', $usb_log_maxsize);
		}else{
			$aql->assign_append('usb_data_log', 'maxsize', $usb_log_maxsize);
		}
	}
	$aql->save_config_file('logfile_monitor.conf');
	unlock_file($hlock);
	exec("kill -9 `pidof logmonitor`");
	
	$client = new SoapClient("http://127.0.0.1:8808/?wsdl");
	$result = $client->__soapCall('SBKSetLogClass', array($rdr_log_class), array('location' => 'http://127.0.0.1:8808', 'uri' => 'webproxy'));
	$result = $client->__soapCall('SBKSetHexDataSwitch', array($usb_data_logs), array('location' => 'http://127.0.0.1:8808', 'uri' => 'webproxy'));
}

if($_POST) {
	if(isset($_POST['send'])) {
		if($_POST['send'] == 'Download') {
			//$id=show_loading("Preparing for downloading......");			
			Download_file();
			//hide_loading($id);
		}else if($_POST['send'] == 'Save'){
			Log_setting();
		}
	}
}

/** show settings **/
$aql = new aql();
$logfile_monitor_path = "/mnt/config/simbank/conf/logfile_monitor.conf";
$hlock = lock_file($logfile_monitor_path);
if(!file_exists($logfile_monitor_path)){
	fclose(fopen($logfile_monitor_path, "w"));
}
$aql->set('basedir','/mnt/config/simbank/conf');
if(!$aql->open_config_file($logfile_monitor_path)){
	echo $aql->get_error();
	unlock_file($hlock);
	return -1;
}

$res = $aql->query("select * from logfile_monitor.conf");
$rdr_log_class = $res['rdr_log']['rdr_log_class'];
$rdr_log_autoclean = $res['rdr_log']['autoclean'];
$rdr_log_maxsize = $res['rdr_log']['maxsize'];
$usb_data_logs = $res['usb_data_log']['usb_data_log_switch'];
$usb_log_autoclean = $res['usb_data_log']['autoclean'];
$usb_log_maxsize = $res['usb_data_log']['maxsize'];
unlock_file($hlock);
?>
	<script type="text/javascript" src="/js/jquery.ibutton.js"></script> 
	<link type="text/css" href="/css/jquery.ibutton.css" rel="stylesheet" media="all" />
	<form id="manform" enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">
	
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg" style="width:auto; padding-right:10px;"><?php echo language('SimProxySvr Logs Settings');?></li>
		<li class="tb2">&nbsp;</li>
	</div>
	
	<table width="100%" class="tedit" >
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Log_class');?>:
					<span class="showhelp">
					<?php echo language('Log_class help','Displays different log information.');?>
					</span>
				</div>
			</th>
			<td >
				<select id="log_maxsize" name="rdr_log_class" >
					<option value='0' <?php if($rdr_log_class == 0){echo "selected";}?>><?php echo language('Info');?></option>
					<option value='1' <?php if($rdr_log_class == 1){echo "selected";}?>><?php echo language('Warning');?></option>
					<option value='2' <?php if($rdr_log_class == 2){echo "selected";}?>><?php echo language('Error');?></option>
				</select>
			</td>				
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Auto clean');?>:
					<span class="showhelp">
					<?php echo language('Auto clean help','
						switch on : when the size of log file reaches the max size, <br> 
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; the system will cut a half of the file. New logs will be retained.<br>
						switch off : logs will remain, and the file size will increase gradually. <br>');
						echo language('Auto clean default@Asterisk Logs','default on, default size=100KB.');
					?>
					</span>
				</div>
			</th>
			<td >
				<table><tr>
					<td style="margin:0px;padding:0px;border:0">
						<input type=checkbox id="rdr_log_autoclean" name="rdr_log_autoclean" <?php if($rdr_log_autoclean == 'on')echo 'checked';?> >
					</td>
					<td style="border:0">
						<?php echo language('maxsize');?> : 
						<select id="rdr_log_maxsize" name="rdr_log_maxsize" <?php if($rdr_log_autoclean != "on")echo "disabled";?>>
							<?php
								$value_array = array("20KB","50KB","100KB","200KB","500KB","1MB","2MB");
								foreach($value_array as $value){
									$selected = "";
									if($rdr_log_maxsize == $value)
										$selected = "selected";
									echo "<option value=\"$value\" $selected>$value</option>";
								}
							?>
						</select>
					</td>
				</tr></table>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('USB data Logs');?>:
					<span class="showhelp">
					<?php echo language('USB data Logs help','Displays different log information.');?>
					</span>
				</div>
			</th>
			<td >
				<input type=checkbox id="usb_data_logs" name="usb_data_logs" <?php if($usb_data_logs == 1)echo 'checked';?> >
			</td>				
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Auto clean');?>:
					<span class="showhelp">
					<?php echo language('Auto clean help','
						switch on : when the size of log file reaches the max size, <br> 
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; the system will cut a half of the file. New logs will be retained.<br>
						switch off : logs will remain, and the file size will increase gradually. <br>');
						echo language('Auto clean default@Asterisk Logs','default on, default size=100KB.');
					?>
					</span>
				</div>
			</th>
			<td >
				<table><tr>
					<td style="margin:0px;padding:0px;border:0">
						<input type=checkbox id="usb_log_autoclean" name="usb_log_autoclean" <?php if($usb_log_autoclean == 'on')echo 'checked';?> >
					</td>
					<td style="border:0">
						<?php echo language('maxsize');?> : 
						<select id="usb_log_maxsize" name="usb_log_maxsize" <?php if($usb_log_autoclean != "on")echo "disabled";?>>
							<?php
								$value_array = array("20KB","50KB","100KB","200KB");
								foreach($value_array as $value){
									$selected = "";
									if($usb_log_maxsize == $value)
										$selected = "selected";
									echo "<option value=\"$value\" $selected>$value</option>";
								}
							?>
						</select>
					</td>
				</tr></table>
			</td>
		</tr>
	</table>
	<br>
	<button onclick="document.getElementById('send').value='Save';"><?php echo language('Save');?></button>
	<br>
	<br>
	
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('SimRdrSvr logs');?></li>
		<li class="tb2">&nbsp;</li>
	</div>
	<center>
		<textarea id="showlog" wrap="on" style="width:100%;height:450px" readonly></textarea>
		<input id="size" type="hidden" value="" />
		<table>
			<tr>	
				<td><?php echo language('Refresh Rate');?>:</td>
				<td>
					<select id="interval" onchange="change_refresh_rate(this.value);">
						<option value="0" selected>Off</option>
						<option value="1">1s</option>
						<option value="2">2s</option>
						<option value="3">3s</option>
						<option value="4">4s</option>
						<option value="5">5s</option>
						<option value="6">6s</option>
						<option value="7">7s</option>
						<option value="8">8s</option>
						<option value="9">9s</option>
					</select>
				</td>
				<td>
					&nbsp;&nbsp;&nbsp;&nbsp;
				</td>
				<td>
					<input type="button" value="<?php echo language('Refresh');?>" onclick="refresh();"/>
				</td>
				<td>
					<input type="button" value="<?php echo language('Clean Up');?>"  onclick="return CleanUp();"/>
				</td>
				<td>
				<input type="submit" value="<?php echo language('Download');?>" 
					onclick="document.getElementById('send').value='Download';"/>
				</td>
			</tr>
		</table>
	</center>
	<input type="hidden" name="send" id="send" value="" />
	</form>
	

<script type="text/javascript" src="/js/functions.js">
</script>

<script type="text/javascript">
function show_last()
{
	var t = document.getElementById("showlog");
	t.scrollTop = t.scrollHeight;
}

function CleanUp()
{
	if(!confirm("<?php echo language('Clean Up confirm','Are you sure to clean up this logs?');?>")) return false;
	var size  = $("#size").attr("value");

	$.ajax({
		url :'../simproxy/ajax_server_simbank.php?nocache='+Math.random(),
		type: 'GET',
		dataType: 'text', 
		data: {
			'action':'process_log',
			'log_type':'SimRdrSvr_log',
			'method':'clean'			,
			'size':0
		},
		error: function(data){                          //request failed callback function;
			//alert("get data error");
		},
		success: function(data){                        //request success callback function;
			
			document.getElementById("showlog").value = '';
			show_last();
		}
	});
}

var updateStop = false;
function change_refresh_rate(value) {	
	setCookie("cookieInterval", value);
	if(value != 0 && updateStop){
		updateStop = false;		
		update_log();
	}
}

function refresh() {
	window.location.href="<?php echo get_self()?>";
}

function update_log() {
	
	var size  = $("#size").attr("value");
	$.ajax({
		url :'../simproxy/ajax_server_simbank.php?random='+Math.random(),       //request file;
		type: 'GET',                                    //request type: 'GET','POST';
		dataType: 'text',                               //return data type: 'text','xml','json','html','script','jsonp';
		data: {
			'action':'process_log',
			'log_type':'SimRdrSvr_log',
			'method':'update',
			'size':size
		},
		error: function(data){                          //request failed callback function;
			//alert("get data error");
		},
		success: function(data){                        //request success callback function;
			
			var pos = data.indexOf('&');
			var size = data.substring(0,pos);						
			var contents = data.substring(pos+1);
			
			
			if(size=='') size = 0;
			
			if(size == 0) {
				document.getElementById("showlog").value = '';
				document.getElementById("showlog").value = contents;
				show_last();								
			}else if (size<0){
				document.getElementById("showlog").value = '';
				document.getElementById("showlog").value = contents;
				show_last();								
				$("#size").attr("value", Math.abs(size));	
			}else {
				$("#size").attr("value", size);	
				if (contents != "") {
					var t = document.getElementById("showlog");
					t.value += contents;
					t.scrollTop = t.scrollHeight;
					//show_last();
				}
			}
		},
		complete: function(){
			var timeout = $("#interval").attr("value");
			if( timeout != 0) {
				setTimeout(function(){update_log();}, timeout*1000);
			}else{
				updateStop = true;
			}
		}
	});
}

function cookie_update() {
	var cookieInterval = getCookie('cookieInterval');
	var nowInterval = document.getElementById("interval");

	if (cookieInterval == null) {
		setCookie("cookieInterval", nowInterval.value)
	} else {
		nowInterval.value = cookieInterval;
	}
}

$(document).ready(function(){
	
	cookie_update();
	show_last();
	var timeout = $("#interval").attr("value");
	if( timeout != 0) {
		update_log();
	}else{
		updateStop = true;
	}
	
	$(":checkbox").iButton(); 
	$("#rdr_log_autoclean").change(function(){$("#rdr_log_maxsize").attr("disabled", !$("#rdr_log_autoclean").attr("checked"));});
	$("#usb_log_autoclean").change(function(){$("#usb_log_maxsize").attr("disabled", !$("#usb_log_autoclean").attr("checked"));});
});
</script>
<?php
	echo_contents();
?>
<?php require("../inc/boot.inc");?>
