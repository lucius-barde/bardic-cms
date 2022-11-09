<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;



$app->post('/form/send[/]', function (Request $q, Response $r, array $args) {
	
	$validate = new Validator($this->db);
	$formModel = new Form($this->db);
	$post = $q->getParsedBody();
	$blobModel = new Blob($this->db);
	$formObject = $blobModel->getBlob($post['formData']['id']);
    
    
	if(!$post['formData']['id'] || !$post['formData']['check'] || $post['formData']['check'] % ($post['formData']['id'] + $formObject['params']['nonce']) != 0){ //replace 42 with a config param
		echo 'Your query was flagged as Spam. Please retry.';
		exit;
	}
	

    foreach($post['formData'] as $key => $field){
		$post['formData'][$key] = $validate->asString($field);
		if($key == 'email'){
			$post['formData']['email'] = $validate->asEmail($post['formData']['email']);
		}
	}
		
	// Recaptcha
	
	global $config;
	define("RECAPTCHA_V3_SECRET_KEY", $config['recaptcha']['v3_secret_key']);
	
	if (isset($post['formData']['email'])) {
		$email = filter_var($_POST['email'], FILTER_SANITIZE_STRING);
	} else {
		return $r->withStatus(400)->withJson(['statusText'=>'Error, no e-mail address']);
	}
		
	$token = $post['token'];
	$action = $post['action'];
		
	// call curl to POST request
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => RECAPTCHA_V3_SECRET_KEY, 'response' => $token)));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
	$arrResponse = json_decode($response, true);
	// verify the response
	if($arrResponse["success"] == '1') {
	} else {
		return $r->withStatus(403)->withJson(['statusText'=>'Something went wrong. Please try again later. Status: '.var_export($arrResponse,true)]);
	}
			
	
	// End Recaptcha
		
	$sendForm = $formModel->sendForm($post);
	
	//Copy-pasted from PageController...
	$adminModel = new Admin($this->db);
	$pageModel = new Page($this->db);
	$blob = $blobModel->getBlob($sendForm['callbackPageID']); //page id is given by the form callback
	$blockModel = new Block($this->db);
	$site = $blobModel->getSiteProperties(); 
	
	$params = [ 
		"ABSPATH" => ABSPATH,
		"ABSDIR" => ABSDIR,
		"RECAPTCHA_V3_SITE_KEY" => RECAPTCHA_V3_SITE_KEY,
		"action" => "read",
		"blob" => $blob,
		"blobModel" => $blobModel,
		"blocks" => $blockModel->getBlocks(),
		"elements" => $pageModel->getPageElements($sendForm['callbackPageID']), //page id is given by the form callback
		"isHome" => $pageModel->isHome($blob['id'],$blob['lang']),
		"session" => $_SESSION,
		"site" => $site,
		"sitemap" => $adminModel->getSitemap(),
		"translations" => $pageModel->getTranslations($blob['translation_of']),
		"type" => $blob['type'],
		"validate" => $validate,
		
		//...with form params added
		"status" => $sendForm['status'],
		"statusText" => $sendForm['statusText']
	];

	
	$r = $this->view->render($r, "standard.html.twig", $params);
	
})->setName('formSend');
