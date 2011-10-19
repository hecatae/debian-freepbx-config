<?php
class VOIPQueue extends VOIPXmlConfiguredElement  {
	// <queue extension="3001" name="Fila do STC" prefix="STC" recording="wav" sla="60" announce="30" />
	public $prefix = null;
	public $recording = null;
	public $sla = null;
	public $announce = null;
	public $staticUsers = array();
	public $ddr = null;
	public $max = null;
	public $dest = null;
	public $strategy = null;

	public function parse() {
		$this->prefix = $this->readXMLAttrString("prefix");
		$this->recording = $this->readXMLAttrString("recording");
		$this->sla = $this->readXMLAttrInt("sla");
		$this->announce = $this->readXMLAttrInt("announce");
		$this->ddr = $this->readXMLAttrString("ddr") == "true";
		$this->max = $this->readXMLAttrInt("max");
		$this->dest = $this->readXMLAttrString("dest");
		$this->strategy = $this->readXMLAttrString("strategy");
	}
	
	public function getExcelColumnVars() {
		return array(
			'extension' => null, 
			'name' => null,
			'strategy' => null,
			'prefix' => null,
			'recording' => null,
			'sla' => null,
			'announce' => null,
			'ddr' => null,
			'max' => null,
			'dest' => null,
			'users' => $this->getArrayItemInfo($this->staticUsers)
			);
	}
	
	public function autoInboundRoute() {
		if ($this->ddr) {
			$this->info("Creating auto inbound route for queue {$this->extension}");
			$data = array();
			$data['extension'] = $this->extension;
			$data['name'] = 'Inbound DID for QUEUE ' . $this->name;
			$data['destination'] = "ext-queues,{$this->extension},1";
			$data['prefix'] = 'EXT';
			// @TODO: cidlookup source!
			$this->config->inboundRoutes[] = new VOIPInboundRoute($data, $this->config, 'inRoutes');
		}
	}
	
		
	
	public function applyConfigToFreePBX() {
		/*
		$this->config->queryExec("DELETE from queues_config WHERE extension = '{$this->extension}'");
		$this->config->queryExec("DELETE from queues_details WHERE id = '{$this->extension}'");
		*/
		$this->config->insert("queues_config", array(
			'extension' => "{$this->extension}",
			'descr' => $this->translateChars($this->name),
			'grppre' => $this->getPrefixOrEmpty($this->prefix),
			'alertinfo' => "",
			'ringing' => "0",
			'maxwait' => "{$this->max}",
			'password' => "",
			'ivr_id' => "none",
			'dest' => $this->dest?$this->dest:"app-blackhole,busy,1",
			'cwignore' => "1", // was 2
			'queuewait' => "0",
			'use_queue_context' => "0",
			'togglehint' => "0",
			'qregex' => "",
			'agentannounce_id' => "0",
			'joinannounce_id' => "0"
			));
		
		$this->config->addQueueDetailInsert($this->extension, "announce-frequency", "30");
		$this->config->addQueueDetailInsert($this->extension, "announce-holdtime", "once"); // was yes
		$this->config->addQueueDetailInsert($this->extension, "announce-position", "yes");
		$this->config->addQueueDetailInsert($this->extension, "autofill", "no");
		$this->config->addQueueDetailInsert($this->extension, "eventmemberstatus", "no");
		$this->config->addQueueDetailInsert($this->extension, "eventwhencalled", "no");
		$this->config->addQueueDetailInsert($this->extension, "joinempty", "strict"); // was no
		$this->config->addQueueDetailInsert($this->extension, "leavewhenempty", "strict"); // was no
		$this->config->addQueueDetailInsert($this->extension, "maxlen", "0");
		$this->config->addQueueDetailInsert($this->extension, "monitor-format", "wav");
		$this->config->addQueueDetailInsert($this->extension, "monitor-join", "yes");
		$this->config->addQueueDetailInsert($this->extension, "periodic-announce-frequency", "0");
		$this->config->addQueueDetailInsert($this->extension, "queue-callswaiting", "queue-callswaiting");
		$this->config->addQueueDetailInsert($this->extension, "queue-thankyou", "queue-thankyou");
		$this->config->addQueueDetailInsert($this->extension, "queue-thereare", "queue-thereare");
		$this->config->addQueueDetailInsert($this->extension, "queue-youarenext", "queue-youarenext");
		$this->config->addQueueDetailInsert($this->extension, "reportholdtime", "yes");
		$this->config->addQueueDetailInsert($this->extension, "retry", "5");
		$this->config->addQueueDetailInsert($this->extension, "ringinuse", "no");
		$this->config->addQueueDetailInsert($this->extension, "servicelevel", "60");
		$this->config->addQueueDetailInsert($this->extension, "strategy", $this->strategy?$this->strategy:"ringall");
		$this->config->addQueueDetailInsert($this->extension, "timeout", "15");
		$this->config->addQueueDetailInsert($this->extension, "weight", "0");
		$this->config->addQueueDetailInsert($this->extension, "wrapuptime", "0"); // was 0
		
		// Membros estáticos:
		$i = 0;
		foreach ($this->getOrderedArrayItemInfo($this->staticUsers) as $user) {
			$this->info("Queue: " . $this->extension . "($this->strategy) extension " . $user->extension . " final order: " . $i);
			$this->config->addQueueDetailInsert($this->extension, "member", "Local/{$user->extension}@from-queue/n,0", $i);
			$i++;
		}
		
		$this->config->astDbSet("QPENALTY", "{$this->extension}/dynmemberonly", "no");
	}
	
}
?>