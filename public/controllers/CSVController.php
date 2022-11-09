<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


$app->get('/csv/{filename}[/]', function (Request $q, Response $r, $args) {
	
	
	$adminModel = new Admin($this->db);
	$validate = new Validator($this->db);
	$pageModel = new Page($this->db);
	$blobModel = new Blob($this->db);
	$blockModel = new Block($this->db);

	$site = $blobModel->getSiteProperties(); 
	
	//mandatory blob info for readModule view
	$blob['type'] = "csv";
	$blob['lang'] = "en";
	
	$id = $blob['id'];
	$blocks = $blockModel->getBlocks();

	$filename = $validate->asParam($args['filename']);
	$file = ABSDIR.'/uploads/'.$filename.'.csv';
	
	if(!is_file($file)){
		return $r->withStatus(404);
	}
	
	$csv = array_map('str_getcsv', file($file));
	
	
	//to adapt according to what is needed
	$csvList = [];
	foreach($csv as $key => $csvElement){
		if($key > 0){
			$csvList[$csvElement[0]]['id'] = $csvElement[0];
			$csvList[$csvElement[0]]['name'] = $csvElement[1];
			$csvList[$csvElement[0]]['description'] = $csvElement[2];
			$csvList[$csvElement[0]]['image'] = $csvElement[3];
		}
	}
	
	
	//Build view
    $params = [ 
		"ABSPATH" => ABSPATH,
		"ABSDIR" => ABSDIR,
		"action" => "readModule",
		"blob" => $blob,
		"blobModel" => $blobModel,
		"blocks" => $blockModel->getBlocks(),
		"csv" => $csvList,
		"csvName" => $filename,
		"session" => $_SESSION,
		"site" => $site,
		"sitemap" => $adminModel->getSitemap(),
		"type" => $blob['type'],
		"validate" => $validate
	];
	
    $r = $this->viewAdmin->render($r, "standard.html.twig", $params);
	return $r;
	
	
});


$app->get('/csv/{filename}/detail/{id}[/]', function (Request $q, Response $r, $args) {
	
	var_dump($args['id']);exit;

});
