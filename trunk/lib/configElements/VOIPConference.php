<?php
class VOIPConference extends VOIPXmlConfiguredElement  {

	public $ddr = null;

	public function parse() {
		$this->ddr = $this->readXMLAttrString("ddr") == "true";
	}

	public function applyConfigToFreePBX() {
		$this->config->insert("meetme", array(
			'exten' => "{$this->extension}",
			'options' => "oTcM", // was oTcir
			'userpin' => "",
			'adminpin' => "",
			'description' => $this->translateChars($this->name),
			'joinmsg_id' => "0",
			'music' => "inherit",
			'users' => "0",		
			));
	}
	
	
	public function autoInboundRoute() {
		if ($this->ddr) {
			$this->info("Creating auto inbound route for conf {$this->extension}");
			$data = array();
			$data['extension'] = $this->extension;
			$data['name'] = 'Inbound DID for CONF ' . $this->name;
			$data['destination'] = "ext-meetme,{$this->extension},1";
			$data['prefix'] = 'EXT';
			// @TODO: cidlookup source!
			$this->config->inboundRoutes[] = new VOIPInboundRoute($data, $this->config, 'inRoutes');
		}
	}
		
	
}
?>