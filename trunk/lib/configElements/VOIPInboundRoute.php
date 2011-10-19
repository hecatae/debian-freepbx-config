<?php
class VOIPInboundRoute extends VOIPXmlConfiguredElement {
	// <inboundRoute extension="1140638488" name="VONO pardini manda para Queue 3001" destination="ext-queues,3001,1" prefix="VONODDR" />
	public $destination = null;
	public $prefix = null;
	public $ddr = true;
	
	public function parse() {
		$this->destination = $this->readXMLAttrString("destination");
		$this->prefix = $this->readXMLAttrString("prefix");
		$this->cidlookup = $this->readXMLAttrString("cidlookup");
	}
	
	public function applyConfigToFreePBX() {
		$this->config->insert("incoming", array(
			'cidnum' => "",
			'extension' => "{$this->extension}",
			'destination' => "{$this->destination}",
			'faxexten' => "",
			'faxemail' => "",
			'answer' => "",
			'wait' => "",
			'privacyman' => "0",
			'alertinfo' => "",
			'ringing' => "",
			'mohclass' => "default",
			'description' => $this->translateChars($this->name),
			'grppre' => $this->getPrefixOrEmpty($this->prefix),
			'delay_answer' => "0",
			'pricid' => "",
			'pmmaxretries' => "3",
			'pmminlength' => "10"
			));
			
			
		if ($this->cidlookup) {
			$this->config->insert("cidlookup_incoming", array(
				'cidlookup_id' => $this->cidlookup,
				'extension' => $this->extension,
				'cidnum' => ""
			));
		
		}
	}
	
}
?>