<?php
class VOIPMiscApp extends VOIPXmlConfiguredElement  {
	public $id = null;
	public $dest = null;
	public $ddr = null;
	
	public function parse() {
		$this->id = $this->readXMLAttrInt("id");
		$this->dest = $this->readXMLAttrString("dest");
		$this->ddr = $this->readXMLAttrString("ddr") == "true";
	}

	public function applyConfigToFreePBX() {
	
		$this->config->insert("miscapps", array(
			'miscapps_id' => "{$this->id}",
			'description' => $this->name,
			'dest' => $this->dest,
			'ext' => "{$this->extension}"
			));

			
		$this->config->insert("featurecodes", array(
			'modulename' => 'miscapps',
			'featurename' => 'miscapp_' . $this->id,
			'description' => $this->name,
			'defaultcode' => "{$this->extension}",
			'enabled' => (int)1
			));
	}

	
	public function getExcelColumnVars() {
		return array(
			'id' => null,
			'extension' => null, 
			'name' => null,
			'dest' => null,
			'ddr' => null
			);
	}	
	
}
?>