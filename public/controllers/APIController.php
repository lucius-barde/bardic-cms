<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


//for Form testing
$app->post('/api/dummy[/]', function (Request $q, Response $r, array $args) {
	return $r->withJson(200);
})->setName('apiDummy');

//Admin API request - Get All Routes (to verify constraint problems)
$app->get('/api/getAllRoutes[/]', function (Request $q, Response $r, array $args) {
	
	if(!isset($_SESSION['id'])){return $r->withStatus(302)->withHeader('Location', ABSPATH.'/user/login/');}
	
	$apiModel = new API($this->db);
	$allRoutes = $apiModel->getAllRoutes();
    return $r->withJson($allRoutes);
    
})->setName('apiGetAllRoutes');


//Admin - Get Blob
$app->get('/api/getBlob/{id:[0-9]+}[/]', function (Request $q, Response $r, array $args) {
	
	if(!isset($_SESSION['id'])){return $r->withStatus(302)->withHeader('Location', ABSPATH.'/user/login/');}
	
	$blobModel = new Blob($this->db);
	$id = (int)$args['id'];
	$blob = $blobModel->getBlob($id);
    return $r->withJson($blob);
    
})->setName('apiGetBlob');

//Admin - quick password hash (insecure, keeps password in URL history)
$app->get('/api/bcrypt/{testpassword}[/]', function (Request $q, Response $r, array $args) {
	
	if(!isset($_SESSION['id'])){return $r->withStatus(302)->withHeader('Location', ABSPATH.'/user/login/');}
	return password_hash($args['testpassword'], PASSWORD_BCRYPT);
})->setName('apiBcrypt');


// Update single blob field
$app->post('/api/updateField[/]', function(Request $q, Response $r){
	
	if(!isset($_SESSION['id'])){return $r->withStatus(302)->withHeader('Location', ABSPATH.'/user/login/');}
	
	
	$validate = new Validator($this->db);
	$post = $q->getParsedBody();
	$id = (int)$post['id'];
	$field = $validate->asParam($post['field']);
	$value = $post['value'];
	
	$adminModel = new Admin($this->db);	
	if(!$adminModel->canEdit($id)){return $r->withStatus(403);}
	
	switch($field){
		case 'type': case 'url': $output = $validate->asURL($value); break;
		case 'name': case 'content': $output = $validate->asString($value); $output = html_entity_decode($output, ENT_QUOTES); break;
		case 'author': case 'parent': case 'translation_of': $output = $validate->asUnsignedInt($value); break; //todo: asExistingBlob
		case 'status': $output = $validate->asStatus($value); break;
		case 'edited': $output = $validate->asDateTime($value); break; //TODO: as date
		case 'params': $output = $validate->asJson($value); break;
		case 'lang': $output = $validate->asParam($value); break;
	}
	
	if($output === false){
		return $r->withStatus(403)->withJson(['status'=>'error','statusText'=>'invalid_parameter','value'=>$value, 'output'=>$output]);
	} else {
		$blobModel = new Blob($this->db);
		try{
			$sql = $this->db->prepare('UPDATE '.TBL.' SET '.$field.' = :value WHERE id = :id;');
			$sql->execute([':value'=>$output,':id'=>$id]);
			return $r->withStatus(200)->withJson(['status'=>'success','value'=>$value,'output'=>$output,'id'=>$id,'field'=>$field]);
		} catch(PDOException $e){
			return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>$e->getMessage()]);
		}
		
	} 
	
	
})->setName('apiUpdateField');