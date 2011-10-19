<?php
class VOIPURA extends VOIPXmlConfiguredElement  {
	
	public $msgs = array();
	public $recording = null;
	public $options = array();
	
	public function parse() {
		$this->msgs = array();
		$this->options = array();
		foreach ($this->xml->intro as $intro) {
			$msg = $this->readXMLBodyContents($intro);
			if ($msg) $this->msgs[] = $msg;
		}
		
		foreach ($this->xml->option as $option) {
			$msg = $this->readXMLBodyContents($option);
			if ($msg) $this->msgs[] = $msg;
			$opt = array();
			$opt['value'] = $option['value'];
			$opt['dest'] = $option['dest'];
			$opt['message'] = $msg;
			$this->options[] = $opt;
		}
	}

	public function findRefs() {
		$data['extension'] = $this->name;
		$data['name'] = $this->name . "_recURA";
		$data['msgs'] = $this->msgs;
		$this->recording = new VOIPRecording($data, $this->config, 'recordings', false, $this->name);
	}
	
	
	public function applyConfigToFreePBX() {
		$this->config->insert("ivr", array(
			'ivr_id' => "{$this->extension}",
			'displayname' => $this->name,
			'timeout' => (int)10,
			'timeout_id' => (int)0,
			'invalid_id' => (int)0,
			'loops' => (int)2,
			'announcement_id' => (int)$this->recording->extension
			));
			
		foreach ($this->options as $opt) {
			$this->config->insert("ivr_dests", array(
				'ivr_id' => "{$this->extension}",
				'selection' => $opt['value'],
				'dest' => $opt['dest'],
				'ivr_ret' => (int)0
				));
		}
			
	}
	
	public function getExcelColumnVars() {
		return array(
			'extension' => null, 
			'name' => null,
			'recording_extension' => $this->recording->extension
			);
	}	
	
}
?>