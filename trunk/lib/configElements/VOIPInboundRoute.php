<?php
class VOIPInboundRoute extends VOIPXmlConfiguredElement {
	// <inboundRoute extension="1140638488" name="VONO pardini manda para Queue 3001" destination="ext-queues,3001,1" prefix="VONODDR" />
	public $destination = null;
	public $prefix = null;
	
	public function parse() {
		$this->destination = $this->readXMLAttrString("destination");
		$this->prefix = $this->readXMLAttrString("prefix");
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
	}
	
}
?>