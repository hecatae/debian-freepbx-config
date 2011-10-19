<?php
class VOIPUser extends VOIPXmlConfiguredElement {
	// <user extension="1003" name="Pardini Celular" secret="me1234" voicemail="1234" email="ricardo@pardini.net" type="acrobits" ringGroups="Desenvolvimento" queues="Fila do STC,Fila do STF"/>
	public $config = null;
	public $secret = null;
	public $voicemail = null;
	public $email = null;
	public $type = null;
	
	// Informational only
	public $area = null;
	public $adLogin = null;
	public $mantisLogin = null;

	// More user data
	public $ddr = null;
	public $ddd = null;
	public $international = null;
	public $cellular = null;
	
	public $extraRoutes = null;
	
	public $cwEnabled = null;
	
	public $ringGroups = array();
	public $queues = array();
	
	// Internal stuff
	public $isSIP = null;
	public $isIAX = null;
	
	// Keep
	public $currVoiceMailPassword = null;
	
	public function parse() {
		$this->secret = $this->readXMLAttrString("secret");
		$this->voicemail = $this->readXMLAttrString("voicemail");
		$this->email = $this->readXMLAttrString("email");
		$this->type = $this->readXMLAttrString("type");
		
		$this->ringGroupsTxt = $this->explodeAndClean($this->readXMLAttrString("ringGroups"));
		$this->queuesTxt =  $this->explodeAndClean($this->readXMLAttrString("queues"));
		
		$this->ddr = $this->readXMLAttrString("ddr") == "true";
		$this->ddd = $this->readXMLAttrString("ddd") == "true";
		$this->international = $this->readXMLAttrString("international") == "true";
		$this->cellular = $this->readXMLAttrString("cellular") == "false" ? false : true; // todo mundo pode celular menos quem nao pode
		
		$this->extraRoutes = $this->explodeAndClean($this->readXMLAttrString("extraRoutes"));
		
		$this->area = $this->readXMLAttrString("area");
		$this->adLogin = $this->readXMLAttrString("adLogin");
		$this->mantisLogin= $this->readXMLAttrString("mantisLogin");
		
		$strCw = $this->readXMLAttrString("callWaiting");
		if ($strCw == "enabled") {
			$this->cwEnabled = 'forced_yes';
		} elseif ($strCw == "disabled") {
			$this->cwEnabled = 'forced_no';
		} else {
			if ($this->queuesTxt) {
				$this->cwEnabled = 'queue_no';
			} else {
				$this->cwEnabled = 'queue_yes';
			}
		
		}
		
		
		$this->isSIP = false;
		$this->isIAX = false;
		if ( ($this->type == "iax_headset") || ($this->type == "iax_handset") ) {
			$this->isIAX = true;
		} else {
			$this->isSIP = true;
		}
	}
	
	public function autoInboundRoute() {
		if ($this->ddr) {
			$this->debug("Creating auto inbound route for user {$this->extension}");
			$data = array();
			$data['extension'] = $this->extension;
			$data['name'] = 'Inbound DID for USER ' . $this->name;
			$data['destination'] = "from-did-direct,{$this->extension},1";
			$data['prefix'] = 'EXT';
			// @TODO: cidlookup source!
			$this->config->inboundRoutes[] = new VOIPInboundRoute($data, $this->config, 'inRoutes');
		}
		
		
		$conf = array();
		$conf['extension'] = '99' . $this->extension;
		$conf['name'] = 'Conf para ' . $this->translateChars($this->name);
		new VOIPConference($conf, $this->config, 'confs', true);
		
	}
	
	
	public function getExcelColumnVars() {
		return array(
			'extension' => null, 
			'area' => null,
			'name' => null,
			'type' => null,
			'email' => null,
			'secret' => null,
			'voicemail' => null,
			'ddr' => $this->ddr?'yes':'no',
			'ddd' => $this->ddd?'yes':'no',
			'cellular' => $this->cellular?'yes':'no',
			'international' => $this->international?'yes':'no',
			'adLogin' => null,
			'mantisLogin' => null,
			'ringGroups' => $this->getArrayItemInfo($this->ringGroups),
			'queues' => $this->getArrayItemInfo($this->queues),
			'isIAX' => $this->isIAX ? 'yes, IAX' : 'no',
			'isSIP' => $this->isSIP ? 'yes, SIP' : 'no',
			'extraRoutes' => $this->getArrayTxtInfo($this->extraRoutes),
			'context' => $this->getUserContext(),
			'callWaitingEnabled' => $this->cwEnabled
			);
	}
	
	
	public function findRefs() {
	
		foreach ($this->ringGroupsTxt as $txt) {
			list($txt, $prio) = $this->splitParentheses($txt);
			$this->info("Found RingGroup $txt with prio $prio for user $this->extension");
			$rg = $this->config->ringGroupsNames[$txt];
			if (!$rg) $this->error("Unable to find RingGroup $txt for user $this->name ($this->extension)");
			
			$rg->users[] = array('item' => $this, 'prio' => $prio);
			$this->ringGroups[] = array('item' => $rg, 'prio' => $prio);
		}
		
		foreach ($this->queuesTxt as $txt) {
			list($txt, $prio) = $this->splitParentheses($txt);
			$this->info("Found Queue $txt with prio $prio for user $this->extension");
			$rg = $this->config->queuesNames[$txt];
			if (!$rg) $this->error("Unable to find Queue $txt for user $this->name ($this->extension)");
			
			$rg->staticUsers[] = array('item' => $this, 'prio' => $prio);
			$this->queues[] = array('item' => $rg, 'prio' => $prio);
		}
	
	}

	public function applyConfigToFreePBX() {
		/*
		INSERT INTO asterisk.devices (id, tech, dial, devicetype, user, description) VALUES (1002, "sip", "SIP/1002", "fixed", 1002, "ATA 1002");		
		INSERT INTO asterisk.users (extension, password, name, voicemail, ringtimer, recording, mohclass) VALUES (1002, "", "ATA 1002 user", "default", 0, "out=Always|in=Always", "default");
		*/
		
		/* Agora o config vai apagar tudo, mesmo...
		$this->config->queryExec("DELETE from devices WHERE id = '{$this->extension}'");
		$this->config->queryExec("DELETE from users WHERE extension = '{$this->extension}'");
		$this->config->queryExec("DELETE from fax_users WHERE user = '{$this->extension}'");
		*/
		
		if ($this->isSIP) {
			$this->config->insert("devices", array(
				"id" => (int)$this->extension, 
				"tech" => "sip", 
				"dial" => "SIP/".$this->extension, 
				"devicetype" => "fixed", 
				"user" => (int)$this->extension, 
				"description" => $this->translateChars($this->name)
				));
		}

		if ($this->isIAX) {
			$this->config->insert("devices", array(
				"id" => (int)$this->extension, 
				"tech" => "iax2", 
				"dial" => "IAX2/".$this->extension, 
				"devicetype" => "fixed", 
				"user" => (int)$this->extension, 
				"description" => $this->translateChars($this->name)
				));
		}

		
		
		$this->config->insert("users", array(
			"extension" => (int)$this->extension, 
			"password" => $this->secret, 
			"name" => $this->translateChars($this->name), 
			"voicemail" => $this->hasVM()?"default":"",  // @TODO: voicemail
			"ringtimer" => (int)40, 
			"recording" =>  "out=Never|in=Never", // "out=Always|in=Always", // @TODO não é verdade
			"mohclass" => "default"
		));
		
		if ($this->email) {
			$this->config->insert("fax_users", array('user' => $this->extension, 'faxenabled' => 'true', 'faxemail' => $this->email), true);
		}
		
		if ($this->isSIP) {
			$i = 2;
			$this->config->addSIPInsert($this->extension, "secret", "{$this->secret}", $i++);
			$this->config->addSIPInsert($this->extension, "dtmfmode", "rfc2833", $i++);
			$this->config->addSIPInsert($this->extension, "canreinvite", "no", $i++);
			$this->config->addSIPInsert($this->extension, "context", $this->getUserContext(), $i++); // @TODO: quase com certeza é a forma de controlar os trunks
			$this->config->addSIPInsert($this->extension, "host", "dynamic", $i++);
			$this->config->addSIPInsert($this->extension, "type", "friend", $i++);
			$this->config->addSIPInsert($this->extension, "nat", "yes", $i++);
			$this->config->addSIPInsert($this->extension, "port", "5060", $i++);
			$this->config->addSIPInsert($this->extension, "qualify", "yes", $i++);
			$this->config->addSIPInsert($this->extension, "callgroup", "", $i++);
			$this->config->addSIPInsert($this->extension, "pickupgroup", "", $i++);
			$this->config->addSIPInsert($this->extension, "disallow", "", $i++);
			$this->config->addSIPInsert($this->extension, "allow", "", $i++);
			$this->config->addSIPInsert($this->extension, "dial", "SIP/{$this->extension}", $i++);
			$this->config->addSIPInsert($this->extension, "accountcode", "", $i++);
			$this->config->addSIPInsert($this->extension, "mailbox", ($this->hasVM()? ("{$this->extension}@default") : ""), $i++); // @TODO voicemail 
			$this->config->addSIPInsert($this->extension, "deny", "0.0.0.0/0.0.0.0", $i++);
			$this->config->addSIPInsert($this->extension, "permit", "0.0.0.0/0.0.0.0", $i++);
			$this->config->addSIPInsert($this->extension, "account", "{$this->extension}", $i++);
			$this->config->addSIPInsert($this->extension, "callerid", $this->translateChars($this->name)." <{$this->extension}>", $i++);
			$this->config->addSIPInsert($this->extension, "record_in", "Never", $i++);  // @TODO não é verdade
			$this->config->addSIPInsert($this->extension, "record_out", "Never", $i++);  // @TODO não é verdade
		}
		
		if ($this->isIAX) {
			$i = 2;
			$this->config->addIAXInsert($this->extension, "secret", "{$this->secret}", $i++);
			$this->config->addIAXInsert($this->extension, "notransfer", "yes", $i++);
			$this->config->addIAXInsert($this->extension, "context", $this->getUserContext(), $i++);
			$this->config->addIAXInsert($this->extension, "host", "dynamic", $i++);
			$this->config->addIAXInsert($this->extension, "type", "friend", $i++);
			$this->config->addIAXInsert($this->extension, "port", "4569", $i++);
			$this->config->addIAXInsert($this->extension, "qualify", "yes", $i++);
			$this->config->addIAXInsert($this->extension, "disallow", "", $i++);
			$this->config->addIAXInsert($this->extension, "allow", "", $i++);
			$this->config->addIAXInsert($this->extension, "dial", "IAX2/{$this->extension}", $i++);
			$this->config->addIAXInsert($this->extension, "accountcode", "", $i++);
			$this->config->addIAXInsert($this->extension, "mailbox", ($this->hasVM()? ("{$this->extension}@default") : ""), $i++); // @TODO: voicemail
			$this->config->addIAXInsert($this->extension, "deny", "0.0.0.0/0.0.0.0", $i++);
			$this->config->addIAXInsert($this->extension, "permit", "0.0.0.0/0.0.0.0", $i++);
			$this->config->addIAXInsert($this->extension, "requirecalltoken", "", $i++);
			$this->config->addIAXInsert($this->extension, "account", "{$this->extension}", $i++);
			$this->config->addIAXInsert($this->extension, "callerid", $this->translateChars($this->name)." <{$this->extension}>", $i++);
			$this->config->addIAXInsert($this->extension, "setvar", "REALCALLERIDNUM={$this->extension}", $i++);
			$this->config->addIAXInsert($this->extension, "record_in", "Never", $i++);  // @TODO não é verdade
			$this->config->addIAXInsert($this->extension, "record_out", "Never", $i++);  // @TODO não é verdade		 
		}
		
		
		
		
		/*
		[/DEVICE/3490/default_user] => 3490
		[/DEVICE/3490/dial] => SIP/3490
		[/DEVICE/3490/type] => fixed
		[/DEVICE/3490/user] => 3490
		*/
		$this->config->astDbSet("DEVICE", "{$this->extension}/default_user", "{$this->extension}");
		if ($this->isSIP) {
			$this->config->astDbSet("DEVICE", "{$this->extension}/dial", "SIP/{$this->extension}");
		}
		if ($this->isIAX) {
			$this->config->astDbSet("DEVICE", "{$this->extension}/dial", "IAX2/{$this->extension}");
		}
		$this->config->astDbSet("DEVICE", "{$this->extension}/type", "fixed");
		$this->config->astDbSet("DEVICE", "{$this->extension}/user", "{$this->extension}");
		

		/*
		cidname] => Milton Okasawa
		cidnum] => 3490
		device] => 3490
		dictate/email] => japaumdoistres@japa2.com
		dictate/enabled] => enabled
		dictate/format] => ogg
		language] =>
		noanswer] =>
		outboundcid] =>
		password] =>
		recording] => out=Always|in=Always
		ringtimer] => 0
		voicemail] => default		
		*/
		
		$this->config->astDbSet("AMPUSER", "{$this->extension}/cidname", "\"{$this->name}\"");
		$this->config->astDbSet("AMPUSER", "{$this->extension}/cidnum", "{$this->extension}");
		$this->config->astDbSet("AMPUSER", "{$this->extension}/device", "{$this->extension}");
		$this->config->astDbSet("AMPUSER", "{$this->extension}/queues/qnostate", "usestate");
		if ($this->email) {
			$this->config->astDbSet("AMPUSER", "{$this->extension}/dictate/email", "{$this->email}");
			$this->config->astDbSet("AMPUSER", "{$this->extension}/dictate/enabled", "enabled");
			$this->config->astDbSet("AMPUSER", "{$this->extension}/dictate/format", "ogg");
		}
		$this->config->astDbSet("AMPUSER", "{$this->extension}/language", "");
		$this->config->astDbSet("AMPUSER", "{$this->extension}/noanswer", "");
		$this->config->astDbSet("AMPUSER", "{$this->extension}/outboundcid", "");
		$this->config->astDbSet("AMPUSER", "{$this->extension}/password", ""); // @TODO: o que será isso?!!!!
		$this->config->astDbSet("AMPUSER", "{$this->extension}/recording", "out=Never|in=Never"); // @TODO, não é verdade, acho.
		$this->config->astDbSet("AMPUSER", "{$this->extension}/ringtimer", "40");
		$this->config->astDbSet("AMPUSER", "{$this->extension}/voicemail", ($this->hasVM()? "default" : "")); // @TODO: voicemail

		
		if (($this->cwEnabled == 'forced_yes') || ($this->cwEnabled == 'queue_yes')) { 
			$this->config->astDbSet("CW", "{$this->extension}", "ENABLED"); // Call Waiting enabled...
		} else {
			$this->config->astDbDel("CW", "{$this->extension}"); // Call Waiting disabled...
		}
	}	

	// 1001 => 1234,Pardini IAX,ricardo@pardini.net,,attach=yes|saycid=yes|envelope=yes|delete=no
	public function getVoiceMailConfigLine() {
		if (!$this->voicemail) return null; // Se não tiver definido, mata mesmo que tenha
		if (!$this->email) return null; // Se não tiver email, mata sempre
		if ($this->currVoiceMailPassword) $this->voicemail = $this->currVoiceMailPassword;
		$str = "{$this->extension} => {$this->voicemail},".$this->translateChars($this->name).",{$this->email},,attach=yes|saycid=yes|envelope=yes|delete=no";
		return $str;
	}
	
	public function hasVM() {
		if (!$this->voicemail) return false;
		if (!$this->email) return false;
		return true;
	}
	
	public function getUserContext() {
		$names = array();
		if ($this->international) $names[] = "gringo";
		if ($this->ddd) $names[] = "ddd";
		if ($this->cellular) $names[] = "cellular";
		
		foreach ($this->extraRoutes as $extra) {
			$names[] = $extra;
		}
		
		$name = "from-internal-" . implode($names, "-");
		global $userContexts;
		$userContexts[$name] = $names;
		
		return $name;
		
	}
	
	
	
}
?>