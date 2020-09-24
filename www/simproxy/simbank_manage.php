<?php
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require('../inc/mysql_class.php');
?>

<script type="text/javascript" src="/js/check.js"></script>
<script type="text/javascript" src="/js/functions.js"></script>
<?php
$db = new mysql('simserver');
$tmp_db = new mysql();

function show_simbank(){
	global $tmp_db;
	
	$data = $tmp_db->Get("tb_simbank_info",'*','order by n_sb_online desc');
	$all_simbank=array();
	$k = 0;
	while($row = mysqli_fetch_array($data,MYSQLI_ASSOC)){
		$all_simbank[$k] = $row;
		$k += 1;
	}
	
?>
<form enctype="multipart/form-data" action="<?php echo get_self(); ?>" method="post">
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Simbank Information');?></li>
		<li class="tb2">&nbsp;</li>
	</div>
	
	<table width="100%" class="tshow">
		<tr>
			<th><?php echo language('Serial Number');?></th>
			<th><?php echo language('Alias name');?></th>
			<th><?php echo language('Product Name');?></th>
			<th><?php echo language('Simbank Line Number');?></th>
			<th><?php echo language('Enable sign');?></th>
			<th><?php echo language('On-line state');?></th>
			<th width="80px"><?php echo language('Actions');?></th>
			<input type="hidden" id="sel_simbank_seri" name="sel_simbank_seri" value="" />
		</tr>
<?php
	if($all_simbank){
		foreach($all_simbank as $simbank) {
?>
		<tr>
			<td>
				<?php echo $simbank['ob_sb_seri']; ?>
			</td>
			<td>
				<?php echo $simbank['ob_sb_alias']; ?>
			</td>
			<td>
				<?php echo $simbank['v_sb_product_name']; ?>
			</td>
			<td>
				<?php echo $simbank['n_sb_links']; ?>
			</td>
			<td>
				<?php 
				if($simbank['n_sb_available']==1){
					echo language('On@register','On');
				}else{
					echo language('Off@register','Off');
				}
				?>
			</td>
			<td>
				<?php if ($simbank['n_sb_online']) {
					echo language("On-Line");}else{ echo language("Off-Line");} 
				?>
			</td>
			<td>
				<button type="button" value="Modify" style="width:32px;height:32px;" 
					onclick="getPage('<?php echo $simbank['ob_sb_seri'];?>');">
					<img src="/images/edit.gif">
				</button>
				<button type="submit" value="Delete" style="width:32px;height:32px;" 
					onclick="document.getElementById('send').value='Delete';return delete_click('<?php echo $simbank['ob_sb_seri']; ?>','<?php echo $simbank['n_sb_online'];?>');" >
					<img src="/images/delete.gif">
				</button>
			</td>
		</tr>
<?php
		}
	}
?>
	</table>
	<div id="newline"></div>
	<input type="hidden" name="send" id="send" value="" />
	<input type="submit" value="<?php echo language('Add New Simbank Info');?>" onclick="document.getElementById('send').value='Add New Simbank Info';" />
</form>
	
<script type="text/javascript">
function getPage(value){
	window.location.href = '<?php echo get_self();?>?sel_simbank='+escape(value);
}

function delete_click(value1,online_state){
	ret = confirm("<?php echo language('delete tip','Are you sure to delete you selected ?'); ?>");
	
	if(ret) {
		if(online_state == '1'){
			alert("<?php echo language('Online State Tip','Online status cannot be deleted.');?>");
			return false;
		}
		
		document.getElementById('sel_simbank_seri').value = value1;
		return true;
	}

	return false;
}
</script>

<?php
}

function add_simbank_info($sb_info = ''){
	global $tmp_db;
	
	$data = $tmp_db->Get("tb_simbank_info",'*',"where ob_sb_seri = \"$sb_info\"");
	$all_simbank = mysqli_fetch_array($data,MYSQLI_ASSOC);
	
	if($sb_info){
		if($all_simbank['n_sb_available'] == 1){
			$n_sb_available = 'checked';
		}else{
			$n_sb_available = '';
		}
	}else{
		$n_sb_available = 'checked';
	}
?>
	<script type="text/javascript" src="/js/jquery.ibutton.js"></script>
	<link type="text/css" href="/css/jquery.ibutton.css" rel="stylesheet" media="all" />
	<form enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">
	<input type="hidden" id="old_ob_sb_seri" name="old_ob_sb_seri" value="<?php echo $all_simbank['ob_sb_seri'];?>" />

	<div id="tab_main" style="display:block">
		<table width="100%" class="tedit" >
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Enable');?>:
						<span class="showhelp">
							<?php echo language('Enable help','ON means open; OFF means closed.');?>
						</span>
					</div>
				</th>
				<td>
					<input type="checkbox" id="n_sb_available" name="n_sb_available" <?php echo $n_sb_available; ?> />
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Serial Number');?>:
						<span class="showhelp">
							<?php echo language('Serial Number help@simbank','The serial number of simbank is automatically generated by simbank. Please fill it in after viewing the system information page.');?>
						</span>
					</div>
				</th>
				<td>
					<input type="text" name="ob_sb_seri" id="ob_sb_seri" value="<?php echo $all_simbank['ob_sb_seri'];?>" />
					<span id="cob_sb_seri"></span>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Alias name'); ?>:
						<span class="showhelp">
							<?php echo language('Alias name help@simbank','Note of simbank');?>
						</span>
					</div>
				</th>
				<td>
					<input type="text" name="ob_sb_alias" id="ob_sb_alias" value="<?php echo $all_simbank['ob_sb_alias'];?>" />
					<span id="cob_sb_alias"></span>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Description');?>:
						<span class="showhelp">
							<?php echo language('Description help');?>
						</span>
					</div>
				</th>
				<td>
					<textarea style="width:60%;height:100px;" name="v_sb_desc" id="v_sb_desc" ><?php echo $all_simbank['v_sb_desc'];?></textarea>
					<span id="cv_sb_desc"></span>
				</td>
			</tr>
		</table>
	</div>
	
	<div id="newline"></div>

	<br>

	<input type="hidden" name="send" id="send" value="" />
	<input type="submit" class="float_btn gen_short_btn"   value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';return check();"/>
	&nbsp;
	<input type="button" value="<?php echo language('Cancel');?>" onclick="window.location.href='<?php echo get_self();?>'" />
	</form>
	
	<script type="text/javascript">
		$(document).ready(function (){
			$("#n_sb_available").iButton();
		});
		function check(){
			var serial_number = document.getElementById('ob_sb_seri').value;
			var seri_arr = [];
			<?php
				$data = $tmp_db->Get("tb_simbank_info",'*','order by n_sb_online desc');
				$k = 0;
				while($row = mysqli_fetch_array($data,MYSQLI_ASSOC)){
					if($row['ob_sb_seri'] == $_GET['sel_simbank']) continue;
			?>
					seri_arr.push('<?php echo $row['ob_sb_seri'];?>');
			<?php
				}
			?>
			for(var temp in seri_arr){
				if(seri_arr[temp] == serial_number){
					document.getElementById('cob_sb_seri').innerHTML = con_str("<?php echo language('Serial Number Exists','Serial Number already exists.');?>");
					return false;
				}
			}
			
			
			if(!check_para()) {
				return false;
			} else {
				if(document.getElementById('old_ob_sb_seri').value != ''){
					save_confirm = confirm('<?php echo language('sim_manage_tip1','It will empty the record of original call time and change the registration data! Are you sure to change original setting ?'); ?>');
					if(!save_confirm) return false;
				}
			}
			
			var enable = '<?php echo $all_simbank['n_sb_online']?>';
			if(enable == '1'){
				var page_seri = document.getElementById('ob_sb_seri').value;
				var file_seri = document.getElementById('old_ob_sb_seri').value;
				if(page_seri != file_seri){
					alert('<?php echo language('Modify Seri Tip@simbank','The current simbank is online, and the client must be closed before modifying the serial number.');?>');
					return false;
				}
			}
			return true;
		}
		
		/*
		 *	Allowed character must be any of [\u4E00-\u9FA5A-Za-z0-9]{2,10}$(chinese number and english),2 - 10 characters
		 */
		function check_serial_number(str){
			var rex = /^[\u4E00-\u9FA5A-Za-z0-9]{2,10}$/i;
			if(rex.test(str)) {
				return true;
			}
			return false;
		}

		function check_para(){
			var serial_number = document.getElementById('ob_sb_seri').value;
			if(!check_serial_number(serial_number)){
				document.getElementById('cob_sb_seri').innerHTML = con_str("<?php echo language('js_serial_tip','Allow serial number must be any of [A-Za-z0-9]{2,10}$(chinese number and english),2 - 10 characters.');?>");
				return false;
			}
			return true;
		}
	</script>
<?php
}

function save_simbank_info(){
	global $db;
	global $tmp_db;
	
	if(isset($_POST['n_sb_available'])){
		$n_sb_available = 1;
	} else {
		$n_sb_available = 0;
	}
	
	$ob_sb_seri = $_POST['ob_sb_seri'];
	$old_ob_sb_seri = $_POST['old_ob_sb_seri'];
	
	$ob_sb_alias = $_POST['ob_sb_alias'];
	
	$v_sb_desc = trim($_POST['v_sb_desc']);
	
	if($_POST['old_ob_sb_seri'] == ''){				//add location
		$values = "\"$n_sb_available\",\"\",0,0,\"\",\"$ob_sb_seri\",\"$ob_sb_alias\",\"$v_sb_desc\"";
		$fields = "n_sb_available,v_sb_product_name,n_sb_links,n_sb_online,d_heartbeat_time,ob_sb_seri,ob_sb_alias,v_sb_desc";
		$db->Add("tb_simbank_info",$fields,$values);
		$data = $tmp_db->Add("tb_simbank_info",$fields,$values);
	}else{														//modify location
		$condition = "where ob_sb_seri = \"$old_ob_sb_seri\"";
		$fields = "n_sb_available=\"$n_sb_available\",ob_sb_seri=\"$ob_sb_seri\",ob_sb_alias=\"$ob_sb_alias\",v_sb_desc=\"$v_sb_desc\"";
		$db->Set("tb_simbank_info",$fields,$condition);
		$data = $tmp_db->Set("tb_simbank_info",$fields,$condition);
		
		$db->Set('tb_group_info',"ob_sb_seri=\"$ob_sb_seri\"","where ob_sb_seri = '$old_ob_sb_seri'");
		
		//gsoap
		$params = array(
			'oldseri' => $old_ob_sb_seri,
			'newseri' => $ob_sb_seri
		);
		
		$xml = get_xml('SBKSIMGroupUpdateSB',$params);
		$wsdl = "http://127.0.0.1:8888/?wsdl";
		$client = new SoapClient($wsdl);
		$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
	}
	
	return $data;
}

function del_simbank_info(){
	global $db;
	global $tmp_db;
	
	$sel_simbank_seri = $_POST['sel_simbank_seri'];
	$condition = "ob_sb_seri = \"$sel_simbank_seri\"";
	$data = $db->Del("tb_simbank_info",$condition);
	
	$tmp_data = $tmp_db->Del("tb_simbank_info",$condition);
	
	$db->Del('tb_group_info',"ob_sb_seri = '$sel_simbank_seri'");
	
	$params = array('sbseri' => $sel_simbank_seri);
	$xml = get_xml('SBKSIMGroupDelbySeri',$params);
	$wsdl = "http://127.0.0.1:8888/?wsdl";
	$client = new SoapClient($wsdl);
	$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
	
	if($data && $tmp_data){
		return 1;
	}else{
		return 0;
	}
}
?>

<?php
	if($_POST) {
		if( (isset($_POST['send']) && ($_POST['send'] == 'Add New Simbank Info') ) ) {
			add_simbank_info();
		} elseif (isset($_POST['send']) && $_POST['send'] == 'Save') {
			$status = save_simbank_info();
			if($status == 1){
				echo "<h4>";echo language('Operation Success');echo "</h4>";
			} elseif($status == "repeat") {
				echo "<h4>";echo language('Gateway Info Already Exists');echo "</h4>";
			} else {
				echo "<h4>";echo language('Operation Miss');echo "</h4>";
			}
			show_simbank();
		} elseif (isset($_POST['send']) && $_POST['send'] == 'Delete') {
			if(del_simbank_info() == 1) {
				echo "<h4>";echo language('Delete Success');echo "</h4>";
			} else {
				echo "<h4>";echo language('Delete Failed');echo "</h4>";
			}
			show_simbank();
		}
	} else if($_GET) {
		if( isset($_GET['sel_simbank']) ) {
			add_simbank_info($_GET['sel_simbank'],'');
		}
	} else {
		show_simbank();
	}
?>

<?php 
require("../inc/boot.inc");
?>