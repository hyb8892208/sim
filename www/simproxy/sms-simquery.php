<?php
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
include_once("../inc/wrcfg.inc");
include_once("../inc/aql.php");

function show_simquery(){
	if(!file_exists('/config/simbank/conf/balance_smsinfo.conf')) touch('/config/simbank/conf/balance_smsinfo.conf');
	
	$aql = new aql();
	$aql->set('basedir','/config/simbank/conf/');
	$res = $aql->query('select * from balance_smsinfo.conf');
	
	if(count($res) == 1) $res = [''];
	
	$ins_balance = $res['general']['ins_balance'];
	$st_balance = $res['general']['st_balance'];
?>
<script src="/js/check.js"></script>
<script type="text/javascript" src="/js/float_btn.js"></script>
<form action="<?php echo get_self();?>" method="post" >
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg" style="width:215px;"><?php echo language('Balance Status Settings');?></li>
		<li class="tb2">&nbsp;</li>
	</div>
	
	<table width="100%" class="tedit">
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Insufficient Balance@sms-simquery');?>:
					<span class="showhelp">
					<?php echo language('Insufficient Balance@sms-simquery help','Insufficient Balance');?>
					</span>
				</div>
			</th>
			<td>
				<input type="text" id="ins_balance" name="ins_balance" value="<?php echo $ins_balance;?>" />
				<span id="cins_balance"></span>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Shutdown Balance');?>:
					<span class="showhelp">
					<?php echo language('Shutdown Balance help','Shutdown Balance');?>
					</span>
				</div>
			</th>
			<td>
				<input type="text" id="st_balance" name="st_balance" value="<?php echo $st_balance;?>" />
				<span id="cst_balance"></span>
			</td>
		</tr>
	</table>
	
	<div id="newline"></div>

	<div id="tab">
		<li class="tb_unfold" onclick="lud(this,'tab_main')" id="tab_main_li">&nbsp;</li>
		<li class="tbg_fold" onclick="lud(this,'tab_main')"><?php echo language('Balance Query');?></li>
		<li class="tb2_fold" onclick="lud(this,'tab_main')">&nbsp;</li>
		<li class="tb_end">&nbsp;</li>
	</div>
	<div style="clear:both;"></div>
	
	<div id="tab_main">
<?php
	$n = 0;
	foreach($res as $key=>$val){
		if($key === '[unsection]' || $key === 'general') continue;
		$n++;
?>
		<table width="100%" class="tedit">
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Carrier Name');?>:
						<span class="showhelp">
						<?php echo language('Carrier Name help','Carrier Name');?>
						</span>
					</div>
				</th>
				<td>
					<input class="carrier_name" type="text" name="carrier_name[]" value="<?php echo $val['carrier_name'];?>" />
					<span class="ccarrier_name"></span>
					
					<button type="button" class="delete" style="width:32px;height:32px;float:right;">
						<img src="/images/delete.gif">
					</button>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Carrier Number');?>:
						<span class="showhelp">
						<?php echo language('Carrier Number help','Carrier Number');?>
						</span>
					</div>
				</th>
				<td>
					<input class="carrier_number" type="text" value="<?php echo $key == 0 ? '' : $key;?>" name="carrier_number[]" />
					<span class="ccarrier_number"></span>
				</td>
			</tr>
		
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Destination Number');?>:
						<span class="showhelp">
						<?php echo language('Destination help','This is the destination number which you want to send.');?>
						</span>
					</div>
				</th>
				<td>
					<input class="dstnum" type="text" name="dstnum[]" value="<?php echo $val['dst_phone'];?>" onafterpaste="this.value=this.value.replace(/\D/g,'')"/>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Message');?>:
						<span class="showhelp">
						<?php echo language('Message help','The SMS content to be sent out.');?>
						</span>
					</div>
				</th>
				<td>
					<input class="sendmsg" type="text" name="sendmsg[]" value="<?php echo $val['send_msg'];?>" />
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('Match Number');?>:
						<span class="showhelp">
						<?php echo language('Match number help','This number will receive the message.');
						?>
						</span>
					</div>
				</th>
				<td>
					<input class="srcnum" type="text" name="srcnum[]" value="<?php echo $val['src_phone'];?>" onkeyup="this.value=this.value.replace(/\D/g,'')" onafterpaste="this.value=this.value.replace(/\D/g,'')"/>
				</td>
			</tr>
			
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo language('The Matching Characters');?>:
						<span class="showhelp">
						<?php echo language('characters help','Here is the key to find the phone number or SIM balance.For example, if you want to query the phone number,and receive message is "your phone number is xxxxxx",You should fill in "number".<br>If you want to send other message,you can ignore it.');?>
						</span>
					</div>
				</th>
				<td>
					<input class="matchstr" type="text" name="matchstr[]" value="<?php echo $val['match_str'];?>" />
				</td>
			</tr>
		</table>
		
		<div id="newline"></div>
<?php
	}
?>

	</div>

	<div id="newline"></div>
	
	<input type="hidden" name="send" value="Save" />
	
	<table id="float_btn" class="float_btn">
		<tr id="float_btn_tr" class="float_btn_tr">
			<td>
				<button style="margin-right:15px;" class="add_more_query" type="button"><?php echo language('Add more Query');?></button>
			</td>
			<td>
				<button style="margin-right:15px;" class="save" type="submit" onclick="return check()"><?php echo language('Save');?></button>
			</td>
			<td>
				<input style="margin-right:15px;" class="start_query" type="button" value="<?php echo language('Start Query');?>" />
			</td>
			<td>
				<input style="margin-right:15px;" class="end_query" type="button" value="<?php echo language('End Query');?>" />
			</td>
			<td>
				<span class="query_state"></span>
			</td>
		</tr>
		<table id="float_btn2" style="border:none;" class="float_btn2">
			<tr id="float_btn_tr2" class="float_btn_tr2">
				<td>
					<button style="margin-right:15px;" class="add_more_query" type="button"><?php echo language('Add more Query');?></button>
				</td>
				<td>
					<button style="margin-right:15px;" class="save" type="submit" onclick="return check()"><?php echo language('Save');?></button>
				</td>
				<td>
					<input style="margin-right:15px;" class="start_query" type="button" value="<?php echo language('Start Query');?>" />
				</td>
				<td>
					<input style="margin-right:15px;" class="end_query" type="button" value="<?php echo language('End Query');?>" />
				</td>
				<td>
					<span class="query_state"></span>
				</td>
			</tr>
		</table>
	</table>
</form>

<?php
}

function save_simquery(){
	$aql = new aql();
	
	$setok = $aql->set('basedir', '/mnt/config/simbank/conf/');
	if(!$setok){
		echo $aql->get_error();
		return false;
	}
	
	$conf_path = "/mnt/config/simbank/conf/balance_smsinfo.conf";
	
	file_put_contents($conf_path, '');
	
	if(!file_exists($conf_path)){
		touch($conf_path);
	}
	
	$hlock = lock_file($conf_path);
	
	if(!$aql->open_config_file($conf_path)){
		echo $aql->get_error();
		unlock_file($hlock);
		return false;
	}
	
	$res = $aql->query("select * from balance_smsinfo.conf");
	
	if(!isset($res['general'])){
		$aql->assign_addsection('general', '');
	}
	
	if(isset($res['general']['ins_balance'])){
		$aql->assign_editkey('general', 'ins_balance', $_POST['ins_balance']);
	}else{
		$aql->assign_append('general', 'ins_balance', $_POST['ins_balance']);
	}
	
	if(isset($res['general']['st_balance'])){
		$aql->assign_editkey('general', 'st_balance', $_POST['st_balance']);
	}else{
		$aql->assign_append('general', 'st_balance', $_POST['st_balance']);
	}
	
	for($i=0;$i<count($_POST['carrier_number']);$i++){
		$carrier_number = $_POST['carrier_number'][$i];
		
		$aql->assign_addsection($carrier_number, '');
		
		$aql->assign_append($carrier_number, 'carrier_name', $_POST['carrier_name'][$i]);
		$aql->assign_append($carrier_number, 'dst_phone', $_POST['dstnum'][$i]);
		$aql->assign_append($carrier_number, 'send_msg', $_POST['sendmsg'][$i]);
		$aql->assign_append($carrier_number, 'src_phone', $_POST['srcnum'][$i]);
		$aql->assign_append($carrier_number, 'match_str', $_POST['matchstr'][$i]);
	}
	
	$aql->save_config_file('balance_smsinfo.conf');
	
	unlock_file($hlock);
	
	$params = array();
	
	$xml = get_xml('SBKSaveSimBalanceInfo',$params);
	$wsdl = "http://127.0.0.1:8888/?wsdl";
	$client = new SoapClient($wsdl);
	$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
}

if($_POST && isset($_POST['send'])){
	if($_POST['send'] == 'Save'){
		save_simquery();
	}
}

show_simquery();
?>
<script>
function check(){
	var ins_balance = parseInt(document.getElementById('ins_balance').value);
	var st_balance = parseInt(document.getElementById('st_balance').value);
	
	document.getElementById('cst_balance').innerHTML = '';
	if(ins_balance != '' && st_balance != ''){
		if(ins_balance < st_balance){
			document.getElementById('st_balance').focus();
			document.getElementById('cst_balance').innerHTML = con_str('<?php echo language("Shutdown Balance tip","Shutdown Balance must be less than Insufficient Balance.");?>');
			return false;
		}
	}
	
	var carrier_number_flag = false;
	
	var carrier_number_arr = [];
	$(".carrier_number").each(function(){
		if($(this).val() == ''){
			$(this).focus();
			$(this).siblings('.ccarrier_number').html(con_str('<?php echo language('can not be none');?>'));
			
			carrier_number_flag = true;
		}else{
			carrier_number_arr.push($(this).val());
			$(this).siblings('.ccarrier_number').html('');
		}
	});
	
	var n = 0;
	$(".carrier_number").each(function(){
		for(var i=0;i<carrier_number_arr.length;i++){
			if(i != n){
				if(carrier_number_arr[i] == $(this).val()){
					$(this).focus();
					$(this).siblings('.ccarrier_number').html(con_str('<?php echo language('Carrier Number cannot be repeated');?>'));
					carrier_number_flag = true;
				}
			}
		}
		
		n++;
	});
	
	if(carrier_number_flag){
		return false;
	}
	
	return true;
}

$(document).on('click',".add_more_query", function(){
	var _html = $(".tedit:last").html();
	var carrier_name = $(".tedit:last .carrier_name").val();
	var carrier_number = $(".tedit:last .carrier_number").val();
	var dstnum = $(".tedit:last .dstnum").val();
	var sendmsg = $(".tedit:last .sendmsg").val();
	var srcnum = $(".tedit:last .srcnum").val();
	var matchstr = $(".tedit:last .matchstr").val();
	
	$(".tedit:last").after("<div id='newline'></div><table width='100%' class='tedit'>"+_html+"</table>");
	$(".tedit:last .carrier_name").val(carrier_name);
	$(".tedit:last .carrier_number").val(carrier_number);
	$(".tedit:last .dstnum").val(dstnum);
	$(".tedit:last .sendmsg").val(sendmsg);
	$(".tedit:last .srcnum").val(srcnum);
	$(".tedit:last .matchstr").val(matchstr);
	
	$(".delete").show();
	$(".delete:first").hide();
});

$(".start_query").click(function(){
	$.ajax({
		url: 'ajax_server_new.php?action=query_sim_balance',
		type: 'GET',
		success: function(res){
			get_status();
		},
		error: function(){
			alert('<?php echo language('Balance query error');?>');
		}
	})
});

$(".end_query").click(function(){
	$.ajax({
		url: 'ajax_server_new.php?action=end_query_sim_balance',
		type: 'GET',
		success: function(res){
			$(".query_state").html("");
		},
		error: function(){
			alert('End query failed');
		}
	})
});

$(document).on('click', '.delete', function(){
	$(this).parent().parent().parent().parent().remove();
});

$(".delete:first").hide();

function get_status(){
	$.ajax({
		url: 'ajax_server_new.php?action=get_sim_balance_status',
		type: 'GET',
		success: function(res){
			if(res.indexOf("<result>3</result>") != -1 || res.indexOf("<result>1</result>") != -1){
				$(".query_state").html("<span style='color:blue;'><?php echo language('Querying');?></span><img src='/images/mini_loading.gif' />");
				setTimeout(get_status,1000);
			}else if(res.indexOf("<result>2</result>") != -1){
				$(".query_state").html("<span style='color:blue;'><?php echo language('Returning data');?></span><img src='/images/mini_loading.gif' />");
				setTimeout(get_status,1000);
			}else if(res.indexOf("<result>0</result>") != -1){
				$(".query_state").html("");
			}
		},
		error: function(){
		}
	})
}

get_status();
</script>

<?php require("/opt/simbank/www/inc/boot.inc"); ?>

<div id="float_btn1" class="sec_float_btn1"></div>
<div  class="float_close" onclick="close_btn()"></div>