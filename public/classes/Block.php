<?php
class Block{
	
	function __construct($db) {
		$this->db = $db;
  	}
	public function writeLog($text){
		//file_put_contents(ABSDIR.'/history.log', $text."\n", FILE_APPEND | LOCK_EX);
	}
  	
  	public function getBlocks($withContent = true){
		$this->writeLog('getBlocks');
		$blobModel = new Blob($this->db);
		$sql = $this->db->prepare('SELECT * FROM '.TBL.' WHERE type = "block";');
		$sql->execute();
		$rows = $sql->fetchAll();
		$blocks = [];
		foreach($rows as $key => $block){
			$blocks[$block['url']] = $this->getBlockElements($block['id']);
			$blocks[$block['url']]['params'] = json_decode($block['params'], true);
			$blocks[$block['url']]['children'] = $this->getBlockElements($block['id']);
			
			//on some configs, this condition avoids an infinite loop if block.params.externalData is not set
			if($blocks[$block['url']]['params']['externalData']){
				$blocks[$block['url']]['externalData'] = $this->getBlockExternalData($blocks[$block['url']]['params']['externalData']);
			} 
		}
		return $blocks;
	}
	
	
  	private function getBlockElements($id, $limit = NULL, $type = NULL){
		$this->writeLog('getBlockElements - id: '.$id);
		$typeStr = !$type ? '!= "page"' : '= "'.$type.'"';
		$limitStr = $limit > 0 ? 'LIMIT '.$limit : '';
		$orderby = 'ORDER BY name ';
  		$sql = $this->db->prepare('SELECT * FROM '.TBL.' WHERE parent = :id AND type '.$typeStr.' AND status > -1 '.$orderby.$limitStr.';');
		$sql->execute([':id'=>$id]);
		$rows = $sql->fetchAll();
		foreach($rows as $key => $row){
			$rows[$key]['params'] = json_decode($rows[$key]['params'],true);
			if($rows[$key]['type'] == "gallery"){
				$galleryModel = new Gallery($this->db);
				$rows[$key]['_external']['images'] = $galleryModel->getGallery($rows[$key]['params']);
		
			}
		}
		return $rows;
  	}
        
    
  	private function getBlockExternalData($query){
		//TODO: doesn't work with Docker ports (8080 etc.), solution to find with a Proxy parameter
		$this->writeLog('getBlockExternalData - query: '.$query);
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, ABSPATH.$query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        return json_decode($data,true);
	}
	
}
