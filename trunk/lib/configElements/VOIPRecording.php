<?php
class VOIPRecording extends VOIPXmlConfiguredElement  {
	
	public $msgs;
	
	public function parse() {
		$this->msgs = $this->xml['msgs'];
	}

	public function applyConfigToFreePBX() {
		$ivrMenu = array();
		foreach ($this->msgs as $msg) {
			$ivrMenu[] = getWaveFilePhrase($msg);
		}
		$joined = getConcatenatedWaves($ivrMenu);
		setFreePbxRecordingWave($joined, $this->name);
		
		
		$this->config->insert("recordings", array(
			'id' => "{$this->extension}",
			'displayname' => $this->name,
			'filename' => "custom/{$this->name}",
			'description' => "{$this->name}",
			'fcode' => (int)0,
			'fcode_pass' => ""
			));
	}
	
	public function findRefs($counter) {
		$this->extension = $counter;
	}
	
	public function getExcelColumnVars() {
		return array(
			'extension' => null, 
			'name' => null,
			'msgs' => implode($this->msgs, "; ")
			);
	}	
}
?>