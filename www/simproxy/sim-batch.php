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
	
	$simbank_select_str = '';
	for($i=1;$i<=count($simbank_info);$i++){
		if($simbank_info[$i-1]['n_sb_links'] == 0) continue;
		
		$selected = "";
		if($_GET['simbank_seri'] == $simbank_info[$i-1]['ob_sb_seri']){
			$selected = "selected";
		}
		
		$simbank_select_str .= '<option value="'.$simbank_info[$i-1]['ob_sb_seri'].'" '.$selected.' >'.$simbank_info[$i-1]['ob_sb_alias'].'-'.$simbank_info[$i-1]['ob_sb_seri'].'</option>';
	}
	
	return $simbank_select_str;
}

function show_batch_channel(){
	global $tmp_db;
	global $db;
	
	$condition = "limit 1";
	if(isset($_GET['simbank_seri'])){
		$condition = "where ob_sb_seri=\"".$_GET['simbank_seri']."\"";
	}
	
	$simbank_data = $tmp_db->Get("tb_simbank_info","*",$condition);
	$simbank_info = mysqli_fetch_array($simbank_data,MYSQLI_ASSOC);
	
	$gateway_data = $tmp_db->Get("tb_gateway_info","*","");
	
	$gateway_select_str = '<option value="" >None</option>';
	while($gateway_info = mysqli_fetch_array($gateway_data,MYSQLI_ASSOC)){
		if($gateway_info['ob_grp_name'] != '') continue;
		
		$gateway_select_str .= '<option value="'.$gateway_info['ob_gw_seri'].'" >'.$gateway_info['ob_gw_seri'].'</option>';
	}
	
	//SIM组中其他序列号已使用的sim卡
	$other_arr = [];
	$other_res = $db->Get('tb_group_info','*','');
	while($other_info = mysqli_fetch_array($other_res,MYSQLI_ASSOC)){
		$temp_arr = explode(',',$other_info['v_grp_sim']);
		$other_arr = array_merge($other_arr,$temp_arr);
	}
	
	//tb_mapping_info中已使用过sim卡
	$mapping_arr = [];
	
	
	if(isset($_GET['simbank_seri'])){
		$condition = "where ob_sb_seri=\"".$_GET['simbank_seri']."\"";
	}else{
		$simbank_seri = $simbank_info['ob_sb_seri'];
		$condition = "where ob_sb_seri=\"".$simbank_info['ob_sb_seri']."\"";
	}
	
	$mapping_res = $db->Get('tb_mapping_info','*',$condition);
	while($mapping_info = mysqli_fetch_array($mapping_res,MYSQLI_ASSOC)){
		$ob_sb_bank = $mapping_info['ob_sb_bank'];
		$ob_sb_slot = $mapping_info['ob_sb_slot'];
		
		$sim_id = $ob_sb_bank*8 + $ob_sb_slot + 1;
		
		array_push($mapping_arr,$sim_id);
	}
	
?>
<script type="text/javascript" src="/js/float_btn.js"></script>
<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
	<div id="filter" style="margin:20px 0;">
		<input type="text" id="filter_from" style="width:50px;" /> - <input type="text" id="filter_to" style="width:50px;" />
		<input type="button" id="filter_btn" style="font-size:12px;" value="<?php echo language('Filter');?>" />
		<input type="button" id="clean_filter_btn" style="font-size:12px;" value="<?php echo language('Clean Filter');?>" />
	</div>
	
	<select id="simbank_seri0" name="simbank_seri0" class="div_tab_title" style="margin-bottom:20px;" onchange="switch_tab(this)" >
		<?php echo get_simabnk_select_str(); ?>
	</select>
	
	<table width="100%" class="tshow">
		<input type="hidden" id="sel_sim" name="sel_sim" value="" />
		<tr>
			<th style="width:03%" class="nosort">
				<input type="checkbox" name="selall" onclick="selectall(this.checked)" />
			</th>
			<th width="25%">SIM ID</th>
			<th width="25%"><?php echo 'Gateway '.language('Serial Number');?></th>
			<th width="25%"><?php echo language('Port');?></th>
		</tr>

		<tr>
			<td style="background-color:#daedf4"></td>
			
			<td style="background-color:#daedf4"></td>
			
			<td style="background-color:#daedf4">
				<select name="gateway_seri0" id="gateway_seri0" class="gateway_seri" >
					<?php echo $gateway_select_str;?>
				</select>
			</td>
			
			<td style="background-color:#daedf4">
				<select name="gateway_channel0" class="gateway_channel" id="gateway_channel0">
					<option value="">None</option>
				</select>
			</td>
		</tr>
		
		<?php
			$sim_num = $simbank_info['n_sb_links'];
		?>
		<tbody class="sim_info" id="<?php echo $simbank_info['ob_sb_seri'];?>">
		<?php 
		for($i=1;$i<=$sim_num;$i++){
			$disabled = '';
			if(in_array($i, $other_arr)){
				$disabled = 'disabled';
			}
			if(in_array($i, $mapping_arr)){
				$disabled = 'disabled';
			}
		?>
		
			<tr>
				<td>
					<input type="checkbox" class="sim_checked" name="sim_<?php echo $simbank_info['ob_sb_seri'].$i;?>" id="sim_<?php echo $simbank_info['ob_sb_seri'].$i;?>" <?php echo $disabled;?>/>
					<input type="hidden" name="get_chan[<?php echo $simbank_info['ob_sb_seri'];?>][]" class="get_chan" value="<?php echo $i;?>" />
				</td>
				
				<td><?php echo 'Sim-'.$i;?></td>
				
				<td>
					<select name="gateway_seri_<?php echo $simbank_info['ob_sb_seri'].$i;?>" id="gateway_seri_<?php echo $simbank_info['ob_sb_seri'].$i;?>" class="gateway_seri" <?php echo $disabled;?> >
						<?php echo $gateway_select_str;?>
					</select>
				</td>
				
				<td>
					<select name="gateway_channel_<?php echo $simbank_info['ob_sb_seri'].$i;?>" class="gateway_channel" id="gateway_channel_<?php echo $simbank_info['ob_sb_seri'].$i;?>" <?php echo $disabled;?> >
						<option value="">None</option>
					</select>
				</td>
			</tr>
		
		<?php } ?>
		</tbody>
	</table>

	<div id="newline"></div>
	
	<table id="float_btn" class="float_btn">
		<tr id="float_btn_tr" class="float_btn_tr">
			<td>
				<input type="hidden" name="send" id="send" value="Save" />
				<input type="submit" id="sendlabel" value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';return check();"/>
			</td>
			<td>
				<input type="button" value="<?php echo language('Batch');?>" onclick="Batch_setValue();" />
			</td>
		</tr>
	</table>
	<table id="float_btn2" style="border:none;" class="float_btn2">
		<tr id="float_btn_tr2" class="float_btn_tr2">
			<td style="width:51px;">
				<input type="submit" id="float_button_1" class="float_short_button" value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';return check();"/>
			</td>
			<td style="width:51px;">
				<input type="button" id="float_button_3" name="" class="float_short_button" value="<?php echo language('Batch');?>" onclick="Batch_setValue();" />
			</td>
		</tr>
	</table>
</form>

<script>
function check(){
	var seri_flag = 0;
	var channel_flag = 0;
	$(".sim_checked").each(function(){
		if($(this).attr("checked") == "checked"){
			var gateway_seri_val = $(this).parent().siblings().children(".gateway_seri").val();
			var gateway_channel_val = $(this).parent().siblings().children(".gateway_channel").val();
			
			if(gateway_seri_val == ""){
				seri_flag = 1;
			}
			
			if(gateway_channel_val == ""){
				channel_flag = 1;
			}
		}
	});
	
	if(seri_flag == 1){
		alert("<?php echo 'Gateway '.language('Serial Number').' '.language('can not be none')?>")
		return false;
	}
	
	if(channel_flag == 1){
		alert("<?php echo language('Port').' '.language('can not be none')?>")
		return false;
	}
	
	return true;
}

function switch_tab(that){
	var url = '<?php echo get_self();?>'+'?';
	
	var simbank_seri = $(that).val();
	
	url += "simbank_seri="+simbank_seri;
	
	window.location.href = url;
}

function selectall(checked){
	if(checked == true){
		$(".sim_checked").each(function(){
			if($(this).parent().parent().css("display") == "table-row"){
				if($(this).attr("disabled") == undefined){
					$(this).attr("checked","checked");
				}
			}
		});
	}else{
		$(":checkbox").removeAttr("checked");
	}
}

function Batch_setValue(){
	var simbank_seri0 = document.getElementById('simbank_seri0').value;
	var gateway_seri0 = document.getElementById('gateway_seri0').value;
	var gateway_channel_html = $("#gateway_channel0").html();
	var gateway_channel0 = document.getElementById('gateway_channel0').value;
	var n = 0;
	for(var i=0;i<gw_seri_arr.length;i++){
		if(gateway_channel0 == gw_seri_arr[i]){
			n = i;
		}
	}
	
	$("#"+simbank_seri0+" .get_chan").each(function(){
		var channel = $(this).val();
		if($(this).siblings('#sim_'+simbank_seri0+channel).prop("checked")){
			
			$("#gateway_seri_"+simbank_seri0+channel).val(gateway_seri0);
			$("#gateway_channel_"+simbank_seri0+channel).html(gateway_channel_html);
			
			$("#gateway_channel_"+simbank_seri0+channel).val(gw_seri_arr[n]);
			n++;
		}
	});
}

var gw_seri_arr = [];
$(".gateway_seri").change(function(){
	var gateway_seri = $(this).val();
	var that = $(this);
	
	$.ajax({
		url: 'ajax_server_new.php?action=sim_batch_get_channel_num&gateway_seri='+gateway_seri,
		type: 'GET',
		success: function(data){
			gw_seri_arr = JSON.parse(data);
			
			var str = '<option value="">None</option>';
			for(var j=0;j<gw_seri_arr.length;j++){
				str += '<option value="'+gw_seri_arr[j]+'">'+gw_seri_arr[j]+'</option>';
			}
			
			$(that).parent().next().children().html(str);
		},
		error: function(data){
			console.log('error');
		}
	});
});

$("#filter_btn").click(function(){
	var filter_from = document.getElementById('filter_from').value;
	var filter_to = document.getElementById('filter_to').value;
	
	if(filter_from == '') filter_from = 1;
	if(filter_to == '') filter_to = 1000;
	
	var simbank_seri = document.getElementById('simbank_seri0').value;
	
	var i=1;
	$("#"+simbank_seri+" tr").each(function(){
		$(this).show();
		if(i<filter_from || i> filter_to){
			$(this).hide();
			$(this).children().children(".sim_checked").removeAttr("checked");
		}
		i++;
	});
});

$("#clean_filter_btn").click(function(){
	var simbank_seri = document.getElementById('simbank_seri0').value;
	
	$("#"+simbank_seri+" tr").each(function(){
		$(this).show();
	});
});
</script>

<?php
}

function save_batch_channel(){
	global $tmp_db;
	global $db;
	
	$simbank_seri0 = $_POST['simbank_seri0'];
	
	$data = $tmp_db->Get("tb_simbank_info","n_sb_links","where ob_sb_seri='".$simbank_seri0."'");
	
	$info = mysqli_fetch_array($data, MYSQLI_ASSOC);
	
	for($i=1;$i<=$info['n_sb_links'];$i++){
		$chan = $_POST['get_chan'][$simbank_seri0][$i-1];
		if(!isset($_POST['sim_'.$simbank_seri0.$chan])) continue;
		
		$simbank_seri = $simbank_seri0;
		
		$gateway_seri = $_POST['gateway_seri_'.$simbank_seri0.$i];
		
		$gateway_channel = $_POST['gateway_channel_'.$simbank_seri0.$i];
		
		$n = $i-1;
		$ob_sb_bank = floor($n/8);
		$ob_sb_slot = $n%8;
		
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
		save_batch_channel();
		show_batch_channel();
	}
}else{
	show_batch_channel();
}


require("/opt/simbank/www/inc/boot.inc");
?>

<div id="float_btn1" class="sec_float_btn1"></div>
<div  class="float_close" onclick="close_btn()"></div>