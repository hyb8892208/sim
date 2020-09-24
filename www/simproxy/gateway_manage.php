<?php
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require('../inc/mysql_class.php');
?>

<script type="text/javascript" src="/js/check.js"></script>
<script type="text/javascript" src="/js/functions.js"></script>
<script type="text/javascript">
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
</script>

<?php
$db = new mysql('simserver');
$tmp_db=new mysql();

$sql = "create table if not exists tb_mapping_info(
			ob_sb_seri varchar(32),
			ob_sb_bank int,
			ob_sb_slot int,
			ob_gw_seri varchar(32),
			ob_gw_chnl int,
			primary key (ob_sb_seri,ob_sb_bank,ob_sb_slot)
)";
$db->query($sql);

function show_gateway(){
	global $db;
	global $tmp_db;
	
	$data = $tmp_db->Get("tb_gateway_info",'*','');
	$all_gateway=array();
	$k = 0;
	while($row = mysqli_fetch_array($data,MYSQLI_ASSOC)){
		$all_gateway[$k] = $row;
		$k += 1;
	}
	
?>
	<form enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg">
			<?php echo language('Gateway Information');?>
		</li>
		<li class="tb2">&nbsp;</li>
	</div>
	<table width="100%" class="tshow">
		<tr>
			<th><?php echo language('Serial Number');?></th>
			<th><?php echo language('Alias name');?></th>
			<th><?php echo language('Product Name');?></th>
			<th><?php echo language('Gateway Line Number');?></th>
			<th><?php echo language('Distribution Mode');?></th>
			<th><?php echo language('Enable sign');?></th>
			<th><?php echo language('On-line state');?></th>
			<th><?php echo language('The heartbeat of time');?></th>
			<th width="80px"><?php echo language('Actions');?></th>
			<input type="hidden" id="sel_gateway_seri" name="sel_gateway_seri" value="" />
		</tr>
<?php
	if($all_gateway){
		foreach($all_gateway as $gateway) {
			$mapping_data = $db->Get("tb_mapping_info","*",'where ob_gw_seri="'.$gateway['ob_gw_seri'].'"');
			$mapping_info = mysqli_fetch_array($mapping_data,MYSQLI_ASSOC);
?>
		<tr>
			<td>
				<?php echo $gateway['ob_gw_seri']; ?>
			</td>
			<td>
				<?php echo $gateway['ob_alias']?>
			</td>
			<td>
				<?php echo $gateway['v_gw_product_name']; ?>
			</td>
			<td>
				<?php echo $gateway['n_gw_links']; ?>
			</td>
			<td>
				<?php 
					if($gateway['ob_grp_name'] != ""){
						echo "SIM-Group:".$gateway['ob_grp_name'];
					}else if($mapping_info['ob_gw_seri'] != ""){
						echo "SIM Bind";
					}
				?>
			</td>
			<td>
				<?php 
				if($gateway['n_gw_available'] == 1){
					echo language('On@register','On');
				}else{
					echo language('Off@register','Off');
				}
				?>
			</td>
			
			<td>
				<?php 
				if ($gateway['n_gw_online']){
					echo language("On-Line");
				}else{
					echo language("Off-Line");
				}
				?>
			</td>
			<td>
				<?php echo $gateway['d_heartbeat_time']; ?>
			</td>
			<td>
				<button type="button" value="Modify" style="width:32px;height:32px;" 
					onclick="getPage('<?php echo $gateway['ob_gw_seri']; ?>');">
					<img src="/images/edit.gif">
				</button>
				<button type="submit" value="Delete" style="width:32px;height:32px;" 
					onclick="document.getElementById('send').value='Delete';return delete_click('<?php echo $gateway['ob_gw_seri']; ?>','<?php echo $gateway['n_gw_online'];?>');" >
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
	<input type="submit" value="<?php echo language('Add New Gateway Info');?>" onclick="document.getElementById('send').value='Add New Gateway Info';" />
	</form>
<script type="text/javascript">
function getPage(value)
{
	window.location.href = '<?php echo get_self();?>?sel_gateway='+escape(value);
}

function delete_click(value1,online_state)
{
	ret = confirm("<?php echo language('delete tip','Are you sure to delete you selected ?'); ?>");
	
	if(ret) {
		if(online_state == '1'){
			alert("<?php echo language('Online State Tip','Online status cannot be deleted.');?>");
			return false;
		}
		
		document.getElementById('sel_gateway_seri').value = value1;
		return true;
	}

	return false;
}
</script>

<?php
}

function add_gateway_info($gw_info = ''){
	global $db;
	global $tmp_db;
	
	$all_gateway=array();
	if($gw_info) {
		$data = $tmp_db->Get("tb_gateway_info",'*',"where ob_gw_seri = \"$gw_info\"");
		$k = 0;
		$all_gateway = mysqli_fetch_array($data,MYSQLI_ASSOC);
		if($all_gateway['n_gw_available'] == 1){
			$n_gw_available = 'checked';
		}else{
			$n_gw_available = '';
		}
		echo "<h4>";echo language('Modify gateway Info');echo "</h4>";
	} else {
		$all_gateway['ob_gw_seri'] = '';
		$all_gateway['ob_alias'] = '';
		$all_gateway['v_gw_product_name'] = '';
		$all_gateway['n_gw_links'] = '';
		$all_gateway['ob_grp_name'] = '';
		$all_gateway['n_gw_online'] = '';
		$all_gateway['d_heartbeat_time'] = '';
		$all_gateway['n_gw_linked'] = '';
		$all_gateway['n_gw_available'] = '';
		$all_gateway['v_gw_desc'] = '';
		$n_gw_available = 'checked';
		echo "<h4>";echo language('Create gateway Info');echo "</h4>";
	}
	
?>
	<script type="text/javascript" src="/js/jquery.ibutton.js"></script>
	<link type="text/css" href="/css/jquery.ibutton.css" rel="stylesheet" media="all" />
	<form enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">
	<input type="hidden" id="old_ob_gw_seri" name="old_ob_gw_seri" value="<?php echo $all_gateway['ob_gw_seri'];?>" />

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
				<td >
					<input type="checkbox" id="n_gw_available" name="n_gw_available" <?php echo $n_gw_available ?>/>
				</td>
			</tr>
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Serial Number');?>:
						<span class="showhelp">
							<?php echo language('Serial Number help@gateway','Gateway\'s simbank page is automatically generated and filled in to this point');?>
						</span>
					</div>
				</th>
				<td >
					<input type="text" name="ob_gw_seri" id="ob_gw_seri" value="<?php echo htmlentities($all_gateway['ob_gw_seri']);?>" />
					<span id="cob_gw_seri"></span>
				</td>
			</tr>
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Alias name');?>:
						<span class="showhelp">
							<?php echo language('Alias name help@gateway','The name of the gateway');?>
						</span>
					</div>
				</th>
				<td>
					<input type="text" name="ob_alias" id="ob_alias" value="<?php echo $all_gateway['ob_alias'];?>" />
					<span id="cob_alias"></span>
				</td>
			</tr>
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Gateway Line Number');?>:
						<span class="showhelp">
							<?php echo language('Gateway Line Number help','Generally set as the number of ports of the gateway; when the number of ports is greater than or equal to the number of ports of the gateway, allocate the number of SIM cards of the number of ports of the gateway to the gateway; when the number of ports of the gateway is smaller, allocate the number of SIM cards of the specified number to the gateway.');?>
						</span>
					</div>
				</th>
				<td >
					<input style="width:70%;" type="text" name="n_gw_links" id="n_gw_links" value="<?php echo htmlentities($all_gateway['n_gw_links']);?>" />
					<span id="cn_gw_links"></span>
				</td>
			</tr>
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Sim Group');?>:
						<span class="showhelp">
							<?php echo language('Sim Group help@gateway','Choose from which or several SIM groups to allocate SIM cards to gateways');?>
						</span>
					</div>
				</th>
				<td>
					<table class="port_table">
						<?php 
						$temp = explode(',',$all_gateway['ob_grp_name']);
						
						$data = $db->Get("tb_group_info",'distinct ob_grp_name,ob_gw_seri','');
						
						$mapping_data = $db->Get("tb_mapping_info","*",'where ob_gw_seri="'.$all_gateway['ob_gw_seri'].'"');
						$mapping_info = mysqli_fetch_array($mapping_data,MYSQLI_ASSOC);
						
						while($group_info = mysqli_fetch_array($data,MYSQLI_ASSOC)){
							$ob_grp_name = $group_info['ob_grp_name'];
							
							$checked = '';
							if(in_array($ob_grp_name,$temp)){
								$checked = 'checked';
							}
							$color_grey = '';
							$disabled = '';
							if($mapping_info['ob_gw_seri'] != "" || ($group_info['ob_gw_seri'] != '' && !in_array($ob_grp_name,$temp))){
								$disabled = 'disabled';
								$color_grey = 'style="color:grey;"';
								$checked = 'checked';
							}
						?>
						<td class="sms_port">
							<input type="checkbox" name="spans[<?php echo $ob_grp_name;?>]" <?php echo $checked;?> <?php echo $disabled;?>/>
							<span <?php echo $color_grey;?>><?php echo $ob_grp_name;?></span>
						</td>
						<?php }?>
					</table>
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
				<td >
					<textarea style="width:60%;height:100px;" name="v_gw_desc" id="v_gw_desc" ><?php echo htmlentities($all_gateway['v_gw_desc']);?></textarea>
					<span id="cv_gw_desc"></span>
				</td>
			</tr>
		</table>
	</div>
	
	<div id="newline"></div>

	<br>

	<input type="hidden" name="send" id="send" value="" />
	<input type="submit" class="float_btn gen_short_btn"   value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save'; return check_para()"/>
	&nbsp;
	<input type=button  value="<?php echo language('Cancel');?>" onclick="window.location.href='<?php echo get_self();?>'" />
	</form>
	
	<script type="text/javascript">
		$(document).ready(function (){
			$("#n_gw_available").iButton();
		});
		function check_para(){
			var serial_number = document.getElementById('ob_gw_seri').value;
			var seri_arr = [];
			<?php 
				$data = $tmp_db->Get("tb_gateway_info",'*','');
				$k = 0;
				while($row = mysqli_fetch_array($data,MYSQLI_ASSOC)){
					if($row['ob_gw_seri'] == $_GET['sel_gateway']) continue;
			?>
					seri_arr.push('<?php echo $row['ob_gw_seri'];?>');
			<?php
				}
			?>
			for(var temp in seri_arr){
				if(seri_arr[temp] == serial_number){
					document.getElementById('cob_gw_seri').innerHTML = con_str("<?php echo language('Serial Number Exists','Serial Number already exists.');?>");
					return false;
				}
			}
			
			var gateway_links 	  = document.getElementById('n_gw_links').value;
			if(!check_serial_number(serial_number)){
				document.getElementById('cob_gw_seri').innerHTML = con_str("<?php echo language('js_serial_tip','Allow serial number must be any of [\u4E00-\u9FA5A-Za-z0-9]{2,10}$(chinese number and english),2 - 10 characters.');?>");
				return false;
			}
			if(!check_diyname(gateway_links)) {
				document.getElementById('cn_gw_links').innerHTML = con_str("<?php echo language('js_gateway_tip','Allow gateway line number must be any of [-_+.<>&0-9a-zA-Z],1 - 32 characters.');?>");
				return false;
			}
			
			var enable = '<?php echo $all_gateway['n_gw_online'];?>';
			
			if(enable == '1'){
				var page_seri = document.getElementById('ob_gw_seri').value;
				var file_seri = '<?php echo htmlentities($all_gateway['ob_gw_seri']);?>';
				if(page_seri != file_seri){
					alert('<?php echo language('Modify Seri Tip','The current gateway is online, and the client must be closed before modifying the serial number.');?>');
					return false;
				}
			}
			return true;
		}
	</script>
<?php
}

function save_gateway_info()				//save group
{
	global $db;
	global $tmp_db;
	
	if(isset($_POST['n_gw_available'])){
		$n_gw_available = 1;
	} else {
		$n_gw_available = 0;
	}
	
	$ob_gw_seri = trim($_POST['ob_gw_seri']);
	$old_ob_gw_seri = trim($_POST['old_ob_gw_seri']);
	
	$ob_alias = $_POST['ob_alias'];
	
	$v_gw_product_name = '';
	
	$n_gw_links = trim($_POST['n_gw_links']);
	
	$spans = $_POST['spans'];
	
	$v_gw_desc = trim($_POST['v_gw_desc']);
	
	$n_gw_online = 0;
	
	$d_heartbeat_time = '';
	
	$n_gw_linked = 0;
	
	$group_arr = [];
	$gateway_data = $tmp_db->Get('tb_gateway_info',"ob_grp_name","where ob_gw_seri = '$ob_gw_seri'");
	$temp = mysqli_fetch_array($gateway_data,MYSQLI_ASSOC);
	$temp_arr = explode(',',$temp['ob_grp_name']);
	for($i=0;$i<count($temp_arr);$i++){
		$t = $temp_arr[$i];
		$db->Set('tb_group_info',"ob_gw_seri=''","where ob_grp_name = '$t'");
		
		if($temp['ob_grp_name'] != ''){
			array_push($group_arr,$t);
		}
	}
	
	$ob_grp_name_str = '';
	$spans_arr = [];
	foreach($spans as $key => $val){
		$ob_grp_name_str .= $key.',';
		//tb_group_info
		$db->Set('tb_group_info',"ob_gw_seri='$ob_gw_seri'","where ob_grp_name = '$key'");
		
		if(!in_array($key,$group_arr)){
			array_push($group_arr,$key);
		}
		
		array_push($spans_arr,$key);
	}
	$ob_grp_name = substr($ob_grp_name_str, 0, -1);
	
	if($old_ob_gw_seri != '' && $old_ob_gw_seri != $ob_gw_seri){
		$data = $db->Get("tb_gateway_info",'*','');
		$check_gw = '';
		while($row = mysqli_fetch_array($data,MYSQLI_ASSOC))
		{
			if($row['ob_gw_seri'] == $ob_gw_seri){			//if gateway repeat,return "repeat".
				$check_gw = "repeat";
				return $check_gw;
			}
		}
	}
	
	if($_POST['old_ob_gw_seri'] == ''){				//add location
		$values = "\"$ob_gw_seri\",\"$ob_alias\",\"$v_gw_product_name\",\"$n_gw_links\",\"$ob_grp_name\",\"$v_gw_desc\",\"$n_gw_online\",\"$d_heartbeat_time\",\"$n_gw_linked\",\"$n_gw_available\"";
		$fields = "ob_gw_seri,ob_alias,v_gw_product_name,n_gw_links,ob_grp_name,v_gw_desc,n_gw_online,d_heartbeat_time,n_gw_linked,n_gw_available";
		$tmp_db->Add("tb_gateway_info",$fields,$values);
		$data = $db->Add("tb_gateway_info",$fields,$values);
	} else {														//modify location
		$condition = "where ob_gw_seri = \"$old_ob_gw_seri\"";
		$fields = "ob_gw_seri=\"$ob_gw_seri\",ob_alias=\"$ob_alias\",n_gw_links=\"$n_gw_links\",ob_grp_name=\"$ob_grp_name\",v_gw_desc=\"$v_gw_desc\",n_gw_available=\"$n_gw_available\"";
		$tmp_db->Set("tb_gateway_info",$fields,$condition);
		$data = $db->Set("tb_gateway_info",$fields,$condition);
	}
	
	//gsoap
	for($i=0;$i<count($group_arr);$i++){
		if(in_array($group_arr[$i],$temp_arr) && (!in_array($group_arr[$i],$spans_arr))){
			$t = '';
		}else{
			$t = $ob_gw_seri;
		}
		
		$params = array(
			'groupname' => $group_arr[$i],
			'gwseri' => $t
		);
		$xml = get_xml('SBKSIMGroupUpdateGW',$params);
		$wsdl = "http://127.0.0.1:8888/?wsdl";
		$client = new SoapClient($wsdl);
		$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
	}
	
	return $data;
}

function del_gateway_info()					//delete gateway info
{
	global $db;
	global $tmp_db;
	
	$sel_gateway_seri = $_POST['sel_gateway_seri'];
	
	$gateway_data = $tmp_db->Get('tb_gateway_info',"ob_grp_name","where ob_gw_seri = '$sel_gateway_seri'");
	$temp = mysqli_fetch_array($gateway_data,MYSQLI_ASSOC);
	$temp_arr = explode(',',$temp['ob_grp_name']);
	for($i=0;$i<count($temp_arr);$i++){
		$t = $temp_arr[$i];
		$db->Set('tb_group_info',"ob_gw_seri=''","where ob_grp_name = '$t'");
		
		$params = array(
			'groupname' => $t,
			'gwseri' => ""
		);
		$xml = get_xml('SBKSIMGroupUpdateGW',$params);
		$wsdl = "http://127.0.0.1:8888/?wsdl";
		$client = new SoapClient($wsdl);
		$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
	}
	
	$condition = "ob_gw_seri = \"$sel_gateway_seri\"";
	$data = $db->Del("tb_gateway_info",$condition);
	$tmp_data = $tmp_db->Del("tb_gateway_info",$condition);
	
	if($data && $tmp_data){
		return 1;
	}else{
		return 0;
	}
}

if($_POST) {
	if( (isset($_POST['send']) && ($_POST['send'] == 'Add New Gateway Info') ) ) {
		add_gateway_info();
	} elseif (isset($_POST['send']) && $_POST['send'] == 'Save') {
		$status = save_gateway_info();
		$status = 1;
		if($status == 1){
			echo "<h4>";echo language('Operation Success');echo "</h4>";
		} elseif($status == "repeat") {
			echo "<h4>";echo language('Gateway Info Already Exists');echo "</h4>";
		} else {
			echo "<h4>";echo language('Operation Miss');echo "</h4>";
		}
		show_gateway();
	} elseif (isset($_POST['send']) && $_POST['send'] == 'Delete') {
		if(del_gateway_info() == 1) {
			echo "<h4>";echo language('Delete Success');echo "</h4>";
		} else {
			echo "<h4>";echo language('Delete Failed');echo "</h4>";
		}
		show_gateway();
	}
} else if($_GET) {
	if( isset($_GET['sel_gateway']) ) {
		add_gateway_info($_GET['sel_gateway'],'');
	}
} else {
	show_gateway();
}
	
?>


<?php require("../inc/boot.inc"); ?>