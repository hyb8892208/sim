<?php
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require('../inc/mysql_class.php');
include_once("../inc/aql.php");

function show_balance_list(){
	$balance_smsinfo_res = get_balance_smsinfo_conf();
	
	$db = new mysql();
	
	$condition = "where n_sb_available=1 order by n_sb_online desc";
	$data = $db->Get("tb_simbank_info",'*',$condition);
	$all_sim_info = mysqli_fetch_all($data, MYSQLI_ASSOC);
	$row = mysqli_num_rows($data);
	$db="";
	
	$htmlstr .= '<select class="div_tab_title sim_index_tab" onchange="switch_tab(this)" >';
	for($j =0; $j < $row; $j++){
		$color = '';
		$selected = '';
		if($j == 0){
			$selected = 'selected';
			$color = "style='color:#ECFE82;'";
		}
		
		$htmlstr .= '<option value='.$all_sim_info[$j]['ob_sb_seri'].' '.$selected.'>'.$all_sim_info[$j]['ob_sb_alias'].'-'.$all_sim_info[$j]['ob_sb_seri'].'</option>';
	}
	$htmlstr .= '</select>';
	
	$htmlstr .= '<div id="newline"></div>';
	
	for($j =0; $j < $row; $j++){
		$display = '';
		if($j != 0) $display = "style='display:none;'";
		$simbank_serial_num[$j] = $all_sim_info[$j]['ob_sb_seri'];
		$sim_count= $all_sim_info[$j]['n_sb_links'];

		for ($i=0;$i<$sim_count;$i++){
			$result[$i]['id']=$i;
			$result[$i]['ob_sb_seri']="";
			$result[$i]['ob_sb_link_bank_nbr']="";
			$result[$i]['ob_sb_link_sim_nbr']="";
			$result[$i]['ob_gw_seri']="";
			$result[$i]['ob_gw_link_bank_nbr']="";
			$result[$i]['ob_gw_link_slot_nbr']="";
			$result[$i]['n_sb_link_stat']="";
			$result[$i]['ob_gw_link_chn_nbr']="";
			$result[$i]['n_sb_link_call_rest_time'] ="";
			$result[$sim_index]['n_sim_no'] ="";
			
		}
		
		$db=new mysql();
		$condition = "where ob_sb_seri=\"$simbank_serial_num[$j]\"";
		$fileds = "ob_sb_link_bank_nbr,ob_sb_seri,ob_sb_link_bank_nbr,ob_sb_link_sim_nbr,ob_gw_seri,ob_gw_link_bank_nbr,ob_gw_link_slot_nbr,n_sb_link_stat,ob_gw_link_chn_nbr,n_sb_link_call_rest_time,v_sim_phone_number,n_sim_balance";
		$data = $db->Get("tb_simbank_link_info",$fileds,$condition);
		while($sim = mysqli_fetch_array($data,MYSQLI_ASSOC)){
			$sim_index=$sim['ob_sb_link_bank_nbr']*8+$sim['ob_sb_link_sim_nbr'];				
			$result[$sim_index]['ob_sb_seri']=$sim['ob_sb_seri'];
			$result[$sim_index]['ob_sb_link_bank_nbr']=$sim['ob_sb_link_bank_nbr'];
			$result[$sim_index]['ob_sb_link_sim_nbr']=$sim['ob_sb_link_sim_nbr'];
			$result[$sim_index]['ob_gw_seri']=$sim['ob_gw_seri'];
			
			if($sim['ob_gw_seri'] != ''){
				$result[$sim_index]['n_sim_no'] = $sim['ob_gw_link_bank_nbr'] * 8 + $sim['ob_gw_link_slot_nbr'] + 1;
				$result[$sim_index]['ob_gw_link_chn_nbr']=$sim['ob_gw_link_chn_nbr'];
			}else{
				$result[$sim_index]['n_sim_no'] = '';
				$result[$sim_index]['ob_gw_link_chn_nbr'] = '';
			}
			
			$result[$sim_index]['n_sb_link_stat']=$sim['n_sb_link_stat'];
			$result[$sim_index]['n_sb_link_call_rest_time'] = $sim['n_sb_link_call_rest_time'];
			$result[$sim_index]['v_sim_phone_number'] = $sim['v_sim_phone_number'];
			if($sim['n_sim_balance'] == -95.27){
				$result[$sim_index]['n_sim_balance'] = '';
			}else{
				$result[$sim_index]['n_sim_balance'] = $sim['n_sim_balance'];
			}
		}
		$row_count=$sim_count/16;
		$row_index=1;
		$sim_index=0;
		
		$htmlstr.="<table width=\"100%\" class='tshow $simbank_serial_num[$j]' $display>";
		$htmlstr.="<tr class='index_center_tr'><th>$simbank_serial_num[$j]</th>".
			"<th width='126px'>Col 1</th>".
			"<th width='126px'>Col 2</th>".
			"<th width='126px'>Col 3</th>".
			"<th width='126px'>Col 4</th>".
			"<th width='126px'>Col 5</th>".
			"<th width='126px'>Col 6</th>".
			"<th width='126px'>Col 7</th>".
			"<th width='126px'>Col 8</th></tr>";			
		
		for ($row_id=1;$row_id<=$row_count;$row_id++){
			
			$htmlstr.="<TR><TD ROWSPAN=2 style='text-align:center;'>$row_id</TD>";
			for ($i=1;$i<=16;$i++){					
				$sim_id = (16*($row_id-1)+$i);
				
				$seri_name = $simbank_serial_num[$j];
				$htmlstr.="<td style='padding:10px 5px 5px 5px;'><span style='min-width:10px;font-size:10px;font-weight:bold;'>".$sim_id."</span>";
				
				$balance = floatval($result[$sim_index]['n_sim_balance']);
				
				if($result[$sim_index]['n_sb_link_stat'] == 0){//未插卡-灰色
					$state = '<span style="color:grey">'.language('Empty').'</span>';
					$balance_val = '';
				}else if($balance > $balance_smsinfo_res['ins_balance']){//正常-绿色
					$state = '<span style="color:green">'.language('Normal').'</span>';
					$balance_val = $balance;
				}else if($balance <= $balance_smsinfo_res['ins_balance'] && $balance > $balance_smsinfo_res['st_balance']){//需要充值-橘色
					$state = '<span style="color:orange">'.language('Insufficient Balance').'</span>';
					$balance_val = $balance;
				}else if($balance <= $balance_smsinfo_res['st_balance'] && !($result[$sim_index]['n_sim_balance'] === '')){//停机-红色
					$state = '<span style="color:red">'.language('Shutdown').'</span>';
					$balance_val = $balance;
				}else{//余额未知-蓝色
					$state = '<span style="color:blue">'.language('Balance Unknown').'</span>';
					$balance_val = '';
				}
				
				//显示内容
				$html_str .= "<div class='helptooltips' style='display:block;'>";
					$htmlstr.='<p style="word-break:normal;margin:6px 0;">'.language('Balance').': '.$balance_val."</p>";
					$htmlstr.='<p style="word-break:normal;margin:6px 0">'.language('State').':' .$state."</p>";
				$html_str .= "</div>";
				
				if ($i==8){
					$htmlstr.="</tr><tr>";
				}
				$sim_index++;
			}
			$htmlstr.="</tr>";
			}
			
		$htmlstr.="</table>";
	}
	
	echo $htmlstr;
?>

<script>
function switch_tab(that){
	var seri_name = $(that).val();
	
	$(".tshow").each(function(){
		if($(this).hasClass(seri_name)){
			
			$(".tshow").hide();
			$(this).show();
		}
	});
}
</script>
	
<?php
}

function get_balance_smsinfo_conf(){
	$aql = new aql();
	$aql->set('basedir','/config/simbank/conf/');
	$res = $aql->query('select * from balance_smsinfo.conf');
	
	return array(
		'ins_balance' => $res['general']['ins_balance'],
		'st_balance' => $res['general']['st_balance']
	);
}

show_balance_list();

require("../inc/boot.inc");
?>