<?php

class ModelToolStyle extends Model {
	public function getFilepath($filename, $styles) {
		$dir = DIR_TEMPLATE . $this->config->get('config_theme') . '/stylesheet/';
		$filepath = $dir . $filename . '.css';
		$lastUpdate = file_exists($filepath) ? filemtime($filepath) : 0;
		foreach ($styles as $style) {
			$stylepath = $dir . $style . '.css';
			if (file_exists($stylepath) && filemtime($stylepath) > $lastUpdate) {
				$this->createFile($filename, $styles);
				break;
			}
		}
		return substr(DIR_TEMPLATE, strlen(DIR_BASE)) . $this->config->get('config_theme')
				. '/stylesheet/' . $filename .'.css?d=' . filemtime($filepath);
	}
	
	public function createFile($filename, $styles) {
		$dir = DIR_TEMPLATE . $this->config->get('config_theme') . '/stylesheet/';
		$filepath = $dir . $filename . '.css';
		
		ob_start();
		foreach ($styles as $i => $style) {
			$stylepath = $dir . $style . '.css';
			if ($i>0) echo "\r\n\r\n\r\n\r\n";
			echo "/* * * $style.css * * */\r\n\r\n";
			if (file_exists($stylepath)) {
				include $stylepath;
			} else {
				echo "/* File not exists: $stylepath */";
			}
		}
		$style = ob_get_clean();
		
		file_put_contents($filepath, $style);
	}
}