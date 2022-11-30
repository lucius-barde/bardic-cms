<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/metalarch/getBand/{id}[/]', function (Request $q, Response $r, array $args) {
	
	$id = (int)$args['id'];
	if(!$id){return $r->withStatus(400)->withJson(['status'=>'error','statusText'=>'Bad request (invalid ID)']);}

	$cachedir = ABSDIR.'/cache/metalarch/'.$id;
	$cachefile = ABSDIR.'/cache/metalarch/'.$id.'/'.$id.'.txt';
	
	if(!is_dir($cachedir)){
		mkdir($cachedir);
	}

	if(!is_dir($cachedir)){
		if(!$id){return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>'Unable to create the cache folder, please check the permissions on the server.']);}
	}

	$metalArchModel = new MetalArch();
		
	//check datediff
	
	if(is_file($cachefile)){
		$tmpJSON = json_decode(file_get_contents($cachefile),true);
		$fetchDateDiff = time() - $tmpJSON['edited'];
	}

	// max storage period before reloading remote source: 1 month
	if($fetchDateDiff > 2630000 or !$fetchDateDiff or !is_file($cachefile)){
		
			
		//fetch data from website    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.metal-archives.com/band/view/id/".$id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $bandHTML = curl_exec($ch);
        curl_close($ch);     
		
		
		$bandJSON = $metalArchModel->parseFile($bandHTML);
		if(!$bandJSON){
			return $r->withStatus(204)->withJson(['status'=>'error','statusText'=>'No data available with the requested ID']);
		}
		
		//get images
		$ch = curl_init($bandJSON['params']['logo']);
		$fp = fopen(ABSDIR.'/cache/metalarch/'.$id.'/'.$id.'-logo.png', 'wb');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		$ch = curl_init($bandJSON['params']['photo']);
		$fp = fopen(ABSDIR.'/cache/metalarch/'.$id.'/'.$id.'-photo.jpg', 'wb');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
					
		$bandJSON['params']['logo'] = '/cache/metalarch/'.$id.'/'.$id.'-logo.png';
		$bandJSON['params']['photo'] = '/cache/metalarch/'.$id.'/'.$id.'-photo.jpg';
		
		$bandJSON['params']['bandId'] = $id;
		$bandJSON['url'] = 'metalarch-'.$id;
		$bandJSON['params']['fetchDateDiff'] = 0;
		
		
		//geocode the city, region
		$coords = $metalArchModel->geocode($bandJSON['params']['location'],$bandJSON['params']['countryOfOrigin']); 
		if($coords[0]){
			$bandJSON['params']['coordinates']['lat'] = $coords[0]['lat'];
			$bandJSON['params']['coordinates']['lng'] = $coords[0]['lon'];
		}
		
		$bandJSON['edited'] = time();
		
		ksort($bandJSON);
		ksort($bandJSON['params']);
		file_put_contents($cachefile, json_encode($bandJSON));
		
		
		
		//check if data has been written successfully in cache	
		if(is_file($cachefile)){
			
			
			$bandJSON['params']['source'] = 'remote';
			ksort($bandJSON);
			ksort($bandJSON['params']);
			return $r->withStatus(200)->withJson($bandJSON);
			
		}else{
			return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>'Unable to write the cache file, please check the permissions on the website.','source'=>'remote']);
		}
		
		
	}else{
		//get data from cache (assuming stored in JSON)
		$bandJSON = json_decode(file_get_contents($cachefile),true);
		$bandJSON['params']['source'] = 'local';
		$bandJSON['params']['fetchDateDiff'] = $fetchDateDiff;
		ksort($bandJSON);
		ksort($bandJSON['params']);
		return $r->withStatus(200)->withJson($bandJSON);
	}	
	
})->setName('metalArchGetBand');
