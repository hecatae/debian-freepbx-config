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
	public $isSIPURAtrunk = false;
	public $patterns = array();
	public $patTxt = array();
	
	public function parse() {
		$this->type = $this->readXMLAttrString("type");
		$this->user = $this->readXMLAttrString("user");
		$this->password = $this->readXMLAttrString("password");
		$this->number = $this->readXMLAttrString("number");
		$this->custom = $this->readXMLAttrString("custom");
		
		if ($this->type == "vono") {
			$this->tech = "sip";
			$this->isVONOtrunk = true;
		} elseif ($this->type == "sipura_fxo") {
			$this->tech = "sip";
			$this->isSIPURAtrunk = true;
		} elseif ($this->type == "fonebridge") {
			$this->tech = "dahdi";
			$this->isCustomTrunk = true;
		} elseif ($this->type == "digivoice") {
			$this->tech = "custom";
			$this->isCustomTrunk = true;
			if (!$this->custom) $this->error("trunk type '{$this->type}' for trunk {$this->extension} {$this->name} requires 'custom'.");
		} else {
			$this->error("Unknown trunk type '{$this->type}' for trunk {$this->extension} {$this->name}");
		}
		
		// <trunkDialPattern prefix="00"  pass="ZXX."           prepend="0021"   seq="1"   desc="fidelidade embratel ddi"/>
		$this->patterns = array();
		$this->patTxt = array();
		foreach ($this->xml->trunkDialPattern as $pattern) {
			$pat = array();
			$pat['prefix'] = $pattern['prefix'];
			$pat['pass'] = $pattern['pass'];
			$pat['prepend'] = $pattern['prepend'];
			$pat['seq'] = $pattern['seq'];
			$pat['desc'] = $pattern['desc'];
			$this->patTxt[] = $pattern['desc'] . "(" . $pat['prefix'] . "/" . $pat['pass'] . "/" . $pat['prepend'] . ")";
			$desc = $pattern['desc'];
			$this->info("Found pattern '$desc' for trunk {$this->name}");
			$this->patterns[] = $pat;
		}
	}
	
	public function getExcelColumnVars() {
		return array(
			'extension' => null, 
			'name' => null,
			'type' => null,
			'tech' => null,
			'patterns' => $this->getArrayTxtInfo($this->patTxt)
			);
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
			'maxchans' => $this->isSIPURAtrunk ? "1" : "",
			'failscript' => "",
			'dialoutprefix' => "",
			'channelid' => $this->isCustomTrunk ? $this->custom : ($this->isSIPURAtrunk ? $this->user : $this->translateChars($this->name)),
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
		
		if ($this->isSIPURAtrunk) {
			$i = 2;
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "account", "{$this->user}", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "disallow", "all", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "allow", "ulaw", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "canreinvite", "no", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "context", "from-trunk", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "dtmfmode", "rfc2833", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "host", "dynamic", $i++); // oh really? and how does it know?
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "incominglimit", "1", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "nat", "never", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "port", "5061", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "qualify", "yes", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "secret", "{$this->password}", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "type", "friend", $i++);
			$this->config->addSIPInsert("tr-peer-{$this->extension}", "username", "{$this->user}", $i++);		
		}
		
		foreach ($this->patterns as $pat) {
			$this->config->insert("trunk_dialpatterns", array(
				'trunkid' => "{$this->extension}",
				'match_pattern_prefix' => $pat['prefix'],
				'match_pattern_pass' => $pat['pass'],
				'prepend_digits' => $pat['prepend'],
				'seq' => $pat['seq']
				));			
		}
	}
	
}
?>