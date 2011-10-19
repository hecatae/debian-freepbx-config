<?php
// 

/*
$ivrMenu = array();
$ivrMenu[] = getWaveFilePhrase("Bem vindo ao Mercado Eletrônico!");
$ivrMenu[] = getWaveFilePhrase("Digite 1, para suporte técnico ao fornecedor...");
$ivrMenu[] = getWaveFilePhrase("Tecle 2, para suporte técnico comprador...");
$ivrMenu[] = getWaveFilePhrase("Digite 3, para cobrança...");
$ivrMenu[] = getWaveFilePhrase("Digite 4, para SMB...");
$ivrMenu[] = getWaveFilePhrase("Para enviar um fax, digite 5...");
$ivrMenu[] = getWaveFilePhrase("ou, para telefonista, tecle 9...");
getConcatenatedWaves($ivrMenu);

*/
// /var/lib/asterisk/sounds/custom/bemvindoMEMenuWave.wav

/*
setAsteriskDirectWave("cannot-complete-as-dialed", "Sua chamada não pode ser concluída.");
setAsteriskDirectWave("check-number-dial-again", "Por favor, verifique o número, e disque novamente.");

setAsteriskDirectWave("your", "seu");
setAsteriskDirectWave("extension", "ramal");
*/


/*
getWaveFilePhrase("Sua chamada não pode ser concluída.");
getWaveFilePhrase("Por favor, verifique o número, e disque novamente.");
getWaveFilePhrase("adeus");
*/

//getWaveFilePhrase("Eu gosto do Michael Jackson");
//getWaveFilePhrase("Eu não sei porquê não funciona direito essa merda");

function getConcatenatedWaves($waves) {
	$allwaves = "";
	foreach ($waves as $wave) {
		$allwaves .= $wave;
	}
	$id = md5($allwaves);
	$dir = getTempAudioPath();
	$file = $dir . DIRECTORY_SEPARATOR . $id . '.wav';
	if (! file_exists($file)) {
		$sox = getSoxPath();
		$call = "$sox " . implode($waves, " ") . " $file";
		//echo "Calling SOX! $call\n";
		$result = `$call`;
		return $file;
	} else {
		//echo "Using cached concatenation $id\n";
		return $file;
	}
}

function setFreePbxRecordingWave($orig, $dest) {
	$path = "/var/lib/asterisk/sounds/custom/".$dest.".wav";
	//echo "FreePbx Dest path: $path \n";
	copy($orig, $path);
}


function setAsteriskDirectWave($id, $phrase) {
	$wav = getWaveFilePhrase($phrase);
	if (DIRECTORY_SEPARATOR != "\\") {
		$path = "/var/lib/asterisk/sounds/pt_BR/" . $id . ".wav";
		//echo "Dest path: $path \n";
		copy($wav, $path);
	}
}

function getWaveFilePhrase($phrase) {
	$dir = getTempAudioPath();
	$hash = getHashPhrase($phrase);
	$wavFile = $dir.DIRECTORY_SEPARATOR.$hash.'.wav';
	if (!file_exists($wavFile)) {
		echo "Downloading ($hash) new audio for '$phrase'... ";
		$tmpfile = downloadFromGoogle($dir, $phrase);
		$tmpwave = convertMP3ToWaveAsterisk($tmpfile.'.mp3', $wavFile);
		unlink($tmpfile.'.mp3');
		echo "OK.\n";
	} else {
		//echo "Using cached ($hash) version of '$phrase'\n";
	}
	playWaveFileInWindowsDebug($wavFile, $phrase);
	return $wavFile;
}

function downloadFromGoogle($dir, $phrase) {
	$phrase = " " . $phrase;
	$hash = getHashPhrase($phrase);
	
	$file = $dir.DIRECTORY_SEPARATOR.$hash . '.mp3';
	$url = "http://translate.google.com.br/translate_tts?ie=UTF-8&tl=pt&q=" . urlencode(utf8_encode($phrase));
	$bytes = file_get_contents($url);
	file_put_contents($file, $bytes);
	return $dir.DIRECTORY_SEPARATOR.$hash;
}


function getHashPhrase($phrase) {
	return md5($phrase);
}

function getTempAudioPath() {
	$dir = dirname(__FILE__).DIRECTORY_SEPARATOR."generatedSounds";
	if (!file_exists($dir)) mkdir($dir);
	return realpath($dir);
}


function convertMP3ToWaveAsterisk($in, $out) {
	$sox = getSoxPath();
	$in = realpath($in);
	$out = $out;
	$call = "$sox $in --rate 8000 $out";
	//echo "Sox call: $call \n";
	$result = `$call`;
	return $out;
}

function getSoxPath() {
	if (DIRECTORY_SEPARATOR != "\\") {
		return "/usr/bin/sox";
	} else {
		return realpath(dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."utils".DIRECTORY_SEPARATOR."soxwin32".DIRECTORY_SEPARATOR."sox.exe");
	}

}

function playWaveFileInWindows($wav) {
	$playwav = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."utils".DIRECTORY_SEPARATOR."soxwin32".DIRECTORY_SEPARATOR."playwav.exe");
	$wav = realpath($wav);
	$call = "$playwav $wav";
	$result = `$call`;
	return $wav;
}

function playWaveFileInWindowsDebug($wav, $phrase) {
	if (DIRECTORY_SEPARATOR != "\\") return;
	if (true) return; // disable for debug
	echo "Playing '$phrase'...: ";
	playWaveFileInWindows($wav);
	echo "done.\n";
	return $wav;
}


?>