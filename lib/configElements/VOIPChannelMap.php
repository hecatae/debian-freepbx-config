<?php
class VOIPChannelMap extends VOIPXmlConfiguredElement  {

	public $did = null;
	
	public function parse() {
		$this->did = $this->readXMLAttrString("did");
	}

	public function applyConfigToFreePBX() {
	
		$this->config->insert("zapchandids", array(
			'channel' => "{$this->extension}",
			'description' => $this->translateChars($this->did . " :: ". $this->name),
			'did' => "{$this->did}"
			));
	}
	
	public function getExcelColumnVars() {
		return array(
			'extension' => null, 
			'name' => null,
			'did' => null
			);
	}	
	
}
?>