<?php
class VOIPSound extends VOIPXmlConfiguredElement  {
	
	public $id;
	
	public function parse() {
		$this->id = $this->readXMLAttrString('id');
	}

	public function applyConfigToFreePBX() {
		setAsteriskDirectWave($this->id, $this->name);
	}
	
	public function getExcelColumnVars() {
		return array(
			'extension' => null, 
			'id' => null,
			'name' => null
			);
	}	
	
}
?>