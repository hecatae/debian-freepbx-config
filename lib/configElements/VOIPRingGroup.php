<?php
class VOIPRingGroup extends VOIPXmlConfiguredElement  {
	// <ringgroup extension="4001" name="Desenvolvimento" strategy="ringall" prefix="DEV"/>
	public $strategy = null;
	public $prefix = null;
	public $users = array();
	public $postdest = null;
	public $ringtime = null;
	public $ddr = null;
	
	public function parse() {
		$this->strategy = $this->readXMLAttrString("strategy");
		$this->prefix = $this->readXMLAttrString("prefix");
		$this->postdest = $this->readXMLAttrString("postdest");
		$this->ringtime = $this->readXMLAttrInt("ringtime");
		$this->ddr = $this->readXMLAttrString("ddr") == "true";
	}
	
	public function getExcelColumnVars() {
		return array(
			'extension' => null, 
			'name' => null,
			'ringtime' => null,
			'strategy' => null,
			'prefix' => null,
			'ddr' => null,
			'users' => $this->getArrayItemInfo($this->users)
			);
	}
	
	
	public function autoInboundRoute() {
		if ($this->ddr) {
			$this->info("Creating auto inbound route for ringGroup {$this->extension}");
			$data = array();
			$data['extension'] = $this->extension;
			$data['name'] = 'Inbound DID for GROUP ' . $this->name;
			$data['destination'] = "ext-group,{$this->extension},1";
			$data['prefix'] = 'EXT';
			// @TODO: cidlookup source!
			$this->config->inboundRoutes[] = new VOIPInboundRoute($data, $this->config, 'inRoutes');
		}
	}
		
	
	public function applyConfigToFreePBX() {
		$u = array();
		$after = $this->getOrderedArrayItemInfo($this->users);
		foreach ($after as $user) {
			$u[] = $user->extension;
		}
		$lista = implode("-", $u);
		/*
		$this->config->queryExec("DELETE from ringgroups WHERE grpnum = '{$this->extension}'");
		*/
		$this->config->insert("ringgroups", array(
			'grpnum' => "{$this->extension}",
			'strategy' => "{$this->strategy}",
			'grptime' => "{$this->ringtime}",
			'grppre' => $this->getPrefixOrEmpty($this->prefix),
			'grplist' => "$lista",
			'annmsg_id' => "0",
			'postdest' => $this->postdest? $this->postdest : "app-blackhole,hangup,1",
			'description' => $this->translateChars($this->name),
			'alertinfo' => "",
			'remotealert_id' => "0",
			'needsconf' => "",
			'toolate_id' => "0",
			'ringing' => "Ring",
			'cwignore' => "", // was CHECKED
			'cfignore' => ""
			));
		$this->config->astDbSet("RINGGROUP", "{$this->extension}/changecid", "default");
		$this->config->astDbSet("RINGGROUP", "{$this->extension}/fixedcid", "");
	}

}
?>