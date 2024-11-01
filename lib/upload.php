<?php
if (!function_exists('add_action')){
	require_once(dirname(__FILE__).'/../../../../wp-config.php');
	require_once(dirname(__FILE__).'/spMainClass.php');
	global $wpdb;
}
if (isset($_FILES["resume_file"]) && is_uploaded_file($_FILES["resume_file"]["tmp_name"]) && $_FILES["resume_file"]["error"] == 0) {
	$settings = spMainClass::getSettings('spRapid');

	$source = $_FILES['resume_file']['tmp_name'];
	$dest = dirname(__FILE__).'/../tmp/'.$_FILES['resume_file']['name'];
	if ($source){
		copy($source,$dest);
	}

	$upload = new spRapidshare();
	if(isset($settings->spRapidType)){
		$upload->config($settings->spRapidType,$settings->spRapidId,$settings->spRapidPass);
	}
	$retour = $upload->sendFile($dest);
	if($retour){
		$fileId = spFileResponseReader::get('fileId', $retour[0]);
		$fileName = spFileResponseReader::get('fileName', $retour[0]);
		$fileSize = $retour[2];
		$killCode = spFileResponseReader::get('killCode', $retour[1]);

		// do the short url
		$shortApi = file('http://shorted.de/api.php?url='.$retour[0]);
		if(!spRapidshare::isUser($settings->spRapidType, $settings->spRapidId, $settings->spRapidPass)){
			$wpdb->insert($wpdb->prefix.'rapid_files', array('fileid' => $fileId, 'filename' => $fileName, 'size' => $fileSize, 'killcode' => $killCode, 'uploadtime' => time()));
		}
		$wpdb->insert($wpdb->prefix.'rapid_links', array('fileid' => $fileId, 'short' => $shortApi[0]));

		unlink($dest);

		echo 'OK';
	}
}

class spFileResponseReader {
	private $string = '';

	protected function killCode(){
		$file = explode('=', $this->string);
		return $file[1];
	}

	protected function fileName(){
		$file = explode('/', $this->string);
		$file = substr($file[5],0,-5);
		return $file;
	}

	protected function fileId(){
		$file = substr($this->string, 28, 15);
		$file = explode('/', $file);
		return $file[0];
	}

	public static function get($type, $string){
		$file =  new spFileResponseReader();
		$file->string = $string;
		return $file->$type();
	}
}

exit(0);
