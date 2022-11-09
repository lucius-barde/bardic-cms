<?php
class Gallery{
	
	function __construct($db) {
		$this->db = $db;
  	}
  	
  	function getGallery($params){
		$images = [];
		
		if(is_dir(ABSDIR.'/uploads/'.$params['folderBase'])){
			if($dir = opendir(ABSDIR.'/uploads/'.$params['folderBase'])){
				while (false !== ($entry = readdir($dir))) {
					if(preg_match('/^.*\.(jpg|png)$/i',$entry)){ // /i = jpg JPG case insensitive
						$images[] = $entry;
					}
				}
				closedir($dir);
			}
		}
		sort($images);
		return $images;
	}
        
}
