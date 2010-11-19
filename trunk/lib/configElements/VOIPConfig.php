<?php
class VOIPConfig extends VOIPXmlConfiguredElement {
	public $users = array();
	public $queues = array();
	public $ringGroups = array();
	public $confs = array();
	public $trunks = array();
	public $outRoutes = array();
	public $inRoutes = array();
	
	
	public $mysqli = null;
	public $astman = null;
	
	
	// Local config fields
	public $externalIp = null;
	public $localNet = null;
	public $localNetMask = null;
	
	
	public function parse() {
		$this->externalIp = (string)$this->xml->externalIp;
		$this->localNet = (string)$this->xml->localNet;
		$this->localNetMask = (string)$this->xml->localNetMask;
	
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
		
		foreach ($this->xml->outboundRoutes->outboundRoute as $xml) {
			$nu = new VOIPOutboundRoute($xml, $this, "outRoutes");
		}
		
		foreach ($this->xml->inboundRoutes->inboundRoute as $xml) {
			$nu = new VOIPInboundRoute($xml, $this, "inRoutes");
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
		$this->writeExcelArray($this->inRoutes);
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
	}
	
	public function findObjRefsArray($arr) {
		foreach ($arr as $item) {
			$item->findRefs();
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
		$this->config->queryExec("REPLACE INTO sipsettings (keyword, data, seq, type) VALUES ('nat_mode', 'externip', 10, 0)");
		$this->config->queryExec("REPLACE INTO sipsettings (keyword, data, seq, type) VALUES ('externip_val', '{$this->externalIp}', 40, 0)");
		$this->config->queryExec("REPLACE INTO sipsettings (keyword, data, seq, type) VALUES ('localnet_0', '{$this->localNet}', 42, 0)");
		$this->config->queryExec("REPLACE INTO sipsettings (keyword, data, seq, type) VALUES ('netmask_0', '{$this->localNetMask}', 0, 0)");
		$this->config->queryExec("REPLACE INTO sipsettings (keyword, data, seq, type) VALUES ('sip_language', 'pt_BR', 0, 0)");
		
		$this->config->queryExec("REPLACE INTO iaxsettings (keyword, data, seq, type) VALUES ('iax_language', 'pt_BR', 0, 0)");
		
		$this->info("Clearing/deleting old settings from most FreePBX tables...");
		$this->config->queryExec("DELETE from devices");
		$this->config->queryExec("DELETE from users");
		$this->config->queryExec("DELETE from fax_users");
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
		$this->config->queryExec("DELETE from sip"); // For users and trunks?
		$this->config->queryExec("DELETE from iax"); // For users and trunks?
		
		
		$this->applyConfigToFreePBXArray($this->users, 'users');
		$this->applyConfigToFreePBXArray($this->queues, 'queues');
		$this->applyConfigToFreePBXArray($this->ringGroups, 'ringgroups');
		$this->applyConfigToFreePBXArray($this->confs, 'conferences');
		$this->applyConfigToFreePBXArray($this->trunks, 'trunks');
		$this->applyConfigToFreePBXArray($this->outRoutes, 'outbound routes');
		$this->applyConfigToFreePBXArray($this->inRoutes, 'inbound routes');
		
		
		$this->doVoiceMailConfig();
		
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
			if (!$this->users[$ext]) {
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
	
	public function addQueueDetailInsert($ext, $name, $value) {
		return $this->insert("queues_details", array('id' => $ext, 'keyword' => $name, 'data' => $value), true);
	}
	
	public function astDbSet($family, $key, $value) {
		$this->astman->database_put($family, $key, $value);
	}
	
}
?>