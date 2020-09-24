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


date_default_timezone_set('UTC');
?>


<?php
$cur_cfg_version = trim(@file_get_contents('/data/config/default/config.info'));
$cur_sys_version = trim(@file_get_contents('/etc/fw_release'));

function make_update_file_path()
{
	$file_path="/opt/simbank/update/simbank".date("YmdHim");
	$tmp = $file_path;

	$i=0;
	while(file_exists($file_path)) {
		$i++;
		$file_path = $tmp."$i";
	}

	return $file_path;
}

function del_old_updatefile()
{
	exec("rm -rf /opt/simbank/update/simbank*");
}

function update_system()
{
	if(! $_FILES) {		
		return;
	}
	
	echo "<br>";
	$Report = language('Report');
	$Result = language('Result');
	$System_Update = language('System Update');
	trace_output_start("$Report", "$System_Update");
	trace_output_newline();
	
	$fireware_name = $_FILES['update_sys_file']['name'];
	
	if(isset($_FILES['update_sys_file']['error']) && $_FILES['update_sys_file']['error'] == 0) {  //Update successful
		if(!(isset($_FILES['update_sys_file']['size'])) || $_FILES['update_sys_file']['size'] > 80*1000*1000) { //Max file size 80Mbyte
			echo language('System Update Filesize error',"Your updated file was larger than 60M!<br>Updating system was failed.");
			trace_output_end();
			return;
		}
		
		sleep(2);
		$store_file = "/mnt/data/simbank.bin";
		unlink($store_file);
		sleep(2);
		//echo "move_uploaded_file<br>\n";
		
		
		if (!move_uploaded_file($_FILES['update_sys_file']['tmp_name'], $store_file)) {  
			echo language('System Update Move error',"Moving your updated file was failed!<br>Updating system was failed.");  
			trace_output_end();
			return;
		}
		
		echo language("System Updating");echo " ......<br>\n";		
		ob_flush();
		flush();
		
		
		//exec("opt/simbank/update/update.sh $store_file > /tmp/update.txt || echo $?",$output);		
		//$cmd="/opt/simbank/update/update.sh $store_file > /dev/null 2>&1 &";
		//echo $cmd;
		//$cmd="/opt/simbank/update/update.sh $store_file > /tmp/update.txt";
		$cmd="/opt/simbank/update/update.sh $store_file > /dev/null 2>&1 &";
		$cmd="sudo /opt/simbank/update/update.sh $store_file > /opt/simbank/update/update.txt || /dev/null 2>&1 &";
		$cmd="/opt/simbank/update/update.sh $store_file >>/dev/null 2>&1 || echo $?";
		//echo $cmd;		
		exec("$cmd",$output);
		
		
		
		
		if($output) {
			trace_output_newhead("$Result");
			echo language("System Update Failed")." ";
			echo language("Error code");echo ": ".$output[0];
			echo "<br>\n";
		} else {
				echo "<!-- Successfully update your system! -->";
				echo language("System Update Succeeded");echo "<br>\n";
				echo language('System Update Succeeded help',"You must reboot system to entry the newer system.");
				
				delete_db_fields_for_lower_version($fireware_name, 1);
		}
		ob_flush();
		flush();
		
		del_old_updatefile();
		
	} else {
		del_old_updatefile();    //Need edit this code by another time. --Freedom--
		if(isset($_FILES['update_sys_file']['error'])) {
			switch($_FILES['update_sys_file']['error']) {
			case 1: // 文件大小超出了服务器的空间大???   
				echo language('System Update error 1',"The file was larger than the server space 60M!");
				break;
			case 2: // 要上传的文件大小超出浏览器限???   
				echo language('System Update error 2',"The file was larger than the browser's limit!");
				break;
			case 3: // 文件仅部分被上传
				echo language('System Update error 3',"The file was only partially uploaded!");
				break;
			case 4: // 没有找到要上传的文件
				echo language('System Update error 4',"Can not find uploaded file!");
				break;
			case 5: // 服务器临时文件夹丢失
				echo language('System Update error 5',"The server temporarily lost folder!");    
				break;
			case 6: // 文件写入到临时文件夹出错
				echo language('System Update error 6',"Failed to write to the temporary folder!");    
				break;    
			}
		}
		echo "<br>";
		trace_output_newhead("$Result");
		echo language("System Update Failed");
	}
	trace_output_end();
}

function update_system_online()
{
	?>
	<br>
	<table width="75%" style="font-size:12px;" align="center">
		<tr>
			<td align="center"><?php echo language('Downloading');?></td>
			<td width="30px"></td>
			<td rowspan=2 width="60px" valign="bottom"><input type="button" value="Cancel" onclick="location=location" /></td>
		</tr>
		<tr>
			<td style="border: 1px solid rgb(59, 112, 162);">
				<div id="progress_bar" style="float:left;text-align:center;width:1px;color:#000000;background-color:rgb(208, 224, 238)"></div>
			</td>
			<td width="30px"><div id="progress_per">0%</div></td>
		</tr>
	</table>

	<script type="text/javascript">
		var filesize=0;

		function set_filesize(fsize)
		{
			filesize=fsize;
		}

		function set_downloaded(fsize)
		{
			if(filesize>0){
				var percent=Math.round(fsize*100/filesize);
				document.getElementById("progress_bar").style.width=(percent+"%");
				if(percent>0){
					document.getElementById("progress_bar").innerHTML = fsize+"/"+filesize;
					document.getElementById("progress_per").innerHTML = percent+"%";
				}else{
					document.getElementById("progress_per").innerHTML = percent+"%";
				}
			}
		}

	</script>

	<?php
	$url="https://downloads.openvox.cn/pub/firmwares/Digital%20Gateway/DGW100x-current.bin";
	//$store_file = make_update_file_path();
	$store_file = "/mnt/ext4/sda7/update/bin/dgw100x-xxx.bin";
	$remote_fh = @fopen ($url, "rb");
	if ($remote_fh){
		$filesize = -1;
		$headers = @get_headers($url, 1); 
		if(is_array($headers)){
			if ((!array_key_exists("Content-Length", $headers))) {
				$filesize=0;
			}
			$filesize = $headers["Content-Length"];
		}else{
			echo "<script>\n";
			echo "alert(\"";echo language('System Online Update Download error','Download system file failed. Please check the network connection is correct!');echo "\");\n";
			echo "window.location.href=\"".get_self()."\"\n";
			echo "</script>\n";
		}
		if($filesize != -1) 
			echo "<script>set_filesize($filesize);</script>";
		$store_fh = @fopen ($store_file, "wb");
		$downlen = 0;
		if ($store_fh){
			while(!feof($remote_fh)) {
				$data=fread($remote_fh, 1024 * 8 );
				if($data==false){
					echo "<script>\n";
					echo "alert(\"";echo language('System Online Update Download error','Download system file failed. Please check the network connection is correct!');echo "\");\n";
					echo "window.location.href=\"".get_self()."\"\n";
					echo "</script>\n";
					break;
				}else{
					$downlen += strlen($data);
					fwrite($store_fh, $data, 1024 * 8 );
					echo "<script>set_downloaded($downlen);</script>";
					ob_flush();
					flush();
				}
			}
			fclose($store_fh);
		}else{
			echo "<script>\n";
			echo "alert(\"";echo language('System Online Update fopen error','Save system file failed!');echo "\");\n";
			echo "window.location.href=\"".get_self()."\"\n";
			echo "</script>";
		}
		fclose($remote_fh);
	}else{
		echo "<script>\n";
		echo "alert(\"";echo language('System Online Update Download error','Download system file failed. Please check the network connection is correct!');echo "\");\n";
		echo "window.location.href=\"".get_self()."\"\n";
		echo "</script>\n";
	}

	if(!file_exists($store_file))
		return false;

	$Report = language('Report');
	$Result = language('Result');
	$System_Online_Update = language('System Online Update');
	trace_output_start("$Report", "$System_Online_Update");
	trace_output_newline();

	echo language("System Updating");echo "......<br>";
	ob_flush();
	flush();

	global $cluster_info;
	if($cluster_info['mode'] == 'master') {
		global $__BRD_SUM__;
		global $__BRD_HEAD__;
		$httpd_conf = '/etc/asterisk/gw/httpd.conf';
		$lighttpd_user_conf = '/etc/asterisk/gw/lighttpd_user.conf';
		$lighttpdpassword = '/etc/asterisk/gw/lighttpdpassword';
		$user = get_web_user();

		$lighttpd_user_contents = 'server.port = 80';
		if(is_file($lighttpd_user_conf)){
			$lighttpd_user_contents = file_get_contents($lighttpd_user_conf);
			if($lighttpd_user_contents == ''){
				$lighttpd_user_contents = 'server.port = 80';
			}
		}

		$lighttpdpassword_contents = 'admin:admin';
		if(is_file($lighttpdpassword)){
			$lighttpdpassword_contents = file_get_contents($lighttpdpassword);
			if($lighttpdpassword_contents == ''){
				$lighttpdpassword_contents = 'admin:admin';
			}
		}
		for($b=2; $b<=$__BRD_SUM__; $b++) {
			if($cluster_info[$__BRD_HEAD__.$b.'_ip'] != '') {
				$slaveip = $cluster_info[$__BRD_HEAD__.$b.'_ip'];
				echo language("Slave");echo ": ". $slaveip ." ";
				echo language('System Updating');echo " .....<br>";
				ob_flush();
				flush();

				$pid = pcntl_fork();
				if ($pid == 0) {
					$data  = 'syscmd:sed -i "s/^\/:.*/\/:'.$user['name'].':'.$user['password'].'/g" '.$httpd_conf.';';
					$data .= '/etc/init.d/httpd restart;';
					$data .= 'sleep 1;';
					request_slave($slaveip,$data,5,true);

					$data  = 'syscmd:echo -e "'.$lighttpd_user_contents.'" > '.$lighttpd_user_conf.';';
					$data .= 'echo -e "'.$lighttpdpassword_contents.'" > '.$lighttpdpassword.';';
					$data .= '/etc/init.d/lighttpd restart;';
					$data .= 'sleep 1;';
					request_slave($slaveip,$data,5,true);

					$data = 'syscmd:if [ -e /etc/init.d/httpd ]; then echo httpd; else echo lighttpd; fi';
					$ret = request_slave($slaveip,$data,5,true);
					$port = '';
					if(strncmp($ret,'httpd',5)==0){
						$port = 80;
					}

					$ret = update_slave($slaveip,$store_file,$port);
					if($ret){
						echo language("System Update Succeeded");echo "<br>\n";
					}else{
						echo language("System Update Failed");echo "<br>\n";
					}
					exit(0);
				} else if($pid > 0) {
					$pids[] = $pid;
				} else {
					$error_str = language('fork error');
					echo $slaveip." ";
					echo language('Update failed');
					echo " ($error_str).<br>";
				}
				ob_flush();
				flush();
			}
		}

		if(isset($pids) && is_array($pids)) {
			foreach($pids as $each) {
				pcntl_waitpid($each,$status);
			}
		}
	}

//	exec("auto_update -u -f $store_file > /dev/null 2>&1 || echo $?",$output);
	exec("/bin/unpack.sh /mnt/ext4/sda7/update/bin/dgw100x-xxx.bin  > /tmp/update.txt || echo $?",$output);
	if($output) {
		trace_output_newhead("$Result");
		echo language("System Update Failed");echo "<br>\n";
		echo language("Error code");echo ": ".$output[0];
	} else {
		//echo "autorun.lua  .....";
		exec("/mnt/ext4/sda7/update/tools/autorun.lua > /tmp/update.txt || echo $?",$output);

		trace_output_newhead("$Result");
		if($output) {
			echo language('System Update Failed');echo "<br>\n";
			echo language("Error code");echo ": ".$output[0];
		} else {
			//exec("/my_tools/add_syslog \"System Update\"");
			echo language('System Update Succeeded');echo "<br>\n";
			echo language('System Update Succeeded help',"You must reboot system to entry the newer system.");
		}
	}
	del_old_updatefile();
	trace_output_end();
}


function upload_cfg_file($type)
{
	if(! $_FILES) {
		return;
	}

	echo "<br>";
	$Report = language('Report');
	$Result = language('Result');
	$theme = language("Configuration Files Upload");
	trace_output_start("$Report", "$theme");
	trace_output_newline();
	
	if(isset($_FILES['upload_cfg_file']['error']) && $_FILES['upload_cfg_file']['error'] == 0) {  //Update successful
		if(!(isset($_FILES['upload_cfg_file']['size'])) || $_FILES['upload_cfg_file']['size'] > 60*1000*1000) { //Max file size 60Mbyte
			echo language('Configuration Files Upload Filesize error',"Your uploaded file was larger than 60M!<br>Uploading configuration files was failed.");
			return;
		}

		$store_file = make_update_file_path();
		$store_file="/data/config/upload_config.tar.gz";
		
		if (!move_uploaded_file($_FILES['upload_cfg_file']['tmp_name'], $store_file)) {  
			echo language('Configuration Files Upload Move error',"Moving your updated file was failed!<br>Uploading configuration files was failed.");  
			return;
		}
		echo language("Configuration Files Uploading");echo " ......<br>";
		ob_flush();
		flush();
		
		//uppack uploaded file
		exec("/bin/restore_config.sh ".$store_file." $type  > /dev/null 2>&1 || echo $?",$output);
	
		if($output) {
			echo "</br>$cfg_name ";
			echo language("Unpacking was failed");echo "</br>";
			echo language("Error code");echo ": ".$output[0];
			return;
		}


		trace_output_newhead("$Result");
		echo language("Configuration Files Upload Succeeded");echo "</br>";
		trace_output_end();
		$System_Reboot_help = language('System Reboot wait','System Rebooting...<br>Please wait for about 60s, system will be rebooting.');
	    js_reboot_progress("$System_Reboot_help");
		exec("systemctl reboot > /dev/null 2>&1");
		//system_reboot();
	} else {
		
		if(isset($_FILES['upload_cfg_file']['error'])) {
			switch($_FILES['upload_cfg_file']['error']) {
			case 1:    
				echo language('Configuration Files Upload error 1',"The file was larger than the server space 60M!");
				break;
			case 2:    
				echo language('Configuration Files Upload error 2',"The file was larger than the browser's limit!");
				break;
			case 3:
				echo language('Configuration Files Upload error 3',"The file was only partially uploaded!");
				break;
			case 4: 
				echo language('Configuration Files Upload error 4',"Can not find uploaded file!");
				break;
			case 5: 
				echo language('Configuration Files Upload error 5',"The server temporarily lost folder!");    
				break;
			case 6: 
				echo language('Configuration Files Upload error 6',"Failed to write to the temporary folder!");    
				break;    
			}
		}
		echo "<br>";
		trace_output_newhead("$Result");
		echo language("Configuration Files Upload Failed");
		trace_output_end();
	}
	
}

function backup_cfg_file()
{
	

	
	
	//Current config version
	global $cur_cfg_version;

	//configuration file name
	$cfg_name="config-$cur_cfg_version.tar.gz";

	//configuration file path
	$cfg_path="/tmp/$cfg_name";

	//$pack_cmd="tar -zcvpf /tmp/$cfg_name /mnt/ext4/sda7/config/defconfig";
	//exec("$pack_cmd > /dev/null 2>&1 || echo $?",$output);
	
	exec("sh /bin/backup_config.sh ".$cfg_path." > /dev/null 2>&1 || echo $?",$output);
	
	if($output) {
		echo "</br>$cfg_name ";
		echo language("Packing was failed");echo "</br>";
		echo language("Error code");echo ": ".$output[0];
		return;
	}

	if(!file_exists($cfg_path)) {
		echo "</br>$cfg_name";
		echo language("Can not find");
		return;
	}

	//打开文件  
	$file = fopen ($cfg_path, "r" ); 
	$size = filesize($cfg_path) ;

	//输入文件标签 
	header('Content-Encoding: none');
	header('Content-Type: application/force-download');
	header('Content-Type: application/octet-stream');
	header('Content-Type: application/download');
	header('Content-Description: File Transfer');  
	header('Accept-Ranges: bytes');  
	header( "Accept-Length: $size");  
	header( 'Content-Transfer-Encoding: binary' );
	header( "Content-Disposition: attachment; filename=$cfg_name" ); 
	header('Pragma: no-cache');
	header('Expires: 0');
	//输出文件内容   
	//读取文件内容并直接输出到浏览???
	ob_clean();
	flush();
	echo fread($file, $size);
	fclose ($file);

	unlink($cfg_path);

	
	
}

$g_restore_cfg_file = false;
function res_def_cfg_file()
{
	// global $cluster_info;
	// global $__BRD_SUM__;
	// global $__BRD_HEAD__;

	$default_cfg_Restore = language('Configuration Restore wait',"Default Configuration Files Restoring...<br>Please wait for about 60s, system will be rebooting.");
	js_reboot_progress("$default_cfg_Restore");

	echo "<br>";
	$Report = language('Report');
	$Configuration_Restore = language('Configuration Restore');
	trace_output_start("$Report","$Configuration_Restore");
	trace_output_newline();
	
	/*
	if($cluster_info['mode'] == 'master') {
		for($b=2; $b<=$__BRD_SUM__; $b++) {
			if($cluster_info[$__BRD_HEAD__.$b.'_ip'] != '') {
				trace_output_newline();
				$data = "syscmd:/my_tools/restore_cfg_file > /dev/null 2>&1\n";
				$ip = $cluster_info[$__BRD_HEAD__.$b.'_ip'];
				request_slave($ip, $data, 5, false);
				echo language("Slave");echo ": $ip ";
				echo language("Configuration Restoring");echo " ......<br/>";
				ob_flush(); 
				flush();
			}
		}
	}*/
	
	exec("/tools/conf_restore.sh > /dev/null 2>&1 || echo $?",$output);
	echo language("Configuration Restoring");echo " ......<br/>";
	if($output) {
		echo language("Configuration Restore Failed<br/>");
		echo language("Error code");echo ": ".$output[0];
		flush();
		ob_flush();
	}
	trace_output_end();

	global $g_restore_cfg_file;
	$g_restore_cfg_file = true;
	exec("systemctl reboot > /dev/null 2>&1");
}

function system_reboot()
{
	global $cluster_info;
	global $__BRD_HEAD__;
	global $__BRD_SUM__;

	$System_Reboot_help = language('System Reboot wait','System Rebooting...<br>Please wait for about 60s, system will be rebooting.');
	js_reboot_progress("$System_Reboot_help");
	
	echo "<br>";
	$Report = language('Report');
	$System_Reboot = language('System Reboot');
	trace_output_start("$Report","$System_Reboot");
	trace_output_newline();
	
	/*
	if($cluster_info['mode'] == 'master') {
		for($b=2; $b<=$__BRD_SUM__; $b++) {
			if($cluster_info[$__BRD_HEAD__.$b.'_ip'] != '') {
				$data = "syscmd:reboot > /dev/null 2>&1\n";
				$ip = $cluster_info[$__BRD_HEAD__.$b.'_ip'];
				request_slave($ip, $data, 5, false);
				echo language("Slave");echo ":$ip ";echo language("System Rebooting");echo " ......<br/>";
				ob_flush();
				flush();
			}
		}
		echo language("System Rebooting");echo "......";
	}else{
		echo language("System Rebooting");echo "......";
	}
	*/
	echo language("System Rebooting");echo "......";
	trace_output_end();
	exec("sleep 3");
	exec("systemctl reboot > /dev/null 2>&1");
}

function ast_reboot()
{
	global $cluster_info;
	global $__BRD_HEAD__;
	global $__BRD_SUM__;

	echo "<br>";
	$Report = language('Report');
	$Asterisk_Reboot = language('Asterisk Reboot');
	trace_output_start("$Report","$Asterisk_Reboot");
	trace_output_newline();
	ob_flush();
	flush();

	if($cluster_info['mode'] == 'master') {
		for($b=2; $b<=$__BRD_SUM__; $b++) {
			if($cluster_info[$__BRD_HEAD__.$b.'_ip'] != '') {
				$data = "syscmd:/etc/init.d/asterisk restart > /dev/null 2>&1\n";
				$ip = $cluster_info[$__BRD_HEAD__.$b.'_ip'];
				request_slave($ip, $data, 5, false);
				echo language("Slave");echo ":$ip ";echo language("Asterisk Rebooting");echo " ......<br/>";
				ob_flush();
				flush();
			}
		}
		echo language("Asterisk Rebooting");echo " ......<br/>";
	}else{
		echo language("Asterisk Rebooting");echo " ......<br/>";
	}
	exec("/etc/init.d/asterisk restart > /dev/null 2>&1 || echo $?",$output);

	$Result = language('Result');
	trace_output_newhead("$Result");
	if(!$output) {
		echo language("Asterisk Reboot Succeeded");
	} else {
		echo language("Asterisk Reboot Failed");echo "($output[0])";
	}

	trace_output_end();
}

function system_switch(){
	echo "<br/>";
	trace_output_start(language('Report'),language('System Switch'));
	trace_output_newline();
	ob_flush();
	flush();
	
	echo language('System Switching').'......<br/>';
	exec("/tools/switch_sys.sh > /dev/null 2>&1 || echo $?",$output);
	
	trace_output_newhead(language('Result'));
	
	if(!$output){
		delete_db_fields_for_lower_version("",2);
		
		echo language("System Switch Succeeded");
	}else{
		echo language("System Switch Failed");echo "($output[0])";
	}
	
	trace_output_end();
}
?>


<script type="text/javascript">
var g_bAllowFile = false;

function checkFileChange(obj)
{
	var filesize = 0;  
	var  Sys = {};  

	if(navigator.userAgent.indexOf("MSIE")>0){
		Sys.ie=true;  
	} else
	//if(isFirefox=navigator.userAgent.indexOf("Firefox")>0)  
	{  
		Sys.firefox=true;  
	}
	   
	if(Sys.firefox){  
		//filesize = obj.files[0].fileSize;  
		filesize = obj.files[0].size;  
	} else if(Sys.ie){
		try {
			obj.select();
			var realpath = document.selection.createRange().text;
			//alert(obj.value);
			//alert(realpath);
			var fileobject = new ActiveXObject ("Scripting.FileSystemObject");//获取上传文件的对??? 
			//var file = fileobject.GetFile (obj.value);//获取上传的文??? 
			var file = fileobject.GetFile (realpath);//获取上传的文??? 
			var filesize = file.Size;//文件大小  
		} catch(e){
			alert("<?php echo language('System Update IE alert','Please allow ActiveX Scripting File System Object!');?>");
			return false;
		}
	}

	if(filesize > 1000*1000*100) {
		alert("<?php echo language('System Update filesize alert','Uploaded max file is 100M!');?>");
		g_bAllowFile = false;
		return false;
	}

	g_bAllowFile = true;
	return true;
} 

function isAllowFile(file_id)
{
	var x = document.getElementById(file_id).value;
	if(x=="")
	{
		alert("<?php echo language('Select File alert','Please select your file first!');?>");
		return false;
	}
	//return true;

	if(g_bAllowFile)
		return true;

	alert("<?php echo language('System Update filesize alert','Uploaded max file is 60M!');?>");
	return false;
}

function update_system()
{
	if(!isAllowFile('update_sys_file')) {
		return false;
	}

	if(!confirm("<?php echo language('System Update confirm','Are you sure to update your system?\\nUse caution, please!This might damage the structure of your original configuration files.');?>")){
		return false;
	}
	upload_file();
	return true;
}

function trim(str){  
    str = str.replace(/^(\s|\u00A0)+/,'');  
    for(var i=str.length-1; i>=0; i--){  
        if(/\S/.test(str.charAt(i))){  
            str = str.substring(0, i+1);  
            break;  
        }  
    }  
    return str;  
} 


function update_system_online_step1()
{
	document.getElementById('showmsg').value = 'Getting information...';
	$( "#update_online_dg" ).dialog({
		resizable: false,
		height:400,
		width:507,
		
		modal: true,	
		buttons: {
			"<?php echo language('Change Log')?>": function() {
				update_system_online_step2();
			},
			"<?php echo language('Detailed')?>": function() {
				update_system_online_step3();
			},
			"<?php echo language('Update Online Now')?>": function() {
				$( this ).dialog( "close" );
				document.getElementById('send').value='System Online Update';
				document.getElementById('manform').submit();
			},
			<?php echo language('Cancel')?>: function() {
				$( this ).dialog( "close" );
			}
			
		}
	});

	var server_file = "./../../cgi-bin/php/ajax_server.php";
	$.ajax({
		url: server_file+"?random="+Math.random()+"&type=system&system_type=newest_sys_version",	
		async: false,
		dataType: 'text',
		type: 'GET',
		timeout: 5000,
		error: function(data){				//request failed callback function;
			document.getElementById('showmsg').value = "<?php echo language('System Online Update version error','Get remote version failed. Please check the network connection is correct!');?>";
		},
		success: function(data){			//request success callback function;
			var versionnum = trim(data);			
			if((versionnum.indexOf(".")!=-1)){			
				document.getElementById('showmsg').value = "<?php echo language('System version help 1','Your current system version is :'); echo $cur_sys_version;?>"+"\n"+"<?php echo language('System version help 2','The latest system version is :'); ?>" + versionnum + "\n<?php echo language('System Online Update confirm',"Be cautious, please:\\nThis might damage the structure of your original configuration files! \\nAre you sure to update your system?\\n");?>" + "<?php echo language('System version help 1','Your current system version is :'); echo $cur_sys_version;?>"+"\n\n"+"<?php echo language('System version help 3','Warning:\\nDO NOT leave this page in the process of updating; OTHERWISE system updating will fail!\\n'); ?>";
				return;
			} else {
				document.getElementById('showmsg').value = "<?php echo language('System Online Update version error','Get remote version failed. Please check the network connection is correct!');?>";
				return;
			}
		}
	});
}

function update_system_online_step2()
{
	var server_file = "./../../cgi-bin/php/ajax_server.php";

	document.getElementById('showmsg').value = 'Getting information...';

	$.ajax({
		url: server_file+"?random="+Math.random()+"&type=system&system_type=newest_sys_changelog",	
		async: false,
		dataType: 'text',				
		type: 'GET',					
		timeout: 5000,
		error: function(data){				//request failed callback function;
			document.getElementById('showmsg').value = "Can't get change log.";
		},
		success: function(data){			//request success callback function;
			data=trim(data);
			document.getElementById('showmsg').value = data;			
			if((versionnum.indexOf(".")!=-1)){			
				document.getElementById('showmsg').value = "Can't get change log.";
				return;
			} else {
				document.getElementById('showmsg').value = data;
				return;
			}
			
			
		}
	});
}

function update_system_online_step3()
{
	var server_file = "./../../cgi-bin/php/ajax_server.php";

	document.getElementById('showmsg').value = 'Getting information...';

	$.ajax({
		url: server_file+"?random="+Math.random()+"&type=system&system_type=sys_changelog",	
		async: false,
		dataType: 'text',				
		type: 'GET',					
		timeout: 5000,  
		error: function(data){				//request failed callback function;
			document.getElementById('showmsg').value = "Can't get detial change log.";
		},
		success: function(data){			//request success callback function;
			document.getElementById('showmsg').value = data;
		}
	});
}

function update_kernel_file2()
{
	if(!isAllowFile('update_kernel_file')) {
		return false;
	}

	if( ! confirm("<?php echo language('Factory Kernel Update confirm','Are you sure to update factory kernel?\\nUse caution, please!This might damage the structure of your original configuration files.');?>")) {
		return false;
	}

	return true;
}
function upload_cfg_file2()
{
	if(!isAllowFile('upload_cfg_file')) {
		return false;
	}

	if( ! confirm("<?php echo language('File Upload confirm','Are you sure to upload configuration files?\nThis will damage the structure of your original configuration files.');?>") ) {
		return false;
	}
	
	if(confirm("<?php echo language('do you want to reserve the original configuration of network?'); ?>"))
		document.getElementById('send').value='File Upload 1';	
	else
		document.getElementById('send').value='File Upload 0';

	return true;
}
function upload_file(){
	var _file = document.getElementById("update_sys_file").files;
	var _file_name = _file[0].name;
	var _simbank_str = _file_name.substr(0, 7);
	var _bin_str = _file_name.substr(_file_name.length-4, 4);
	if(_simbank_str != 'Simbank' || _bin_str != '.bin'){
		alert("<?php echo language("Upload File tip","The file name does not comply with the specification!");?>");
		event.preventDefault();
		window.event.returnValue = false;
		return false;
	}
}
</script>


	<form id="manform" enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">

	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Reboot Tools');?></li>
		<li class="tb2">&nbsp;</li>
	</div>

	<table width="100%" class="tctl" >
		<tr>
			<th>
			<?php echo language('System Reboot help','Reboot the Simbank and all the current calls will be dropped.');?>
			</th>
			
			<td>
				<input type="submit" <?php if ($_SESSION['demo_mode']=='on'){echo "disabled='disabled'";}?>  value="<?php echo language('System Reboot');?>" 
					onclick="document.getElementById('send').value='System Reboot';return confirm('<?php echo language('System Reboot confirm','Are you sure to reboot your gateway now?\nYou will lose all data in memory!');?>')"/>
			</td>
			
		</tr>
	</table>
	
	<br/>
	
	<table width="100%" class="tctl">
		<tr>
			<th>
				<?php echo language('System Reboot help','Reboot the Simbank and all the current calls will be dropped.');?>
			</th>
			
			<td>
				<input type="submit" value="<?php echo language('System Switch');?>" <?php if($_SESSION['demo_mode']=='on'){echo 'disabled';}?>
					onclick="document.getElementById('send').value='System Switch';return confirm('<?php echo language('System Switch confirm','Are you sure to switch system now?After switching over, the system needs to be restarted to take effect.');?>');" />
			</td>
		</tr>
	</table>
	
	<!--
	<br/>
	
	<table width="100%" class="tctl" >
		<tr>
			<th>
			<?php echo language('Asterisk Reboot help','Reboot the asterisk and all the current calls will be dropped.');?>
			</th>
			
			<td>
				<input type="submit"  <?php if ($_SESSION['demo_mode']=='on'){echo "disabled='disabled'";}?>  value="<?php echo language('Asterisk Reboot');?>" 
					onclick="document.getElementById('send').value='Asterisk Reboot';return confirm('<?php echo language('Asterisk Reboot confirm','Are you sure to reboot Asterisk now?');?>')"/>
			</td>
			
		</tr>
	</table>
	-->
	<br/>

	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Update Firmware');?></li>
		<li class="tb2">&nbsp;</li>
	</div>

	<table width="100%" class="tctl" >
		<tr>
			<th>
				<?php echo language('New system file');?>:<input type="file" name="update_sys_file" onchange="return checkFileChange(this)" id="update_sys_file"/>
			</th>
			
			<td>
				<input type="submit" <?php if ($_SESSION['demo_mode']=='on'){echo "disabled='disabled'";}?>  value="<?php echo language('System Update');?>" 
					onclick="document.getElementById('send').value='System Update';return update_system()" />
			</td>
			
		</tr>
	</table>

	<br>
<!--	这个打开以后，会和jgrowl的CSS冲突，导致弹屏窗口变成黄???
	<link type="text/css" href="/css/jquery-ui-1.10.2.custom.all.css" rel="stylesheet" media="all"/>
	<script type="text/javascript" src="/js/jquery-ui-1.10.2.custom.all.min.js"></script>	
-->
	<link type="text/css" href="/css/jquery-ui-1.10.2.custom.all.css" rel="stylesheet" media="all"/>
	<script type="text/javascript" src="/js/jquery-ui-1.10.2.custom.all.min.js"></script>
	
	<style>
		.ui-dialog-title{color:white}
		.ui-widget-header{background-image:none;background-color:#329ee2}
	</style>
	<!--
	<div id="update_online_dg" title="<?php echo language("Update Online Information");?>" style="display:none;">		
		<center>
			<textarea id="showmsg" style="height:290px;width:400px;" readonly></textarea>
		</center>
	</div>	
	<?php if ($_SESSION["adv.com.name"]=='openvox'){ ?>
	<table width="100%" class="tctl" >
		<tr>
			<th>
			<?php echo language('System Online Update help','
				New system file is downloaded from official website and update system.');
			?>
			</th>
			
			<td>
				<input type="button" <?php if ($_SESSION['demo_mode']=='on'){echo "disabled='disabled'";}?>  value="<?php echo language('System Online Update');?>" onclick="update_system_online_step1();"/>
			</td>
			
		</tr>
	</table>
	<?php }?>
	<br/>
	
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Upload Configuration');?></li>
		<li class="tb2">&nbsp;</li>
	</div>

	<table width="100%" class="tctl">
		<tr>
			<th>
				<?php echo language('New configuration file');?>:<input type="file" name="upload_cfg_file" onchange="return checkFileChange(this)" id="upload_cfg_file"/>
			</th>
			
			<td>
				<input type="submit" <?php if ($_SESSION['demo_mode']=='on'){echo "disabled='disabled'";}?>  value="<?php echo language('File Upload');?>" 
					onclick="document.getElementById('send').value='File Upload';return upload_cfg_file2()" />
			</td>
			
		</tr>
	</table>

	<br/>

	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Backup Configuration');?></li>
		<li class="tb2">&nbsp;</li>
	</div>
	<table width="100%" class="tctl">
		<tr>
			<th>
				<?php echo language('Current configuration file version');echo ": $cur_cfg_version"; ?>
			</th>
			
			<td>
				<input type="submit" <?php if ($_SESSION['demo_mode']=='on'){echo "disabled='disabled'";}?>  value="<?php echo language('Download Backup');?>" 
					onclick="document.getElementById('send').value='Download Backup';"/>
			</td>
			
		</tr>
	</table>
	
	<br/>
-->
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Restore Configuration');?></li>
		<li class="tb2">&nbsp;</li>
	</div>

	<table width="100%" class="tctl" >
		<tr>
			<th>
			<?php echo language('Factory Reset help','
				This will cause all the configuration files to back to default factory values! And reboot your gateway once it finishes.');
			?>
			</th>
			
			<td>
				<input type="submit" <?php if ($_SESSION['demo_mode']=='on'){echo "disabled='disabled'";}?>  value="<?php echo language('Factory Reset');?>" 
					onclick="document.getElementById('send').value='Factory Reset';return confirm('<?php echo language('Factory Reset confirm','Are you sure to restore configuration file now?');?>')"/>
			</td>
			
		</tr>
	</table>
	
	<input type="hidden" name="send" id="send" value="" />

	</form>

	
<?php
if($_POST) {
	if(isset($_POST['send'])) {
		if($_POST['send'] == 'System Update') {
			$id=show_loading(language("System Updating","System Updating......"));
			// uncompress firmware image in memory replace in data patition, 2016-03-07 15:07 //
			//exec("rm -rf  /data/update/work/dgw100x-firmware > /tmp/update.txt || echo $?",$output);
			//exec("mkdir -p /.update && ln -s /.update /data/update/work/dgw100x-firmware  > /tmp/update.txt || echo $?",$output);
			update_system();
			hide_loading($id);
			//exec("rm -rf  /data/update/work/dgw100x-firmware > /tmp/update.txt || echo $?",$output);
			//exec("rm -rf /.update && mkdir -p /data/update/work/dgw100x-firmware > /tmp/update.txt || echo $?",$output);
		} else if($_POST['send'] == 'System Online Update') {
			$id=show_loading(language("System Online Updating","System Online Updating......"));
			// uncompress firmware image in memory replace in data patition, 2016-03-07 15:07 //
			exec("rm -rf  /data/update/work/dgw100x-firmware > /tmp/update.txt || echo $?",$output);
			exec("mkdir -p /.update && ln -s /.update /data/update/work/dgw100x-firmware  > /tmp/update.txt || echo $?",$output);
			update_system_online();
			hide_loading($id);
			exec("rm -rf  /data/update/work/dgw100x-firmware > /tmp/update.txt || echo $?",$output);
			exec("rm -rf /.update && mkdir -p /data/update/work/dgw100x-firmware > /tmp/update.txt || echo $?",$output);
		} else if($_POST['send'] == 'File Upload 1') {
			$id=show_loading(language("File Uploading","File Uploading......"));
			upload_cfg_file('1');
			hide_loading($id);
		} else if($_POST['send'] == 'File Upload 0') {                 
			$id=show_loading(language("File Uploading","File Uploading......"));              
		        upload_cfg_file('0');                                  
		        hide_loading($id);                                     
		}else if($_POST['send'] == 'Factory Reset') {			
			res_def_cfg_file();
		} else if($_POST['send'] == 'System Reboot') {
			system_reboot();
		} else if($_POST['send'] == 'Asterisk Reboot') {
			ast_reboot();
		} else if($_POST['send'] == 'System Switch') {
			system_switch();
		}
		
	}	
}

if($_POST) {
	if(isset($_POST['send'])) {
		if($_POST['send'] == 'Download Backup') {
			//$id=show_loading("Preparing for downloading......");
			backup_cfg_file();
			//hide_loading($id);
		}
	}
}

function show_loading($str)
{
	$id = rand(1,10000);
	$id = "L$id";

	echo <<<EOF
	<table align="center" id="$id">
		<tr>
			<td align="center">
				<img src="/images/loading.gif" align="middle"/>
			</td>
		</tr>
		<tr>
			<td align="center">
				<?php language('System Updating')."......"?>				
			</td>
		</tr>
	</table>
EOF;
//EOF

	ob_flush();
	flush();

	return $id;
}

function hide_loading($id)
{
	echo <<<EOF
	<script type="text/javascript">
	document.getElementById("$id").style.display='none';
	</script>
EOF;
//EOF

	ob_flush();
	flush();
}
?>

<?php require("../inc/boot.inc");?>
