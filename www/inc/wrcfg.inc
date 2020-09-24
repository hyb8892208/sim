<?php
//AQL
include_once("aql.php");
include_once("function.inc");
include_once("cluster.inc");
include_once("config.inc");

function get_cdr_sw()
{
	$aql = new aql();
	$aql->set('basedir','/etc/asterisk');
	$hlock = lock_file('/etc/asterisk/gw.conf');
	$res = $aql->query("select * from gw.conf where section='cdr'");
	unlock_file($hlock);
	if(isset($res['cdr']['switch'])) {
		if(is_true(trim($res['cdr']['switch']))) {
			return true;
		}
	}

	return false;
}

function get_sys_log_sw()
{
	$aql = new aql();
	$aql->set('basedir','/etc/asterisk');
	$hlock = lock_file('/etc/asterisk/gw.conf');
	$res = $aql->query("select * from gw.conf where section='sys-log'");
	unlock_file($hlock);
	if(isset($res['sys-log']['switch'])) {
		if(is_true(trim($res['sys-log']['switch']))) {
			return true;
		}
	}

	return false;
}

function get_sip_log_sw()
{
	$aql = new aql();
	$aql->set('basedir','/etc/asterisk');
	$hlock = lock_file('/etc/asterisk/gw.conf');
	$res = $aql->query("select * from gw.conf where section='sip-log'");
	unlock_file($hlock);
	if(isset($res['sip-log']['switch'])) {
		if(is_true(trim($res['sip-log']['switch']))) {
			return true;
		}
	}

	return false;
}

function get_debugat_log_sw()
{
	$aql = new aql();
	$aql->set('basedir','/etc/asterisk');
	$hlock = lock_file('/etc/asterisk/gw.conf');
	$res = $aql->query("select * from gw.conf where section='debugat-log'");
	unlock_file($hlock);
	if(isset($res['debugat-log']['switch'])) {
		if(is_true(trim($res['debugat-log']['switch']))) {
			return true;
		}
	}

	return false;
}

function get_intercall_sw()
{
	$aql = new aql();
	$aql->set('basedir','/etc/asterisk/gw');
	$hlock = lock_file('/etc/asterisk/gw/auto_intercall.conf');
	$res = $aql->query("select * from auto_intercall.conf where section='general'");
	unlock_file($hlock);
	if(isset($res['general']['enable'])) {
		if(is_true(trim($res['general']['enable']))) {
			return true;
		}
	}

	return false;
}

function get_hangupcausecode()
{
	$aql = new aql();
	$aql->set('basedir','/etc/asterisk/');
	$hlock = lock_file('/etc/asterisk/sip_general.conf');
	$res = $aql->query("select * from sip_general.conf where section='general'");
	unlock_file($hlock);
	if(isset($res['general']['hangupcausecode'])) {
		return trim($res['general']['hangupcausecode']);
	}

	return '';
}

/*
function get_gsm_dialprefix($gsm_channel)
{
	if(!$gsm_channel)
		return '';

	$aql = new aql();
	$aql->set('basedir','/etc/asterisk');
	$hlock = lock_file('/etc/asterisk/gw_gsm.conf');
	$res = $aql->query("select * from gw_gsm.conf where section=\"$gsm_channel\"");
	unlock_file($hlock);
	if(isset($res[$gsm_channel]['dialprefix'])) {
		return trim($res[$gsm_channel]['dialprefix']);
	}

	return '';
}
*/

function get_smssender_option()
{
	$ret = array();
	$aql = new aql();
	$aql->set('basedir','/etc/asterisk/gw');
	$hlock = lock_file('/etc/asterisk/gw/sms.conf');
	$val = $aql->query("select * from sms.conf where section='send'");
	unlock_file($hlock);
	if(isset($val['send']['attempt'])) {
		$ret['attempt'] = trim($val['send']['attempt']);
	}

	if(isset($val['send']['repeat'])) {
		$ret['repeat'] = trim($val['send']['repeat']);
	}

	if(isset($val['send']['verbose'])) {
		$ret['verbose'] = trim($val['send']['verbose']);
	}

	return $ret;
}


function get_sip_username($endpoint_name)
{
/* /etc/asterisk/gw_endpoints.conf */
// [endpoint_name]
// username = 
// registration =  
// register =    like sip.conf [general]
// .......       like all sip.conf

	$aql = new aql();
	$aql->set('basedir','/etc/asterisk');
	$hlock = lock_file('/etc/asterisk/gw_endpoints.conf');
	$res = $aql->query("select * from gw_endpoints.conf where section='$endpoint_name'");
	unlock_file($hlock);

	if(isset($res[$endpoint_name]['username'])) {
		return trim($res[$endpoint_name]['username']);
	}

	return '';
}


function get_sip_username_and_ip($endpoint_name)
{
/* /etc/asterisk/gw_endpoints.conf */
// [endpoint_name]
// username = 
// registration =  
// register =    like sip.conf [general]
// .......       like all sip.conf

/*
	$aql = new aql();
	$aql->set('basedir','/etc/asterisk');
	$hlock = lock_file('/etc/asterisk/gw_endpoints.conf');
	$res = $aql->query("select * from gw_endpoints.conf where section='$endpoint_name'");
	unlock_file($hlock);
*/
	$res = get_config("/etc/asterisk/gw_endpoints.conf", $endpoint_name);

	if(isset($res[$endpoint_name]['username'])) {
		$username = trim($res[$endpoint_name]['username']);

		if(isset($res[$endpoint_name]['host'])) {
			$ip = trim($res[$endpoint_name]['host']);
			if($ip != '' && $ip != 'dynamic') {

				if(isset($res[$endpoint_name]['allow_anonymous']) && $res[$endpoint_name]['allow_anonymous'] == 'yes') {
					return $ip;
				}

				return $username.'-'.$ip;
			}

			return $username;
		} else {
			return $username;
		}
	}

	return '';
}

function get_sip_registration($endpoint_name)
{
/* /etc/asterisk/gw_endpoints.conf */
// [endpoint_name]
// username = 
// registration =  
// register =    like sip.conf [general]
// .......       like all sip.conf
/*
	$aql = new aql();
	$aql->set('basedir','/etc/asterisk');
	$hlock = lock_file('/etc/asterisk/gw_endpoints.conf');
	$res = $aql->query("select * from gw_endpoints.conf where section='$endpoint_name'");
	unlock_file($hlock);
*/
	$res = get_config("/etc/asterisk/gw_endpoints.conf", $endpoint_name);	

	if(isset($res[$endpoint_name]['registration'])) {
		return trim($res[$endpoint_name]['registration']);
	}

	return '';
}

function get_channel_info($type_and_name)
{//Support gsm,sip,group,borad(other slot gateway)
	$info['type'] = '';
	$info['name'] = '';
	$info['device'] = '';
	$info['context'] = '';
	$info['registration'] = '';    //GSM not has this.
	$info['channel'] = '';         //SIP not has this.

	global $__GSM_HEAD__;
	global $__SIP_HEAD__;
	global $__BRD_HEAD__;
	global $__GRP_HEAD__;

	if(substr($type_and_name,0,strlen($__SIP_HEAD__)) == $__SIP_HEAD__) { //SIP  sip-sipendpointname
		$name = substr($type_and_name,strlen($__SIP_HEAD__));
		$sip_username_and_ip = get_sip_username_and_ip($name);
		$registration = get_sip_registration($name);
		if($sip_username_and_ip == '') {
			$context = '';
			$device = '';
		} else {
			$context = $__SIP_HEAD__.$sip_username_and_ip;
			$device = "SIP/$sip_username_and_ip";
		}

		$info['type'] = 'sip';
		$info['name'] = $name;
		$info['context'] = $context;
		$info['device'] = $device;
		$info['registration'] = $registration;
	} else if(substr($type_and_name,0,strlen($__GSM_HEAD__)) == $__GSM_HEAD__) { //GSM  gsm-1
		$name = substr($type_and_name,strlen($__GSM_HEAD__));
		if($name == '') {
			$context = '';
			$device = '';
		} else {
			$info['channel'] = $name;
			$voice_channel = $name*2-1;
			$context = $__GSM_HEAD__.$name;
			$name = $__GSM_HEAD__.$name;
			$device = "extra/$voice_channel";
		}

		$info['type'] = 'gsm';
		$info['name'] = $name;
		$info['context'] = $context;
		$info['device'] = $device;
	} else if(substr($type_and_name,0,strlen($__BRD_HEAD__)) == $__BRD_HEAD__) { //BOARD GSM  Board-2-gsm-1
		@sscanf($type_and_name,"$__BRD_HEAD__%d-$__GSM_HEAD__%d",$board_num,$gsm_num);

		$context = '';
		$device = '';

		if($gsm_num != '' && $board_num != '') {
			$slave_info = get_slave_info($board_num);
			if($slave_info['password'] != '' && $slave_info['ip'] != '') {
				$context = $__BRD_HEAD__.$board_num.'-'.$__GSM_HEAD__.$gsm_num;

				$device = 'SIP/'.$slave_info['password'].$gsm_num.'-'.$slave_info['ip'];
			}
		}

		$info['type'] = 'brd';
		$info['name'] = $type_and_name;
		$info['context'] = $context;
		$info['device'] = $device;
		$info['channel'] = $gsm_num;
	} else if(substr($type_and_name,0,strlen($__GRP_HEAD__)) == $__GRP_HEAD__) { //group
		$name = substr($type_and_name,strlen($__GRP_HEAD__));
		$info['type'] = 'grp';
		$info['name'] = $name;
	} else if(!strcmp($type_and_name,"anysip")) {
		$info['type'] = 'anysip';
		$info['name'] = 'anysip';
		$info['context'] = 'sipdefault';
	}

	return $info;
}

function get_internal_sip_call()
{
	$aql = new aql();
	$aql->set('basedir','/etc/asterisk');
	$hlock = lock_file('/etc/asterisk/sip_general.conf');
	$sip_general = $aql->query("select * from sip_general.conf");
	unlock_file($hlock);
	if( isset($sip_general['general']['enable_internal_sip_call']) &&
		isset($sip_general['general']['internal_sip_call_prefix'])
	) {
		if($sip_general['general']['enable_internal_sip_call'] == 'yes' ) {
			$ret['prefix'] = trim($sip_general['general']['internal_sip_call_prefix']);
			return $ret;
		}
	}
		
	return false;	
}


function get_all_sips($sort = false)
{
/* /etc/asterisk/gw_endpoints.conf */
// [endpoint_name]
// order = 1,2,3,4,5....    //Must set
// username = 
// registration =  
// register =    like sip.conf [general]
// .......       like all sip.conf

	$sips = array();
	$aql = new aql();
	$aql->set('basedir','/etc/asterisk');
	$hlock = lock_file('/etc/asterisk/gw_endpoints.conf');
	$sips = $aql->query("select * from gw_endpoints.conf");
	unlock_file($hlock);

	if($sips) {
		foreach($sips as $endpoint_name=>$content) {
			if(!isset($content['order'])) {
				unset($sips[$endpoint_name]);
				continue;
			}

			if(!isset($content['username'])) {
				unset($sips[$endpoint_name]);
				continue;
			}
			if(!isset($content['registration'])) {
				unset($sips[$endpoint_name]);
				continue;
			}

			$username = trim($content['username']);
			if($username == '') {
				unset($sips[$endpoint_name]);
				continue;
			}

			$registration = trim($content['registration']);
			if($registration == '') {
				unset($sips[$endpoint_name]);
				continue;
			}

			$order = trim($content['order']);
			if($order == '') {
				unset($sips[$endpoint_name]);
				continue;
			}

			$sips[$endpoint_name]['endpoint_name'] = trim($endpoint_name);

			if($sort) {
				$ary2[$endpoint_name]  = $order;
			}
		}

		if(isset($ary2)) {
			array_multisort($ary2,SORT_ASC,$sips);  //It will be change key if your set key is numbers. Freedom Note 2012-11-27
		}
	}

	return $sips;
}


function get_all_routings($sort = false)
{
	// /etc/asterisk/gw_routing.conf
	// [routing_name]
	// order = 1,2,3,4,5....    //Must set
	// from_channel = channel (sip-sipname or gsm-gsmname  type: sip- is SIP, gsm- is GSM)
	// to_channel = channel,channel......
	// dial_pattern = prepend|prefix|match pattern|cid,prepend|prefix|match pattern|cid........
	// time_pattern = time|week|day|month,time|week|day|month........
	// cid_name
	// cid_number
	// forward_number

	$routings = array();
	$aql = new aql();
	$aql->set('basedir','/etc/asterisk');
	$hlock = lock_file('/etc/asterisk/gw_routing.conf');
	$routings = $aql->query("select * from gw_routing.conf");
	unlock_file($hlock);

	if($routings) {
		foreach($routings as $key=>$value) {
			if(!isset($value['from_channel']) || (trim($value['from_channel']) == '')) {
				unset($routings[$key]);
				continue;
			} 

			if(!isset($value['to_channel']) || (trim($value['to_channel']) == '')) {
				unset($routings[$key]);
				continue;
			}

			if(!isset($value['order']) || (trim($value['order']) == '')) {
				unset($routings[$key]);
				continue;
			}

			$order = trim($value['order']);
			if($order == '') {
				unset($routings[$key]);
				continue;
			}

			$routings[$key]['routing_name'] = trim($key);    //Must save key, because call array_multisort then will change key name. Freedom Note 2012-11-27

			if(!isset($routings[$key]['dial_pattern']))
				$routings[$key]['dial_pattern'] = '';

			if(!isset($routings[$key]['time_pattern']))
				$routings[$key]['time_pattern'] = '';

			if(!isset($routings[$key]['cid_name']))
				$routings[$key]['cid_name'] = '';

			if(!isset($routings[$key]['cid_number']))
				$routings[$key]['cid_number'] = '';

			if(!isset($routings[$key]['forward_number']))
				$routings[$key]['forward_number'] = '';

			if($sort) {
				$ary2[$key]  = $order;
			}
		}

		if(isset($ary2)) {
			array_multisort($ary2,SORT_ASC,$routings);  //It will be change key if your set key is numbers. Freedom Note 2012-11-27
		}
	}

	if(is_array($routings)) {
		return $routings;
	}

	return false;
}


function get_all_groups($sort = false)
{
	// /etc/asterisk/gw_group.conf
	// [group_name]
	// order = 1,2,3,4,5....    //Must set
	// type = sip or gsm
	// policy = 
	// members = channel,channel......

	$aql = new aql();
	$aql->set('basedir','/etc/asterisk');
	$hlock = lock_file('/etc/asterisk/gw_group.conf');
	$groups = $aql->query("select * from gw_group.conf");
	unlock_file($hlock);

	if($groups) {
		foreach($groups as $key=>$value) {
			if(!isset($value['type']) || (trim($value['type']) == '')) {
				unset($groups[$key]);
				continue;
			} 

			if(!isset($value['policy']) || (trim($value['policy']) == '')) {
				unset($groups[$key]);
				continue;
			} 


			if(!isset($value['members']) || (trim($value['members']) == '')) {
				unset($groups[$key]);
				continue;
			}

			if(!isset($value['order']) || (trim($value['order']) == '')) {
				unset($groups[$key]);
				continue;
			}

			$order = trim($value['order']);
			if($order == '') {
				unset($groups[$key]);
				continue;
			}

			$groups[$key]['group_name'] = trim($key);    //Must save key, because call array_multisort then will change key name. Freedom Note 2012-11-27

			if($sort) {
				$ary2[$key]  = $order;
			}
		}

		if(isset($ary2)) {
			array_multisort($ary2,SORT_ASC,$groups);  //It will be change key if your set key is numbers. Freedom Note 2012-11-27
		}
	}

	return $groups;
}


function get_group_info($name,$sort=false)
{
	// /etc/asterisk/gw_group.conf
	// [group_name]
	// order = 1,2,3,4,5....    //Must set
	// type = sip or gsm
	// policy = 
	// members = channel,channel......

	$info = array();
	$members = array();
	$devices = '';

	$aql = new aql();
	$aql->set('basedir','/etc/asterisk');
	$hlock = lock_file('/etc/asterisk/gw_group.conf');
	$groups = $aql->query("select * from gw_group.conf");
	unlock_file($hlock);

	$type = '';
	$policy = '';

	if(isset($groups[$name]['members']) && isset($groups[$name]['type']) && isset($groups[$name]['policy'])) {
		$output = explode(',',$groups[$name]['members']);
		foreach($output as $mb) {
			$channel = trim($mb);
			$members[] = $channel;
			$chan_info = get_channel_info($channel);
			$devices .= $chan_info['device'].'&';
		}
		$devices = rtrim($devices,'&');

		$type = trim($groups[$name]['type']);
		$policy = trim($groups[$name]['policy']);
	}

	$info['members'] = $members;
	$info['type'] = $type;
	$info['policy'] = $policy;
	$info['devices'] = $devices;

	return $info;
}


function is_valid_phone_num($str)
{
	if(preg_match('/^[*#+0-9]+$/',$str)) {
		return true;
	}
	return false;
}

function save_routings_to_extensions()
{
	// /etc/asterisk/gw_routing.conf
	// [routing_name]
	// order = 1,2,3,4,5....    //Must set
	// from_channel = channel (sip-sipname or gsm-gsmname  type: sip- is SIP, gsm- is GSM, or Board-gsm- is other board gsm)
	// to_channel = channel,channel......
	// dial_pattern = prepend|prefix|match pattern|cid,prepend|prefix|match pattern|cid........
	// time_pattern = time|week|day|month,time|week|day|month........
	// cid_name
	// cid_number
	// forward_number

	global $__SIP_HEAD__;
	global $__GSM_HEAD__;
	global $__RTG_HEAD__;
	global $__GSM_SUM__;
	global $__BRD_HEAD__;
	global $__BRD_SUM__;

	$MAX_PREFIX = 32;

	$cdr_sw = get_cdr_sw();

	$internalcall_sw = get_intercall_sw();

	$mnpinfo = get_mnp_info();

	$hangupcausecode = get_hangupcausecode();

	$write_array = array();

	$all_routings = get_all_routings(true);
	if($all_routings) {
		foreach($all_routings as $routing) {
			$routing_name = trim($routing['routing_name']);
			$fc = trim($routing['from_channel']);

			$fc_info = get_channel_info($fc);

			$from_channels = array();

			if($fc_info['type'] == 'grp') {
				$grp_info = get_group_info($fc_info['name']);
				$from_channels = $grp_info['members'];
			} else {
				$from_channels[0] = $fc;
			}

			//Parse dial_pattern
			/////////////////////////////////////////////////////
			$dial_pattern = false;
			if(isset($routing['dial_pattern'])) {
				$dial_pattern = parse_dial_pattern(trim($routing['dial_pattern']));
			}
			/////////////////////////////////////////////////////

			//Parse time_pattern
			/////////////////////////////////////////////////////
			$time_pattern = false;
			if(isset($routing['time_pattern'])) {
				$time_pattern = parse_time_pattern(trim($routing['time_pattern']));
			}
			/////////////////////////////////////////////////////

			$routing_index = 0;
			foreach($from_channels as $from_channel) {
				$routing_index++;
				$routing_context = $__RTG_HEAD__.$routing_name.'-'.$routing_index;

				$from_channel_info = get_channel_info($from_channel);
				$to_channel = trim($routing['to_channel']);

				$grp_policy_act_ary = array();   //For Group policy

				if(isset($routing['cid_name']))
					$cid_name = trim($routing['cid_name']);
				else
					$cid_name = '';

				if(isset($routing['cid_number']))
					$cid_number = trim($routing['cid_number']);
				else
					$cid_number = '';

				if(isset($routing['forward_number']))
					$forward_number = trim($routing['forward_number']);
				else
					$forward_number = '';

				// [$__RTG_HEAD__ routing_name-$routing_index] context
				////////////////////////////////////////////////////////////////////
				$devices = '';
				$to_channels = explode(',',$to_channel);
				if($to_channels) {
					foreach($to_channels as $each) {
						$channel = trim($each);
						$info = get_channel_info($channel);

						if($info['type'] == 'grp') {  //Group, It's need policy
							$grp_name = $info['name'];
							$grp_policy_name = 'POLICY_'.$grp_name;
						
							//$grp_policy_act_ary[] = "Set($grp_policy_name=\${SHELL(/my_tools/grppolicy \"$grp_name\" 2> /dev/null)})";
							//$grp_policy_act_ary[] = "Set($grp_policy_name=\${GRPPOLICY($grp_name)})";
							$grp_policy_act_ary[] = "GrpPolicy($grp_name)";
							$devices .= "\${".$grp_policy_name."}";
						} else {   // General channel
							$cdr_name = $info['name'];
							$device = $info['device'];

							//s:server, c:client, t:trunk.
							if($from_channel_info['type'] == 'sip' || $from_channel_info['type'] == 'anysip' ) {
								if($info['type'] == 'sip') {
									if($info['registration'] == 'server') {
										$devices .= "$device,0,$cdr_name,";      // SIP(s,c,t) ---> SIP(s)   Transmit exten number
									} else {
										$devices .= "$device,0,$cdr_name,";      // SIP(s,c,t) ---> SIP(c,t) Transmit exten number
									}
								} else {
									$devices .= "$device,0,$cdr_name,";          // SIP(s,c,t) ---> GSM      Transmit exten number
								}
							} else {
								if($info['type'] == 'sip') {
									if($info['registration'] == 'server') {  
										$devices .= "$device,1,$cdr_name,";      // GSM ---> SIP(s)          Not need transmit exten number
									} else {
										$devices .= "$device,1,$cdr_name,";      // GSM ---> SIP(c,t)        Not need transmit exten number
									}
								} else {
									$devices .= "$device,1,$cdr_name,";          // GSM ---> GSM             Not need transmit exten number
								}
							}
						}
					}
					$devices = rtrim($devices,',');
				}

				if(isset($dial_pattern) && is_array($dial_pattern)) {
					if($from_channel_info['type'] == 'gsm') {
						$exten['s']['e'] = 's';
						$exten['s']['d'] = '';
					} else if($from_channel_info['type'] == 'brd') {
						$exten[0]['e'] = '_[*#+0-9].';
						$exten[0]['d'] = '';
					} else {
						$i = 0;
						foreach($dial_pattern as $each) {
							$prepend = '';
							if(isset($each['prepend'])) {
								$prepend = $each['prepend'];
							}
							$prefix = '';
							if(isset($each['prefix'])) {
								$prefix = $each['prefix'];
							}
							$pattern = '';
							if(isset($each['pattern'])) {
								$pattern = $each['pattern'];
							}
							$cid = '';
							if(isset($each['cid'])) {
								$cid = $each['cid'];
							}

							if($prefix == '') {
								if($pattern == '') {
									$exten[$i]['e'] = '_[*#+0-9].';
								} else {
									if(is_valid_phone_num($pattern)) {
										$exten[$i]['e'] = "$pattern";
									} else {
										$exten[$i]['e'] = "_$pattern";
									}
								}
							} else {
								if($pattern == '') {
									if(is_valid_phone_num($prefix)) {
										$exten[$i]['e'] = "$prefix";
									} else {
										$exten[$i]['e'] = "_$prefix";
									}
								} else {
									if(is_valid_phone_num($prefix) && is_valid_phone_num($pattern)) {
										$exten[$i]['e'] = "$prefix$pattern";
									} else {
										$exten[$i]['e'] = "_$prefix$pattern";
									}
								}
							}

							if($cid != '') {
								if(is_valid_phone_num($cid)) {
									$exten[$i]['e'] .= "/$cid"; 
								} else {
									$exten[$i]['e'] .= "/_$cid";
								}
							}

							$strip = strlen($prefix);

							if($prepend == '') {
								if($strip > 0) {
									$exten[$i]['d'] = "\${EXTEN:$strip}";
								} else {
									$exten[$i]['d'] = "\${EXTEN}";
								}
							} else {
								if(is_valid_phone_num($prepend)){
									if($strip > 0) {
										$exten[$i]['d'] = "$prepend\${EXTEN:$strip}";
									} else {
										$exten[$i]['d'] = "$prepend\${EXTEN}";
									}
								} else {
									if($strip > 0) {
										$exten[$i]['d'] = "_$prepend\${EXTEN:$strip}";
									} else {
										$exten[$i]['d'] = "_$prepend\${EXTEN}";
									}
								}
							}
						
							$i++;
						}
					} 
				} else {
					if($from_channel_info['type'] == 'gsm') {
						$exten['s']['e'] = 's';
						$exten['s']['d'] = '';
					} else if($from_channel_info['type'] == 'brd') {
						$exten[0]['e'] = '_[*#+0-9].';
						$exten[0]['d'] = '';
					} else {
						$exten[0]['e'] = '_[*#+0-9].';
						$exten[0]['d'] = '${EXTEN}';
					}
				}


				$routing_content = '';
				foreach($exten as $each) {
					$routing_content .= "exten => $each[e],1,NoOp($each[e] matches Rule $routing_context)\n";

					if($from_channel_info['type'] == 'brd') {
						//Modify CALLERID(number)
						//SIP From header:
						// "GSM callerid"<sip:hide sip extension@ip>;
						// "GSM callerid" is ${CALLERID(name)}.
						// "hide sip extension" is ${CALLERID(number)}
						//Not need show 'hide sip extension'
						//So neeed change
						$routing_content .= "exten => $each[e],n,Set(CALLERID(number)=\${CALLERID(name)})\n";
						//$routing_content .= "exten => $each[e],n,SIPAddHeader(X-Best-Codec: \${CHANNEL(audionativeformat)})\n";
					}

					$routing_content .= "exten => $each[e],n,Set(CDR_CALLEEID=\${EXTEN})\n";

					//Set CID name and CID number
					//Must use temporay value to save CID name and CID number, If you need swap CID name and CID number.
					///////////////////////////////////////////////////////////////////////////////////////
					if($cid_name != '')
						$routing_content .= "exten => $each[e],n,Set(T_CID_NAME=$cid_name)\n";
					if($cid_number != '')
						$routing_content .= "exten => $each[e],n,Set(T_CID_NUM=$cid_number)\n";
					if($cid_name != '')
						$routing_content .= "exten => $each[e],n,Set(CALLERID(name)=\${T_CID_NAME})\n";
					if($cid_number != '')
						$routing_content .= "exten => $each[e],n,Set(CALLERID(number)=\${T_CID_NUM})\n";
					///////////////////////////////////////////////////////////////////////////////////////

					if($devices != '') {
						foreach($grp_policy_act_ary as $act) {
							$routing_content .= "exten => $each[e],n,$act\n";
						}

						if(is_true($mnpinfo['enable']) && $mnpinfo['manipulation'] == 'before' && $each['e'] != 's') {
							$mnpinfo['url'] = str_replace("\${num}","$each[d]",$mnpinfo['url']);

							$routing_content .= "exten => $each[e],n,Set(CURLOPT(conntimeout)=$mnpinfo[timeout])\n";
							$routing_content .= "exten => $each[e],n,Set(CURLOPT(httptimeout)=$mnpinfo[timeout])\n";
							$routing_content .= "exten => $each[e],n,Set(CURLOPT(ssl_verifypeer)=0)\n";
							$routing_content .= "exten => $each[e],n,Set(NEWEXTEN=\${CURL($mnpinfo[url])})\n";
							$routing_content .= "exten => $each[e],n,GotoIf(\$[\"\${NEWEXTEN}\" != \"\"]?newexten)\n";
							$routing_content .= "exten => $each[e],n,Macro(dial-failover,$forward_number,$each[d],$devices)\n";
							$routing_content .= "exten => $each[e],n,GotoIf(1?out)\n";
							$routing_content .= "exten => $each[e],n(newexten),Macro(dial-failover,$forward_number,\${NEWEXTEN},$devices)\n";
							$routing_content .= "exten => $each[e],n(out),NoOp\n";
						} else {
							$routing_content .= "exten => $each[e],n,Macro(dial-failover,$forward_number,$each[d],$devices)\n";
						}
						$routing_content .= "exten => $each[e],n,Goto(nocdr,s,1)\n";
					}
				}
				unset($exten);

				$write_array[$routing_context] = $routing_content;
				///////////////////////////////////////////////////////////////////////

				// [from_channel] context
				///////////////////////////////////////////////////////////////////////
				$channel_context = $from_channel_info['context'];
				if($channel_context) {
					$from_channel_content = '';
					if(isset($time_pattern) && is_array($time_pattern)) {
						foreach($time_pattern as $tp) {
							$time='*';
							$week='*';
							$day='*';
							$month='*';
							if($tp['stime'] != '' && $tp['stime'] != ':' && $tp['etime'] != '' && $tp['etime'] != ':')  $time = $tp['stime'].'-'.$tp['etime'];
							if($tp['sweek'] != '' && $tp['sweek'] != '')  $week = $tp['sweek'].'-'.$tp['eweek'];
							if($tp['sday'] != '' && $tp['sday'] != '')  $day = $tp['sday'].'-'.$tp['eday'];
							if($tp['smonth'] != '' && $tp['smonth'] != '')  $month = $tp['smonth'].'-'.$tp['emonth'];

							$gotoiftime_str = ",$time,$week,$day,$month";
							$from_channel_content .= "include => $routing_context$gotoiftime_str\n";
						}
					} else {
						$from_channel_content .= "include => $routing_context\n";
					}

					if(array_key_exists($channel_context,$write_array)) { // [from_channel] context already writed
						$write_array[$channel_context] .= $from_channel_content;
					} else {  //First write
						$write_array[$channel_context] = $from_channel_content;
					}
				}
				///////////////////////////////////////////////////////////////////////
			}
		}
	}


	// Set All Channel context (Defaults)
	//////////////////////////////////////////////////////////////////////////////////
	// GSM
	$sms_info = get_sms_info();

	$sms_to_http_url = '';
	if(is_true($sms_info['sms_to_http']['enable'])){
		if(isset($sms_info['sms_to_http']['url']) && $sms_info['sms_to_http']['url'] != ''){
			$board = get_slotnum();
			$sms_to_http_url = $sms_info['sms_to_http']['url'];
		}
	}
	for($i=1; $i<=$__GSM_SUM__; $i++) {
		$key = $__GSM_HEAD__.$i;
		if(!array_key_exists($key,$write_array)) {
			$write_array[$key] = '';
			if($internalcall_sw) {
				$write_array[$key] .= "exten => s,1,Answer(\${RAND(1000,5000)},nocdr)\n";
				$write_array[$key] .= "exten => s,n,Set(X=1)\n";
				$write_array[$key] .= "exten => s,n(loop),PlayBack(/sound/demo-instruct)\n";
				$write_array[$key] .= "exten => s,n,Wait(\${RAND(1,10)})\n";
				$write_array[$key] .= "exten => s,n,Set(X=\${INC(X)})\n";
				$write_array[$key] .= "exten => s,n,GotoIf(\$[\${X}<=200]?loop)\n";
				$write_array[$key] .= "exten => s,n,Hangup()\n";
			} else {
				$write_array[$key] .= "exten => s,1,NoOp(Nothing to do, Not setting out channel)\n";
				$write_array[$key] .= "exten => s,n,Hangup()\n";
			}
		} else {
			if($cdr_sw) {
				$write_array[$key] .= "exten => h,1,WriteCDR(\"\${CDR(src)}\",\"\${CDR_CALLEEID}\",\"$__GSM_HEAD__$i\",\"\${CDR_TOCHAN}\",\"\${CDR(start)}\",\"\${CDR(billsec)}\",\"\${CDR(disposition)}\")\n";
			}
		}
//		$write_array[$key] .= "exten => sms_send_ok, 1, System(touch /tmp/sms/S\${SMS_SEND_ID})\n";
//		$write_array[$key] .= "exten => sms_send_failed, 1, System(touch /tmp/sms/F\${SMS_SEND_ID})\n\n";
		$write_array[$key] .= "exten => sms,1,NoOp(SMS In)\n";
		if($sms_to_http_url != ''){
			$write_array[$key] .= <<<EOF
exten => sms,n,NoOp(SMS CURL)
exten => sms,n,Set(CURLOPT(conntimeout)=5)
exten => sms,n,Set(CURLOPT(httptimeout)=5)
exten => sms,n,Set(CURLOPT(ssl_verifypeer)=0)
exten => sms,n,Set(phonenumber=\${URIENCODE(\${SMSSRC})})
exten => sms,n,Set(port=\${URIENCODE($__GSM_HEAD__$board.$i)})
exten => sms,n,Set(message=\${URIENCODE(\${SMSORITXT})})
exten => sms,n,Set(time=\${URIENCODE(\${SMSTIME})})
exten => sms,n,Set(RET=\${CURL($sms_to_http_url)})

EOF;
//EOF
		}
		
		if(is_true($sms_info['local_store']['enable'])){
			$write_array[$key] .= "exten => sms,n,System(/my_tools/process_sms \"$i\" \"\${SMSSRC}\" \"\${SMSTIME}\" \"\${SMSTXT}\" > /dev/null 2>&1 &)\n\n";
		}
		
	}

	// SIP
	$internal_sip_call = get_internal_sip_call();
	$allsips = get_all_sips();
	$write_array['nothingtodo'] = "exten => _[*#+0-9].,1,NoOp(Nothing to do, Not setting out channel)\n";
//	$write_array['nothingtodo'] .= "exten => _[*#+0-9].,n,Set(CDR_CALLEEID=\${EXTEN})\n";
	$write_array['nothingtodo'] .= "exten => _[*#+0-9].,n,Hangup($hangupcausecode)\n\n";

	if($allsips) {
		foreach($allsips as $sip) {
			if(isset($sip['context']) && $sip['context']) {
				$info = get_channel_info($__SIP_HEAD__.$sip['endpoint_name']);
				$key = $info['context'];
				if(!array_key_exists($key,$write_array)) {

					if(array_key_exists('sipdefault',$write_array)) {
						$write_array[$key] = "include => sipdefault\n";
					} else {
						$write_array[$key] = "include => nothingtodo\n";
					}
				} else {

					$hangup_start = '1';
					if($internalcall_sw) {
						$write_array[$key] .= "exten => h,1,IncraseOutCounter(\${OUTDEV})\n";
						$hangup_start = 'n';
					}

					if($cdr_sw) {
						$cdr_from = $sip['endpoint_name'];
						$write_array[$key] .= "exten => h,${hangup_start},WriteCDR(\"\${CDR(src)}\",\"\${CDR_CALLEEID}\",\"$cdr_from\",\"\${CDR_TOCHAN}\",\"\${CDR(start)}\",\"\${CDR(billsec)}\",\"\${CDR(disposition)}\")\n";
					}
				}

				if($internal_sip_call) {
					if($sip['registration'] == 'server') {
						$write_array[$key] = "include => internalsip\n" . $write_array[$key];  //Must put on first line.
					}
				}
			}
		}
		if($internal_sip_call) {
			$prefix = $internal_sip_call['prefix'];
			$len = strlen($prefix);
			$each = '_'.$prefix.'X.';
			$num = '${EXTEN:'.$len."}";
			$write_array['internalsip'] = "exten => $each,1,NoOp(SIP Internal Call\n";
 			$write_array['internalsip'] .= "exten => $each,n,Set(CDR_CALLEEID=$num)\n";
//			$write_array['internalsip'] .= "exten => $each,n,Set(CDR_CALLEEID=\${EXTEN})\n";
			$write_array['internalsip'] .= "exten => $each,n,Set(CDR_TOCHAN=$num)\n";
			$write_array['internalsip'] .= "exten => $each,n,Set(CDR_TOCHAN=\${SHELL(/my_tools/get_sipendpointname \"\${CDR_TOCHAN}\")})\n";
			$write_array['internalsip'] .= "exten => $each,n,Dial(SIP/$num)\n";
			$write_array['internalsip'] .= "exten => $each,n,Hangup($hangupcausecode)\n\n";
		}
	}

	// Board
	$brd_info = get_cluster_info();
	for($b=2; $b<=$__BRD_SUM__; $b++) {
		if(isset($brd_info[$__BRD_HEAD__.$b.'_ip']) && $brd_info[$__BRD_HEAD__.$b.'_ip'] != '' ) {
			for($i=1; $i<=$__GSM_SUM__; $i++) {
				$key = $__BRD_HEAD__.$b.'-'.$__GSM_HEAD__.$i;
				if(!array_key_exists($key,$write_array)) {
					$write_array[$key] = '';
					if($internalcall_sw) {
						$write_array[$key] .= "exten => _[*#+0-9].,1,Answer(\${RAND(1000,5000)},nocdr)\n";
						$write_array[$key] .= "exten => _[*#+0-9].,n,Set(X=1)\n";
						$write_array[$key] .= "exten => _[*#+0-9].,n(loop),PlayBack(/sound/demo-instruct)\n";
						$write_array[$key] .= "exten => _[*#+0-9].,n,Wait(\${RAND(1,10)})\n";
						$write_array[$key] .= "exten => _[*#+0-9].,n,Set(X=\${INC(X)})\n";
						$write_array[$key] .= "exten => _[*#+0-9].,n,GotoIf(\$[\${X}<=200]?loop)\n";
						$write_array[$key] .= "exten => _[*#+0-9].,n,Hangup()\n";
					} else {
						$write_array[$key] .= "exten => _[*#+0-9].,1,NoOp(Nothing to do, Not setting out channel)\n";
						$write_array[$key] .= "exten => _[*#+0-9].,n,Hangup()\n";
					}
				} else {
					if($cdr_sw) {
						$write_array[$key] .= "exten => h,1,WriteCDR(\"\${CALLERID(name)}\",\"\${CDR_CALLEEID}\",\"$key\",\"\${CDR_TOCHAN}\",\"\${CDR(start)}\",\"\${CDR(billsec)}\",\"\${CDR(disposition)}\")\n";
					}
				}

//				$write_array[$key] .= "exten => smsfromsip,1,NoOp(Do SMS process)\n";
//				$write_array[$key] .= "exten => smsfromsip,n,Set(SMSSRC=\${SIP_HEADER(SMSSRC)})\n";
//				$write_array[$key] .= "exten => smsfromsip,n,Set(SMSTIME=\${SIP_HEADER(SMSTIME)})\n";
//				$write_array[$key] .= "exten => smsfromsip,n,Set(SMSTXT=\${SIP_HEADER(SMSTXT)})\n";
//				$write_array[$key] .= "exten => smsfromsip,n,System(/my_tools/process_sms \"$key\" \"\${SMSSRC}\" \"\${SMSTIME}\" \"\${SMSTXT}\")\n";

//				$write_array[$key] .= "exten => smsfromsip,n,Set(SMSTXT=\${SIP_HEADER(SMSTXT)})\n";
//				$write_array[$key] .= "exten => smsfromsip,n,Set(SMSTXT=\${REPLACE(SMSTXT,\\\\030,\\r)})\n";
//				$write_array[$key] .= "exten => smsfromsip,n,Set(SMSTXT=\${REPLACE(SMSTXT,\\\\031,\\n)})\n";
//				$write_array[$key] .= "exten => smsfromsip,n,System(/my_tools/process_sms \"$key\" \"\${SIP_HEADER(SMSSRC)}\" \"\${SIP_HEADER(SMSTIME)}\" \"\${SMSTXT}\")\n";
//				$write_array[$key] .= "exten => smsfromsip,n,Goto(nocdr,s,1)\n";
			}
		}
	}
	//////////////////////////////////////////////////////////////////////////////////

/*
mnp_enable=on
mnp_url=https://s1.bichara.com.br:8181/chkporta.php?user=832700&pwd=sfhawz826&tn=8388166902
mnp_timeout=2
*/
	//For all sip endpoint inbound
	if(is_true($mnpinfo['enable']) && $mnpinfo['manipulation'] != 'before' ) {

		$mnpinfo['url'] = str_replace("\${num}","\${EXTEN}",$mnpinfo['url']);

		$sipinbound_content = <<<EOF
exten => _[*#+0-9].,1,NoOp(SIP Inbound)
exten => _[*#+0-9].,n,Set(CURLOPT(conntimeout)=$mnpinfo[timeout])
exten => _[*#+0-9].,n,Set(CURLOPT(httptimeout)=$mnpinfo[timeout])
exten => _[*#+0-9].,n,Set(CURLOPT(ssl_verifypeer)=0)
exten => _[*#+0-9].,n,Set(NEWEXTEN=\${CURL($mnpinfo[url])})
exten => _[*#+0-9].,n,GotoIf(\$["\${NEWEXTEN}" = ""]?oldexten)
exten => _[*#+0-9].,n,GotoIf(\${DIALPLAN_EXISTS(\${SIPROUTE},\${NEWEXTEN},1)}?:nocdr)
exten => _[*#+0-9].,n,Goto(\${SIPROUTE},\${NEWEXTEN},1)
exten => _[*#+0-9].,n(oldexten),GotoIf(\${DIALPLAN_EXISTS(\${SIPROUTE},\${EXTEN},1)}?:nocdr)
exten => _[*#+0-9].,n,Goto(\${SIPROUTE},\${EXTEN},1)
exten => _[*#+0-9].,n(nocdr),Goto(nocdr,s,1)
EOF;
//EOF
	} else {
	$sipinbound_content = <<<EOF
exten => _[*#+0-9].,1,NoOp(SIP Inbound)
exten => _[*#+0-9].,n,GotoIf(\${DIALPLAN_EXISTS(\${SIPROUTE},\${EXTEN},1)}?:nocdr)
exten => _[*#+0-9].,n,Goto(\${SIPROUTE},\${EXTEN},1)
exten => _[*#+0-9].,n(nocdr),Goto(nocdr,s,1)
EOF;
//EOF
	}
	$write_array['sipinbound'] = $sipinbound_content;


	//For No CDR process.
	$write_array['nocdr'] = "exten => s,1,Hangup($hangupcausecode)\n";


	//For globals variable
	$write_array['globals'] = "SIPROUTE=sipdefault\n";


	if(array_key_exists('sipdefault',$write_array)) {
		$hangup_start = '1';
		if($internalcall_sw) {
			$write_array['sipdefault'] .= "exten => h,1,IncraseOutCounter(\${OUTDEV})\n";
			$hangup_start = 'n';
		}

		//For Any SIP Write CDR
		if($cdr_sw) {
			$write_array['sipdefault'] .= "exten => h,${hangup_start},WriteCDR(\"\${CDR(src)}\",\"\${CDR_CALLEEID}\",\"\${CDR(src)}\",\"\${CDR_TOCHAN}\",\"\${CDR(start)}\",\"\${CDR(billsec)}\",\"\${CDR(disposition)}\")\n";
		}
	}


	//For GSM Auto Internal Call Dialplan, Play sound.
	if($internalcall_sw) {
		$write_array['internalcall'] = "exten => s,1,Set(X=1)\n";
		$write_array['internalcall'] .= "exten => s,n(loop),PlayBack(/sound/demo-instruct)\n";
		$write_array['internalcall'] .= "exten => s,n,Wait(\${RAND(1,10)})\n";
		$write_array['internalcall'] .= "exten => s,n,Set(X=\${INC(X)})\n";
		$write_array['internalcall'] .= "exten => s,n,GotoIf(\$[\${X}<=200]?loop)\n";
		$write_array['internalcall'] .= "exten => s,n,Hangup()\n";
	}

	//Write to extensions_routing.conf
	//////////////////////////////////////////////////////////////////////////////////
	$gsm_already_context = array();
	$cfg_file = '/etc/asterisk/extensions_routing.conf';
	$hlock = lock_file($cfg_file);
	$handle = fopen($cfg_file,"w");
	if($write_array) {
		foreach($write_array as $key => $value) {
			$str = "[$key]\n" . $value."\n\n";
			fwrite($handle,$str);
		}
	}
	fclose($handle);
	unlock_file($hlock);
	//////////////////////////////////////////////////////////////////////////////////
}

function del_all_register()
{
	$sip_general_conf = "/etc/asterisk/sip_general.conf";

	if(!file_exists($sip_general_conf))
		return;

	$hlock = lock_file($sip_general_conf);
	$handle = fopen($sip_general_conf,"r");
	$start = false;

	$write_str = '';
	while (!feof($handle)) {
		$line = fgets($handle);

		if(!$start) {
			if(preg_match("/^\s*\[\s*general\s*\]\s*/",$line)) {
				$start = true;
			}
		} else {
			if(preg_match("/^\s*\[\s*.*\s*\]\s*/",$line)) {
				$start = false;
			}
		}

		if($start) {
			if(preg_match("/^\s*register\s*=\s*.*/",$line)) {
				continue;
			}
		}

		$write_str .= $line;
	}
	fclose($handle);

	//Write
	if($write_str != '') {
		$handle = fopen($sip_general_conf,"w");
		fwrite($handle,$write_str);
		fclose($handle);
	}
	unlock_file($hlock);
}

function set_sip_general_allowguest()
{
	$sip_general_conf = "/etc/asterisk/sip_general.conf";

	if(!file_exists($sip_general_conf))
		return;

	$hlock = lock_file($sip_general_conf);
	$handle = fopen($sip_general_conf,"r");
	$guest_set = false;
	$context_set =false;

	$write_str = '';
	while (!feof($handle)) {
		$line = fgets($handle);

		if(!$guest_set) {
			if(preg_match("/^\s*allowguest\s*=\s*.*/",$line)) {
				$write_str .= "allowguest=yes\n";
				$guest_set = true;
				continue;
			}
		} 

		if(!$context_set) {
			if(preg_match("/^\s*context\s{0,}=.*/",$line)) {
				$write_str .= "context=sipinbound\n";
				$context_set = true;
				continue;
			}
		}

		$write_str .= $line;
	}
	fclose($handle);

	if(!$guest_set) {
		$write_str .= "allowguest=yes\n";
	}

	if(!$context_set) {
		$write_str .= "context=sipinbound\n";
	}

	//Write
	if($write_str != '') {
		$handle = fopen($sip_general_conf,"w");
		fwrite($handle,$write_str);
		fclose($handle);
	}
	unlock_file($hlock);
}

function save_endpoints_to_sips()
{
/* /etc/asterisk/gw_endpoints.conf */
// [endpoint_name]
// order = 1,2,3,4,5....    //Must set
// username = 
// registration =  
// allow_anonymous = yes|no
// register =    like sip.conf [general]
// .......       like all sip.conf

	global $__SIP_HEAD__;

	$cfg_file = '/etc/asterisk/sip_endpoints.conf';

	del_all_register();
	$all_sips = get_all_sips();

	if($all_sips) {
		$write_str='';
		foreach($all_sips as $sip) {
			$username = $sip['username'];
			$context = $username;
				
			if(isset($sip['host'])) {
				$ip = trim($sip['host']);
				if($ip != '' && $ip != 'dynamic') {
					$context = $username.'-'.$ip;
				}

				if(isset($sip['allow_anonymous']) && $sip['allow_anonymous'] == 'yes')  {
					$context = $ip;
				}
			}
			$write_str .= "[$context]\n";

			foreach($sip as $key=>$value) {
				if($key == 'allow') {
				    $allow = explode(',',$value);
					foreach($allow as $each) {
						if($each != '')
							$write_str .= "allow=$each\n";
					}
				} else if($key == 'disallow') {
					continue; 
				} else if($key =='context') {
					if(isset($sip['allow_anonymous']) && $sip['allow_anonymous'] == 'yes')  {
						//$write_str .= "context=".$__SIP_HEAD__."anonymous-$context\n"; 
						//Freedom Modify for mnp 2014-01-16 13:23
						//$write_str .= "context=".$__SIP_HEAD__."$context\n";
						$write_str .= "context=sipinbound\n";
						$write_str .= "setvar=SIPROUTE=".$__SIP_HEAD__."$context\n";
					} else {
						//Freedom Modify for mnp 2014-01-16 13:23
						//$write_str .= "context=".$__SIP_HEAD__."$context\n"; 
						$write_str .= "context=sipinbound\n";
						$write_str .= "setvar=SIPROUTE=".$__SIP_HEAD__."$context\n";
					}
				} else if($key == 'register') {
					$register[] = $value;
				} else if($key == 'order') {
					//Not do anythings
				} else if($key == 'registration') {
					//Not do anythings
				} else if($key == 'register_extension') {
				    //Not do anythings
				} else if($key == 'allow_anonymous') {
					//Not do anythings
				} else {
					$write_str .= "$key=$value\n";
				}
			}
			$write_str .= "\n";
		}

		$hlock = lock_file($cfg_file);
		$handle = fopen($cfg_file,"w");
		fwrite($handle, $write_str);
		fclose($handle);
		unlock_file($hlock);

		if(isset($register)) {
			$aql = new aql();
			$aql->set('basedir','/etc/asterisk');
			$hlock = lock_file('/etc/asterisk/sip_general.conf');
			$exist_general_section = $aql->query("select * from sip_general.conf");
			if(!isset($exist_general_section['general'])) {  // if not exist 'general' section
				$aql->assign_addsection('general','');
				$aql->save_config_file('sip_general.conf');
			}
			foreach($register as $each) {
				$aql->assign_append('general','register','>'.$each);
			}
			$aql->save_config_file('sip_general.conf');
			unlock_file($hlock);
		}
	} else {
		$hlock = lock_file($cfg_file);
		fclose(fopen($cfg_file,"w"));
		unlock_file($hlock);
	}
}


function save_gsm_to_extra_conf()
{
/*
Location: /etc/asterisk/extra-channels.conf

group=1
context=gsm-1
signalling = gsm
vol=70
mic=1
dacgain=-15
adcgain=-3
debugat=on
band=ALL_BAND
dialprefix=
smscodec=utf-8
;simulatedial=1
;hwdtmfdet=1
switchtype=SIMCOM_SIM840W
channel => 1
dl_step=1               //>0
dl_single_sw=off        //on/off
dl_single_limit=0
dl_total_sw=off         //on/off
dl_total_limit=0
dl_free_time=0          //免费通话时长(<step)
dl_warning_time=0
dl_warning_num=
dl_warning_describe=
dl_auto_reset_sw=off    //on/off
dl_auto_reset_type=off  //day/week/month
dl_auto_reset_date=     //

group=1
...


*/


	global $__GSM_SUM__;

	$aql = new aql();

	$aql->set('basedir','/etc/asterisk');
	$hlock = lock_file('/etc/asterisk/gw_gsm.conf');
	$res = $aql->query("select * from gw_gsm.conf");
	unlock_file($hlock);

	$write_str = '';

	if(get_debugat_log_sw()) {
		$debugat='on';
	} else {
		$debugat='off';
	}

	for($channel=1; $channel <= $__GSM_SUM__; $channel++) {
		if(isset($res[$channel]['vol'])) {
			$vol=trim($res[$channel]['vol']);
		} else {
			$vol='';
		}

		if(isset($res[$channel]['mic'])) {
			$mic=trim($res[$channel]['mic']);
		} else {
			$mic='';
		}

		if(isset($res[$channel]['dacgain'])) {
			$dacgain=trim($res[$channel]['dacgain']);
		} else {
			$dacgain='';
		}

		if(isset($res[$channel]['adcgain'])) {
			$adcgain=trim($res[$channel]['adcgain']);
		} else {
			$adcgain='';
		}

		$pin = '';
		if(isset($res[$channel]['needpin'])) {
			if(trim($res[$channel]['needpin']) == "true") {
				if(isset($res[$channel]['pin'])) {
					$pin=trim($res[$channel]['pin']);
				}
			}
		}

		if(isset($res[$channel]['custom_start_at'])) {
			$custom_start_at=trim($res[$channel]['custom_start_at']);
		} else {
			$custom_start_at='';
		}

		if(isset($res[$channel]['gsm_ec'])) {
			$gsm_ec = trim($res[$channel]['gsm_ec']);
		} else {
			$gsm_ec = 'off';
		}

		if(isset($res[$channel]['anonymouscall'])) {
			$anonymouscall = trim($res[$channel]['anonymouscall']);
		} else {
			$anonymouscall = 'off';
		}

		if(isset($res[$channel]['call_waiting'])) {
			$call_waiting = trim($res[$channel]['call_waiting']);
		} else {
			$call_waiting = 'off';
		}

		if(isset($res[$channel]['band'])) {
			$band = trim($res[$channel]['band']);
		} else {
			$band = '';
		}

		if(isset($res[$channel]['dialprefix'])) {
			$dialprefix = trim($res[$channel]['dialprefix']);
		} else {
			$dialprefix = '';
		}
		
		if(isset($res[$channel]['seloperator'])) {
			$seloperator = trim($res[$channel]['seloperator']);
		} else {
			$seloperator = '';
		}

		$write_str .= "group=1\n";
		$write_str .= "context=gsm-$channel\n";
		$write_str .= "signalling = gsm\n";
		$write_str .= "vol=$vol\n";
		$write_str .= "mic=$mic\n";
		$write_str .= "dacgain=$dacgain\n";
		$write_str .= "adcgain=$adcgain\n";
		$write_str .= "debugat=$debugat\n";
		$write_str .= "gsm_ec=$gsm_ec\n";
		$write_str .= "anonymouscall=$anonymouscall\n";
		$write_str .= "call_waiting=$call_waiting\n";
		$write_str .= "band=$band\n";
		$write_str .= "dialprefix=$dialprefix\n";
		$write_str .= "seloperator=$seloperator\n";
		$write_str .= "smscodec=utf-8\n";
		$write_str .= ";simulatedial=1\n";
		$write_str .= ";hwdtmfdet=1\n";
		if($pin)$write_str .= "pin=$pin\n";
		$write_str .= "custom_start_at=$custom_start_at\n";
		$write_str .= "switchtype=SIMCOM_SIM840W\n";

		/* Dial Limit Settings */
		$dl_array = array(	'dl_step'=>'60',
					'dl_single_sw'=>'off',
					'dl_single_limit'=>'',
					'dl_total_sw'=>'off',
					'dl_total_limit'=>'',
					'dl_free_time'=>'0',
					'dl_warning_time'=>'0',
					'dl_warning_num'=>'',
					'dl_warning_describe'=>'',
					'dl_auto_reset_sw'=>'off',
					'dl_auto_reset_type'=>'day',
					'dl_auto_reset_date'=>''
				);

		foreach($dl_array as $key => $value){
			if(isset($res[$channel][$key])) {
				$write_str .= "$key=".trim($res[$channel][$key])."\n";
			} else {
				$write_str .= "$key=$value\n";
			}
		}

		$tmp = $channel*2 -1;
		$write_str .= "channel => $tmp\n\n";
	}

	$extra_conf = '/etc/asterisk/extra-channels.conf';
	$hlock = lock_file($extra_conf);
	$handle = fopen($extra_conf,"w");
	fwrite($handle, $write_str);
	fclose($handle);
	unlock_file($hlock);
}

function save_webserver_to_lighttpd()
{
/*
#/etc/asterisk/gw/lighttpdpassword
admin:admin
#/etc/asterisk/gw/lighttpd_user.conf
server.port = 80
*/
	$aql = new aql();
	$setok = $aql->set('basedir','/config/simbank/conf');
	if (!$setok) {
		echo $aql->get_error();
		return false;
	}
        
	$conf_path = '/config/simbank/conf/web_server.conf';
	if(!file_exists($conf_path)) {
		fclose(fopen($conf_path,"w"));
	}
	$hlock = lock_file($conf_path);
	if(!$aql->open_config_file($conf_path)){
		echo $aql->get_error();
		unlock_file($hlock);
		return false;
	}
	$exist_array = $aql->query("select * from web_server.conf");
	unlock_file($hlock);

	if(isset($exist_array['general']['username']) && isset($exist_array['general']['password'])){
		$username = trim($exist_array['general']['username']);
		$password = trim($exist_array['general']['password']);

		$file_path = "/etc/lighttpdpassword";
		file_put_contents($file_path, "$username:$password", LOCK_EX);
	}

	$http_port = '80';
	$https_port = '443';
	if (isset($exist_array['general']['port'])) {
		$http_port = trim($exist_array['general']['port']);
	}
	if (isset($exist_array['general']['https_port'])) {
		$https_port = trim($exist_array['general']['https_port']);
	}
	
	$write_str = '';
	if(isset($exist_array['general']['login_mode'])){
		if($exist_array['general']['login_mode'] == 'http_https'){
			$write_str .= "server.port = $http_port\n";
			$write_str .= "\$SERVER[\"socket\"] == \":$https_port\" {\n";
			$write_str .= "  ssl.engine = \"enable\"\n";
			$write_str .= "	 ssl.pemfile = \"/etc/server.pem\"\n";
			$write_str .= "}";
		}else if($exist_array['general']['login_mode'] == 'https'){
			$write_str .= "ssl.engine = \"enable\"\n";
			$write_str .= "ssl.pemfile = \"/etc/server.pem\"\n";
			$write_str .= "server.port = $https_port\n";
		}else if($exist_array['general']['login_mode'] == 'http'){
			$write_str .= "server.port = $http_port\n";
		}
	}else{
		$write_str = "server.port = $http_port\n";
	}
	
	$file_path = "/etc/lighttpd_user.conf";
	$flock = lock_file($file_path);
	file_put_contents($file_path, $write_str);
	unlock_file($flock);

	return true;
}

?>
