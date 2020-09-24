<?php 
require("../inc/head.inc");
require("../inc/menu.inc");
?>

<script type="text/javascript" src="/js/jquery.ibutton.js"></script> 
<link type="text/css" href="/css/jquery.ibutton.css" rel="stylesheet" media="all" />
<form enctype="multipart/form-data" action="<?php echo get_self();?>" method="post">
	<table width="100%" class="tedit">
		<tr>
			<th>
				<div class="helptooltips">
					Led测试类型:
				</div>
			</th>
			<td>
				<span style="margin-right:30px;">熄灭:<input type="radio" name="led_light" value="off" id="off" checked <?php if($_POST['led_light'] == 'off') echo 'checked';?>/></span>
				<span style="margin-right:30px;">红色:<input type="radio" name="led_light" value="red" id="red_light" <?php if($_POST['led_light'] == 'red') echo 'checked';?> /></span>
				<span style="margin-right:30px;">绿色:<input type="radio" name="led_light" value="green" id="green_light" <?php if($_POST['led_light'] == 'green') echo 'checked';?> /></span>
				<span style="margin-right:30px;">红闪:<input type="radio" name="led_light" value="redflash" id="red_flash" <?php if($_POST['led_light'] == 'redflash') echo 'checked';?> /></span>
				<span style="margin-right:30px;">绿闪:<input type="radio" name="led_light" value="greenflash" id="green_flash" <?php if($_POST['led_light'] == 'greenflash') echo 'checked';?> /></span>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					Led测试:
				</div>
			</th>
			<td>
				<input type="submit" value="测试" onclick="document.getElementById('send').value='Led Test';" />
			</td>
		</tr>
	</table>
	
	<div id="newline"></div>
	
	<table width="100%" class="tedit">
		<tr>
			<th>
				<div class="helptooltips">
					Sim卡测试开关:
				</div>
			</th>
			<td>
				<input type="checkbox" name="sim_test" id="sim_test" <?php if(isset($_POST['sim_test'])) echo 'checked';?> />
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					Sim卡测试:
				</div>
			</th>
			<td>
				<input type="submit" value="测试" onclick="document.getElementById('send').value='Sim Test';return check();" />
			</td>
		</tr>
	</table>
	
	<div id="newline"></div>
	
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg">固件升级</li>
		<li class="tb2">&nbsp;</li>
	</div>
	
	<table width="100%" class="tedit">
		<tr>
			<th>
				<div class="helptooltips">
					bank数:
					<span class="showhelp">
					</span>
				</div>
			</th>
			<td>
				<select id="firmware_para" name="firmware_para">
					<option value="8">8</option>
					<option value="16">16</option>
					<option value="32">32</option>
					<option value="40">40</option>
				</select>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					文件:
					<span class="showhelp">
					</span>
				</div>
			</th>
			<td>
				<input type="file" name="firmware" id="firmware" />
				<input type="submit" value="升级" style="float:right;margin-right:10px;" onclick="document.getElementById('send').value='Update';" />
			</td>
		</tr>
	</table>
	
	<input type="hidden" name="send" id="send" value="" />
</form>

<script>
$(function(){
	$("#sim_test").iButton();
});

function check(){
	alert("常亮绿色测试成功，红色测试失败。");
}
</script>

<?php 
function save_Led(){
	$led_light = $_POST['led_light'];
	
	exec("/tools/burntest led $led_light");
	
	$return = '';
	if($led_light == 'off'){
		$return = 'Led灯熄灭';
	}else if($led_light == 'red'){
		$return = 'Led红灯亮';
	}else if($led_light == 'green'){
		$return = 'Led绿灯亮';
	}else if($led_light == 'redflash'){
		$return = 'Led红灯闪烁';
	}else if($led_light == 'greenflash'){
		$return = 'Led绿灯闪烁';
	}
	
	$str = '<b>Led测试</b>';
	$str .= '<table style="width:100%;font-size:12px;border:1px solid rgb(59,112,162);margin-bottom:20px;">';
	$str .= '<tbody><tr style="background-color:#D0E0EE;height:26px;">';
	$str .= '<th style="width:30%">结果</th></tr>';
	$str .= '<tr style="background-color: rgb(232, 239, 247);"><td style="width:30%">'.$return.'</td></tr>';
	$str .= '</tbody></table>';
	
	echo $str;
}

function save_Sim(){
	if(isset($_POST['sim_test'])){
		exec("/tools/burntest led off");
		exec("/tools/burntest sim",$return);
		
		$return_str = '';
		for($i=0;$i<count($return);$i++){
			$return_str .= $return[$i].'<br/>';
		}
	}else{
		$return_str = '请打开Sim卡测试开关。';
	}
	
	$str = '<b>Sim卡测试</b>';
	$str .= '<table style="width:100%;font-size:12px;border:1px solid rgb(59,112,162);margin-bottom:20px;">';
	$str .= '<tbody><tr style="background-color:#D0E0EE;height:26px;">';
	$str .= '<th style="width:30%">结果</th></tr>';
	$str .= '<tr style="background-color: rgb(232, 239, 247);"><td style="width:30%">'.$return_str.'</td></tr>';
	$str .= '</tbody></table>';
	
	echo $str;
}

function update(){
	if(! $_FILES){
		echo '文件未上传';
		return;
	}
	
	echo "<br>";
	$Report = "报告";
	$Result = "结果";
	trace_output_start("$Report", "固件升级");
	trace_output_newline();
	if(isset($_FILES['firmware']['error']) && $_FILES['firmware']['error'] == 0){
		if(!(isset($_FILES['firmware']['size'])) || $_FILES['firmware']['size'] > 80*1000*1000){
			echo 'Your uploaded file was larger than 80M';
			trace_output_end();
			return;
		}
		
		$store_file = "/tmp/firmware/";
		if(!is_dir($store_file)){
			mkdir($store_file);
		}
		
		$file_path = $store_file.$_FILES['firmware']['name'];
		
		if(!move_uploaded_file($_FILES['firmware']['tmp_name'], $file_path)){
			echo 'Moving your updated file was failed!';
			trace_output_end();
			return;
		}
		echo '升级中...<br/>';
		ob_flush();
		flush();
		
		$firmware_para = $_POST['firmware_para'];
		
		$cmd = "/tools/UpdateBank $file_path $firmware_para 1 0";
		exec($cmd, $output, $return);
		if($return == 0){
			echo "结果：升级成功<br/>";
			trace_output_newhead("详细");
			for($i=0;$i<count($output);$i++){
				echo $output[$i].'<br/>';
			}
			trace_output_end();
		}else{
			trace_output_newhead("$Result");
			echo "升级失败<br/>"; 
			trace_output_end();
		}
		exec("rm -rf $store_file");
	}
}

if(isset($_POST)){
	if($_POST['send'] == 'Led Test'){
		save_Led();
	}else if($_POST['send'] == 'Sim Test'){
		save_Sim();
	}else if($_POST['send'] == 'Update'){
		update();
	}
}
?>

<?php 
require("/opt/simbank/www/inc/boot.inc");
?>
