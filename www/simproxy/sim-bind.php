<?php
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require("../inc/mysql_class.php");
include_once("../inc/wrcfg.inc");
include_once("../inc/aql.php");
include_once("../inc/define.inc");
include_once("../inc/language.inc");

$tmp_db = new mysql();
$db = new mysql('simserver');
$sql = "create table if not exists tb_mapping_info(
			ob_sb_seri varchar(32),
			ob_sb_bank int,
			ob_sb_slot int,
			ob_gw_seri varchar(32),
			ob_gw_chnl int,
			primary key (ob_sb_seri,ob_sb_bank,ob_sb_slot)
)";
$db->query($sql);

function get_simabnk_select_str(){
	global $tmp_db;
	
	$simbank_data = $tmp_db->Get("tb_simbank_info","*","");
	$simbank_info = mysqli_fetch_all($simbank_data, MYSQLI_ASSOC);
	
	$simbank_select_str = '<option value="">None</option>';
	$simbank_select_str = '';
	for($i=1;$i<=count($simbank_info);$i++){
		if($simbank_info[$i-1]['n_sb_links'] == 0) continue;
		
		$simbank_select_str .= '<option value="'.$simbank_info[$i-1]['ob_sb_seri'].'" >'.$simbank_info[$i-1]['ob_sb_alias'].'-'.$simbank_info[$i-1]['ob_sb_seri'].'</option>';
	}
	
	return $simbank_select_str;
}

function show_sim_bind(){
	global $db;
	global $tmp_db;
	
	$data = $db->Get("tb_mapping_info","*","order by ob_sb_seri,ob_sb_bank,ob_sb_slot");
	
	//gateway seri 
	$gateway_data = $tmp_db->Get("tb_gateway_info","*","");
	
	$gateway_select_str = '<option value="" >None</option>';
	while($gateway_info = mysqli_fetch_array($gateway_data,MYSQLI_ASSOC)){
		if($gateway_info['ob_grp_name'] != '') continue;
		
		$gateway_select_str .= '<option value="'.$gateway_info['ob_gw_seri'].'" >'.$gateway_info['ob_gw_seri'].'</option>';
	}
?>
<script type="text/javascript" src="/js/functions.js"></script>

<form id="mainform" enctype="multipart/form-data" action="<?php get_self();?>" method="post">
	<input type="hidden" name="sel_sim" id="sel_sim" value="" />
	<div id="main">
		<table width="100%" class="tsort">
			<tr>
				<th style="width:03%" class="nosort">
					<input type="checkbox" name="selall_sip" onclick="selectAll(this.checked,'sim[]')" />
				</th>
				<th>SIM ID</th>
				<th><?php echo 'Simbank '.language('Serial Number');?></th>
				<th><?php echo 'Gateway '.language('Serial Number');?></th>
				<th><?php echo language('Port');?></th>
				<th width="40px"><?php echo language('Action');?></th>
			</tr>
			
			<?php
			while($info = mysqli_fetch_array($data,MYSQLI_ASSOC)){
				$sim_id = $info['ob_sb_bank']*8 + $info['ob_sb_slot'] + 1;
			?>
			<tr>
				<td> 
					<input type="checkbox" class="sim_checkbox" name="sim[]" value="<?php echo $sim_id; ?>" />
				</td>
				<td><?php echo $sim_id;?></td>
				<td><?php echo $info['ob_sb_seri'];?></td>
				<td><?php echo $info['ob_gw_seri'];?></td>
				<td><?php echo $info['ob_gw_chnl'];?></td>
				<td>
					<button type="submit" value="Delete" style="width:32px;height:32px" 
						onclick="document.getElementById('send').value='Delete';return delete_click('<?php echo $sim_id.',';?>');">
						<img src="/images/delete.gif" />
					</button>
				</td>
			</tr>
			<?php
			}
			?>
		</table>
		
		<br>
		
		<input type="hidden" name="send" id="send" value="" />
		<input type="button" value="<?php echo language('New')?>" onclick="add_sim()" />
		<input type="submit" value="<?php echo language('Delete');?>" onclick="document.getElementById('send').value='Delete';return delete_click()" />
		
		<br>
	</div>
</form>

<script>

function check(){
	var sim_flag = 0;
	$(".sim_id").each(function(){
		if($(this).val() == ""){
			sim_flag = 1;
		}
	});
	if(sim_flag == 1){
		alert("<?php echo 'SIM ID '.language('can not be none')?>");
		return false;
	}
	
	var seri_flag = 0;
	$(".gateway_seri").each(function(){
		if($(this).val() == ""){
			seri_flag = 1;
		}
	});
	if(seri_flag == 1){
		alert("<?php echo 'Gateway '.language('Serial Number').' '.language('can not be none')?>");
		return false;
	}
	
	var channel_flag = 0;
	$(".gateway_channel").each(function(){
		if($(this).val() == ""){
			channel_flag = 1;
		}
	});
	if(channel_flag == 1){
		alert("<?php echo language('Port').' '.language('can not be none')?>");
		return false;
	}
	
	return true;
}

var flag_bind = 0;
function add_sim(){
	if(flag_bind == 0){
		var str = '<br/><table width="100%" class="tshow">';
		str += '<tr>';
			str += '<th width="25%">SIM ID</th>';
			str += '<th width="25%"><?php echo 'Simbank '.language('Serial Number');?></th>';
			str += '<th width="25%"><?php echo 'Gateway '.language('Serial Number');?></th>';
			str += '<th width="25%"><?php echo language('Port');?></th>';
		str += '</tr>';
		str += '</table>';
		$("#main").append(str);
	}
	
	var str = '<tr>';
		str += '<td>';
			str += '<input class="sim_id" type="text" name="sim_id[]" />';
		str += '</td>';
		str += '<td>';
			str += '<select name="simbank_seri[]" class="simbank_seri">';
			str += '<?php echo get_simabnk_select_str();?>';
			str += '</select>';
		str += '</td>';
		str += '<td>';
			str += '<select name="gateway_seri[]" class="gateway_seri">';
			str += '<?php echo $gateway_select_str;?>';
			str += '</select>';
		str += '</td>';
		str += '<td>';
			str += '<select name="gateway_channel[]" class="gateway_channel">';
				str += '<option value="">None</option>'
			str += '</select>';
		str += '</td>';
	str += '</tr>';
	
	$(".tshow").append(str);
	
	if(flag_bind == 0){
		var submit_str = '<br/>';
		submit_str += '<input type="submit" value="<?php echo language("Save");?>" onclick="document.getElementById(\'send\').value=\'Save\';return check();" />';
		
		$("#main").after(submit_str);
		flag_bind = 1;
	}
	
}

function delete_click(value){
	var ret = confirm("<?php echo language('Delete Selected confirm','Are you sure to delete you selected ?');?>");

	if(value == undefined){
		var val_str = '';
		$(".sim_checkbox").each(function(){
			if($(this).prop("checked")){
				val_str += $(this).val() + ',';
			}
		});
		
		document.getElementById('sel_sim').value = val_str;
	}else{
		document.getElementById('sel_sim').value = value;
	}

	if(ret) {
		return true;
	}

	return false;
}

$(document).on('change','.gateway_seri',function(){
	var gateway_seri = $(this).val();
	var that = $(this);
	
	$.ajax({
		url: 'ajax_server_new.php?action=sim_batch_get_channel_num&gateway_seri='+gateway_seri,
		type: 'GET',
		success: function(data){
			var arr = JSON.parse(data);
			
			var str = '<option value="">None</option>';
			for(var j=0;j<arr.length;j++){
				str += '<option value="'+arr[j]+'">'+arr[j]+'</option>';
			}
			
			$(that).parent().next().children().html(str);
		},
		error: function(data){
			console.log('error');
		}
	});
});
</script>
<?php
}

function del_sim(){
	global $db;
	
	$temp = explode(',',$_POST['sel_sim']);
	
	foreach($temp as $sim){
		if($sim != ''){
			$sim = $sim - 1;
			$ob_sb_bank = floor($sim/8);
			$ob_sb_slot = $sim%8;
			
			$condition = "ob_sb_bank='$ob_sb_bank' and ob_sb_slot='$ob_sb_slot'";
			$db->Del('tb_mapping_info',$condition);
		}
	}
}

function save_sim_bind(){
	global $db;
	global $tmp_db;
	
	//SIM组中其他序列号已使用的sim卡
	$other_arr = [];
	$other_res = $db->Get('tb_group_info','*','');
	while($other_info = mysqli_fetch_array($other_res,MYSQLI_ASSOC)){
		$temp_arr = explode(',',$other_info['v_grp_sim']);
		$other_arr = array_merge($other_arr,$temp_arr);
	}
	
	//tb_mapping_info中已使用过sim卡
	$mapping_arr = [];
	$mapping_res = $db->Get('tb_mapping_info','*','');
	while($mapping_info = mysqli_fetch_array($mapping_res,MYSQLI_ASSOC)){
		$ob_sb_bank = $mapping_info['ob_sb_bank'];
		$ob_sb_slot = $mapping_info['ob_sb_slot'];
		
		$sim_id = $ob_sb_bank*8 + $ob_sb_slot + 1;
		
		array_push($mapping_arr,$sim_id);
	}
	
	for($i=0;$i<count($_POST['sim_id']);$i++){
		if($_POST['sim_id'][$i] == '' || $_POST['simbank_seri'][$i] == '' || $_POST['gateway_seri'][$i] == '' || $_POST['gateway_channel'][$i] == ''){
			continue;
		}
		
		if(in_array($_POST['sim_id'][$i], $other_arr)){
			echo "SIM ID:".$_POST['sim_id'][$i]." ".language('has been used')."<br/>";
			continue;
		}
		if(in_array($_POST['sim_id'][$i], $mapping_arr)){
			echo "SIM ID:".$_POST['sim_id'][$i]." ".language('has been used')."<br/>";
			continue;
		}
		
		$sim_id = $_POST['sim_id'][$i] - 1;
		$simbank_seri = $_POST['simbank_seri'][$i];
		$gateway_seri = $_POST['gateway_seri'][$i];
		$gateway_channel = $_POST['gateway_channel'][$i];
		
		$ob_sb_bank = floor($sim_id/8);
		$ob_sb_slot = $sim_id%8;
		
		$maping_data = $db->Get("tb_mapping_info","*","where ob_gw_chnl='".$gateway_channel."' and ob_gw_seri='".$gateway_seri."'");
		$maping_info = mysqli_fetch_array($maping_data,MYSQLI_ASSOC);
		
		if(isset($maping_info)){
			$fields = "ob_sb_seri='$simbank_seri',ob_sb_bank='$ob_sb_bank',ob_sb_slot='$ob_sb_slot'";
			$condition = "where ob_gw_seri='$gateway_seri' and ob_gw_chnl='$gateway_channel'";
			
			$db->Set("tb_mapping_info",$fields,$condition);
		}else{
			$fields = "ob_sb_seri,ob_sb_bank,ob_sb_slot,ob_gw_seri,ob_gw_chnl";
			$values = "'$simbank_seri','$ob_sb_bank','$ob_sb_slot','$gateway_seri','$gateway_channel'";
			
			$db->Add("tb_mapping_info",$fields,$values);
		}
	}
}

if($_POST){
	if(isset($_POST['send']) && $_POST['send'] == 'Save'){
		save_sim_bind();
		show_sim_bind();
	}else if($_POST['send'] == 'Delete'){
		del_sim();
		show_sim_bind();
	}
}else{
	show_sim_bind();
}

require("/opt/simbank/www/inc/boot.inc");
?>

<div id="float_btn1" class="sec_float_btn1"></div>
<div  class="float_close" onclick="close_btn()"></div>