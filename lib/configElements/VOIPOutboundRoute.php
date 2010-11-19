<?php
class VOIPOutboundRoute extends VOIPXmlConfiguredElement {
	/*
	<outboundRoute extension="1" name="Saida via VONO" sequence="0">
		<pattern>NXXXXXXX</pattern>
		<pattern>NXXXXXXXXX</pattern>
		<outTrunk>VonoSIP_rpardini</outTrunk>
	</outboundRoute>
	*/
	public $sequence = null;
	public $user = null;
	public $password = null;
	public $number = null;
	
	public $patternsTxt = array();
	public $trunksTxt = array();
	public $trunks = array();
	
	public function parse() {
		$this->sequence = $this->readXMLAttrInt("sequence");
		foreach ($this->xml->pattern as $pattern) {
			$pat = (string)$pattern; // Será?
			$pat = trim($pat);
			if (!$pat) continue;
			$this->patternsTxt[] = $pat;
			$this->debug("Found pattern $pat for outbound route {$this->name}");
		}
		
		foreach ($this->xml->outTrunk as $atrunk) {
			$tname = (string)$atrunk;
			$tname = trim($tname);
			if (!$tname) continue;
			$this->trunksTxt[] = $tname;
		}
	}
	
	public function findRefs() {
		foreach ($this->trunksTxt as $txt) {
			$rg = $this->config->trunksNames[$txt];
			if (!$rg) $this->error("Unable to find Trunk $txt for outboundRoute $this->name ($this->extension)");
			$rg->outboundRoutes[] = $this;
			$this->trunks[] = $rg;
			$this->debug("Found trunk {$rg->name} for outbound route {$this->name}");
		}
	}
	
	public function applyConfigToFreePBX() {
		/*
		$this->config->queryExec("DELETE from outbound_routes WHERE route_id = '{$this->extension}'");
		$this->config->queryExec("DELETE from outbound_route_patterns WHERE route_id = '{$this->extension}'");
		$this->config->queryExec("DELETE from outbound_route_sequence WHERE route_id = '{$this->extension}'");
		$this->config->queryExec("DELETE from outbound_route_trunks WHERE route_id = '{$this->extension}'");
		*/
		
		$this->config->insert("outbound_routes", array(
			'route_id' => "{$this->extension}",
			'name' => $this->translateChars($this->name, true), // No spaces
			'outcid' => "",
			'outcid_mode' => "",
			'password' => "",
			'emergency_route' => "",
			'intracompany_route' => "",
			'mohclass' => "default",
			'time_group_id' => ""
		));
	
	
		foreach ($this->patternsTxt as $pat) {
			$this->config->insert("outbound_route_patterns", array(
				'route_id' => "{$this->extension}",
				'match_pattern_prefix' => "",
				'match_pattern_pass' => "{$pat}",
				'match_cid' => "",
				'prepend_digits' => ""
			));
		}
		
		$this->config->insert("outbound_route_sequence", array(
			'route_id' => "{$this->extension}",
			'seq' => "0"
		));
		
		$i=0;
		foreach ($this->trunks as $trunk) {
			$this->config->insert("outbound_route_trunks", array(
				'route_id' => "{$this->extension}",
				'trunk_id' => "{$trunk->extension}",
				'seq' => "{$i}"				
			));
			$i++;
		}
	}
	
}
?>