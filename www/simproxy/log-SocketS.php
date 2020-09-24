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
	return 0;
	$file_name="socket_s_log.tar.gz";
	$log_name="SimProxySvr.log";
	$file_path="/opt/simbank/SimProxySvr/";
	echo "tar czf $file_path".$file_name." $file_path".$log_name;
	exec("sudo cd $file_path;sudo tar czf $file_path".$file_name." $file_path".$log_name);
	exec("cd $file_path");
	exec("ls",$data);
	print_rr($data);
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
		url :'ajax_server_simbank.php?nocache='+Math.random(),
		type: 'GET',
		dataType: 'text', 
		data: {
			'action':'process_log',
			'log_type':'socket_s_log',
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

if($_POST) {
	if(isset($_POST['send'])) {
		if($_POST['send'] == 'Download') {
			//$id=show_loading("Preparing for downloading......");			
			Download_file();
			//hide_loading($id);
		}
	}
}

?>
	<form id="manform" enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Service Logs');?></li>
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
				<!--
				<input type="submit" value="<?php echo language('Download');?>" 
					onclick="document.getElementById('send').value='Download';"/>
				</td>
				-->
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
		url :'ajax_server_simbank.php?nocache='+Math.random(),
		type: 'GET',
		dataType: 'text', 
		data: {
			'action':'process_log',
			'log_type':'socket_s_log',
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
		url :'ajax_server_simbank.php?random='+Math.random(),       //request file;
		type: 'GET',                                    //request type: 'GET','POST';
		dataType: 'text',                               //return data type: 'text','xml','json','html','script','jsonp';
		data: {
			'action':'process_log',
			'log_type':'socket_s_log',
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
});
</script>
<?php
	echo_contents();
?>
<?php require("../inc/boot.inc");?>
