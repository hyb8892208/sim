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
$tmp_db = new mysql();

function show_strategy(){
	global $db;
	
	$res = $db->Get('tb_policy_info','*','');
?>
	<form id="mainform" enctype="multipart/form-data" action="<?php get_self();?>" method="post" >
		<input type="hidden" id="id" name="id" value="" />
		<input type="hidden" id="ob_pol_name" name="ob_pol_name" value="" />
		<table width="100%" class="tsort">
			<tbody>
				<tr>
					<th><?php echo language('Policy Name');?></th>
					<th><?php echo language('Alloc Condition');?></th>
					<th><?php echo language('Swtich Condition');?></th>
					<th><?php echo language('Max call time Month');?></th>
					<th><?php echo language('Call Limit');?></th>
					<th width="80px"><?php echo language('Actions');?></th>
				</tr>
		<?php while($info = mysqli_fetch_array($res,MYSQLI_ASSOC)){ 
				if($info['n_pol_select_order'] == 2){
					$n_pol_select_order = language('Descending');
				}else if($info['n_pol_select_order'] == 3){
					$n_pol_select_order = language('Random');
				}else if($info['n_pol_select_order'] == 4){
					$n_pol_select_order = language('Max Unused Time');
				}else if($info['n_pol_select_order'] == 5){
					$n_pol_select_order = language('Min Call Time');
				}else if($info['n_pol_select_order'] == 6){
					$n_pol_select_order = language('Max Call Time');
				}else if($info['n_pol_select_order'] == 7){
					$n_pol_select_order = language('Min Call Count');
				}else if($info['n_pol_select_order'] == 8){
					$n_pol_select_order = language('Max Call count');
				}else{
					$n_pol_select_order = language('Ascending');
				}
				
				if(intval($info['b_pol_switch_mode']) > 0){
					$switch_mode = 'ON';
				}else{
					$switch_mode = 'OFF';
				}
				
				if(intval($info['b_pol_limit_switch_mode']) == 0 || ($info['b_pol_limit_switch_mode']&32) != 0 || ($info['b_pol_limit_switch_mode']&64) != 0){
					$limit_mode = 'OFF';
				}else{
					$limit_mode = 'ON';
				}
		?>
				<tr>
					<td><?php echo $info['ob_pol_name'];?></td>
					<td><?php echo $n_pol_select_order;?></td>
					<td><?php echo $switch_mode;?></td>
					<td><?php 
						if($limit_mode == 'OFF'){
							echo language('Unlimit');
						}else{
							echo $info['n_pol_max_call_time_month'];
						}
						?>
					</td>
					<td><?php echo $limit_mode;?></td>
					<td>
						<button type="button" value="Modify" style="width:32px;height:32px;" onclick="getPage('<?php echo $info['id'];?>')" >
							<img src="/images/edit.gif" />
						</button>
						<button type="submit" value="Delete" style="width:32px;height:32px;"
							onclick="document.getElementById('send').value='Delete';return delete_check('<?php echo $info['id']?>','<?php echo $info['ob_pol_name'];?>');">
							<img src="/images/delete.gif" />
						</button>
					</td>
				</tr>
		<?php } ?>
			</tbody>
		</table>
		
		<br/>
		
		<input type="hidden" name="send" id="send" value="" />
		<input type="submit" value="<?php echo language('New Policy');?>" onclick="document.getElementById('send').value='New Policy'" />
	</form>
	
	<script>
	function getPage(id){
		window.location.href = '<?php echo get_self();?>?id='+id;
	}
	
	function delete_check(id,ob_pol_name){
		var ret = confirm('<?php echo language('delete tip','Are you sure to delete you selected ?');?>');
		
		if(ret){
			document.getElementById('id').value = id;
			document.getElementById('ob_pol_name').value = ob_pol_name;
			return true;
		}
		
		return false;
	}
	</script>
<?php
}

function edit_strategy(){
	global $db;
	
	$id = '';
	if($_GET){
		$id = $_GET['id'];
		$res = $db->Get('tb_policy_info','*',"where id=$id");
		$strategy_info = mysqli_fetch_array($res,MYSQLI_ASSOC);
		
		$ob_pol_name = $strategy_info['ob_pol_name'];
		$n_pol_select_order = $strategy_info['n_pol_select_order'];
		
		$b_pol_switch_mode_val = $strategy_info['b_pol_switch_mode'];
		$b_pol_switch_mode = '';
		if($b_pol_switch_mode_val != 0){
			$b_pol_switch_mode = 'checked';
		}
		
		$call_dura_enable = '';
		if(($b_pol_switch_mode_val&1) != 0){
			$call_dura_enable = 'checked';
		}
		
		$call_counts_enable = '';
		if(($b_pol_switch_mode_val&2) != 0){
			$call_counts_enable = 'checked';
		}
		
		$sim_use_dura_enable = '';
		if(($b_pol_switch_mode_val&4) != 0){
			$sim_use_dura_enable = 'checked';
		}
		
		$sms_result_counts_enable = '';
		if(($b_pol_switch_mode_val&8) != 0){
			$sms_result_counts_enable = 'checked';
		}
		
		$sms_report_counts_enable = '';
		if(($b_pol_switch_mode_val&16) != 0){
			$sms_report_counts_enable = 'checked';
		}
		
		$answer_counts_enable = '';
		if(($b_pol_switch_mode_val&32) != 0){
			$answer_counts_enable = 'checked';
		}
		
		$n_pol_switch_call_dura = $strategy_info['n_pol_switch_call_dura'];
		$n_pol_switch_call_counts = $strategy_info['n_pol_switch_call_counts'];
		$n_pol_switch_sim_use_dura = $strategy_info['n_pol_switch_sim_use_dura'];
		$n_pol_switch_sms_result_counts = $strategy_info['n_pol_switch_sms_result_counts'];
		$n_pol_switch_sms_report_counts = $strategy_info['n_pol_switch_sms_report_counts'];
		$n_pol_switch_call_anscounts = $strategy_info['n_pol_switch_call_anscounts'];
		$n_pol_sleep_time = $strategy_info['n_pol_sleep_time'];
		
		$b_pol_limit_switch_mode_val = $strategy_info['b_pol_limit_switch_mode'];
		$b_pol_limit_switch_mode = '';
		if($b_pol_limit_switch_mode_val != 0){
			$b_pol_limit_switch_mode = 'checked';
		}
		
		$counts_hour_enable = '';
		if(($b_pol_limit_switch_mode_val&1) != 0){
			$counts_hour_enable = 'checked';
		}
		
		$counts_day_enable = '';
		if(($b_pol_limit_switch_mode_val&2) != 0){
			$counts_day_enable = 'checked';
		}
		
		$receive_day_enable = '';
		if(($b_pol_limit_switch_mode_val&4) != 0){
			$receive_day_enable = 'checked';
		}
		
		$call_time_day_enable = '';
		if(($b_pol_limit_switch_mode_val&8) != 0){
			$call_time_day_enable = 'checked';
		}
		
		$call_time_month_enable = '';
		if(($b_pol_limit_switch_mode_val&16) != 0){
			$call_time_month_enable = 'checked';
		}
		
		$sms_counts_day_enable = '';
		if(($b_pol_limit_switch_mode_val&32) != 0){
			$sms_counts_day_enable = 'checked';
		}
		
		$sms_report_counts_day_enable = '';
		if(($b_pol_limit_switch_mode_val&64) != 0){
			$sms_report_counts_day_enable = 'checked';
		}
		
		$n_pol_max_call_counts_hour = $strategy_info['n_pol_max_call_counts_hour'];
		$n_pol_max_call_counts_day = $strategy_info['n_pol_max_call_counts_day'];
		$n_pol_max_call_receive_day = $strategy_info['n_pol_max_call_receive_day'];
		$n_pol_max_call_call_time_day = $strategy_info['n_pol_max_call_call_time_day'];
		$n_pol_max_call_time_month = $strategy_info['n_pol_max_call_time_month'];
		$n_pol_max_sms_counts_day = $strategy_info['n_pol_max_sms_counts_day'];
		$n_pol_max_sms_report_counts_day = $strategy_info['n_pol_max_sms_report_counts_day'];
		$n_clear_month_day = $strategy_info['n_clear_month_day'];
		$b_pol_fresh_mode = $strategy_info['b_pol_fresh_mode'];
	}
?>
<script type="text/javascript" src="/js/check.js"></script>
<script type="text/javascript" src="/js/jquery.ibutton.js"></script> 
<link type="text/css" href="/css/jquery.ibutton.css" rel="stylesheet" media="all" />
<form enctype="multipart/form-data" action="<?php echo get_self();?>" method="post">
	<input type="hidden" name="id" id="id" value="<?php echo $id;?>" />
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Policy');?></li>
		<li class="tb2">&nbsp;</li>
	</div>
	
	<table width="100%" class="tedit">
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Policy Name');?>:
					<span class="showhelp">
					<?php echo language('Policy Name help','Terms used to describe strategies');?>
					</span>
				</div>
			</th>
			<td>
				<input type="text" name="ob_pol_name" id="ob_pol_name" value="<?php echo $ob_pol_name;?>" />
				<input type="hidden" name="old_ob_pol_name" id="old_ob_pol_name" value="<?php echo $ob_pol_name;?>" />
				<span id="cob_pol_name"></span>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Alloc Condition');?>:
					<span class="showhelp">
					<?php echo language('Alloc Condition help','Sequence of SIM cards allocated for the first time after successful docking between gateway and simbank');?>
					</span>
				</div>
			</th>
			<td>
				<select name="n_pol_select_order" id="n_pol_select_order" >
					<option value="1" <?php if($n_pol_select_order == 1) echo 'selected';?>><?php echo language('Ascending');?></option>
					<option value="2" <?php if($n_pol_select_order == 2) echo 'selected';?>><?php echo language('Descending');?></option>
					<option value="3" <?php if($n_pol_select_order == 3) echo 'selected';?>><?php echo language('Random');?></option>
					<option value="4" <?php if($n_pol_select_order == 4) echo 'selected';?>><?php echo language('Max Unused Time');?></option>
					<option value="5" <?php if($n_pol_select_order == 5) echo 'selected';?>><?php echo language('Min Call Time');?></option>
					<option value="6" <?php if($n_pol_select_order == 6) echo 'selected';?>><?php echo language('Max Call Time');?></option>
					<option value="7" <?php if($n_pol_select_order == 7) echo 'selected';?>><?php echo language('Min Call Count');?></option>
					<option value="8" <?php if($n_pol_select_order == 8) echo 'selected';?>><?php echo language('Max Call count');?></option>
				</select>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Swtich Condition');?>:
					<span class="showhelp">
					<?php echo language('Swtich Condition help','When the use of a SIM card meets one of the following conditions, simbank automatically cancels the SIM card to sleep, and selects other idle SIM cards to redistribute to the gateway.');?>
					</span>
				</div>
			</th>
			<td>
				<input type="checkbox" name="b_pol_switch_mode" id="b_pol_switch_mode" <?php echo $b_pol_switch_mode;?> />
				<span id="cb_pol_switch_mode"></span>
			</td>
		</tr>
		
		<tbody id="switch_card">
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Switchover by Max Call Time Once');?>:
						<span class="showhelp">
						<?php echo language('Switchover by Max Call Time Once help','Check the SIM card when the total exhalation and connection time of the SIM card reaches the set time.');?>
						</span>
					</div>
				</th>
				<td>
					<div style="float:left;margin-right:20px;">
						<input type="checkbox" name="call_dura_enable" id="call_dura_enable" <?php echo $call_dura_enable;?>/>
					</div>
					<div id="call_dura">
						<input type="text" name="n_pol_switch_call_dura" id="n_pol_switch_call_dura" value="<?php echo $n_pol_switch_call_dura;?>" />
						<?php echo language('minutes');?>
						<span id="cn_pol_switch_call_dura"></span>
					</div>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Switchover by Max Call Count Once');?>:
						<span class="showhelp">
						<?php echo language('Switchover by Max Call Count Once help','When the number of times the SIM card is exhaled and connected reaches the set value, the SIM card is cut.');?>
						</span>
					</div>
				</th>
				<td>
					<div style="float:left;margin-right:20px;">
						<input type="checkbox" name="call_counts_enable" id="call_counts_enable" <?php echo $call_counts_enable;?> />
					</div>
					<div id="call_counts">
						<input type="text" name="n_pol_switch_call_counts" id="n_pol_switch_call_counts" value="<?php echo $n_pol_switch_call_counts;?>" />
						<span id="cn_pol_switch_call_counts"></span>
					</div>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Switchover by Max Answer Count Once');?>:
						<span class="showhelp">
						<?php echo language('Switchover by Max Answer Count Once help','When the number of times the SIM card is answered reaches the set value, the SIM card is cut.');?>
						</span>
					</div>
				</th>
				<td>
					<div style="float:left;margin-right:20px;">
						<input type="checkbox" name="answer_counts_enable" id="answer_counts_enable" <?php echo $answer_counts_enable;?> />
					</div>
					<div id="answer_counts">
						<input type="text" name="n_pol_switch_call_anscounts" id="n_pol_switch_call_anscounts" value="<?php echo $n_pol_switch_call_anscounts;?>" />
						<span id="cn_pol_switch_call_anscounts"></span>
					</div>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Switchover by Work Time Once');?>:
						<span class="showhelp">
						<?php echo language('Switchover by Work Time Once help','From the SIM card allocation to the gateway start timing, after the set time is reached, the card is cut.');?>
						</span>
					</div>
				</th>
				<td>
					<div style="float:left;margin-right:20px;">
						<input type="checkbox" name="sim_use_dura_enable" id="sim_use_dura_enable" <?php echo $sim_use_dura_enable;?> />
					</div>
					<div id="sim_use_dura">
						<input type="text" name="n_pol_switch_sim_use_dura" id="n_pol_switch_sim_use_dura" value="<?php echo $n_pol_switch_sim_use_dura;?>" />
						<?php echo language('minutes');?>
						<span id="cn_pol_switch_sim_use_dura"></span>
					</div>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Switchover by SMS Count');?>:
						<span class="showhelp">
						<?php echo language('Switchover by SMS Count help','Cut the SIM card when the number of SMS sent by the SIM card reaches the set number of SMS');?>
						</span>
					</div>
				</th>
				<td>
					<div style="float:left;margin-right:20px;">
						<input type="checkbox" name="sms_result_counts_enable" id="sms_result_counts_enable" <?php echo $sms_result_counts_enable;?> />
					</div>
					<div id="sms_result_counts">
						<input type="text" name="n_pol_switch_sms_result_counts" id="n_pol_switch_sms_result_counts" value="<?php echo $n_pol_switch_sms_result_counts;?>" />
						<span id="cn_pol_switch_sms_result_counts"></span>
					</div>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Switchover by SMS Report Count');?>:
						<span class="showhelp">
						<?php echo language('Switchover by SMS Report Count help','Cut the SIM card when the number of SMS reports received by the SIM card reaches the set number of SMS reports');?>
						</span>
					</div>
				</th>
				<td>
					<div style="float:left;margin-right:20px;">
						<input type="checkbox" name="sms_report_counts_enable" id="sms_report_counts_enable" <?php echo $sms_report_counts_enable;?> />
					</div>
					<div id="sms_report_counts">
						<input type="text" name="n_pol_switch_sms_report_counts" id="n_pol_switch_sms_report_counts" value="<?php echo $n_pol_switch_sms_report_counts;?>" />
						<span id="cn_pol_switch_sms_report_counts"></span>
					</div>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Sleep time of switch cards');?>:
						<span class="showhelp">
						<?php echo language('Sleep time of switch cards help');?>
						</span>
					</div>
				</th>
				<td>
					<input type="text" name="n_pol_sleep_time" id="n_pol_sleep_time" value="<?php echo $n_pol_sleep_time;?>" />
					<?php echo language('Seconds');?>
					<span id="cn_pol_sleep_time"></span>
				</td>
			</tr>
		</tbody>	
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Call Limit');?>:
					<span class="showhelp">
					<?php echo language('Call Limit help','When the use of a SIM card meets one of the following conditions, simbank will automatically lock the SIM card into a locked state,<br/> and select other idle SIM cards to redistribute to the gateway. The locked SIM cards can be redistributed after unlocking.');?>
					</span>
				</div>
			</th>
			<td>
				<input type="checkbox" name="b_pol_limit_switch_mode" id="b_pol_limit_switch_mode" <?php echo $b_pol_limit_switch_mode;?> />
				<span id="cb_pol_limit_switch_mode"></span>
			</td>
		</tr>
		
		<tbody id="call_limit">
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Limit by Max Call count hour');?>:
						<span class="showhelp">
						<?php echo language('Limit by Max Call count hour help','When the call count (outgoing and answered) per hour reaches the set value, the card will be locked until to the next whole point.');?>
						</span>
					</div>
				</th>
				<td>
					<div style="float:left;margin-right:20px;">
						<input type="checkbox" name="counts_hour_enable" id="counts_hour_enable" <?php echo $counts_hour_enable;?> />
					</div>
					<div id="counts_hour">
						<input type="text" name="n_pol_max_call_counts_hour" id="n_pol_max_call_counts_hour" value="<?php echo $n_pol_max_call_counts_hour;?>" />
						<span id="cn_pol_max_call_counts_hour"></span>
					</div>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Limit by Max Call Count day');?>:
						<span class="showhelp">
						<?php echo language('Limit by Max Call Count day help','When the number of breaths per day (whether connected or not) of the SIM card reaches the set value, the SIM card is locked and unlocked in the morning of the next day.');?>
						</span>
					</div>
				</th>
				<td>
					<div style="float:left;margin-right:20px;">
						<input type="checkbox" name="counts_day_enable" id="counts_day_enable" <?php echo $counts_day_enable;?> />
					</div>
					<div id="counts_day">
						<input type="text" name="n_pol_max_call_counts_day" id="n_pol_max_call_counts_day" value="<?php echo $n_pol_max_call_counts_day;?>" oninput="n_pol_max_call_counts_day_change(this)" />
						<span id="cn_pol_max_call_counts_day"></span>
					</div>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Limit by Max Call Receive day');?>:
						<span class="showhelp">
						<?php echo language('Limit by Max Call Receive day help','The SIM card is unlocked in the morning when the number of breaths and connections per day reaches the set value.');?>
						</span>
					</div>
				</th>
				<td>
					<div style="float:left;margin-right:20px;">
						<input type="checkbox" name="receive_day_enable" id="receive_day_enable" <?php echo $receive_day_enable;?> />
					</div>
					<div id="receive_day">
						<input type="text" name="n_pol_max_call_receive_day" id="n_pol_max_call_receive_day" value="<?php echo $n_pol_max_call_receive_day;?>" />
						<span id="cn_pol_max_call_receive_day"></span>
					</div>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Limit by Max Call time day');?>:
						<span class="showhelp">
						<?php echo language('Limit by Max Call time day help','When the SIM card is totally exhaled and connected every day for as long as the set time, the lock card is unlocked in the morning of the next day.');?>
						</span>
					</div>
				</th>
				<td>
					<div style="float:left;margin-right:20px;">
						<input type="checkbox" name="call_time_day_enable" id="call_time_day_enable" <?php echo $call_time_day_enable;?> />
					</div>
					<div id="call_time_day">
						<input type="text" name="n_pol_max_call_call_time_day" id="n_pol_max_call_call_time_day" value="<?php echo $n_pol_max_call_call_time_day;?>" />
						<?php echo language('minutes');?>
						<span id="cn_pol_max_call_call_time_day"></span>
					</div>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Limit by Max Call time month');?>:
						<span class="showhelp">
						<?php echo language('Limit by Max Call time month help','When the SIM card is totally exhaled and connected every month, it will be locked up to the set time; unlock the SIM card in the early morning of the set date.');?>
						</span>
					</div>
				</th>
				<td>
					<div style="float:left;margin-right:20px;">
						<input type="checkbox" name="call_time_month_enable" id="call_time_month_enable" <?php echo $call_time_month_enable;?> />
					</div>
					<div id="call_time_month">
						<input type="text" name="n_pol_max_call_time_month" id="n_pol_max_call_time_month" value="<?php echo $n_pol_max_call_time_month;?>" />
						<?php echo language('minutes');?>
						<span id="cn_pol_max_call_time_month"></span>
						<button type="button" id="reset_limit" style="margin-left:10px;"><?php echo language('Reset');?></button>
					</div>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Limit by Max SMS Count Day');?>:
						<span class="showhelp">
						<?php echo language('Limit by Max SMS Count Day help','Limit by Max SMS Count Day');?>
						</span>
					</div>
				</th>
				<td>
					<div style="float:left;margin-right:20px;">
						<input type="checkbox" name="sms_counts_day_enable" id="sms_counts_day_enable" <?php echo $sms_counts_day_enable;?> />
					</div>
					<div id="sms_counts_day">
						<input type="text" name="n_pol_max_sms_counts_day" id="n_pol_max_sms_counts_day" value="<?php echo $n_pol_max_sms_counts_day;?>" />
						<span id="cn_pol_max_sms_counts_day"></span>
					</div>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Limit by Max SMS Report Count Day');?>:
						<span class="showhelp">
						<?php echo language('Limit by Max SMS Report Count Day help','Limit by Max SMS Report Count Day');?>
						</span>
					</div>
				</th>
				<td>
					<div style="float:left;margin-right:20px;">
						<input type="checkbox" name="sms_report_counts_day_enable" id="sms_report_counts_day_enable" <?php echo $sms_report_counts_day_enable;?> />
					</div>
					<div id="sms_report_counts_day">
						<input type="text" name="n_pol_max_sms_report_counts_day" id="n_pol_max_sms_report_counts_day" value="<?php echo $n_pol_max_sms_report_counts_day;?>" />
						<span id="cn_pol_max_sms_report_counts_day"></span>
					</div>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Reset Day');?>:
						<span class="showhelp">
						<?php echo language('Reset Day help','Choose the date to remove the monthly call time limit.');?>
						</span>
					</div>
				</th>
				<td>
					<select name="n_clear_month_day" id="n_clear_month_day">
						<?php 
						for($i=1;$i<=31;$i++){
							$selected = '';
							if($n_clear_month_day == $i) $selected = 'selected';
						?>
						<option value="<?php echo $i;?>" <?php echo $selected;?>><?php echo $i;?></option>
						<?php }?>
					</select>
				</td>
			</tr>
		</tbody>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Sim Card Refresh Mode');?>:
					<span class="showhelp">
					<?php echo language('Sim Card Refresh Mode help','Select refresh mode when SIM card enters SIM group.');?>
					</span>
				</div>
			</th>
			<td>
				<input type="radio" name="b_pol_fresh_mode" value="0" checked /><?php echo language('Clear Status and Data');?>
				<input type="radio" name="b_pol_fresh_mode" value="1" <?php if($b_pol_fresh_mode == 1) echo 'checked';?>/><?php echo language('Hold Status and Data');?>
				<input type="radio" name="b_pol_fresh_mode" value="2" <?php if($b_pol_fresh_mode == 2) echo 'checked';?>/><?php echo language('Clear Status Only');?>
			</td>
		</tr>
	</table>
	
	<div id="newline"></div>

	<table id="float_btn" class="float_btn">
		<tr id="float_btn_tr" class="float_btn_tr">
			<td>
				<input type="hidden" name="send" id="send" value="" />
				<input type="submit" style="margin-right:15px;" value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save';return check();" />
			</td>
		</tr>
	</table>
</form>

<script>
$(function(){
	$("#b_pol_switch_mode").iButton();
	$("#b_pol_limit_switch_mode").iButton();
	$("#call_dura_enable").iButton();
	$("#call_counts_enable").iButton();
	$("#answer_counts_enable").iButton();
	$("#sim_use_dura_enable").iButton();
	$("#counts_hour_enable").iButton();
	$("#counts_day_enable").iButton();
	$("#receive_day_enable").iButton();
	$("#call_time_day_enable").iButton();
	$("#call_time_month_enable").iButton();
	$("#sms_counts_day_enable").iButton();
	$("#sms_report_counts_day_enable").iButton();
	$("#sms_result_counts_enable").iButton();
	$("#sms_report_counts_enable").iButton();
	
	if(document.getElementById('b_pol_switch_mode').checked){
		$("#switch_card").show();
	}else{
		$("#switch_card").hide();
	}
	
	if(document.getElementById('b_pol_limit_switch_mode').checked){
		$("#call_limit").show();
	}else{
		$("#call_limit").hide();
	}
});

$("#b_pol_switch_mode").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#switch_card").show();
	}else{
		$("#switch_card").hide();
	}
});

$("#b_pol_limit_switch_mode").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#call_limit").show();
	}else{
		$("#call_limit").hide();
	}
});

$("#reset_limit").click(function(){
	var old_ob_pol_name = document.getElementById('old_ob_pol_name').value;
	
	$.ajax({
		type:'GET',
		url:'ajax_server_new.php?action=reset_limit&ob_pol_name='+old_ob_pol_name,
		success:function(data){
			if(data == 'success'){
				alert('<?php echo language('Reset Success');?>');
			}else{
				alert('<?php echo language('Reset error');?>');
			}
		},
		error:function(){
			alert('<?php echo language('Reset error');?>');
		}
	});
});

function n_pol_max_call_counts_day_change(that){
	document.getElementById('cn_pol_max_call_counts_day').innerHTML = "";
	if(that.value > 20){
		document.getElementById('cn_pol_max_call_counts_day').innerHTML = con_str("<?php echo language("Max Call Count tip","If the number of times is too large, the card may be blocked");?>");
	}
}

function check(){
	document.getElementById('cob_pol_name').innerHTML = '';
	if(document.getElementById('ob_pol_name').value == ''){
		document.getElementById('cob_pol_name').innerHTML = con_str("Can not be null.");
		return false;
	}
	
	<?php 
	$res = $db->Get('tb_policy_info','ob_pol_name',"where ob_pol_name <> '$ob_pol_name'");
	$js_arr_str = '[';
	while($info = mysqli_fetch_array($res,MYSQLI_ASSOC)){
		$js_arr_str .= '"'.$info['ob_pol_name'].'",';
	}
	$js_arr_str .= ']';
	?>
	
	var strategy_arr = <?php echo $js_arr_str;?>;
	for(var i=0;i<strategy_arr.length;i++){
		if(document.getElementById('ob_pol_name').value == strategy_arr[i]){
			document.getElementById('cob_pol_name').innerHTML = con_str("<?php echo language('Strategy Name already exists.');?>");
			return false;
		}
	}
	
	//b_pol_switch_mode
	var b_pol_switch_mode = document.getElementById('b_pol_switch_mode').checked;
	if(b_pol_switch_mode){
		//n_pol_switch_call_dura
		var call_dura_enable = document.getElementById('call_dura_enable').checked;
		document.getElementById('cn_pol_switch_call_dura').innerHTML = '';
		if(call_dura_enable){
			var n_pol_switch_call_dura = parseInt(document.getElementById('n_pol_switch_call_dura').value);
			if(isNaN(n_pol_switch_call_dura) || n_pol_switch_call_dura < 1 || n_pol_switch_call_dura > 65535){
				document.getElementById('cn_pol_switch_call_dura').innerHTML = con_str('<?php echo language('Range');?>:1-65535');
				return false;
			}
		}
	
		//n_pol_switch_call_counts
		var call_counts_enable = document.getElementById('call_counts_enable').checked;
		document.getElementById('cn_pol_switch_call_counts').innerHTML = '';
		if(call_counts_enable){
			var n_pol_switch_call_counts = parseInt(document.getElementById('n_pol_switch_call_counts').value);
			if(isNaN(n_pol_switch_call_counts) || n_pol_switch_call_counts < 1 || n_pol_switch_call_counts > 65535){
				document.getElementById('cn_pol_switch_call_counts').innerHTML = con_str('<?php echo language('Range');?>:1-65535');
				return false;
			}
		}
		
		//n_pol_switch_call_anscounts
		var answer_counts_enable = document.getElementById('answer_counts_enable').checked;
		document.getElementById('cn_pol_switch_call_anscounts').innerHTML = '';
		if(answer_counts_enable){
			var n_pol_switch_call_anscounts = parseInt(document.getElementById('n_pol_switch_call_anscounts').value);
			if(isNaN(n_pol_switch_call_anscounts) || n_pol_switch_call_anscounts < 1 || n_pol_switch_call_anscounts > 65535){
				document.getElementById('cn_pol_switch_call_anscounts').innerHTML = con_str('<?php echo language('Range');?>:1-65535');
				return false;
			}
		}
		
		//n_pol_switch_sim_use_dura
		var sim_use_dura_enable = document.getElementById('sim_use_dura_enable').checked;
		document.getElementById('cn_pol_switch_sim_use_dura').innerHTML = '';
		if(sim_use_dura_enable){
			var n_pol_switch_sim_use_dura = parseInt(document.getElementById('n_pol_switch_sim_use_dura').value);
			if(isNaN(n_pol_switch_sim_use_dura) || n_pol_switch_sim_use_dura < 1 || n_pol_switch_sim_use_dura > 65535){
				document.getElementById('cn_pol_switch_sim_use_dura').innerHTML = con_str('<?php echo language('Range');?>:1-65535');
				return false;
			}
		}
		
		//n_pol_switch_sms_result_counts
		var sms_result_counts_enable = document.getElementById('sms_result_counts_enable').checked;
		document.getElementById('cn_pol_switch_sms_result_counts').innerHTML = '';
		if(sms_result_counts_enable){
			var n_pol_switch_sms_result_counts = parseInt(document.getElementById('n_pol_switch_sms_result_counts').value);
			if(isNaN(n_pol_switch_sms_result_counts) || n_pol_switch_sms_result_counts < 1 || n_pol_switch_sms_result_counts > 65535){
				document.getElementById('cn_pol_switch_sms_result_counts').innerHTML = con_str('<?php echo language('Range');?>:1-65535');
				return false;
			}
		}
		
		//n_pol_switch_sms_report_counts
		var sms_report_counts_enable = document.getElementById('sms_report_counts_enable').checked;
		document.getElementById('cn_pol_switch_sms_report_counts').innerHTML = '';
		if(sms_report_counts_enable){
			var n_pol_switch_sms_report_counts = parseInt(document.getElementById('n_pol_switch_sms_report_counts').value);
			if(isNaN(n_pol_switch_sms_report_counts) || n_pol_switch_sms_report_counts < 1 || n_pol_switch_sms_report_counts > 65535){
				document.getElementById('cn_pol_switch_sms_report_counts').innerHTML = con_str('<?php echo language('Range');?>:1-65535');
				return false;
			}
		}
	}
	
	//b_pol_limit_switch_mode
	var b_pol_limit_switch_mode = document.getElementById('b_pol_limit_switch_mode').checked;
	if(b_pol_limit_switch_mode){
		//n_pol_max_call_counts_hour
		var counts_hour_enable = document.getElementById('counts_hour_enable').checked;
		document.getElementById('cn_pol_max_call_counts_hour').innerHTML = '';
		if(counts_hour_enable){
			var n_pol_max_call_counts_hour = parseInt(document.getElementById('n_pol_max_call_counts_hour').value);
			if(isNaN(n_pol_max_call_counts_hour) || n_pol_max_call_counts_hour < 1 || n_pol_max_call_counts_hour > 65535){
				document.getElementById('cn_pol_max_call_counts_hour').innerHTML = con_str('<?php echo language('Range');?>:1-65535');
				return false;
			}
		}
		
		//n_pol_max_call_counts_day
		var counts_day_enable = document.getElementById('counts_day_enable').checked;
		document.getElementById('cn_pol_max_call_counts_day').innerHTML = '';
		if(counts_day_enable){
			var n_pol_max_call_counts_day = parseInt(document.getElementById('n_pol_max_call_counts_day').value);
			if(isNaN(n_pol_max_call_counts_day) || n_pol_max_call_counts_day < 1 || n_pol_max_call_counts_day > 65535){
				document.getElementById('cn_pol_max_call_counts_day').innerHTML = con_str('<?php echo language('Range');?>:1-65535');
				return false;
			}
		}
		
		//n_pol_max_call_receive_day
		var receive_day_enable = document.getElementById('receive_day_enable').checked;
		document.getElementById('cn_pol_max_call_receive_day').innerHTML = '';
		if(receive_day_enable){
			var n_pol_max_call_receive_day = parseInt(document.getElementById('n_pol_max_call_receive_day').value);
			if(isNaN(n_pol_max_call_receive_day) || n_pol_max_call_receive_day < 1 || n_pol_max_call_receive_day > 65535){
				document.getElementById('cn_pol_max_call_receive_day').innerHTML = con_str('<?php echo language('Range');?>:1-65535');
				return false;
			}
		}
		
		//n_pol_max_call_call_time_day
		var call_time_day_enable = document.getElementById('call_time_day_enable').checked;
		document.getElementById('cn_pol_max_call_call_time_day').innerHTML = '';
		if(call_time_day_enable){
			var n_pol_max_call_call_time_day = parseInt(document.getElementById('n_pol_max_call_call_time_day').value);
			if(isNaN(n_pol_max_call_call_time_day) || n_pol_max_call_call_time_day < 1 || n_pol_max_call_call_time_day > 65535){
				document.getElementById('cn_pol_max_call_call_time_day').innerHTML = con_str('<?php echo language('Range');?>:1-65535');
				return false;
			}
		}
		
		//n_pol_max_call_time_month
		var call_time_month_enable = document.getElementById('call_time_month_enable').checked;
		document.getElementById('cn_pol_max_call_time_month').innerHTML = '';
		if(call_time_month_enable){
			var n_pol_max_call_time_month = parseInt(document.getElementById('n_pol_max_call_time_month').value);
			if(isNaN(n_pol_max_call_time_month) || n_pol_max_call_time_month < 1 || n_pol_max_call_time_month > 65535){
				document.getElementById('cn_pol_max_call_time_month').innerHTML = con_str('<?php echo language('Range');?>:1-65535');
				return false;
			}
		}
	}
	
	return true;
}

//show enable
if(document.getElementById('call_dura_enable').checked){
	$("#call_dura").show();
}else{
	$("#call_dura").hide();
}
$("#call_dura_enable").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#call_dura").show();
	}else{
		$("#call_dura").hide();
	}
});

if(document.getElementById('call_counts_enable').checked){
	$("#call_counts").show();
}else{
	$("#call_counts").hide();
}
$("#call_counts_enable").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#call_counts").show();
	}else{
		$("#call_counts").hide();
	}
});

if(document.getElementById('answer_counts_enable').checked){
	$("#answer_counts").show();
}else{
	$("#answer_counts").hide();
}
$("#answer_counts_enable").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#answer_counts").show();
	}else{
		$("#answer_counts").hide();
	}
});

if(document.getElementById('sim_use_dura_enable').checked){
	$("#sim_use_dura").show();
}else{
	$("#sim_use_dura").hide();
}
$("#sim_use_dura_enable").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#sim_use_dura").show();
	}else{
		$("#sim_use_dura").hide();
	}
});

if(document.getElementById('sms_result_counts_enable').checked){
	$("#sms_result_counts").show();
}else{
	$("#sms_result_counts").hide();
}
$("#sms_result_counts_enable").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#sms_result_counts").show();
	}else{
		$("#sms_result_counts").hide();
	}
});

if(document.getElementById('sms_report_counts_enable').checked){
	$("#sms_report_counts").show();
}else{
	$("#sms_report_counts").hide();
}
$("#sms_report_counts_enable").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#sms_report_counts").show();
	}else{
		$("#sms_report_counts").hide();
	}
})

if(document.getElementById('counts_hour_enable').checked){
	$("#counts_hour").show();
}else{
	$("#counts_hour").hide();
}
$("#counts_hour_enable").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#counts_hour").show();
	}else{
		$("#counts_hour").hide();
	}
});

if(document.getElementById('counts_day_enable').checked){
	$("#counts_day").show();
}else{
	$("#counts_day").hide();
}
$("#counts_day_enable").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#counts_day").show();
	}else{
		$("#counts_day").hide();
	}
	
	var n_pol_max_call_counts_day = document.getElementById('n_pol_max_call_counts_day').value;
	if(n_pol_max_call_counts_day > 20){
		document.getElementById('cn_pol_max_call_counts_day').innerHTML = con_str("<?php echo language("Max Call Count tip","If the number of times is too large, the card may be blocked");?>");
	}
});

if(document.getElementById('receive_day_enable').checked){
	$("#receive_day").show();
}else{
	$("#receive_day").hide();
}
$("#receive_day_enable").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#receive_day").show();
	}else{
		$("#receive_day").hide();
	}
});

if(document.getElementById('call_time_day_enable').checked){
	$("#call_time_day").show();
}else{
	$("#call_time_day").hide();
}
$("#call_time_day_enable").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#call_time_day").show();
	}else{
		$("#call_time_day").hide();
	}
});

if(document.getElementById('call_time_month_enable').checked){
	$("#call_time_month").show();
}else{
	$("#call_time_month").hide();
}
$("#call_time_month_enable").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#call_time_month").show();
	}else{
		$("#call_time_month").hide();
	}
});

if(document.getElementById('sms_counts_day_enable').checked){
	$("#sms_counts_day").show();
}else{
	$("#sms_counts_day").hide();
}
$("#sms_counts_day_enable").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#sms_counts_day").show();
	}else{
		$("#sms_counts_day").hide();
	}
});

if(document.getElementById('sms_report_counts_day_enable').checked){
	$("#sms_report_counts_day").show();
}else{
	$("#sms_report_counts_day").hide();
}
$("#sms_report_counts_day_enable").change(function(){
	if($(this).attr('checked') == 'checked'){
		$("#sms_report_counts_day").show();
	}else{
		$("#sms_report_counts_day").hide();
	}
});

</script>
<?php
}

function save_strategy(){
	global $db;
	
	$ob_pol_name = $_POST['ob_pol_name'];
	
	$n_pol_select_order = $_POST['n_pol_select_order'];
	
	if(isset($_POST['call_dura_enable'])){
		$call_dura_enable = 1;
	}else{
		$call_dura_enable = 0;
	}
	
	if($_POST['n_pol_switch_call_dura'] != ""){
		$n_pol_switch_call_dura = $_POST['n_pol_switch_call_dura'];
	}else{
		$n_pol_switch_call_dura = 0;
	}
	
	if(isset($_POST['call_counts_enable'])){
		$call_counts_enable = 2;
	}else{
		$call_counts_enable = 0;
	}
	
	if($_POST['n_pol_switch_call_counts'] != ""){
		$n_pol_switch_call_counts = $_POST['n_pol_switch_call_counts'];
	}else{
		$n_pol_switch_call_counts = 0;
	}
	
	if(isset($_POST['sim_use_dura_enable'])){
		$sim_use_dura_enable = 4;
	}else{
		$sim_use_dura_enable = 0;
	}
	
	if($_POST['n_pol_switch_sim_use_dura'] != ""){
		$n_pol_switch_sim_use_dura = $_POST['n_pol_switch_sim_use_dura'];
	}else{
		$n_pol_switch_sim_use_dura = 0;
	}
	
	if(isset($_POST['sms_result_counts_enable'])){
		$sms_result_counts_enable = 8;
	}else{
		$sms_result_counts_enable = 0;
	}
	
	if($_POST['n_pol_switch_sms_result_counts'] != ""){
		$n_pol_switch_sms_result_counts = $_POST['n_pol_switch_sms_result_counts'];
	}else{
		$n_pol_switch_sms_result_counts = 0;
	}
	
	if(isset($_POST['sms_report_counts_enable'])){
		$sms_report_counts_enable = 16;
	}else{
		$sms_report_counts_enable = 0;
	}
	
	if($_POST['n_pol_switch_sms_report_counts'] != ""){
		$n_pol_switch_sms_report_counts = $_POST['n_pol_switch_sms_report_counts'];
	}else{
		$n_pol_switch_sms_report_counts = 0;
	}
	
	if(isset($_POST['answer_counts_enable'])){
		$answer_counts_enable = 32;
	}else{
		$answer_counts_enable = 0;
	}
	
	if($_POST['n_pol_switch_call_anscounts'] != ""){
		$n_pol_switch_call_anscounts = $_POST['n_pol_switch_call_anscounts'];
	}else{
		$n_pol_switch_call_anscounts = 0;
	}
	
	if(isset($_POST['b_pol_switch_mode'])){
		$b_pol_switch_mode = $call_dura_enable + $call_counts_enable + $sim_use_dura_enable + $sms_result_counts_enable + $sms_report_counts_enable + $answer_counts_enable;
	}else{
		$b_pol_switch_mode = 0;
	}
	
	if($_POST['n_pol_sleep_time'] != ""){
		$n_pol_sleep_time = $_POST['n_pol_sleep_time'];
	}else{
		$n_pol_sleep_time = 30;
	}
	
	if(isset($_POST['counts_hour_enable'])){
		$counts_hour_enable = 1;
	}else{
		$counts_hour_enable = 0;
	}
	
	if($_POST['n_pol_max_call_counts_hour'] != ""){
		$n_pol_max_call_counts_hour = $_POST['n_pol_max_call_counts_hour'];
	}else{
		$n_pol_max_call_counts_hour = 666;
	}
	
	if(isset($_POST['counts_day_enable'])){
		$counts_day_enable = 2;
	}else{
		$counts_day_enable = 0;
	}
	
	if($_POST['n_pol_max_call_counts_day'] != ""){
		$n_pol_max_call_counts_day = $_POST['n_pol_max_call_counts_day'];
	}else{
		$n_pol_max_call_counts_day = 1025;
	}
	
	if(isset($_POST['receive_day_enable'])){
		$receive_day_enable = 4;
	}else{
		$receive_day_enable = 0;
	}
	
	if($_POST['n_pol_max_call_receive_day'] != ""){
		$n_pol_max_call_receive_day = $_POST['n_pol_max_call_receive_day'];
	}else{
		$n_pol_max_call_receive_day = 1992;
	}
	
	if(isset($_POST['call_time_day_enable'])){
		$call_time_day_enable = 8;
	}else{
		$call_time_day_enable = 0;
	}
	
	if($_POST['n_pol_max_call_call_time_day'] != ""){
		$n_pol_max_call_call_time_day = $_POST['n_pol_max_call_call_time_day'];
	}else{
		$n_pol_max_call_call_time_day = 825;
	}
	
	if(isset($_POST['call_time_month_enable'])){
		$call_time_month_enable = 16;
	}else{
		$call_time_month_enable = 0;
	}
	
	if($_POST['n_pol_max_call_time_month'] != ""){
		$n_pol_max_call_time_month = $_POST['n_pol_max_call_time_month'];
	}else{
		$n_pol_max_call_time_month = 123138;
	}
	
	if(isset($_POST['sms_counts_day_enable'])){
		$sms_counts_day_enable = 32;
	}else{
		$sms_counts_day_enable = 0;
	}
	
	if($_POST['n_pol_max_sms_counts_day'] != ""){
		$n_pol_max_sms_counts_day = $_POST['n_pol_max_sms_counts_day'];
	}else{
		$n_pol_max_sms_counts_day = 1000;
	}
	
	if(isset($_POST['sms_report_counts_day_enable'])){
		$sms_report_counts_day_enable = 64;
	}else{
		$sms_report_counts_day_enable = 0;
	}
	
	if($_POST['n_pol_max_sms_report_counts_day'] != ""){
		$n_pol_max_sms_report_counts_day = $_POST['n_pol_max_sms_report_counts_day'];
	}else{
		$n_pol_max_sms_report_counts_day = 1000;
	}
	
	if(isset($_POST['b_pol_limit_switch_mode'])){
		$b_pol_limit_switch_mode = $counts_hour_enable + $counts_day_enable + $receive_day_enable + $call_time_day_enable + $call_time_month_enable + $sms_counts_day_enable + $sms_report_counts_day_enable;
	}else{
		$b_pol_limit_switch_mode = 0;
	}
	
	// if($b_pol_limit_switch_mode != 0 && $call_time_month_enable == 0){
		// $n_pol_max_call_time_month = 9527;
	// }
	
	if($_POST['n_clear_month_day'] != ""){
		$n_clear_month_day = $_POST['n_clear_month_day'];
	}else{
		$n_clear_month_day = 1;
	}
	
	if($_POST['b_pol_fresh_mode'] != ""){
		$b_pol_fresh_mode = $_POST['b_pol_fresh_mode'];
	}else{
		$b_pol_fresh_mode = 0;
	}
	
	$id = $_POST['id'];
	if($id != ''){
		$fields = "ob_pol_name='$ob_pol_name',n_pol_select_order='$n_pol_select_order',b_pol_switch_mode='$b_pol_switch_mode',n_pol_switch_call_dura='$n_pol_switch_call_dura',n_pol_switch_call_counts='$n_pol_switch_call_counts',n_pol_switch_sim_use_dura='$n_pol_switch_sim_use_dura',n_pol_sleep_time='$n_pol_sleep_time',b_pol_limit_switch_mode='$b_pol_limit_switch_mode',n_pol_max_call_counts_hour='$n_pol_max_call_counts_hour',n_pol_max_call_counts_day='$n_pol_max_call_counts_day',n_pol_max_call_receive_day='$n_pol_max_call_receive_day',n_pol_max_call_call_time_day='$n_pol_max_call_call_time_day',n_pol_max_call_time_month='$n_pol_max_call_time_month',n_clear_month_day='$n_clear_month_day',b_pol_fresh_mode='$b_pol_fresh_mode',n_pol_max_sms_counts_day='$n_pol_max_sms_counts_day',n_pol_max_sms_report_counts_day='$n_pol_max_sms_report_counts_day',n_pol_switch_sms_result_counts='$n_pol_switch_sms_result_counts',n_pol_switch_sms_report_counts='$n_pol_switch_sms_report_counts',n_pol_switch_call_anscounts='$n_pol_switch_call_anscounts'";
		$condition = "where id = '$id'";
		$db->Set('tb_policy_info',$fields,$condition);
		
		$old_ob_pol_name = $_POST['old_ob_pol_name'];
		
		$db->Set('tb_group_info',"ob_policy_name='$ob_pol_name'","where ob_policy_name='$old_ob_pol_name'");
		
		$params = array(
			'policyname' => $old_ob_pol_name,
			'policy' => array('selectorder'=>$n_pol_select_order,
				'switchmode'=>$b_pol_switch_mode,
				'lockswitchmode'=>$b_pol_limit_switch_mode,
				'datefreshmode'=>$b_pol_fresh_mode,
				'polname'=>$ob_pol_name,
				'switchmaxcalldura'=>$n_pol_switch_call_dura,
				'switchmaxcallcounts'=>$n_pol_switch_call_counts,
				'switchmaxsmsresultcounts'=>$n_pol_switch_sms_result_counts,
				'switchmaxsmsreportcounts'=>$n_pol_switch_sms_report_counts,
				'switchmaxcallanscounts'=>$n_pol_switch_call_anscounts,
				'swtichsimusedura'=>$n_pol_switch_sim_use_dura,
				'sleeptime'=>$n_pol_sleep_time,
				'lockmaxcallcountshour'=>$n_pol_max_call_counts_hour,
				'lockmaxcallcountsday'=>$n_pol_max_call_counts_day,
				'lockmaxcallreceiveday'=>$n_pol_max_call_receive_day,
				'lockmaxcalltimeday'=>$n_pol_max_call_call_time_day,
				'lockmaxcalltimemonth'=>$n_pol_max_call_time_month,
				'lockmaxsmscountsday'=>$n_pol_max_sms_counts_day,
				'lockmaxsmsreportcountsday'=>$n_pol_max_sms_report_counts_day,
				'monthdataclearday'=>$n_clear_month_day
			)
		);
		
		$xml = get_xml('SBKPolicyUpdate',$params);
		$wsdl = "http://127.0.0.1:8888/?wsdl";
		$client = new SoapClient($wsdl);
		$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
	}else{
		$fields = "ob_pol_name,n_pol_select_order,b_pol_switch_mode,n_pol_switch_call_dura,n_pol_switch_call_counts,n_pol_switch_sim_use_dura,n_pol_sleep_time,b_pol_limit_switch_mode,n_pol_max_call_counts_hour,n_pol_max_call_counts_day,n_pol_max_call_receive_day,n_pol_max_call_call_time_day,n_pol_max_call_time_month,n_clear_month_day,b_pol_fresh_mode,n_pol_max_sms_counts_day,n_pol_max_sms_report_counts_day,n_pol_switch_sms_result_counts,n_pol_switch_sms_report_counts,n_pol_switch_call_anscounts";
		$values = "'$ob_pol_name','$n_pol_select_order','$b_pol_switch_mode','$n_pol_switch_call_dura','$n_pol_switch_call_counts','$n_pol_switch_sim_use_dura','$n_pol_sleep_time','$b_pol_limit_switch_mode','$n_pol_max_call_counts_hour','$n_pol_max_call_counts_day','$n_pol_max_call_receive_day','$n_pol_max_call_call_time_day','$n_pol_max_call_time_month','$n_clear_month_day','$b_pol_fresh_mode','$n_pol_max_sms_counts_day','$n_pol_max_sms_report_counts_day','$n_pol_switch_sms_result_counts','$n_pol_switch_sms_report_counts','$n_pol_switch_call_anscounts'";
		$db->Add('tb_policy_info',$fields,$values);
	}
}

function del_strategy(){
	global $db;
	global $tmp_db;
	
	$id = $_POST['id'];
	$ob_pol_name = $_POST['ob_pol_name'];
	
	$condition = "id = '$id'";
	
	$db->Del('tb_policy_info',$condition);
	
	$condition = "where ob_policy_name='$ob_pol_name'";
	$data = $db->Get('tb_group_info','*',$condition);
	$ob_grp_name_info = mysqli_fetch_array($data,MYSQLI_ASSOC);
	$ob_grp_name = $ob_grp_name_info['ob_grp_name'];
	//$db2->Set('tb_simbank_link_info',"n_sb_link_call_rest_time='0'","where ob_grp_name='$ob_grp_name'");
	
	$db->Set('tb_group_info',"ob_policy_name=''","where ob_policy_name='$ob_pol_name'");
	
	$params = array(
		'policyname' => $ob_pol_name
	);
	$xml = get_xml('SBKPolicyDel',$params);
	$wsdl = "http://127.0.0.1:8888/?wsdl";
	$client = new SoapClient($wsdl);
	$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
}

if($_POST){
	if(isset($_POST['send']) && $_POST['send'] == 'New Policy'){
		edit_strategy();
	}else if(isset($_POST['send']) && $_POST['send'] == 'Save'){
		save_strategy();
		show_strategy();
	}else if(isset($_POST['send']) && $_POST['send'] == 'Delete'){
		del_strategy();
		show_strategy();
	}
}else if($_GET){
	edit_strategy();
}else{
	show_strategy();
}

require("/opt/simbank/www/inc/boot.inc");
?>
