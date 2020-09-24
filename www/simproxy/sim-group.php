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
$db2=new mysql();

function show_sim_groups(){
	global $db;

	$res = $db->Get('tb_group_info','*','');

	while($info = mysqli_fetch_array($res,MYSQLI_ASSOC)){
		//把数据库中的数据组装到group_info数组中
		if(!array_key_exists($info['ob_grp_name'],$group_info)){
			$group_info[$info['ob_grp_name']] = [];
			array_push($group_info[$info['ob_grp_name']], ['ob_gw_seri' => $info['ob_gw_seri']]);
			array_push($group_info[$info['ob_grp_name']], ['ob_grp_alias' => $info['ob_grp_alias']]);
			array_push($group_info[$info['ob_grp_name']], ['ob_policy_name' => $info['ob_policy_name']]);
			$group_info[$info['ob_grp_name']]['ob_sb_seri'] = $info['ob_sb_seri'];
		}
		
		if(!isset($group_info[$info['ob_grp_name']]['v_grp_sim'])){
			$v_grp_sim = $info['ob_sb_seri'].'-'.$info['v_grp_sim'].'<br/><br/>';
		}else{
			$v_grp_sim .= $info['ob_sb_seri'].'-'.$info['v_grp_sim'].'<br/><br/>';
		}
		array_push($group_info[$info['ob_grp_name']], ['v_grp_sim' => $v_grp_sim]);
		
		if(!isset($group_info[$info['ob_grp_name']]['n_grp_sim_num'])){
			$n_grp_sim_num = $info['n_grp_sim_num'];
		}else{
			$n_grp_sim_num += $info['n_grp_sim_num'];
		}
		array_push($group_info[$info['ob_grp_name']], ['n_grp_sim_num' => $n_grp_sim_num]);
	}
?>
	<form id="mainform" enctype="multipart/form-data" action="<?php get_self();?>" method="post">
		<input type="hidden" id="sel_group_name" name="sel_group_name" value="" />
		<input type="hidden" id="sel_sim_seri" name="sel_sim_seri" value="" />
		<table width="100%" class="tsort">
			<tbody>
				<tr>
					<th><?php echo language('Group Name'); ?></th>
					<th><?php echo language('Alias name');?></th>
					<th><?php echo language('Gateway Serial Number');?></th>
					<th width="600px"><?php echo language('Members@Sim','Members');?></th>
					<th><?php echo language('Policy');?></th>
					<th><?php echo language('Sim Number');?></th>
					<th width="80px"><?php echo language('Actions');?></th>
				</tr>
				
				<?php 
				foreach($group_info as $key => $val){
				?>
				<tr>
					<td><?php echo $key;?></td>
					<td>
						<?php 
						for($i=0;$i<count($val);$i++){
							if(isset($val[$i]['ob_grp_alias'])) echo $val[$i]['ob_grp_alias'];
						}
						?>
					</td>
					<td>
						<?php 
						for($i=0;$i<count($val);$i++){
							if(isset($val[$i]['ob_gw_seri'])) echo $val[$i]['ob_gw_seri'];
						}
						?>
					</td>
					<td>
						<div style="width:600px;word-wrap:break-word;">
						<?php
						//输出不同序列号的sim卡成员
						for($i=0;$i<count($val);$i++){
							if(isset($val[$i]['v_grp_sim'])) echo $val[$i]['v_grp_sim'];
						}
						?>
						</div>
					</td>
					<td>
						<?php 
						for($i=0;$i<count($val);$i++){
							if(isset($val[$i]['ob_policy_name'])) echo $val[$i]['ob_policy_name'];
						}
						?>
					</td>
					<td>
						<?php 
						$num = 0;
						//同一个组名不同序列号的成员数量相加
						for($i=0;$i<count($val);$i++){
							if(isset($val[$i]['n_grp_sim_num'])){
								$num += $val[$i]['n_grp_sim_num'];
							}
						}
						echo $num;
						?>
					</td>
					<td>
						<button type="button" value="Modify" style="width:32px;height:32px;" onclick="getPage('<?php echo $key;?>','<?php echo $group_info[$key]['ob_sb_seri'];?>')">
							<img src="/images/edit.gif" />
						</button>
						<button type="submit" value="Delete" style="width:32px;height:32px" 
							onclick="document.getElementById('send').value='Delete';return delete_check('<?php echo $key;?>');">
							<img src="/images/delete.gif" />
						</button>
					</td>
				</tr>
				<?php }?>
			</tbody>
		</table>
		
		<br>
		
		<input type="hidden" name="send" id="send" value="" />
		<input type="submit" value="<?php echo language('New Sim Group')?>" onclick="document.getElementById('send').value='New Sim Group'" />
	</form>

	<script>
	function getPage(group_name,sim_seri){
		window.location.href = '<?php echo get_self();?>?sel_group_name='+group_name+'&sel_sim_seri='+sim_seri;
	}

	function delete_check(group_name){
		var ret = confirm('<?php echo language('delete tip','Are you sure to delete you selected ?'); ?>');
		
		if(ret){
			document.getElementById('sel_group_name').value = group_name;
			return true;
		}
		
		return false;
	}
	</script>
<?php 
}

function edit_sim_group(){
	global $db;
	global $db2;
	
	$group_name = '';
	$sim_seri = '';
	$members = '';
	$sel_group_name = '';
	$sel_sim_seri = '';
	$ob_policy_name = '';
	$ob_grp_alias = '';
	
	//编辑的时候进入$_GET，新建的时候不进入
	if($_GET){
		if(isset($_GET['sel_group_name'])){
			$sel_group_name = $_GET['sel_group_name'];
		}
		
		if(isset($_GET['sel_sim_seri'])){
			$sel_sim_seri = $_GET['sel_sim_seri'];
		}
		
		$res = $db->Get('tb_group_info','*','where ob_grp_name = "'.$sel_group_name.'" and ob_sb_seri = "'.$sel_sim_seri.'"');
		$group_info = mysqli_fetch_array($res,MYSQLI_ASSOC);
		
		$group_name = $group_info['ob_grp_name'];
		$sim_seri = $group_info['ob_sb_seri'];
		$members = $group_info['v_grp_sim'];
		$ob_grp_alias = $group_info['ob_grp_alias'];
		$ob_policy_name = $group_info['ob_policy_name'];
	}
?>

<script type="text/javascript" src="/js/check.js"></script>
<script type="text/javascript" src="/js/float_btn.js"></script>
<form enctype="multipart/form-data" action="<?php echo get_self();?>" method="post">
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Sim Group');?></li>
		<li class="tb2">&nbsp;</li>
	</div>
	
	<table width="100%" class="tedit">
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Group Name');?>:
					<span class="showhelp">
					<?php echo language('Group Name help','Name of group SIM.');?>
					</span>
				</div>
			</th>
			<td>
			<!--
				<span id="group_name_span">
					<?php echo $group_name;?>
					<input type="hidden" name="update_group_name" id="update_group_name" value="<?php echo $group_name;?>" />
				</span>-->
				<input type="text" name="group_name" id="group_name" value="<?php echo $group_name;?>" />
				<input type="hidden" name="old_group_name" id="old_group_name" value="<?php echo $group_name;?>" />
				<span id="cgroup_name"></span>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Alias name');?>:
					<span class="showhelp">
					<?php echo language('Alias name help@sim_group','SIM group\'s annotation name can be modified after saving, which is convenient for users to remember.');?>
					</span>
				</div>
			</th>
			<td>
				<input type="text" name="ob_grp_alias" id="ob_grp_alias" value="<?php echo $ob_grp_alias;?>" />
				<span id="cob_grp_alias"></span>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Simbank Serial Number');?>:
					<span class="showhelp">
					<?php echo language('Simbank Serial Number help@sim_group','Select which simbank to create the SIM group for.');?>
					</span>
				</div>
			</th>
			<td>
				<select name="select_simbank" id="select_simbank" class="select_simbank">
				<?php 
				$condition = "";
				$data = $db2->Get("tb_simbank_info",'*',$condition);
				$all_sim_info = mysqli_fetch_all($data, MYSQLI_ASSOC);
				
				$row = mysqli_num_rows($data);
				
				for($i=1;$i<=$row;$i++){
				?>
					<option value="<?php echo $all_sim_info[$i-1]['ob_sb_seri'];?>"><?php echo $all_sim_info[$i-1]['ob_sb_alias'];?>-<?php echo $all_sim_info[$i-1]['ob_sb_seri'];?></option>
					<!--
					<input type="radio" name="select_simbank" id="select_simbank" class="select_simbank" value="<?php echo $all_sim_info[$i-1]['ob_sb_seri'];?>" 
					<?php if($all_sim_info[$i-1]['ob_sb_seri'] == $sim_seri) {
						echo 'checked';
					}else if($i==1){
						echo 'checked';
					}
					?> 
					/>
					<span style="margin-right:10px;"><?php echo $all_sim_info[$i-1]['ob_sb_seri'];?></span>-->
				<?php 
				}
				?>
				</select>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Members@Sim','Members');?>:
					<span class="showhelp">
					<?php echo language('Members@Sim help','Select which SIM cards in the simbank to form a SIM group; a SIM card can only exist in one SIM group');?>
					</span>
				</div>
			</th>
			<td>
				<table cellpadding="0" cellspacing="0" class="port_table">
				<?php for($i=1;$i<=$row;$i++){ ?>
				
					<tr id="port<?php echo $all_sim_info[$i-1]['ob_sb_seri'];?>" class="_port" style="display:none;">
					
					<?php 
						$condition = "where ob_sb_seri=\"".$all_sim_info[$i-1]['ob_sb_seri']."\"";
						$fileds = "n_sb_links";
						$data = $db2->Get("tb_simbank_info",$fileds,$condition);
						
						//当前序列号已选择的端口
						$current_res = $db->Get('tb_group_info','*','where ob_grp_name = "'.$sel_group_name.'" and ob_sb_seri = "'.$all_sim_info[$i-1]['ob_sb_seri'].'"');
						$current_info = mysqli_fetch_array($current_res,MYSQLI_ASSOC);
						$current_arr = explode(',',$current_info['v_grp_sim']);
						
						//其他序列号已选择的端口
						$other_arr = [];
						$other_res = $db->Get('tb_group_info','*','where ob_grp_name <> "'.$sel_group_name.'" and ob_sb_seri = "'.$all_sim_info[$i-1]['ob_sb_seri'].'"');
						while($other_info = mysqli_fetch_array($other_res,MYSQLI_ASSOC)){
							$temp_arr = explode(',',$other_info['v_grp_sim']);
							$other_arr = array_merge($other_arr,$temp_arr);
						}
						
						$sim = mysqli_fetch_array($data,MYSQLI_ASSOC);
						
						for($j=1;$j<=$sim['n_sb_links'];$j++){
							$sim_index=$j;
							$disabled = '';
							$font_color = '';
							$checked = '';
							
							if(in_array($sim_index, $current_arr)){
								$checked = 'checked';
							}
							
							if(in_array($sim_index, $other_arr)){
								$disabled = 'disabled';
								$font_color = 'style="color:darkgrey;"';
								$checked = 'checked';
							}
							
							//选择单行打勾select line
							$line_val = intval(($j-1)/8);
							$line_sel_val = $line_val + 1;
							
							if($j%8 == 1 && $j != 1){
								echo '<td style="width:20px;float:right;padding:0;"><input type="checkbox" class="sel_check_line" value="'.$line_val.'" /></td>';
							}
							
							if($j == $sim['n_sb_links']){
								echo '<td style="width:20px;float:right;padding:0;"><input type="checkbox" class="sel_check_line" value="'.($line_val+1).'" /></td>';
							}
					?>
						<td class="sms_port" style="width:110px;">
							<input type="checkbox" name="spans[<?php echo $all_sim_info[$i-1]['ob_sb_seri'];?>][<?php echo $sim_index;?>]" class="port_sel_class line_sel_<?php echo $line_sel_val;?>" <?php echo $disabled.' '.$checked; ?> />
							<span <?php echo $font_color;?>><?php echo "sim-".$sim_index;?><span>
						</td>
					<?php 
							
						}
					?>
					
					</tr>
					
				<?php } ?>
				
					<tr style="border:none;">
						<td style="padding-left:0;">
							<input type="checkbox" id="select_all" />
							<?php echo language('All');?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Policy');?>:
					<span class="showhelp">
					<?php echo language('Policy help@sim_group','Select the strategy to be used by the SIM group.');?>
					</span>
				</div>
			</th>
			<td>
				<select name="ob_policy_name" id="ob_policy_name">
				<?php 
				$res = $db->Get('tb_policy_info','ob_pol_name','');
				while($info = mysqli_fetch_array($res,MYSQLI_ASSOC)){
					$selected = '';
					if($ob_policy_name == $info['ob_pol_name']){
						$selected = 'selected';
					}
				?>
					<option value="<?php echo $info['ob_pol_name']?>" <?php echo $selected;?>><?php echo $info['ob_pol_name']?></option>
				<?php
				}
				?>
				</select>
			</td>
		</tr>
	</table>
	
	<div id="newline"></div>
	
	<table id="float_btn" class="float_btn">
		<tr id="float_btn_tr" class="float_btn_tr">
			<td>
				<input type="hidden" name="send" id="send" value="" />
				<input style="margin-right:15px;" type="submit" value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';return check();" />
			</td>
		</tr>
	</table>
	<table id="float_btn2" style="border:none;" class="float_btn2">
		<tr id="float_btn_tr2" class="float_btn_tr2">
			<td>
				<input type="submit" id="float_button_1" class="float_short_button" value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';return check();" />
			</td>
		</tr>
	</table>
</form>

<script>
$(function(){
	/*
	var send = '<?php echo $_POST['send']?>';
	if(send == 'New Sim Group'){
		$("#group_name_span").hide();
		//$("#sim_seri_span").hide();
	}else{
		$("#group_name").hide();
		//$("#select_simbank").hide();
	}*/
});

//select simbank
var sim_sel = $('#select_simbank').val();
$("#port"+sim_sel).show();

$(".select_simbank").change(function(){
	var _sim_sel = $(this).val();
	$("._port").hide();
	$("#port"+_sim_sel).show();
});

//select_all
$("#select_all").click(function(){
	var sim_sel = $('#select_simbank').val();
	
	var that = this;
	$("#port"+sim_sel+" .port_sel_class").each(function(){
		if($(this).attr('disabled') != 'disabled'){
			$(this).attr("checked",$(that).is(':checked'));
		}
	});
});
$(".port_sel_class").click(function(){
	if(!$(this).is(':checked')){
		$("#select_all").attr("checked", false);
	}
});

function check(){
	var group_name = document.getElementById('group_name').value;
	if(group_name == '' || group_name.length > 10){
		document.getElementById('cgroup_name').innerHTML = con_str("<?php echo language('group name null help','Group names can not be empty and up to 10 characters.');?>");
		return false;
	}
	
	<?php 
	$res = $db->Get('tb_group_info','ob_grp_name',"where ob_grp_name <> '$group_name'");
	$i = 0;
	$js_arr_str = '[';
	while($info = mysqli_fetch_array($res,MYSQLI_ASSOC)){
		//ob_grp_name
		$js_arr_str .= '"'.$info['ob_grp_name'].'",';
	}
	$js_arr_str .= ']';
	?>
	
	var group_arr = <?php echo $js_arr_str;?>;
	for(var i=0;i<group_arr.length;i++){
		if(group_name == group_arr[i]){
			document.getElementById('cgroup_name').innerHTML = con_str("<?php echo language('Group name already exists');?>");
			return false;
		}
	}
	
	return true;
}

//select line
$(".sel_check_line").click(function(){
	var val = $(this).val();
	
	var sim_sel = $('#select_simbank').val();
	
	if($(this).attr("checked") == "checked"){
		$("#port"+sim_sel+" .line_sel_"+val).each(function(){
			if($(this).attr("disabled") != 'disabled'){
				$(this).attr("checked","checked");
			}
		});
	}else{
		$("#port"+sim_sel+" .line_sel_"+val).each(function(){
			if($(this).attr("disabled") != 'disabled'){
				$(this).removeAttr("checked");
			}
		});
	}
});

</script>

<?php
}

function save_sim_group(){
	global $db;
	global $db2;
	
	$spans = $_POST['spans'];
	
	//新建的时候传入group_name
	//if($_POST['update_group_name'] == ''){//insert(mysql)
	$group_name = $_POST['group_name'];
	//}else{//update(mysql)编辑的时候传入update_group_name
		//$group_name = $_POST['update_group_name'];
	//}
	$old_group_name = $_POST['old_group_name'];
	$ob_grp_alias = $_POST['ob_grp_alias'];
	$ob_policy_name = $_POST['ob_policy_name'];
		
	$condition = "";
	$data = $db2->Get("tb_simbank_info",'*',$condition);
	$all_sim_info = mysqli_fetch_all($data, MYSQLI_ASSOC);
	$row = mysqli_num_rows($data);
	
	//get strategy info
	$data = $db->Get('tb_policy_info','*',"where ob_pol_name = '$ob_policy_name'");
	$strategy_res = mysqli_fetch_all($data, MYSQLI_ASSOC);
	
	for($i=1;$i<=$row;$i++){
		$sim_seri = $all_sim_info[$i-1]['ob_sb_seri'];
		
		//span checked 把选中的sim卡号放入数组span_user_sim中
		$span_use_sim = [];
		foreach($spans[$sim_seri] as $key => $val){
			array_push($span_use_sim,$key);
		}
		
		$res = $db->Get('tb_group_info','*','where ob_grp_name = "'.$old_group_name.'" and ob_sb_seri = "'.$sim_seri.'"');
		$group_info = mysqli_fetch_array($res,MYSQLI_ASSOC);
		
		if(isset($group_info)){//如果存在group_info就执行update语句
			$group_sim_str = '';
			$j = 0;
			foreach($spans[$sim_seri] as $key => $val){
				$group_sim_str .= $key.',';
				$j++;
			}
			$group_sim_str = substr($group_sim_str, 0, -1);
			
			$fields = "ob_grp_name='$group_name',ob_grp_alias='$ob_grp_alias',v_grp_sim='$group_sim_str',n_grp_sim_num='$j',ob_policy_name='$ob_policy_name'";
			$condition = "where ob_grp_name = '$old_group_name' and ob_sb_seri='$sim_seri'";
			$db->Set('tb_group_info',$fields,$condition);
			
			if($group_name != $old_group_name){
				$fields = "ob_grp_name='$group_name'";
				$condition = "where ob_grp_name='$old_group_name'";
				$db->Set('tb_gateway_info',$fields,$condition);
				
				$db2->Set('tb_gateway_info',$fields,$condition);
			}
			
			//gsoap
			$params = array(
				'groupname' => $old_group_name,
				'simgroup' => array(
					'groupname' => $group_name,
					'simgroup' => $group_sim_str,
					'policyname' => $ob_policy_name,
					'sbseri' => $sim_seri
				),
				'policy' => array(
					'selectorder'=>$strategy_res[0]['n_pol_select_order'],
					'switchmode'=>$strategy_res[0]['b_pol_switch_mode'],
					'lockswitchmode'=>$strategy_res[0]['b_pol_limit_switch_mode'],
					'datefreshmode'=>$strategy_res[0]['b_pol_fresh_mode'],
					'polname'=>$ob_policy_name,
					'switchmaxcalldura'=>$strategy_res[0]['n_pol_switch_call_dura'],
					'switchmaxcallcounts'=>$strategy_res[0]['n_pol_switch_call_counts'],
					'switchmaxsmsresultcounts'=>$strategy_res[0]['n_pol_switch_sms_result_counts'],
					'switchmaxsmsreportcounts'=>$strategy_res[0]['n_pol_switch_sms_report_counts'],
					'switchmaxcallanscounts'=>$strategy_res[0]['n_pol_switch_call_anscounts'],
					'swtichsimusedura'=>$strategy_res[0]['n_pol_switch_sim_use_dura'],
					'sleeptime'=>$strategy_res[0]['n_pol_sleep_time'],
					'lockmaxcallcountshour'=>$strategy_res[0]['n_pol_max_call_counts_hour'],
					'lockmaxcallcountsday'=>$strategy_res[0]['n_pol_max_call_counts_day'],
					'lockmaxcallreceiveday'=>$strategy_res[0]['n_pol_max_call_receive_day'],
					'lockmaxcalltimeday'=>$strategy_res[0]['n_pol_max_call_call_time_day'],
					'lockmaxcalltimemonth'=>$strategy_res[0]['n_pol_max_call_time_month'],
					'lockmaxsmscountsday'=>$strategy_res[0]['n_pol_max_sms_counts_day'],
					'lockmaxsmsreportcountsday'=>$strategy_res[0]['n_pol_max_sms_report_counts_day'],
					'monthdataclearday'=>$strategy_res[0]['n_clear_month_day']
				)
			);
			$xml = get_xml('SBKSIMGroupUpdate',$params);
			$wsdl = "http://127.0.0.1:8888/?wsdl";
			$client = new SoapClient($wsdl);
			$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
		}else if(isset($spans[$sim_seri])){//不存在group_info而且页面未选中任一sim卡就执行insert语句
			$group_sim_str = '';
			$j = 0;
			foreach($spans[$sim_seri] as $key => $val){
				$group_sim_str .= $key.',';
				$j++;
			}
			$group_sim_str = substr($group_sim_str, 0, -1);
			
			$res = $db->Get('tb_group_info','ob_gw_seri',"where ob_grp_name='$group_name' limit 1");
			$temp = mysqli_fetch_array($res,MYSQLI_ASSOC);
			$info = $temp['ob_gw_seri'];
			
			$fields = "ob_grp_name,ob_grp_alias,v_grp_sim,n_grp_sim_num,ob_sb_seri,ob_gw_seri,ob_policy_name";
			$values = "'$group_name','$ob_grp_alias','$group_sim_str','$j','$sim_seri','$info','$ob_policy_name'";
			$db->Add('tb_group_info',$fields,$values);
			
			$params = array(
				'simgroup' => array(
					'groupname' => $group_name,
					'simgroup' => $group_sim_str,
					'policyname' => $ob_policy_name,
					'sbseri' => $sim_seri
				),
				'policy' => array(
					'selectorder'=>$strategy_res[0]['n_pol_select_order'],
					'switchmode'=>$strategy_res[0]['b_pol_switch_mode'],
					'lockswitchmode'=>$strategy_res[0]['b_pol_limit_switch_mode'],
					'datefreshmode'=>$strategy_res[0]['b_pol_fresh_mode'],
					'polname'=>$ob_policy_name,
					'switchmaxcalldura'=>$strategy_res[0]['n_pol_switch_call_dura'],
					'switchmaxcallcounts'=>$strategy_res[0]['n_pol_switch_call_counts'],
					'switchmaxsmsresultcounts'=>$strategy_res[0]['n_pol_switch_sms_result_counts'],
					'switchmaxsmsreportcounts'=>$strategy_res[0]['n_pol_switch_sms_report_counts'],
					'switchmaxcallanscounts'=>$strategy_res[0]['n_pol_switch_call_anscounts'],
					'swtichsimusedura'=>$strategy_res[0]['n_pol_switch_sim_use_dura'],
					'sleeptime'=>$strategy_res[0]['n_pol_sleep_time'],
					'lockmaxcallcountshour'=>$strategy_res[0]['n_pol_max_call_counts_hour'],
					'lockmaxcallcountsday'=>$strategy_res[0]['n_pol_max_call_counts_day'],
					'lockmaxcallreceiveday'=>$strategy_res[0]['n_pol_max_call_receive_day'],
					'lockmaxcalltimeday'=>$strategy_res[0]['n_pol_max_call_call_time_day'],
					'lockmaxcalltimemonth'=>$strategy_res[0]['n_pol_max_call_time_month'],
					'lockmaxsmscountsday'=>$strategy_res[0]['n_pol_max_sms_counts_day'],
					'lockmaxsmsreportcountsday'=>$strategy_res[0]['n_pol_max_sms_report_counts_day'],
					'monthdataclearday'=>$strategy_res[0]['n_clear_month_day']
				)
			);
			$xml = get_xml('SBKSIMGroupAdd',$params);
			$wsdl = "http://127.0.0.1:8888/?wsdl";
			$client = new SoapClient($wsdl);
			$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
		}
		
		
		//tb_simbank_link_info //当前序列号中被其他组使用过的sim卡号放到sim_arr中，下面进行过滤用
		$sim_arr = [];
		$data = $db2->Get("tb_simbank_link_info","ob_grp_name,ob_sb_link_bank_nbr,ob_sb_link_sim_nbr","where ob_sb_seri = '$sim_seri'");
		while($grp_info = mysqli_fetch_array($data,MYSQLI_ASSOC)){
			if($grp_info['ob_grp_name'] != NULL && $grp_info['ob_grp_name'] != $old_group_name){
				$ob_sb_link_bank_nbr = $grp_info['ob_sb_link_bank_nbr'];
				$ob_sb_link_sim_nbr = $grp_info['ob_sb_link_sim_nbr'];
				$res = $ob_sb_link_bank_nbr*8 + $ob_sb_link_sim_nbr + 1;
				
				array_push($sim_arr, $res);
			}
		}
		
		$condition = "where ob_sb_seri=\"".$all_sim_info[$i-1]['ob_sb_seri']."\"";
		$fileds = "n_sb_links";
		$data = $db2->Get("tb_simbank_info",$fileds,$condition);
		$sim = mysqli_fetch_array($data,MYSQLI_ASSOC);
		
		for($k=1;$k<=$sim['n_sb_links'];$k++){
			$ob_sb_link_bank_nbr = intval(($k-1)/8);
			$ob_sb_link_sim_nbr = ($k-1)%8;
			
			//先过滤其他组有使用的sim卡号
			if(in_array($k,$sim_arr)){
				continue;
			}
			
			//再更新页面上选中的sim卡和未选中的sim卡
			if(in_array($k,$span_use_sim)){
				$fields = "ob_grp_name='$group_name',n_sb_link_call_rest_time='".$strategy_res[0]['n_pol_max_call_time_month']."'";
			}else{
				$fields = "ob_grp_name=NULL,n_sb_link_call_rest_time='0'";
			}
			
			if(in_array($k,$span_use_sim)){
				$fields_sim = "ob_grp_name='$group_name'";
			}else{
				$fields_sim = "ob_grp_name=NULL";
			}
			$condition = "where ob_sb_seri='$sim_seri' and ob_sb_link_bank_nbr='$ob_sb_link_bank_nbr' and ob_sb_link_sim_nbr='$ob_sb_link_sim_nbr'";
			
			$db2->Set('tb_simbank_link_info',$fields,$condition);
			$db2->Set('tb_sim_info',$fields_sim,$condition);
		}
		
	}
}

function del_sim_group(){
	global $db;
	global $db2;
	
	$group_name = $_POST['sel_group_name'];
	
	$data = $db->Get('tb_group_info','ob_gw_seri',"where ob_grp_name='$group_name'");
	$gw_seri = mysqli_fetch_array($data,MYSQLI_ASSOC);
	$gw_seri_temp = $gw_seri['ob_gw_seri'];
	
	$condition = "ob_grp_name = '$group_name'";
	$db->Del('tb_group_info',$condition);
	
	$grp_str = '';
	$data = $db->Get('tb_group_info','ob_grp_name',"where ob_gw_seri='$gw_seri_temp'");
	while($grp_info = mysqli_fetch_array($data,MYSQLI_ASSOC)){
		$grp_str .= $grp_info['ob_grp_name'].',';
	}
	$grp_str = substr($grp_str, 0, -1);
	
	$db->Set('tb_gateway_info',"ob_grp_name=\"$grp_str\"","where ob_gw_seri='$gw_seri_temp'");
	$db2->Set('tb_gateway_info',"ob_grp_name=\"$grp_str\"","where ob_gw_seri='$gw_seri_temp'");
	//$db2->Set('tb_gateway_link_info','ob_grp_name=""',"where ob_grp_name='$group_name'");
	
	$db2->Set('tb_simbank_link_info',"ob_grp_name=NULL,n_sb_link_call_rest_time='0'","where ob_grp_name='$group_name'");
	
	$db2->Set('tb_sim_info',"ob_grp_name=NULL","where ob_grp_name='$group_name'");
	
	//gsoap
	$params = array( 'groupname' => $group_name );
	$xml = get_xml('SBKSIMGroupDel',$params);
	$wsdl = "http://127.0.0.1:8888/?wsdl";
	$client = new SoapClient($wsdl);
	$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
}


if($_POST){
	if(isset($_POST['send']) && $_POST['send'] == 'New Sim Group'){
		edit_sim_group();
	}else if(isset($_POST['send']) && $_POST['send'] == 'Save'){
		save_sim_group();
		show_sim_groups();
	}else if(isset($_POST['send']) && $_POST['send'] == 'Delete'){
		del_sim_group();
		show_sim_groups();
	}
}else if($_GET){
	edit_sim_group();
}else{
	show_sim_groups();
}

require("/opt/simbank/www/inc/boot.inc");
?>

<div id="float_btn1" class="sec_float_btn1"></div>
<div  class="float_close" onclick="close_btn()"></div>