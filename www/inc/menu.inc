<?php

$filename = basename($_SERVER['REQUEST_URI']);

if(($pos=strpos($filename,'?'))) {
	$filename = substr($filename,0,$pos);
}	
	$menu_mode='simproxy';
	if ((isset($_SERVER['HTTP_REFERER'])) and (strpos($_SERVER['HTTP_REFERER'],"/simbank/")!=false)) {
		$menu_mode='simbank';
	}else{
		$menu_mode='simproxy';
	}

if ($menu_mode=='simproxy'){
	$nav_lists = array(
			
			"System"=>array(			
				array("system-monitor_sim.php","Sim Status"),
				array("sim-info.php","Sim Info"),
				array("sim-balance.php","Sim Balance"),
				array("system-login.php","Login Settings"),	
				array("system-general.php","General"),
				array("system-time.php", "System Time"),
				array("system-tools.php","Tools"),	
				array("system-info.php","Information"),	
			),
			
			"Register"=>array(			
				array("gateway_manage.php","Gateway"),
				array("simbank_manage.php","Simbank"),
			),
			
			"SIM Policy"=>array(			
					array("strategy_manage.php","Policy"),
					//array("sim-group-rules.php","Sim Group Rules"),
					array("sim-group.php","Sim Group"),
					array("sim-bind.php","Sim Bind"),
					array("sim-batch.php","Sim Batch Bind"),
			),
			
			"Call"=>array(
				array("call-advanced.php","Advanced"),
			),
			
			"SMS"=>array(
				array("sms-request.php","SMS Sender"),
				array("sms-inbox.php","SMS Inbox"),
				array("sms-toolkit.php","Toolkit"),
				array("sms-simquery.php","Balance Query"),
			),
			
			"Network"=>array(			
					array("network-lan.php","Lan Settings"),	
					array("network-vpn.php","VPN"),
					array("network-oplink.php","OPlink"),
					array("network-toolkit.php","Toolkit"),
			),
			
			"Log"=>array(			
					array("log-SimProxySvr.php","SimProxySvr Log"),				
					array("log-SimRdrSvr.php","Client Log"),
			),
		
	);
	
	if(isset($cloudSimProxy) && $cloudSimProxy == true){
		unset($nav_lists['System'][2]);
		unset($nav_lists['System'][3]);
		unset($nav_lists['System'][4]);
		unset($nav_lists['System'][5]);
		unset($nav_lists['Network']);
	}
}

function get_model_name(){
	$product_customid = get_redis_value('product.sw.customid');
	$product_name = file_get_contents("/tmp/.product_name");
	if($product_customid == 'general'){
		$temp = explode('-',$product_name);
		return 'Sim-'.$temp[1];
	}else{
		return $product_name;
	}
}

function get_data_info(){
	$aql = new aql();
	$setok = $aql->set('basedir','/opt/simbank/www/myimages/');

	$hlock=lock_file("/opt/simbank/www/myimages/gw_info.conf");
	$general_conf = $aql->query("select * from gw_info.conf where section='general'");
	unlock_file($hlock);
	
	$arr = [];
	$arr['switch'] = $general_conf['general']['switch'];
	$arr['copyright'] = $general_conf['general']['copyright'];
	$arr['header_image'] = $general_conf['general']['header_image'];
	$arr['footer_image'] = $general_conf['general']['footer_image'];
	
	return $arr;
}

$nav_lists_count = count($nav_lists);
?>

<div id="bg">
	
	<?php
	$lang_temp = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5);
	if($lang_temp == 'zh-CN'){
		$lang = 'chinese';
		$lang_content = '系统检测到您默认语言为中文，需要切换到中文吗？';
		$lang_confirm = '确定';
		$lang_cancel = '取消';
		$no_longer_reminding = '不再提醒';
	}else{
		$lang = 'english';
		$lang_content = 'The system detects that your default language is English. Do you need to switch to English?';
		$lang_confirm = 'Confirm';
		$lang_cancel = 'Cancel';
		$no_longer_reminding = 'No Longer Reminding';
	}
	
	$conf_file = '/config/simbank/conf/web_language.conf';
	$aql = new aql();
	$aql->set('basedir','/config/simbank/conf/');
	if(!$aql->open_config_file($conf_file)){
		echo $aql->get_error();
		unlock_file($hlock);
		return -1;
	}
	
	$lang_res = $aql->query("select * from web_language.conf");
	if(isset($lang_res['general']['flag']) && $lang_res['general']['flag'] == 1){
		$sel_flag = 1;
	}else{
		$sel_flag = 0;
	}
	?>
	
	<div id="switch_language">
		<div id="lang_global">
			<img src="/images/lang_switch.png" />
		</div>
		
		<div id="country_icon">
			<div id="change_chinese" class="change_lang_cont">
				<img src="/images/chinese.png" />
				<input type="hidden" class="lang_val" value="chinese" />
				<span id="alt_chinese" class="change_language">中文</span>
			</div>
			<div id="change_english" class="change_lang_cont">
				<img src="/images/english.png" />
				<input type="hidden" class="lang_val" value="english" />
				<span id="alt_english" class="change_language">English</span>
			</div>
		</div>
	</div>
	
	<div id="lang_tip_float">
		<div id="lang_tip_sanjiao">
			<img src="/images/triangle.png" />
		</div>
		<div id="lang_tip_content">
			<span><?php echo $lang_content;?></span>
			<div id="lang_tip_confrim">
				<button id="lang_confrim_btn" style="margin-right:20px;"><?php echo $lang_confirm;?></button>
				<button id="lang_cancel_btn"><?php echo $lang_cancel;?></button>
			</div>
			<span id="lang_notip"><?php echo $no_longer_reminding;?></span>
			<div style="clear:both;"></div>
		</div>
	</div>
	
	<?php $data_info_arr = get_data_info(); ?>
	
	<script>
	$(function(){
		var cus_lang = '<?php echo $lang;?>';
		var sel_lang = '<?php echo $lang_res['general']['language'];?>';
		var sel_flag = '<?php echo $sel_flag;?>';
		
		if(sel_flag == '0'){
			if(sel_lang != cus_lang){
				$("#lang_tip_float").show();
			}
		}
	});
	
	<?php if($data_info_arr['switch'] == 'on'){?>
		var header_image = '<?php echo $data_info_arr['header_image'];?>';
		
		var rand = Math.random();
		if(header_image != ''){
			$("#bg").css("background","url(/myimages/header_image.png?v="+rand+") no-repeat");
		}
	<?php } ?>
	
	var flag = 1;
	$(document).click(function(){
		if(flag == 1){
			if($("#country_icon").css("display") != 'none'){
				$("#country_icon").hide();
			}
		}
		flag = 1;
	});
	
	$("#lang_global").click(function(){
		flag = 0;
		if($("#country_icon").css("display") == 'none'){
			$("#country_icon").show();
		}else{
			$("#country_icon").hide();
		}
	});
	
	$("#lang_notip").click(function(){
		cancel_sel_lang();
	});
	
	$("#lang_confrim_btn").click(function(){
		var cus_lang = '<?php echo $lang;?>';
		change_lang(cus_lang);
	});
	
	$("#lang_cancel_btn").click(function(){
		$("#lang_tip_float").hide();
	});
	
	$(".change_language").click(function(){
		var cus_lang = $(this).siblings('.lang_val').val();
		change_lang(cus_lang);
	});
	
	function change_lang(lang){
		$.ajax({
			type:"GET",
			url:"ajax_server_new.php?action=change_language&language_type="+lang,
			success:function(data){
				window.location.reload();
			},
			error:function(){
				alert("Switch Language failed!");
			}
		});
	}
	
	function cancel_sel_lang(){
		$.ajax({
			type:"GET",
			url:"ajax_server_new.php?action=cancel_language",
			success:function(data){
				$("#lang_tip_float").hide();
			},
			error:function(){
				alert('error');
				$("#lang_tip_float").hide();
			}
		});
	}
	</script>

	<div id="menu">
		<UL id="nav">
<?php
	$k=0;
	$nana="";
	$nanh="";
	$crumb = 0;
	
	foreach($nav_lists as $key=>$value){
		echo "<li>";
		
		
		$n=0;
		
		
		if (is_array($nav_lists[$key][0])==false){
			$title=$nav_lists[$key][1];
			$nanh=$nav_lists[$key][0];
			$nana=strtolower($nav_lists[$key][1]);
			$n++;
			
			//Continue;
		}else{
			$title=$key;
			foreach($value as $row) {
				if(strcmp($filename,$row[0]) == 0) {
					$nana = $key;
				}
				if($n==0) {
					$nanh=$row[0];
				}

				$n++;
			}
		}
		if(strcmp($nana,$key) == 0) {
			$crumb = $k ;
			echo "<a class=\"nav_on\"";
		}else{
			echo "<a class=\"nav_off\"";
		}
		
		if ($menu_mode=='simproxy'){
			$path='simproxy';
		}else{
			$path='simbank';
		}
		
		
		echo " id=mynav".$k." onmousedown=javascript:switch_over(" . $k.",". $nav_lists_count.") href=\"/$path/$nanh\">";
		echo language($title);
		echo "</a></li>\n";

		if($k < count($nav_lists)-1) {
			echo "<li class=\"menu_line\">|</li>\n";
		}
		$k++;
	}
?>
		</UL>

		<div id=menu_con>
			<span id="logo_font">
			<?php 
			$aql = new aql();
			$setok = $aql->set('basedir','/opt/simbank/www/myimages');
			if (!$setok) {
				exec('ln -s /mnt/data/info/images /opt/simbank/www/myimages');
			}

			$hlock=lock_file("/opt/simbank/www/myimages/gw_info.conf");
			$general_conf = $aql->query("select * from gw_info.conf where section='general'");
			unlock_file($hlock);
			
			$switch = $general_conf['general']['switch'];
			if($switch == 'on'){
				echo $general_conf['general']['product_name'];
			}else{
				echo get_model_name();
			}
			?>
			</span>
<?php
	
	$i=0;
	$nanc="";
	foreach($nav_lists as $key=>$value) {
		if (is_array($nav_lists[$key][0])==false){
			$i++;
			Continue;
		}
		
		if($nana === $key){
			echo "<div style=\"DISPLAY: block\" id=qh_con".$i." ><UL>\n";
		}else{
			echo "<div style=\"DISPLAY: none\" id=qh_con".$i." ><UL>\n";
		}
		$n=0;
		
		
		echo "<li class=\"bgl\"><img src=\"/images/bg_l.gif\" /></li>\n";
		
		foreach($value as $row) {
			if($n > 0) {
				echo "<li class=\"menu_con_line\">|</li>\n";
			}
			
			if($filename === $row[0]) {
				echo "<li class=\"bgbg\"><a href=\"$row[0]\" class=\"con_off\">";
				echo language($row[1]);
				echo "</a></li>\n";
				$nanc=$row[1];
			} else {
				echo "<li class=\"bgbg\"><a href=\"$row[0]\" class=\"con_on\">";
				echo language($row[1]);
				echo "</a></li>\n";
			}
			
			$n++;
		}
	
		echo "<li class=\"bgr\"><img src=\"/images/bg_r.gif\" /></li>\n";
		echo "</UL></div>\n";
	
		$i++;
	}
	

		echo "</div>";
?>	
	</div>	
	
	<script type="text/javascript">
		$(document).ready(function(){
			var menu_confirm = 1;
	<?php for($key = 0 ; $key <= $k ;$key++){ ?>
			$("#mynav"+<?php echo $key; ?>).mouseover(function(){
				$(".nav_li_hover").removeClass("nav_li_hover");
				$("#mynav"+<?php echo $key; ?>).addClass("nav_li_hover");
				show_nav(<?php echo $key; ?>);
			});
	<?php } ?>
			function show_nav(cur_num){
				var nav_num = <?php echo $k; ?>;
				for(var num = 0 ; num <= nav_num ; num++){
					if(cur_num == num){
						$("#qh_con"+num).css("display","block");
					} else {
						$("#qh_con"+num).css("display","none");
					}
				}
			}
			$("#menu").mouseover(function(){
				menu_confirm = 1;
			});
			$("#menu").mouseleave(function(){
				menu_confirm = 0;
				setTimeout(function(){
					if(menu_confirm == 0){
						show_nav(<?php echo $crumb; ?>);
						$(".nav_li_hover").removeClass("nav_li_hover");
					}
				},1500);
			});
		});
	</script>
	<div id="aeu">
		<!--<img src="/images/icons/<?PHP echo $nana ?>.gif" />-->
		<img src="/images/icons/detail.gif" />
		
	</div>

	<div id="lps">
