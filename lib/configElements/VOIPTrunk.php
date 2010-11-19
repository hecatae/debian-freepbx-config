<?php
class VOIPTrunk extends VOIPXmlConfiguredElement {
	// <trunk extension="2" name="VonoSIP_rpardini" type="vono" user="rpardini" password="123456" number="1140638488"/>
	public $type = null;
	public $user = null;
	public $password = null;
	public $number = null;
	public $tech = null;
	public $custom = null;
	public $isVONOtrunk = false;
	public $isCustomTrunk = false;
	
	public function parse() {
		$this->type = $this->readXMLAttrString("type");
		$this->user = $this->readXMLAttrString("user");
		$this->password = $this->readXMLAttrString("password");
		$this->number = $this->readXMLAttrString("number");
		$this->custom = $this->readXMLAttrString("custom");
		
		if ($this->type == "vono") {
			$this->tech = "sip";
			$this->isVONOtrunk = true;
		} elseif ($this->type == "digivoice") {
			$this->tech = "custom";
			$this->isCustomTrunk = true;
			if (!$this->custom) $this->error("trunk type '{$this->type}' for trunk {$this->extension} {$this->name} requires 'custom'.");
		} else {
			$this->error("Unknown trunk type '{$this->type}' for trunk {$this->extension} {$this->name}");
		}
		
	}
	
	public function applyConfigToFreePBX() {
		/*
		$this->config->queryExec("DELETE from trunks WHERE trunkid = '{$this->extension}'");
		$this->config->queryExec("DELETE from sip WHERE id = 'tr_peer-{$this->extension}'");
		$this->config->queryExec("DELETE from sip WHERE id = 'tr_reg-{$this->extension}'");
		*/
		
		$this->config->insert("trunks", array(
			'trunkid' => "{$this->extension}",
			'name' => $this->translateChars($this->name),
			'tech' => $this->tech,
			'outcid' => "",
			'keepcid' => "off",
			'maxchans' => "",
			'failscript' => "",
			'dialoutprefix' => "",
			'channelid' => $this->isCustomTrunk ? $this->custom : $this->translateChars($this->name),
			'usercontext' => $this->isCustomTrunk?"notneeded":"",
			'provider' => "",
			'disabled' => "off"
			));
		
		
		if ($this->isVONOtrunk) {
			$i = 2;
			$this->config->addSIPInsert("tr-reg-{$this->extension}", "register", "{$this->user}:{$this->password}@vono.net.br:5060/{$this->number}", 0);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "account", $this->translateChars($this->name), $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "allow", "ilbc&gsm", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "disallow", "all", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "dtmfmode", "rfc2833", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "fromdomain", "vono.net.br", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "fromuser", "{$this->user}", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "host", "vono.net.br", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "insecure", "port,invite", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "port", "5060", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "secret", "{$this->password}", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "type", "friend", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "username", "{$this->user}", $i++);
		}
	}
	
}
?>