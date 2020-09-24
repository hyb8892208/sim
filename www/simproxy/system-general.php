<?php
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/aql.php");
include_once("../inc/function.inc");
include_once("../inc/wrcfg.inc");
include_once("../inc/network_factory.inc");

function save_web_language_conf()
{
/*----------------
[general]
language=chinese

[list]
english=English
chinese=汉语
portuguese=Português	
----------------*/
	$conf_file = "/config/simbank/conf/web_language.conf";
	if(isset($_POST['language_type'])){
		$conf_array['general']['language'] = $_POST['language_type'];
		return modify_conf($conf_file, $conf_array);
	}else{
		return false;
	}
}

function download_language(&$alert)
{
	if(!isset($_POST['language_type']) || $_POST['language_type'] == ''){
		return false;
	}
	$language_type = $_POST['language_type'];
	if(is_file('/opt/simbank/www/lang/'.$language_type))
		$package = '/opt/simbank/www/lang/'.$language_type;
	else if(is_file('/config/simbank/conf/web_language/'.$language_type))
		$package = '/config/simbank/conf/web_language/'.$language_type;
	else
		$package = '';

	if(!file_exists($package)) {
		$alert = language('Language Download error','Can not find Your select language package.');
		return false;
	}

	$file = fopen ($package, 'r');
	$size = filesize($package) ;

	header('Content-Encoding: none');
	header('Content-Type: application/force-download');
	header('Content-Type: application/octet-stream');
	header('Content-Type: application/download');
	header('Content-Description: File Transfer');
	header('Accept-Ranges: bytes');
	header("Accept-Length: $size");
	header('Content-Transfer-Encoding: binary' );
	header("Content-Disposition: attachment; filename=$language_type");
	header('Pragma: no-cache');
	header('Expires: 0');
	ob_clean();
	flush();
	echo fread($file, $size);
	fclose ($file);
	exit(0);

	return true;
}

function delete_language()
{
	global $__BRD_SUM__;
	global $__BRD_HEAD__;

	if(isset($_POST['language_type']) && $_POST['language_type'] != ''){
		$language_type = $_POST['language_type'];
		$conf_file = "/config/simbank/conf/web_language.conf";
		$conf_array = get_conf($conf_file);

		/* check the delete language is current using language */
		if(is_file('/config/simbank/conf/web_language/'.$language_type)){
			/* 1.Modify language conf */
			delete_conf($conf_file, 'list',$language_type);
			/* 2.Delete language package */
			unlink('/config/simbank/conf/web_language/'.$language_type);
			/* 3.Check language setting */
			if(isset($conf_array['general']['language']) &&  $conf_array['general']['language']==$language_type){
				$conf_new['general']['language']='english';
				modify_conf($conf_file,$conf_new);
				wait_apply("exec", "/tools/web_language_init >/dev/null 2>&1 &");
			}
		}
	}
}

function store_language($store_file, &$alert)
{
	if(!$_FILES) {
		return;
	}   

	if(isset($_FILES['upload_lang_file']['error']) && $_FILES['upload_lang_file']['error'] == 0) {  //Update successful
		if(!(isset($_FILES['upload_lang_file']['size'])) || $_FILES['upload_lang_file']['size'] > 1*1000*1000) { //Max file size 1Mbyte
			echo language('Language Package Upload Filesize error',"Your uploaded file was larger than 1MB!<br>Uploading language package was failed.<br>");
			return false;
		}   

		if (!move_uploaded_file($_FILES['upload_lang_file']['tmp_name'], $store_file)) {   
			echo language('Language Package Upload Move Failed');  
			return false;
		}
	}else{
		echo language("Language Package Upload Failed");
		return false;
	}
	return true;
}

function add_language(&$alert, &$confirm, $unstore=true)
{
/*
Language Package first line format:
------------------------------
language#chinese#中文
...
------------------------------

*/

	$store_file = "/tmp/web/new_language";
	if($unstore){
		if(!store_language($store_file, $alert)){
			return false;
		}
	}

	/* check package */
	$info = '';
	if(is_language($store_file,$info)){
		$language_key = $info['key'];
		$language_value = $info['value'];
	}else{
		echo language('Language Package Format error');
		return false;
	}

	/* check whether language exists */
	$conf_file = "/config/simbank/conf/web_language.conf";
	$conf_array = get_conf($conf_file);
	if($unstore){
		if(isset($conf_array['list']) && is_array($conf_array['list'])){
			foreach($conf_array['list'] as $key => $value){
				if($key==$language_key || $value==$language_value){
					if(is_file('/opt/simbank/www/lang/'.$language_key)){
						$alert = language('Add Language overwrite alert','Language already exists!\nReadyonly Language cannot be overwrite!');
						return false;
					}else if(is_file('/config/simbank/conf/web_language/'.$language_key)){
						$confirm = language('Add Language overwrite confirm','Language already exists!\nDo you want to overwrite it?');
						return false;
					}
				}
			}
		}
	}
	/* copy language package from "/tmp/web/new_language" to "/config/simbank/conf/web_language/x" */
	if(!is_dir('/config/simbank/conf/web_language/')){
		mkdir('/config/simbank/conf/web_language/');
	}
	if(!copy($store_file, '/config/simbank/conf/web_language/'.$language_key))return false;

	/* update web language conf */
	$conf_array['list'][$language_key]=$language_value;
	modify_conf($conf_file, $conf_array);

	if(isset($conf_array['general']['language']) && $conf_array['general']['language']==$language_key){
		wait_apply("exec", "/tools/web_language_init >/dev/null 2>&1 &");
	}

	return true;
}

function debug_language($debug)
{
	$debug_file = '/tmp/web/language.debug';
	if($debug == 'on'){
		touch($debug_file);
	}else if($debug == 'off'){
		if(file_exists($debug_file)){
			unlink($debug_file);
		}
	}
}

function read_SimRdrSvr_conf(){
	$file = '/mnt/config/simbank/conf/SimRdrSvr.conf';
	
	if( !file_exists($file) ){
		echo 'file \''.$file.'\' does not exists.<br /><br />';
		require("../inc/boot.inc");
		die();
	}
	
	$aql=new aql;
 	$setok = $aql->set('basedir','/mnt/config/simbank/conf');
	if (!$setok) {
 		echo $aql->get_error();
 		return;
 	}
	
	$db=$aql->query("select * from SimRdrSvr.conf" );
		
	if(!isset($db['SimRdrSvr']['seri'])) {
		$aql->assign_append('SimRdrSvr','seri','');
	} else {
		$Res['seri']=$db['SimRdrSvr']['seri'];
	}
	
	if(isset($db['SimRdrSvr']['net_mode'])){
		$Res['net_mode'] = $db['SimRdrSvr']['net_mode'];
	}else{
		$Res['net_mode'] = 'local';
	}
	
	if(!isset($db['SimRdrSvr']['server_ip'])) {
		$aql->assign_append('SimRdrSvr','server_ip',getLocalIp());
	} else {
		$Res['server_ip']=$db['SimRdrSvr']['server_ip'];
	}
	
	if (!$aql->save_config_file('SimRdrSvr.conf')) {
		echo $aql->get_error();		
		return;
	}	
	return $Res;
}

function save_to_SimRdrSvr_conf(){
	$file = '/mnt/config/simbank/conf/SimRdrSvr.conf';
	
	if( !file_exists($file) ){
		echo 'file \''.$file.'\' does not exists.<br /><br />';
		require("../inc/boot.inc");
		die();
	}
	
	$ipv4_ip = "127.0.0.1";
	
	$aql = new aql();
 	$setok = $aql->set('basedir','/mnt/config/simbank/conf');
	if (!$setok) {
 		echo $aql->get_error();
 		return;
 	}
	$hlock = lock_file($file);
	if (!$aql->open_config_file($file)){
		echo $aql->get_error();
		unlock_file($hlock);
		return false;
	}
	
	$db=$aql->query("select * from SimRdrSvr.conf" );
	
	if(isset($db['SimRdrSvr']['local_ip'])){
		$aql->assign_editkey('SimRdrSvr','local_ip',$ipv4_ip);
	}else{
		$aql->assign_append('SimRdrSvr','local_ip',$ipv4_ip);
	}
	
	if(isset($db['SimRdrSvr']['net_mode'])){
		$aql->assign_editkey('SimRdrSvr','net_mode',$_POST['net_mode']);
	}else{
		$aql->assign_append('SimRdrSvr','net_mode',$_POST['net_mode']);
	}
	
	if($_POST['net_mode'] == 'local'){
		$server_ip = $ipv4_ip;
	}else{
		$server_ip = $_POST['server_ip'];
	}
	if(isset($db['SimRdrSvr']['server_ip'])){
		$aql->assign_editkey('SimRdrSvr','server_ip',$server_ip);
	}else{
		$aql->assign_append('SimRdrSvr','server_ip',$server_ip);
	}
	
	if (!$aql->save_config_file('SimRdrSvr.conf')) {
		echo $aql->get_error();
		unlock_file($hlock);
		return false;
	}
	unlock_file($hlock);
	wait_apply("exec","kill -9 `pidof SimRdrSvr`");
	wait_apply("exec","kill -9 `pidof SimProxySvr`");
}

global $__PORT_SUM__;
global $__BRD_SUM__;
global $__BRD_HEAD__;


$alert = '';//for javascript alert information when html loaded.
$confirm = '';//for javascript confirm information when html loaded.

if($_POST) {
	if($_POST['send'] == 'Language Save') {
		if(save_web_language_conf()){
			// include_once('/opt/simbank/www/inc/language.inc');
			// web_language_init();
			wait_apply("exec", "/tools/web_language_init >/dev/null 2>&1 &");
			$apply_and_refresh = true;
		}
		//添加同步文件操作
	}else if($_POST['send'] == 'Add'){
		add_language($alert, $confirm);
	}else if($_POST['send'] == 'Download'){
		download_language($alert);
	}else if($_POST['send'] == 'Delete'){
		delete_language();
	}else if($_POST['sim_send'] == 'Simbank Save'){
		save_to_SimRdrSvr_conf();
	}
}
if(isset($_GET['overwrite']) && $_GET['overwrite']=='yes'){
	add_language($alert, $confirm, false);
}

$emuRes = read_SimRdrSvr_conf();

?>

<?php

$web_language_conf = '/config/simbank/conf/web_language.conf';
$language_conf = get_conf($web_language_conf);
$language_type = 'english';
?>

<!---// load jQuery and the jQuery iButton Plug-in //---> 
<!--<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script> -->
<script type="text/javascript" src="/js/jquery.ibutton.js"></script> 
<!---// load the iButton CSS stylesheet //---> 
<link type="text/css" href="/css/jquery.ibutton.css" rel="stylesheet" media="all" />
<script type="text/javascript" src="/js/functions.js"></script>
<!--<script type="text/javascript" src="/js/check.js"></script>-->
<script type="text/javascript">
function check_delete_language()
{
	if($('#language_type').attr('value')=='english'){
		alert("<?php echo language('Delete Language alert','Sorry, you can not delete the default language.');?>");
		return false;
	}

	if(!confirm("<?php echo language('Delete Language confirm','Are you sure to delete the selected language package?');?>")) {
		return false;
	}

	return true;
}

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
		filesize = obj.files[0].fileSize;  
	} else if(Sys.ie){
		try {
			obj.select();
			var realpath = document.selection.createRange().text;
			//alert(obj.value);
			//alert(realpath);
			var fileobject = new ActiveXObject ("Scripting.FileSystemObject");//»ñÈ¡ÉÏ´«ÎÄ¼þµÄ¶ÔÏó  
			//var file = fileobject.GetFile (obj.value);//»ñÈ¡ÉÏ´«µÄÎÄ¼þ  
			var file = fileobject.GetFile (realpath);//»ñÈ¡ÉÏ´«µÄÎÄ¼þ  
			var filesize = file.Size;//ÎÄ¼þ´óÐ¡  
		} catch(e){
			alert("<?php echo language("System Update IE alert","Please allow ActiveX Scripting File System Object!");?>");
			return false;
		}
	}

	if(filesize > 1000*1000*1) {
		alert("<?php echo language('Language Package Size alert','Uploaded max file is 1MB!');?>");
		g_bAllowFile = false;
		return false;
	}

	g_bAllowFile = true;
	return true;
}

function change_language(language_type)
{
	<?php
		$language_ro_str = '';
		if(isset($language_conf['list'])&&is_array($language_conf['list'])){
			foreach($language_conf['list'] as $key => $value){
				if(is_file('/opt/simbank/www/lang/'.$key))
					$language_ro_str .= "\"$key\",";
			}
			$language_ro_str = trim($language_ro_str,',');
		}
	?>
	var language_array = new Array(<?php echo $language_ro_str;?>);
	for(var key in language_array){
		if(language_array[key]==language_type){
			$("#delete").attr("disabled",true);
		}else{
			$("#delete").attr("disabled",false);
		}
	}
}

function check_add_language()
{
	if($('#upload_lang_file').attr('value') == '') {
		alert("<?php echo language('Select File alert','Please select your file first!');?>");
		return false;
	}
	return true;
}
</script>

<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
	<!-- ### language settings ### -->
	<div id="tab" style="height:30px;">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Language Settings');?></li>
		<li class="tb2">&nbsp;</li>
	</div>
	
	<div class="div_tab" width="100%" id="language_settings">
		<div class="div_tab_show">
			<div class="div_tab_th"><div class="div_tab_text"><?php echo language('Language');?>:</div></div>
			<div class="div_tab_td">
				<select id="language_type" name="language_type" onchange="change_language(this.value)">
				<?php
					if(isset($language_conf['general']['language']) && isset($language_conf['list'])){
						$language_type = $language_conf['general']['language'];
						if(is_array($language_conf['list'])){
							$language_list = $language_conf['list'];
							foreach($language_list as $key => $value){
								if($key == $language_type)
									$selected="selected";
								else 
									$selected="";
								echo "<option value=\"$key\" $selected>$value</option>";
							}   
						}
					}else{ 
						echo "<option value=\"english\" selected>English</option>";
					}
				?>
				</select>
			</div>
			<div class="div_tab_th"><div class="div_tab_text"><?php echo language('Advanced');?>:</div></div>
			<div class="div_tab_td">
				<input type="checkbox" id="lang_adv_enable" onchange="$('#lang_adv').slideToggle();"/>
			</div>
		</div>
		<div class="div_tab_hide" id="lang_adv">
			<div class="div_tab_th"><div class="div_tab_text"><?php echo language('Language Debug');?>:</div></div>
			<div class="div_tab_td">
				<input type="button" id="" onclick="language_debug('on');" value="<?php echo language('TURN ON');?>"/>&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="button" id="" onclick="language_debug('off');" value="<?php echo language('TURN OFF');?>"/>
			</div>
			<div class="div_tab_th"><div class="div_tab_text"><?php echo language('Download');?>:</div></div>
			<div class="div_tab_td">
				<div class="div_tab_text" style="float:left"><?php echo language('Language Download help','Download selected language package.');?></div>
				<input type="submit" id="download" style="float:right" value="<?php echo language('Download');?>" 
					onclick="document.getElementById('send').value='Download';"/>
			</div>
			<div class="div_tab_th"><div class="div_tab_text"><?php echo language('Delete');?>:</div></div>
			<div class="div_tab_td">
				<div class="div_tab_text" style="float:left"><?php echo language('Delete language help','Delete selected language.');?></div>
				<input type="submit" id="delete" style="float:right" value="<?php echo language('Delete');?>" 
					<?php if(is_file('/opt/simbank/www/lang/'.$language_type))echo 'disabled';?> 
					onclick="document.getElementById('send').value='Delete';return check_delete_language()"/>
			</div>
			<div class="div_tab_th"><div class="div_tab_text"><?php echo language('Add New Language');?>:</div></div>
			<div class="div_tab_td">
				<?php echo language('New language Package');?>: 
				<input type="file" name="upload_lang_file" id="upload_lang_file" onchange="return checkFileChange(this)"/>
				<input type="submit" id="add" style="float:right" value="<?php echo language('Add');?>" 
					onclick="document.getElementById('send').value='Add';return check_add_language()"/>
			</div>
		</div>
	</div>
	
	<div id="newline"></div>

	<input type="submit" value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Language Save';"/>
	<input type="hidden" name="send" id="send" value="" />
	
</form>
	
<div id="newline"></div>
	
<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
	
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Simbank Options');?></li>
		<li class="tb2">&nbsp;</li>
	</div>

	<table width="100%" class="tedit">
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Serial Number');?>:
					<span class="showhelp">
					<?php echo language('Simrdr Serial Number help','Serial Number');?>
					</span>
				</div>
			</th>
			<td>
				<?php echo isset($emuRes['seri'])?$emuRes['seri']:'';?>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Net Mode');?>
					<span class="showhelp">
					<?php echo language('Net Mode help');?>
					</span>
				</div>
			</th>
			<td>
				<select name="net_mode" id="net_mode">
					<option value="local" <?php if($emuRes['net_mode'] == 'local') echo 'selected';?>>Local</option>
					<option value="remote" <?php if($emuRes['net_mode'] == 'remote') echo 'selected';?>>Remote</option>
				</select>
			</td>
		</tr>
		
		<tr id="server_ip_tr">                                                                                                                                                                
			<th>
				<div class="helptooltips">
					<?php echo language('SimProxy Server IP');?>:
					<span class="showhelp">
					<?php echo language('SimProxy Server IP help');?>
					</span>
				</div>
			</th>
			<td>
				<input type="text" name="server_ip" id="server_ip" value="<?php echo isset($emuRes['server_ip'])?$emuRes['server_ip']:'';?>" />
			</td>
		</tr>
	</table>

	<div id="newline"></div>

	<input type="submit" value="<?php echo language('Save');?>" onclick="document.getElementById('sim_send').value='Simbank Save';"/>
	<input type="hidden" name="sim_send" id="sim_send" value="" />
</form>

<br/>

<div id="tab">
	<li class="tb_fold" onclick="lud(this,'led_main')" id="led_main_li">&nbsp;</li>
	<li class="tbg_fold" onclick="lud(this,'led_main')"><?php echo language('Simbank Version');?></li>
	<li class="tb2_fold" onclick="lud(this,'led_main')">&nbsp;</li>
	<li class="tb_end">&nbsp;</li>
</div>

<br/>

<div id="led_main" style="display:none;">
	<table width="100%" class="tedit" >
		<tr id="field_lan_ipaddr" >
			<th>
				<div class="helptooltips">
					<?php echo language("Version");?>:
					<span class="showhelp">
					<?php echo language("Version");?>
					</span>
				</div>
			</th>
			<td style="padding:5px 10px;">
			<?php 
				$content = file_get_contents("/tmp/.bank_status");
				$temp = explode("\n",$content);
				
				$str = '';
				for($i=0;$i<count($temp);$i++){
					if($temp[$i] == "") continue;
					
					$tmp = explode(" ",$temp[$i]);
					$bank = ltrim($tmp[1],"[");
					$bank = rtrim($bank,"]");
					
					if(!strstr($temp[$i], 'led')){
						if(strstr($temp[$i], 'Detected') && !strstr($temp[$i], 'Undetected')){
							$str .= '<span style="line-height:16px;margin-left:10px;">'.$tmp[0].'-'.$bank.' '.$tmp[5].' '.$tmp[6].' '.$tmp[7].' '.$tmp[8].'</span><br/>';
						}else if(strstr($temp[$i], 'Undetected')){
							$str .= '<span style="line-height:16px;margin-left:10px;">'.$tmp[0].'-'.$bank.' '.$tmp[2].'</span><br/>';
						}
					}else{
						$str .= '<span style="line-height:16px;margin-left:10px;">'.$tmp[0].' '.$tmp[2].' '.$tmp[3].' '.$tmp[4].' '.$tmp[5].' '.$tmp[6].' '.$tmp[7].'</span><br/>';
					}
				}
				
				echo $str;
			?>
			</td>
		</tr>
	</table>
</div>
	

<script type="text/javascript"> 
$(document).ready(function (){ 
	$(":checkbox").iButton(); 
	if(<?php if($alert=='')echo "false";else echo "true";?>){
		alert(<?php echo "\"".language("Warning").": ".$alert."\"";?>);
	}else if(<?php if($confirm=='')echo "false";else echo "true";?>){
		if(confirm(<?php echo "\"$confirm\"";?>)){
			window.location.href = "<?php echo $_SERVER['PHP_SELF'];?>"+"?overwrite=yes";
		}
	}
}); 
function language_debug(debug_status)
{
	window.location.href="<?php echo get_self() ?>"+"?send_debug="+debug_status;
}

if(document.getElementById('net_mode').value == 'local'){
	$("#server_ip_tr").hide();
}else{
	$("#server_ip_tr").show();
}

$("#net_mode").change(function(){
	if($(this).val() == 'local'){
		$("#server_ip_tr").hide();
	}else{
		$("#server_ip_tr").show();
	}
});
</script>

<?php require("../inc/boot.inc");?>
