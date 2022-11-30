<?php
class MetalArch{
	
	function __construct() {
	}

	public function elementToObj($element) {
		$obj = array( "tag" => $element->tagName );
		foreach ($element->attributes as $attribute) {
			$obj[$attribute->name] = $attribute->value;
		}
		//for text elements
		if(in_array($element->tagName, ['dd'])){
			
			foreach ($element->childNodes as $subElement) {
				if ($subElement->nodeType == XML_TEXT_NODE) {
					$obj["html"][]['html'] = $subElement->wholeText;
				}
				else {
					$obj["html"][] = $this->elementToObj($subElement);
				}
			}
			
		}else{
		//for other elements
		foreach ($element->childNodes as $subElement) {
			if ($subElement->nodeType == XML_TEXT_NODE) {
				$obj["html"] .= $subElement->wholeText;
			}
			else {
				$obj["children"][] = $this->elementToObj($subElement);
			}
		}
		}
		return $obj;
	}
	
	public function geocode($location,$country){
		
		if(!$location or !$country){ return false; }
		
		//get geocode from an external provider
		 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://nominatim.openstreetmap.org/search.php?q='.rawurlencode($location).',%20'.rawurlencode($country).'&format=jsonv2');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); 
		curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
		$output = curl_exec($ch);
		curl_close($ch);
		return json_decode($output,true); 
		
	}
	
	
	public function implodeHTML($obj){
		foreach($obj as $element){
			$txt .= $element['html'];
		}
		return $txt;
	}
	
	
	public function parseFile($bandHTML){
		
		// BEGIN parse html
			
		
			$domHandler = new DOMDocument();
			$domHandler->loadHTML($bandHTML);
			
	
			// #ID of band_info tag
			$bandInfo = $this->elementToObj($domHandler->getElementById('band_info'));
			if($bandInfo['children'][0]['class'] == "band_name"){
				$bandJSON['bandName'] = $bandInfo['children'][0]['children'][0]['html'];
			}
			
			
			// #ID of band_stats tag
			$bandStats = $this->elementToObj($domHandler->getElementById('band_stats'));
			if($bandStats['children'][0]['tag'] == "dl"){
				
				$bandStatsLeft = $bandStats['children'][0]['children'];
				foreach($bandStatsLeft as $key => $stat){
					
					//country of origin
					if($bandStatsLeft[$key-1]['html'] == 'Country of origin:'){
						$bandJSON['countryOfOrigin'] = $this->implodeHTML($stat['html']); 
					}
					//location
					if($bandStatsLeft[$key-1]['html'] == 'Location:'){
						$locationArr = $this->implodeHTML($stat['html']); 
						$locationArr = str_replace(['(early)','(mid)','(later)',' / '],['','','',';'],$locationArr); //alows exploxding on both / and ;
						$locationArr = explode(';',$locationArr);
						foreach($locationArr as $locationKey => $aLocation){
							$locationArr[$locationKey] = str_replace('N/A','',$aLocation);
						}
						$bandJSON['location'] = trim($locationArr[0]);
						$bandJSON['location2'] = trim($locationArr[1]);
						$bandJSON['location3'] = trim($locationArr[2]);
					}
					//status
					if($bandStatsLeft[$key-1]['html'] == 'Status:'){
						$bandJSON['status'] = $this->implodeHTML($stat['html']); 
					}
					//formed in
					if($bandStatsLeft[$key-1]['html'] == 'Formed in:'){
						$bandJSON['formedIn'] = $this->implodeHTML($stat['html']); 
					}
					
				}
				
				
				$bandStatsRight = $bandStats['children'][1]['children'];
				foreach($bandStatsRight as $key => $stat){
				
					//genre
					if($bandStatsRight[$key-1]['html'] == 'Genre:'){
						$bandJSON['genre'] = $this->implodeHTML($stat['html']); 
					}
					//lyricalThemes
					if($bandStatsRight[$key-1]['html'] == 'Lyrical themes:'){
						$bandJSON['lyricalThemes'] = $this->implodeHTML($stat['html']); 
					}
					//lastLabel
					if($bandStatsRight[$key-1]['html'] == 'Last label:'){
						$bandJSON['lastLabel'] = $this->implodeHTML($stat['html']); 
					}
					//currentLabel
					if($bandStatsRight[$key-1]['html'] == 'Current label:'){
						$bandJSON['currentLabel'] = $this->implodeHTML($stat['html']); 
					}
					
				}
				
				$bandStatsLower = $bandStats['children'][2]['children'];
				foreach($bandStatsLower as $key => $stat){
					//yearsActive
					if($bandStatsLower[$key-1]['html'] == 'Years active:'){
						$bandJSON['yearsActive'] = $this->implodeHTML($stat['html']); 
					}
				}
				
			}
			
			// #ID of logo tag
			$bandLogo = $this->elementToObj($domHandler->getElementById('logo'));
			if(!!$bandLogo['href']){
				$bandJSON['logo'] = $bandLogo['href'];
			}
			
			// #ID of photo tag
			$bandPhoto = $this->elementToObj($domHandler->getElementById('photo'));
			if(!!$bandPhoto['href']){
				$bandJSON['photo'] = $bandPhoto['href'];
			}
			
	
		// END parse html
		return $bandJSON;
	}
		
	
}
