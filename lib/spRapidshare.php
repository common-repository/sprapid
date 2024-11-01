<?php

//////////////////////////////////////////////////////////////////
#   usage for free-users:
#
#     $upload=new rapidphp;
#     $upload->sendfile("myfile.rar");
#
#
#   usage for premium-zone:
#
#     $upload=new rapidphp;
#     $upload->config("prem","username","password");
#     $upload->sendfile("myfile.zip");
#
#
#   usage for collector's zone:
#
#     $upload=new rapidphp;
#     $upload->config("col","username","password");
#     $upload->sendfile("myfile.tar.gz2");
#
#
#   you can upload several files if you want:
#
#     $upload=new rapidphp;
#     $upload->config("prem","username","password");
#     $upload->sendfile("myfile.part1.rar");
#     $upload->sendfile("myfile.part2.rar");
#     // and so on
#
#   sendfile() returns an array with data of the upload
#    [0]=Download-Link
#    [1]=Delete-Link
#    [2]=Size of the sent file in bytes
#    [3]=md5 hash (hex)
#
//////////////////////////////////////////////////////////////////

define('RS_URI', 'http://rapidshare.com/cgi-bin/rsapi.cgi?sub=');

class spRapidshare {

	private $maxbuf = 64000; // max bytes/packet
	private $uploadpath = 'l3';
	private $zone,$login,$passwort;

	private function hashfile($filename) {
		return strtoupper(md5_file($filename));
	}

	protected function getserver() {
		while(empty($server)) {
			$server = file_get_contents(RS_URI.'nextuploadserver_v1');
		}
		return sprintf('rs%s%s.rapidshare.com',$server,$this->uploadpath);
	}

	public function config($zone, $login = '',$passwort = '') { // configuration
		$this->zone = $zone;
		$this->login = $login;
		$this->passwort = $passwort;
	}

	public static function checkFiles($files, $filenames){
		$server = file_get_contents(RS_URI.'checkfiles_v1&files='.$files.'&filenames='.$filenames);
		$list = explode(',', $server);
		$fileClass = 'ERROR';
		if($list[0] != ''){
			$fileClass = new stdClass();
			$fileClass->ident = $list[0];
			$fileClass->fileName = $list[1];
			$fileClass->size = $list[2];
			$fileClass->serverId = $list[3];
			$fileClass->status = $list[4];
			$fileClass->shortHost = $list[5];
			$fileClass->md5 = $list[6];
		}
		return $fileClass;
	}

	public function getAccountInfo(){
		if(empty($this->login) || empty($this->passwort)){
			return __('ERROR: Can only getaccount info from premium or collector accounts. Please do the <a href="admin.php?page=spRapidSettings">settings</a>', 'spRapid');
		}else{
			$server = file_get_contents(RS_URI.'getaccountdetails_v1&type='.$this->zone.'&login='.$this->login.'&password='.$this->passwort);
			$result = explode("\n", $server);

			$account = new stdClass();
			foreach ($result as $key => $entry) {
				preg_match_all ("/([a-z0-9]+)=([a-z0-9@\.]*).*/", trim($entry), $matches);
				$var1 = $matches[1][0];
				$var2 = $matches[2][0];
				if($var1){
					$account->$var1 = $var2;
				}
				$account->premkb = '25000000';
			}
			return $account;
		}
	}

	public static function isUser($type, $login, $pass){
		$rapid = new spRapidshare();
		$rapid->zone = $type;
		$rapid->login = $login;
		$rapid->passwort = $pass;
		if(is_string($account)){
			if(strpos($rapid->getAccountInfo(), 'ERROR') === 0){
				return false;
			}
		}
		return true;
	}

	public function getFiles($aOrder = 'filename', $aDesc = 0){
		if(empty($this->login) || empty($this->passwort)){
			return __('ERROR: Can only list files from premium or collector accounts. Please do the <a href="admin.php?page=spRapidSettings">settings</a>', 'spRapid');
		}else{
			$fields = '&fields=downloads,lastdownload,filename,size,killcode,uploadtime';
			$order = '&order='.$aOrder;
			$desc = '&desc='.$aDesc;
			$server = file_get_contents(RS_URI.'listfiles_v1&type='.$this->zone.'&realfolder=all&login='.$this->login.'&password='.$this->passwort.$fields.$order.$desc);

			$result = explode("\n", $server);
			if($result != 'NONE'){
				// build files to object
				$resultFiles = array();
				foreach ($result as $entry) {
					$list = explode(',', $entry);
					if($list[0] != ''){
						$fileClass = new stdClass();
						$fileClass->fileId = $list[0];
						$fileClass->downloads = $list[1];
						$fileClass->lastDownload = $list[2];
						$fileClass->fileName = $list[3];
						$fileClass->size = $list[4];
						$fileClass->killCode = $list[5];
						$fileClass->uploadTime = $list[6];

						$resultFiles[] = $fileClass;
					}
				}
				return $resultFiles;
			}
			return $result;
		}
	}

	public static function getStatus($status){
		//0=File not found
		//1=File OK (Downloading possible without any logging)
		//2=File OK (TrafficShare direct download without any logging)
		//3=Server down
		//4=File marked as illegal
		//5=Anonymous file locked, because it has more than 10 downloads already
		//6=File OK (TrafficShare direct download with enabled logging. Read our privacy policy to see what is logged.)
		$statusArray = array(
		__('<img src="'.SP_PLUGIN_URI_3.'/images/notfound.png" /> File not found', 'spRapid'),
		__('<img src="'.SP_PLUGIN_URI_3.'/images/online.png" /> Online', 'spRapid'),
		__('<img src="'.SP_PLUGIN_URI_3.'/images/online.png" /> Online', 'spRapid'),
		__('<img src="'.SP_PLUGIN_URI_3.'/images/offline.png" /> Server down', 'spRapid'),
		__('<img src="'.SP_PLUGIN_URI_3.'/images/illegal.png" /> File marked as illegal', 'spRapid'),
		__('<img src="'.SP_PLUGIN_URI_3.'/images/locked.png" /> File locked', 'spRapid'),
		__('<img src="'.SP_PLUGIN_URI_3.'/images/online.png" /> Online', 'spRapid')
		);
		return $statusArray[$status];
	}

	public function deleteFiles($files){
		if(empty($this->login) || empty($this->passwort)){
			return __('ERROR: Can only getaccount info from premium or collector accounts. Please do the <a href="admin.php?page=spRapidSettings">settings</a>', 'spRapid');
		}else{
			$server = file_get_contents(RS_URI.'deletefiles_v1&type='.$this->zone.'&login='.$this->login.'&password='.$this->passwort.'&files='.$files);
			return $server;
		}
	}

	public function sendFile($file) {
		if(empty($this->zone)) {
			$this->zone="free";
		}
		if($this->zone=="prem" OR $this->zone=="col") {
			if(empty($this->login) OR empty($this->passwort)) {
				$this->zone="free";
			}
		}
		if(!file_exists($file)) {
			die("File not found!");
		}
		$hash=$this->hashfile($file);
		$size=filesize($file);
		$cursize=0;
		$server=$this->getserver();
		$sock=	fsockopen($server,80,$errorno,$errormsg,30) or die("Unable to open connection to rapidshare\nError $errorno ($errormsg)");
		stream_set_timeout($sock,3600);
		$fp=	fopen($file,"r");
		$boundary = "---------------------632865735RS4EVER5675865";
		$contentheader="\r\nContent-Disposition: form-data; name=\"rsapi_v1\"\r\n\r\n1\r\n";
		if($this->zone=="prem") {
			$contentheader .= sprintf("%s\r\nContent-Disposition: form-data; name=\"login\"\r\n\r\n%s\r\n",$boundary,$this->login);
			$contentheader .= sprintf("%s\r\nContent-Disposition: form-data; name=\"password\"\r\n\r\n%s\r\n",$boundary,$this->passwort);
		}
		if($this->zone=="col") {
			$contentheader .= sprintf("%s\r\nContent-Disposition: form-data; name=\"freeaccountid\"\r\n\r\n%s\r\n",$boundary,$this->login);
			$contentheader .= sprintf("%s\r\nContent-Disposition: form-data; name=\"password\"\r\n\r\n%s\r\n",$boundary,$this->passwort);
		}
		$contentheader .= sprintf("%s\r\nContent-Disposition: form-data; name=\"filecontent\"; filename=\"%s\"\r\n\r\n",$boundary,$file);
		$contenttail = "\r\n".$boundary."--\r\n";
		$contentlength = strlen($contentheader) + $size + strlen($contenttail);
		$header = "POST /cgi-bin/upload.cgi HTTP/1.0\r\nContent-Type: multipart/form-data; boundary=".$boundary."\r\nContent-Length: ".$contentlength."\r\n\r\n";
		fwrite($sock,$header.$contentheader);

		while($cursize < $size) {
			$buf=fread($fp,$this->maxbuf) or die("");
			$cursize=$cursize+strlen($buf);
			if(fwrite($sock,$buf)) {
				#printf("%d of %d Bytes sent.\n",$cursize,$size);
			}
		}
		fwrite($sock,$contenttail);

		$ret=fread($sock,10000);
		preg_match("/\r\n\r\n(.+)/s",$ret,$match);
		$ret=explode("\n",$match[1]);
		fclose($sock);
		fclose($fp);
		foreach($ret as $id => $cont) {
			if($id!=0) {
				#if($id>4) break;
				$key_val[]=substr($cont,8);
			}
		}
		if($hash==$key_val[3]) {
			return $key_val;
		} else {
			#printf("Upload FAILED! Your hash is %s, while the uploaded file has the hash %s",$hash,$key_val[3]);
			return FALSE;
		}
	}
}