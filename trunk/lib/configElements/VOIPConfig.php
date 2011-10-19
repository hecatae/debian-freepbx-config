<?php
class VOIPConfig extends VOIPXmlConfiguredElement {
	public $users = array();
	public $queues = array();
	public $ringGroups = array();
	public $confs = array();
	public $trunks = array();
	public $outRoutes = array();
	public $inRoutes = array();
	public $channelMaps = array();
	public $callables = array();
	public $sounds = array();
	public $uras = array();
	public $recordings = array();
	public $announces = array();
	public $miscapps = array();
	
	public $mysqli = null;
	public $astman = null;
	
	
	// Local config fields
	public $externalIp = null;
	public $localNet = null;
	public $localNetMask = null;
	public $externalIpType = null;
	
	
	public function parse() {
		$this->externalIp = (string)$this->xml->externalIp;
		$this->localNet = (string)$this->xml->localNet;
		$this->localNetMask = (string)$this->xml->localNetMask;
		$this->externalIpType = (string)$this->xml->externalIpType;
		
		$this->info("Local configs: External IP: $this->externalIp - Local Net: $this->localNet Mask: $this->localNetMask");
	
		foreach ($this->xml->users->user as $xml) {
			new VOIPUser($xml, $this, "users", true);
		}
	
		foreach ($this->xml->queues->queue as $xml) {
			$nu = new VOIPQueue($xml, $this, "queues", true);
		}
	
		foreach ($this->xml->ringgroups->ringgroup as $xml) {
			$nu = new VOIPRingGroup($xml, $this, "ringGroups", true);
		}
	
		foreach ($this->xml->confs->conf as $xml) {
			$nu = new VOIPConference($xml, $this, "confs", true);
		}
		
		foreach ($this->xml->trunks->trunk as $xml) {
			$nu = new VOIPTrunk($xml, $this, "trunks");
		}
		
		$i = 0;
		foreach ($this->xml->outboundRoutes->outboundRoute as $xml) {
			$nu = new VOIPOutboundRoute($xml, $this, "outRoutes", false, ++$i);
		}
		
		foreach ($this->xml->inboundRoutes->inboundRoute as $xml) {
			$nu = new VOIPInboundRoute($xml, $this, "inRoutes", false);
		}
		
		foreach ($this->xml->channelMaps->channelMap as $xml) {
			$nu = new VOIPChannelMap($xml, $this, "channelMaps");
		}
		
		$i = 0;
		foreach ($this->xml->sounds->sound as $xml) {
			$nu = new VOIPSound($xml, $this, "sounds", false, ++$i);
		}		
		
		foreach ($this->xml->uras->ura as $xml) {
			$nu = new VOIPURA($xml, $this, "uras");
		}
		
		foreach ($this->xml->announcements->announce as $xml) {
			$nu = new VOIPAnnounce($xml, $this, "announces");
		}
		
		foreach ($this->xml->miscApps->miscApp as $xml) {
			$nu = new VOIPMiscApp($xml, $this, "miscapps", true);
		}
	}
	
	public function writeExcel($excel) {
		$this->excel = $excel;
		$this->writeExcelArray($this->users);
		$this->writeExcelArray($this->queues);
		$this->writeExcelArray($this->ringGroups);
		$this->writeExcelArray($this->confs);
		$this->writeExcelArray($this->trunks);
		$this->writeExcelArray($this->outRoutes);
		$this->writeExcelArray($this->channelMaps);
		$this->writeExcelArray($this->sounds);
		$this->writeExcelArray($this->uras);
		$this->writeExcelArray($this->recordings);
		$this->writeExcelArray($this->announces);
		
		$this->outRoutes[1]->writeExcelHeader(null); // update the last one width's
	}
	
	public function writeExcelArray($arr) {
		$i = 0;
		foreach ($arr as $item) {
			if ($i == 0) $item->excelWriteHeader();
			$item->excelWriteItem();
			$i++;
		}
	}	
	
	public function findRefs() {
		$this->findObjRefsArray($this->users);
		$this->findObjRefsArray($this->queues);
		$this->findObjRefsArray($this->ringGroups);
		$this->findObjRefsArray($this->confs);
		$this->findObjRefsArray($this->trunks);
		$this->findObjRefsArray($this->outRoutes);
		$this->findObjRefsArray($this->inRoutes);
		$this->findObjRefsArray($this->inRoutes);
		
		$this->findObjRefsArray($this->uras);
		$this->findObjRefsArray($this->announces);
		$this->findObjRefsArrayAutoNumber($this->recordings, 1);
		
		$this->afterFindRefs();
	}
	
	public function afterFindRefs() {
		// We need to auto-create inbound routes for extensions with DDR; the possible ones are users, queues, ringGroups, confs.
		$this->autoInboundRouteArray($this->users);
		$this->autoInboundRouteArray($this->queues);
		$this->autoInboundRouteArray($this->ringGroups);
		$this->autoInboundRouteArray($this->confs);
		
		$this->findFreeNumbers("Free extensions", array_keys($this->callables));
		$this->findFreeNumbers("Free DDR numbers", array_keys($this->filterItemsByDDR($this->callables, true)));
	}
	
	public function filterItemsByDDR($arr, $val) {
		$ret = array();
		foreach ($arr as $num => $item) {
			if ($item->ddr == $val) $ret[$num] = $item;
		}
		return $ret;
	}
	
	public function findFreeNumbers($name, $used) {
		$numbers = array();
		for ($i = 3400; $i < 3600; $i++) {
			$numbers[$i] = false;
		}
		foreach ($used as $num) {
			$numbers[$num] = true;
		}
		$frees = array();
		foreach ($numbers as $num => $isUsed) {
			if (!$isUsed) $frees[] = $num;
		}
		$freeNums = implode($frees, ",");
		$this->info($name." (". count($frees)."): " . $freeNums);
	
	}
	
	
	public function autoInboundRouteArray($arr) {
		foreach ($arr as $item) {
			$item->autoInboundRoute();
		}
	}	
	
	public function findObjRefsArray($arr) {
		foreach ($arr as $item) {
			$item->findRefs();
		}
	}
	
	public function findObjRefsArrayAutoNumber($arr, $start=0) {
		$i = $start;
		foreach ($arr as $item) {
			$item->findRefs(++$i);
		}
	}
	
	
	public function applyConfigToFreePBXArray($arr, $desc=null) {
		if ($desc) {
			$count = count($arr);
			$this->info("Applying $count $desc: ", false);
		}
		foreach ($arr as $item) {
			$item->applyConfigToFreePBX();
			echo ".";
		}
		if ($desc) {
			if (! hasErrors()) {
				echo "]=- ";
				$this->sayOK();
			}
		}
		if ($desc) echo "\n";
		
	}
	
	
	public function applyConfigToFreePBX($server) {
		$mysql = new mysqli("$server", "freepbxConfig", "ast123", "asterisk");
		if (mysqli_connect_errno()) {
			$this->error("Connect to mysql failed: " . mysqli_connect_error());
			exit();
		}
		
		$astman         = new AGI_AsteriskManager();
		if (!$res = $astman->connect($server . ":" . "5038", 'freepbxConfig' , 'ast123', 'off')) {
			$this->error("Erro conectando astman!");
			exit();
		}
		$this->config = $this;
		
		$this->mysql = $mysql;
		$this->astman = $astman;
		
		$this->getVoiceMailCurrentInfo();
		
		$this->info("Configuring basic settings...");
		$this->config->queryExec("REPLACE INTO admin (variable, value) VALUES ('email', 'ricardo@pardini.net')");
		$this->config->queryExec("REPLACE INTO admin (variable, value) VALUES ('need_reload', 'true')");
		
		$this->config->queryExec("REPLACE INTO sipsettings (keyword, data, seq, type) VALUES ('nat', 'yes', 39, 0)");
		
		if ($this->externalIpType == "dynamic") {
			$this->config->queryExec("REPLACE INTO sipsettings (keyword, data, seq, type) VALUES ('nat_mode', 'externhost', 10, 0)");
			$this->config->queryExec("REPLACE INTO sipsettings (keyword, data, seq, type) VALUES ('externhost_val', '{$this->externalIp}', 40, 0)");
			$this->config->queryExec("REPLACE INTO sipsettings (keyword, data, seq, type) VALUES ('externrefresh', '120', 41, 0)");
		} else {
			$this->config->queryExec("REPLACE INTO sipsettings (keyword, data, seq, type) VALUES ('nat_mode', 'externip', 10, 0)");
			$this->config->queryExec("REPLACE INTO sipsettings (keyword, data, seq, type) VALUES ('externip_val', '{$this->externalIp}', 40, 0)");
		}
		
		$this->config->queryExec("REPLACE INTO sipsettings (keyword, data, seq, type) VALUES ('localnet_0', '{$this->localNet}', 42, 0)");
		$this->config->queryExec("REPLACE INTO sipsettings (keyword, data, seq, type) VALUES ('netmask_0', '{$this->localNetMask}', 0, 0)");
		$this->config->queryExec("REPLACE INTO sipsettings (keyword, data, seq, type) VALUES ('sip_language', 'pt_BR', 0, 0)");
		
		$this->config->queryExec("REPLACE INTO iaxsettings (keyword, data, seq, type) VALUES ('iax_language', 'pt_BR', 0, 0)");
		
		$this->info("Clearing/deleting old settings from most FreePBX tables...");
		$this->config->queryExec("DELETE from devices");
		$this->config->queryExec("DELETE from users");
		$this->config->queryExec("DELETE from fax_users");
		$this->config->queryExec("DELETE from fax_incoming");
		$this->config->queryExec("DELETE from queues_config");
		$this->config->queryExec("DELETE from queues_details");
		$this->config->queryExec("DELETE from ringgroups");
		$this->config->queryExec("DELETE from incoming");
		$this->config->queryExec("DELETE FROM cidlookup_incoming");
		$this->config->queryExec("DELETE from outbound_routes");
		$this->config->queryExec("DELETE from outbound_route_patterns");
		$this->config->queryExec("DELETE from outbound_route_sequence");
		$this->config->queryExec("DELETE from outbound_route_trunks");
		$this->config->queryExec("DELETE from meetme");
		
		$this->config->queryExec("DELETE from trunks");
		$this->config->queryExec("DELETE from trunk_dialpatterns");

		$this->config->queryExec("DELETE from sip"); // For users and trunks?
		$this->config->queryExec("DELETE from iax"); // For users and trunks?
		
		$this->config->queryExec("DELETE from zapchandids");
		
		
		$this->applyConfigToFreePBXArray($this->users, 'users');
		$this->applyConfigToFreePBXArray($this->queues, 'queues');
		$this->applyConfigToFreePBXArray($this->ringGroups, 'ringgroups');
		$this->applyConfigToFreePBXArray($this->confs, 'conferences');
		$this->applyConfigToFreePBXArray($this->trunks, 'trunks');
		$this->applyConfigToFreePBXArray($this->outRoutes, 'outbound routes');
		$this->applyConfigToFreePBXArray($this->inRoutes, 'inbound routes');
		$this->applyConfigToFreePBXArray($this->channelMaps, 'channel maps');		
		$this->applyConfigToFreePBXArray($this->sounds, 'sounds');
		
		$this->config->queryExec("DELETE from ivr where ivr_id > 1");
		$this->config->queryExec("DELETE from ivr_dests");
		$this->config->queryExec("DELETE from recordings where id > 1");
		$this->config->queryExec("DELETE from announcement");
		
		$this->config->queryExec("DELETE from miscapps");
		$this->config->queryExec("DELETE from featurecodes where modulename = 'miscapps'");
		
		$this->applyConfigToFreePBXArray($this->recordings, 'recordings');
		$this->applyConfigToFreePBXArray($this->uras, 'uras');
		$this->applyConfigToFreePBXArray($this->announces, 'announces');
		$this->applyConfigToFreePBXArray($this->miscapps, 'miscapps');
		
		
		
		$this->doVoiceMailConfig();
		$this->generateOutboundRulesFile();

		
		if (! hasErrors()) {
			$this->warn("Reloading FreePBX Config (orange bar) automatically...");
			$command = "/var/lib/asterisk/bin/module_admin reload";
			$result = trim(`$command`);
			if ($result == "Successfully reloaded") {
				$this->info("FreePBX successfully reloaded! :-) ", false);
				$this->sayOK();
				echo "\n";
			} else {
				$this->error("Error reloading FreePBX: $result");
			}
		}
		
	}
	
	
	public function doVoiceMailConfig() {
		$this->info("Configuring VoiceMail...");
		$templatefn = BASEDIR.'voicemail.conf.blank';
		$contents = file_get_contents($templatefn);
		
		$users = "";
		$i = 0;
		foreach ($this->users as $id => $user) {
			$add = $user->getVoiceMailConfigLine();
			if ($add) {
				$i++;
				$users = $users . $add . "\n";
			}
		}
		$contents = str_replace('; <<<TEMPLATE>>>', $users, $contents);
		file_put_contents('/etc/asterisk/voicemail.conf', $contents);
		$this->info("$i users have VoiceMail enabled.");
	}
	
	public function getVoiceMailCurrentInfo() {
		$lines = file('/etc/asterisk/voicemail.conf');
		$kept = 0;
		foreach ($lines as $line) {
			$line = trim($line);
			if (!$line) continue;
			if ($line[0] == ";") continue;
			if ($line[0] == "[") continue;
			$partes = explode(" => ", $line);
			if (! (count($partes) == 2)) continue;
			$ext = trim($partes[0]);
			$dados = trim($partes[1]);
			$partes = explode(",", $dados);
			$senha = trim($partes[0]);
			
			$this->debug("Trying to set current VM password $senha for extension $ext");
			if (!@$this->users[$ext]) {
				$this->warn("Unable to keep VM password for extension $ext - ext not found.");
			} else {
				$this->users[$ext]->currVoiceMailPassword = $senha;
				if ($this->users[$ext]->currVoiceMailPassword != $this->users[$ext]->voicemail) {
					$kept++;
				}
			}
		}
		$this->info("Successfully kept $kept users' voicemail password.");
	}
	
	public function getUserArrayByField($arr, $field, $unique=true) {
		$ret[] = array();
		foreach ($arr as $user) {
			$idx = $user->$field;
			$item = array(
				'name' => @$user->name, 
				'secret' => @$user->secret, 
				'extension' => @$user->extension, 
				'adLogin' => @$user->adLogin, 
				'voicemail' => @$user->voicemail, 
				'email' => @$user->email, 
				'type' => @$user->type, 
				'area' => @$user->area
				);
			if ($unique) {
				$ret[$idx] = $item;
			} else {
				if (!@$ret[$idx]) $ret[$idx] = array();
				$ret[$idx][] = $item;
			}
		}
		return $ret;
	}
	
	public function getOutboundRoutesByAllow($names) {
		$my = array();
		foreach ($this->outRoutes as $rt) {
			$use = false;
			$allows = $rt->allowFor;
			foreach ($names as $name) {
				foreach ($allows as $allow) {
					if (($allow == $name) || ($allow == "all")) {
						$use = true;
					}
				}
			}
			if ($use) {
				$my[] = $rt;
			}
		}
		return $my;
	}
	
	
	
	public function generateOutboundRulesFile($filename='/etc/asterisk/outbound_rules.conf') {
		global $userContexts;
		$s = "";
		foreach ($userContexts as $name => $names) {
			$allnames = implode($names, "-");
			$this->info("Creating user context $name for $allnames");
			$s .= $this->createUserContext($name, $names, $allnames);
		}
		
		//$filename = '/etc/asterisk/outbound_rules.conf';
		//$filename = 'outbound_rules.conf';
		file_put_contents($filename, $s);
	}
	
	private function createUserContext($name, $names, $allnames) {
		$s = $this->getUserContextFixedStuff($allnames);
		$s .= $this->getUserContextDynamic($name, $names, $allnames);
		return $s;
		
	}
	
	private function getUserContextDynamic($name, $names, $allnames) {
		$routes = $this->getOutboundRoutesByAllow($names);
		$rt = array();
		foreach ($routes as $route) {
			$rt[] = "include => outrt-{$route->extension} ; {$route->name}";
		}
		$rts = implode($rt, "\n");
	
	
		$s = "
[outbound-$allnames]
include => outbound-allroutes-custom
$rts
exten => foo,1,Noop(bar)		
";
		
		return $s;
	}
	
	private function getUserContextFixedStuff($name) {
		$miscapps = "";
		$allapps = array();
		foreach ($this->miscapps as $ma) {
			$allapps[] = "include => app-miscapps-" . $ma->id;
		}
		$miscapps = implode($allapps, "\n");
	
		return "
[from-internal-xfer-$name]
include => from-internal-custom
include => parkedcalls
include => ext-local-confirm
include => findmefollow-ringallv2
include => from-internal-additional-$name
exten => s,1,Macro(hangupcall)
exten => h,1,Macro(hangupcall)

[from-internal-$name]
include => from-internal-xfer-$name
include => bad-number


[from-internal-additional-$name]
include => from-internal-additional-custom
include => ext-paging
include => app-blacklist
include => app-speeddial
include => app-recordings
include => app-fax
include => app-fmf-toggle
include => ext-findmefollow
include => fmgrps
include => app-callwaiting-cwoff
include => app-callwaiting-cwon
include => ext-meetme
$miscapps
include => ext-group
include => grps
include => app-cf-busy-off
include => app-cf-busy-off-any
include => app-cf-busy-on
include => app-cf-off
include => app-cf-off-any
include => app-cf-on
include => app-cf-unavailable-off
include => app-cf-unavailable-on
include => app-cf-toggle
include => ext-queues
include => app-queue-toggle
include => app-pbdirectory
include => app-dnd-off
include => app-dnd-on
include => app-dnd-toggle
include => app-dictate-record
include => app-dictate-send
include => app-calltrace
include => app-directory
include => app-echo-test
include => app-speakextennum
include => app-speakingclock
include => app-languages
include => vmblast-grp
include => app-dialvm
include => app-vmmain
include => app-userlogonoff
include => app-pickup
include => app-zapbarge
include => app-chanspy
include => ext-test
include => ext-local
include => outbound-$name
exten => h,1,Hangup		
		
		";
	
	
	}
	
	
	
	
	/*** mysql ***/
	public function queryExec($sql) {
		if ($this->mysql->query($sql) === TRUE) {
			$this->debug("MySQL: OK: $sql (".$this->mysql->affected_rows." rows)");
		} else {
			$this->error("Mysql: ERROR: $sql (Error: ".$this->mysql->error.")");
		}
	}
	
	public function insert($table, $data, $replace=false) {
		$fields = array();
		$values = array();
		foreach ($data as $field => $value) {
			$fields[] = $field;
			$values[] = (is_numeric($value)? $value : ("'".$value."'"));
		}
		$sql = ($replace?"REPLACE":"INSERT")." INTO $table (".implode(",", $fields) . ") VALUES (".implode(",", $values).")";
		return $this->queryExec($sql);
	}
	
	public function addSIPInsert($ext, $name, $value, $flags) {
		return $this->insert("sip", array('id' => $ext, 'keyword' => $name, 'data' => $value, 'flags' => $flags), true);
	}	
	
	public function addIAXInsert($ext, $name, $value, $flags) {
		return $this->insert("iax", array('id' => $ext, 'keyword' => $name, 'data' => $value, 'flags' => $flags), true);
	}
	
	public function addQueueDetailInsert($ext, $name, $value, $flags = null) {
		if (!is_null($flags)) {
			return $this->insert("queues_details", array('id' => $ext, 'keyword' => $name, 'data' => $value, 'flags' => $flags), true);
		} else {
			return $this->insert("queues_details", array('id' => $ext, 'keyword' => $name, 'data' => $value), true);
		}
	}
	
	public function astDbSet($family, $key, $value) {
		if (!$this->astman->database_put($family, $key, $value)) {
			$this->error("Error on astDbSet! $family $key $value");
		}
	}
	
	public function astDbDel($family, $key) {
		if (!$this->astman->database_del($family, $key)) {
			//$this->error("Error on astDbDel! $family $key");
		}	
	}
	
}
?>