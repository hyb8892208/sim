
<?php
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require('../inc/mysql_class.php');
//require("../inc/language.inc");
include_once("../inc/wrcfg.inc");
include_once("../inc/aql.php");
include_once("../inc/define.inc");
include_once("../inc/language.inc");

function show_sim(){	
?>	 
	<div class="edit_overlay"></div>
	<form action="<?php echo get_self();?>" method="post" class="edit_form">
		<div class="edit_phonenum">
			<p><?php echo language('Edit Phone Number'); ?>:</p>
			<div class="edit_area">
				<textarea name="phone_num" onkeyup="this.value=this.value.replace(/\D/g,'')" onafterpaste="this.value=this.value.replace(/\D/g,'')"></textarea>
			</div>
			<input type="hidden" id="edit_ob_sb_seri" name="ob_sb_seri" value="">
			<input type="hidden" id="edit_ob_sb_link_bank_nbr" name="ob_sb_link_bank_nbr" value="">
			<input type="hidden" id="edit_ob_sb_link_sim_nbr" name="ob_sb_link_sim_nbr" value="">
			<div class="edit_bottom">
				<button type="submit" class="edit_save"><?php echo language('Save'); ?></button>
				<button type="button" class="edit_cancel"><?php echo language('Cancel'); ?></button>
			</div>
		</div>
		
		<input type="hidden" name="send" id="send" value="PhoneNumber" />
	</form>
	
	<form action="<?php echo get_self();?>" method="post" class="edit_abnormal_form" style="display:none;">
		<div class="edit_phonenum">
			<p><?php echo language('Remove Sim Abnormal'); ?>:</p>
			<div class="edit_abnormal_area" style="padding:5px 10px 10px 10px;min-height:80px;width:220px;">
				
			</div>
			<input type="hidden" id="edit_ab_ob_sb_seri" name="ab_ob_sb_seri" value="">
			<div class="edit_bottom">
				<button type="submit" class="abnormal_edit_save"><?php echo language('Remove'); ?></button>
				<button type="button" class="abnormal_edit_cancel" style="float:right;"><?php echo language('Cancel'); ?></button>
			</div>
		</div>
		
		<input type="hidden" name="abnormal_send" id="abnormal_send" value="Abnormal" />
	</form>
	
	<div id='div_sim'>
	</div>	
	
	<?php 
	if($_POST){
		if(isset($_POST['send']) && $_POST['send'] == 'PhoneNumber'){
			$db = new mysql();
			$new_db = new mysql('simserver');
			
			$_seri = $_POST['ob_sb_seri'];
			$_bank_nbr = $_POST['ob_sb_link_bank_nbr'];
			$_sim_nbr = $_POST['ob_sb_link_sim_nbr'];
		
			$str = "ob_sb_seri='$_seri' and ob_sb_link_bank_nbr=$_bank_nbr and ob_sb_link_sim_nbr=$_sim_nbr";
			if($_POST['phone_num']!=''){
				$db->Set("tb_simbank_link_info","v_sim_phone_number=".$_POST['phone_num'],"where ".$str);
			}else{
				$db->Set("tb_simbank_link_info","v_sim_phone_number=''","where ".$str);
			}
			if($_POST['phone_num']!=''){
				$db->Set("tb_sim_info","v_sim_phone_number=".$_POST['phone_num'],"where ".$str);
			}else{
				$db->Set("tb_sim_info","v_sim_phone_number=''","where ".$str);
			}

			$results = $db->Get('tb_sim_info',"*","where ".$str);
			$sim = mysqli_fetch_array($results,MYSQLI_ASSOC);

			if($_POST['phone_num']!=''){
				$new_db->Set("tb_sim_info","v_sim_phone_number='".$_POST['phone_num']."'","where v_sim_eficcid='".$sim['v_sim_eficcid']."'");
			}else{
				$new_db->Set("tb_sim_info","v_sim_phone_number=''","where v_sim_eficcid='".$sim['v_sim_eficcid']."'");
			}
		}else if(isset($_POST['abnormal_send']) && $_POST['abnormal_send'] == 'Abnormal'){
			$db = new mysql();
			
			$seri_arr = $_POST['seri'];
			$ob_sb_seri = $_POST[ab_ob_sb_seri];
			for($i=0;$i<count($seri_arr);$i++){
				$ob_sb_link_bank_nbr = ceil($seri_arr[$i]/8)-1;
				$ob_sb_link_sim_nbr = ($seri_arr[$i]-1)%8;
				
				$params = array(					
					'oSimAbnormalInfo' => array(
						'sbseri' => $ob_sb_seri,
						'sbbank' => $ob_sb_link_bank_nbr,
						'sbslot' => $ob_sb_link_sim_nbr,
						'isabnormal' => 0
					)
				);
				$xml = get_xml('SBKSetSimAbnormal',$params);
				$wsdl = "http://127.0.0.1:8888/?wsdl";
				$client = new SoapClient($wsdl);
				$result = $client->__doRequest($xml,$wsdl,'SBKSMSReq',1,0);
			}
		}
	}
	
}
?>

<script type="text/javascript">
	function refresh_sim() {
		$.ajax({
			url :'ajax_server_simbank.php?nocache='+Math.random(),      //request file;
			type: 'GET',                                    //request type: 'GET','POST';
			dataType: 'html',                               //return data type: 'text','xml','json','html','script','jsonp';
			data: {
				'action':'refresh_sim'			
			},
			error: function(data){                          //request failed callback function;
				//alert("get data error");
			},
			success: function(data){                        //request success callback function;
				$("#div_sim").html(data);
				refresh_tab();
				edit_click();
			},
			complete: function(){
				//var timeout = $("#interval").attr("value");
				timeout=2;
				setTimeout(function(){refresh_sim();}, timeout*2000); 
			}
		});
	}
</script>

<script>
	function edit_click(){
		$(".helptooltips").click(function(){
			var ob_sb_seri = $(this).siblings("#ob_sb_seri").val();
			
			if($(this).children().hasClass('abnormal')){
				if(ob_sb_seri!=''){
					$(".edit_overlay").css("display","block");
					$(".edit_abnormal_form").css("display","block");
					$("#edit_ab_ob_sb_seri").val(ob_sb_seri);
					
					$(".edit_abnormal_area").html("");
					$(".helptooltips").each(function(){
						if($(this).children().hasClass('abnormal')){
							var seri = $(this).siblings("#ob_sb_seri").val();
							
							var seri = $(this).siblings("#ob_sb_seri").val();
							var link_bank_nbr = $(this).siblings("#ob_sb_link_bank_nbr").val();
							var link_sim_nbr = $(this).siblings("#ob_sb_link_sim_nbr").val();
							
							var index = parseInt(link_bank_nbr)*8 + parseInt(link_sim_nbr) + 1;
							if(seri != ''){
								$(".edit_abnormal_area").append(
									"<span style='display:inline-block;min-width:60px;margin-top:10px;font-size:14px;'>"+index+"<input type='checkbox' class='sel_one' name='seri[]' value='"+index+"'/>"+"</span>"
								);
							}
						}
					});
					$(".edit_abnormal_area").append('<div style="margin-top:10px;font-size:14px;"><?php echo language('All');?><input type="checkbox" id="select_all" /></div>');
				}
				
			}else{
				if(ob_sb_seri!=''){
					$(".edit_overlay").css("display","block");
					$(".edit_form").css("display","block");
					var ob_sb_seri = $(this).siblings("#ob_sb_seri").val();
					var ob_sb_link_bank_nbr = $(this).siblings("#ob_sb_link_bank_nbr").val();
					var ob_sb_link_sim_nbr = $(this).siblings("#ob_sb_link_sim_nbr").val();
					$("#edit_ob_sb_seri").val(ob_sb_seri);
					$("#edit_ob_sb_link_bank_nbr").val(ob_sb_link_bank_nbr);
					$("#edit_ob_sb_link_sim_nbr").val(ob_sb_link_sim_nbr);
				}
			}
		});
		$(".edit_cancel").click(function(){
			$(".edit_overlay").css("display","none");
			$(".edit_form").css("display","none");
		});
		$(".edit_save").click(function(){
			var edit_ob_sb_seri = $("#edit_ob_sb_seri").val();
			if(edit_ob_sb_seri==''){
				alert(language("Empty card tip","Please choose a sim card !"));
				return false;
			}
		});
		$(".abnormal_edit_cancel").click(function(){
			$(".edit_overlay").css("display","none");
			$(".edit_abnormal_form").css("display","none");
		});
		$(".abnormal_edit_save").click(function(){
			var edit_ab_ob_sb_seri = $("#edit_ab_ob_sb_seri").val();
			if(edit_ab_ob_sb_seri == ''){
				alert(language("Empty card tip","Please choose a sim card !"));
				return false;
			}
		});
	}
	
	$(document).on("change", "#select_all", function(){
		if($(this).prop("checked")){
			$(".sel_one").attr("checked","checked");
		}else{
			$(".sel_one").removeAttr("checked");
		}
	});
	
	var tag_flag = '';
	function switch_tab(that){
		var seri_name = $(that).val();
		tag_flag = seri_name;
		
		$(".tshow").each(function(){
			if($(this).hasClass(seri_name)){
				
				$(".tshow").hide();
				$(this).show();
			}
		});
	}
	
	function refresh_tab(){
		if(tag_flag != ''){
			$(".div_tab_title").val(tag_flag);
			
			$(".tshow").hide();
			$("."+tag_flag).show();
		}
	}
	
</script>




<?php
function get_data(){
	return 0;
	$aql=new aql;
	
	$setok = $aql->set('basedir','/etc/asterisk/gw');
	if (!$setok) {
		echo $aql->get_error();
		return;
	}
	$db=$aql->query("select * from interface_type.conf");	
	$_SESSION['all_interface']=$db['port1'][interfacetype];
	
	
	$setok = $aql->set('basedir','/etc/asterisk');
	if (!$setok) {
		echo $aql->get_error();
		return;
	}
	$db=$aql->query("select * from dahdi-channels.conf" );

	for ($spanid=1; $spanid<=$_SESSION['port_count']; $spanid++){
		if ($spanid==1){
			$spanindex="";
		}else{
			$spanindex="[".($spanid-1)."]";
		}
		$table_channels[$spanid]['switchtype']=$db['[unsection]']['switchtype'.$spanindex];
		$table_channels[$spanid]['signalling']=$db['[unsection]']['signalling'.$spanindex];
		$table_channels[$spanid]['channel']=$db['[unsection]']['channel'.$spanindex];		
		$table_channels[$spanid]['description']=$db['[unsection]']['description'.$spanindex];		
	}

	$_SESSION["dahdi-channels"]=$table_channels;
}

?>


<?php
function show_networks()
{
?>
	<br/>
	
	<div id="tab">
		<li class="tb1"></li>
		<li class="tbg"><?php echo language('Network Information');?></li>
		<li class="tb2"></li>
	</div>
	<table width="100%" class="tshow">
		<th width="100px"><?php echo language('Name');?></th>
		<th width="200px"><?php echo language('MAC Address');?></th>
		<th width="200px"><?php echo language('IP Address');?></th>
		<th width="200px"><?php echo language('Mask');?></th>
		<th width="200px"><?php echo language('Gateway');?></th>
		<th width=""><?php echo language('RX Packets');?></th>
		<th width=""><?php echo language('TX Packets');?></th>
<?php
	unset($output);
	exec("/tools/net_tool eth0 2> /dev/null && echo ok",$output);
	if(isset($output[11]) && $output[11] == 'ok' && isset($output[1]) && $output[1] == 'Enable') {
?>
		<tr >
			<td>LAN</td>
			<td>
				<?php if(isset($output[2])) echo $output[2]; ?>								
			</td>
			<td>
				<?php if(isset($output[3])) echo $output[3]; ?>								
			</td>
			<td>
				<?php if(isset($output[5])) echo $output[5]; ?>								
			</td> 
			<td>
				<?php if(isset($output[6])) echo $output[6]; ?>								
			</td>
			<td>
				<?php if(isset($output[8])) echo $output[8]; ?>								
			</td>
			<td>
				<?php if(isset($output[10])) echo $output[10]; ?>			
			</td>
		</tr>
<?php
	}
?>

<?php
	unset($output);
	exec("/tools/net_tool eth0:0 2> /dev/null && echo ok",$output);
	if(isset($output[11]) && $output[11] == 'ok' && isset($output[1]) && $output[1] == 'Enable') {
?>
		<tr>
			<td>WAN</td>
			<td>
				<?php if(isset($output[2])) echo $output[2]; ?>								
			</td>
			<td>
				<?php if(isset($output[3])) echo $output[3]; ?>								
			</td>
			<td>
				<?php if(isset($output[5])) echo $output[5]; ?>								
			</td>
			<td>
				<?php if(isset($output[6])) echo $output[6]; ?>								
			</td>
			<td>
				<?php if(isset($output[8])) echo $output[8]; ?>								
			</td>
			<td>
				<?php if(isset($output[10])) echo $output[10]; ?>								
			</td>
		</tr>
<?php
	}
?>
	</table>
	<br>
<?php
}
?>

<?php
function show_pppoe()
{
?>
	<div id="tab">
		<li class="tb1"></li>
		<li class="tbg"><?php echo language('VPN Information');?></li>
		<li class="tb2"></li>
	</div>
	<table width="100%" class="tshow">
		<th width="100px"><?php echo language('Name');?></th>
		<th width="200px"><?php echo language('IP Address');?></th>
		<th width="200px"><?php echo language('P-t-P IP Address');?></th>
		<th width="200px"><?php echo language('Mask');?></th>
		<th><?php echo language('RX Packets');?></th>
		<th><?php echo language('TX Packets');?></th>
	
<?php
	unset($output);

	exec("/tools/net_tool ppp0 2> /dev/null && echo ok", $output);
	if(isset($output[11]) && $output[11] == 'ok' && isset($output[1]) && $output[1] == 'Enable') {
?>
	
		<tr>
			<td>PPTP</td>
			<td>
				<?php if(isset($output[2])) echo $output[3]; ?>								
			</td>
			<td>
				<?php 
					$p_t_P =`ifconfig ppp0 | awk '/inet/ {FS=" "; gsub("P-t-P:","" ,$3); print $3}'`; 
					echo $p_t_P;
				?>								
			</td>
			<td>
				<?php if(isset($output[6])) echo $output[5]; ?>								
			</td>
			<td>
				<?php if(isset($output[8])) echo $output[8]; ?>								
			</td>
			<td>
				<?php if(isset($output[10])) echo $output[10]; ?>								
			</td>
		</tr>
<?php
	}
	
	
	unset($output);
	exec("/tools/net_tool tun0 2> /dev/null && echo ok", $output);
	if(isset($output[11]) && $output[11] == 'ok' && isset($output[1]) && $output[1] == 'Enable') {
?>
		
		<tr>
			<td>OPENVPN</td>
			<td>
				<?php if(isset($output[2])) echo $output[3]; ?>								
			</td>
			<td>
				<?php 
					$p_t_P =`ifconfig tun0 | awk '/inet/ {FS=" "; gsub("P-t-P:","" ,$3); print $3}'`; 
					echo $p_t_P;
				?>								
			</td>
			<td>
				<?php if(isset($output[6])) echo $output[5]; ?>								
			</td>
			<td>
				<?php if(isset($output[8])) echo $output[8]; ?>								
			</td>
			<td>
				<?php if(isset($output[10])) echo $output[10]; ?>								
			</td>
		</tr>
<?php
	}
?>
	</table>
<?php
}
?>

<?php
	get_data();
	show_sim();
	show_networks();
	show_pppoe();
	
	
	
	
	
	
//	show_pri();	
//show_gsms();	
?>



<script type="text/javascript"> 


$(document).ready(function (){ 
	refresh_sim();
	//refresh_spans();
	//refresh_pri();
	
	
}); 
</script>

<?php
require("/opt/simbank/www/inc/boot.inc");
?>
