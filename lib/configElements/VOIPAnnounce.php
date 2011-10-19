<?php
class VOIPAnnounce extends VOIPXmlConfiguredElement  {
	
	public $dest = null;
	public $msgs = array();
	public $recording = null;
	
	public function parse() {
		$this->dest = $this->readXMLAttrString("dest");
		$this->msgs = array();
		foreach ($this->xml->text as $intro) {
			$msg = $this->readXMLBodyContents($intro);
			if ($msg) $this->msgs[] = $msg;
		}
	}

	public function findRefs() {
		$data['extension'] = $this->name;
		$data['name'] = $this->name . "_recAnnounce";
		$data['msgs'] = $this->msgs;
		$this->recording = new VOIPRecording($data, $this->config, 'recordings', false, $this->name);
	}
	
	
	public function applyConfigToFreePBX() {
		$this->config->insert("announcement", array(
			'announcement_id' => "{$this->extension}",
			'description' => $this->name,
			'allow_skip' => (int)1,
			'post_dest' => $this->dest,
			'return_ivr' => (int)0,
			'noanswer' => (int)0,
			'repeat_msg' => "",
			'recording_id' => (int)$this->recording->extension
			));
	}
	
	public function getExcelColumnVars() {
		return array(
			'extension' => null, 
			'name' => null,
			'dest' => null,
			'recording_extension' => $this->recording->extension
			);
	}	
	
}
?>