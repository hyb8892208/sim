
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
	 <div id="tab" class="div_tab_title"> 
        <li class="tb1">&nbsp;</li>
        <li class="tbg"><?php echo language('Sim Status');?></li>
        <li class="tb2">&nbsp;</li>
    </div>		
	<div id='div_sim'>
	</div>	
	
<?php
}
?>







<script type="text/javascript">
	function refresh_sim() {		
		$.ajax({			
			url :'ajax_server_new.php?nocache='+Math.random(),      //request file;
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
			},
			complete: function(){
				//var timeout = $("#interval").attr("value");
				timeout=2;
				setTimeout(function(){refresh_sim();}, timeout*2000);
			}
		});
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
	get_data();
	show_sim();
		$db=new mysql();
			//$condition = "where ob_pol_name = \"$file_name\"";
	$condition = "";
	$data = $db->Get("tb_simbank_info",'*',$condition);
	$all_sim_info = mysqli_fetch_array($data,MYSQLI_ASSOC);
	$_SESSION['sim_count']=$all_sim_info['n_sb_links'];
	
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
