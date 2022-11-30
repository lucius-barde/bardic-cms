<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/metalarch/getBand/{id}[/]', function (Request $q, Response $r, array $args) {
	
	$id = (int)$args['id'];
	$cachedir = ABSDIR.'/cache/metalarch/'.$id;
	$cachefile = ABSDIR.'/cache/metalarch/'.$id.'/'.$id.'.txt';
	
	if(!is_dir($cachedir)){
		mkdir($cachedir);
	}

	$metalArchModel = new MetalArch();
		
	//check datediff
	
	if(is_file($cachefile)){
		$tmp = json_decode(file_get_contents($cachefile),true);
		$fetchDateDiff = time() - $tmp['fetchedDate'];
	}
	
	if($fetchDateDiff > 86400 or !$fetchDateDiff or !is_file($cachefile)){
		
			
		//fetch data from website    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.metal-archives.com/band/view/id/".$id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $bandHTML = curl_exec($ch);
        curl_close($ch);     
		
		
		$bandJSON = $metalArchModel->parseFile($bandHTML);
		if(!$bandJSON){
			return $r->withStatus(404)->withJson(['status'=>'error','statusText'=>'no_data']);
		}
		
		//get images
		$ch = curl_init($bandJSON['logo']);
		$fp = fopen(ABSDIR.'/cache/metalarch/'.$id.'/'.$id.'-logo.png', 'wb');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		$ch = curl_init($bandJSON['photo']);
		$fp = fopen(ABSDIR.'/cache/metalarch/'.$id.'/'.$id.'-photo.jpg', 'wb');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
					
		$bandJSON['logo'] = '/cache/metalarch/'.$id.'/'.$id.'-logo.png';
		$bandJSON['photo'] = '/cache/metalarch/'.$id.'/'.$id.'-photo.jpg';
		
		$bandJSON['bandId'] = $id;
		
		
		//geocode the city, region
		$coords = $metalArchModel->geocode($bandJSON['location'],$bandJSON['countryOfOrigin']); 
		if($coords[0]){
			$bandJSON['coordinates']['lat'] = $coords[0]['lat'];
			$bandJSON['coordinates']['lng'] = $coords[0]['lon'];
		}
		
		$bandJSON['fetchedDate'] = time();
		
		file_put_contents($cachefile, json_encode($bandJSON));
		
		
		
		//check if data has been written successfully in cache	
		if(is_file($cachefile)){
			
			
			$bandJSON['source'] = 'remote';
			return $r->withStatus(200)->withJson($bandJSON);
			
		}else{
			return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>'unable_to_write_cachefile','source'=>'remote']);
		}
		
		
	}else{
		//get data from cache (assuming stored in JSON)
		$bandJSON = json_decode(file_get_contents($cachefile),true);
		$bandJSON['source'] = 'local';
		return $r->withStatus(200)->withJson($bandJSON);
	}	
	
})->setName('metalArchGetBand');
