<?php

class spMainClass {

	public static function setSettings($plugin){
		$class = new stdClass();
		foreach ($_POST as $key => $entry) {
			$class->$key = $entry;
		}
		update_option($plugin, serialize($class));
	}

	public static function getSettings($plugin){
		return unserialize(get_option($plugin));
	}

	public static function deleteSettings($plugin){
		delete_option($plugin);
	}

	public static function showMessage($message, $class = 'updated'){
		$result = '<div class="'.$class.' fade"><p>'.$message.'</p></div>';
		return $result;
	}

	public static function setLanguage($plugin){
		load_plugin_textdomain($plugin, '/wp-content/plugins/'.$plugin.'/languages/');
	}

	public static function getRightSize($size){
		if ($size<1024) return $size." Byte";
		else if ($size<1024000) return round($size/1024,2)." KB";
		else if ($size<1048576000) return round($size/1048576,2)." MB";
		else if ($size<1073741824000) return round($size/1073741824,2)." GB";
	}
}