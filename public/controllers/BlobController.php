<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//Get Blob - unsafe base, display passwords in fields
$app->get('/{type}/{id:[0-9]+}[/]', function (Request $q, Response $r, array $args) {
	
	//TODO get module, if module is private i.e. site, user: 403, if module is public: continue

	if(!isset($_SESSION['id'])){return $r->withStatus(403);}
	
	$blobModel = new Blob($this->db);
	$id = (int)$args['id'];
	$blob = $blobModel->getBlob($id);
	
	if($blob['status'] == -1){return $r->withStatus(404);}
	if($_SESSION['id'] != $blob['author'] && $blob['status'] == 0){return $r->withStatus(404);} //TODO: function canDisplay() , like canEdit & canDelete.
	
	if($blob['type'] == $args['type']){
		return $r->withJson($blob);
	} else {
		return $r->withStatus(404);
	}
})->setName('getBlob');



$app->post('/blob/add[/]', function (Request $q, Response $r, array $args) {

	
	if(!isset($_SESSION['id'])){return $r->withStatus(403);}
	
	$blobModel = new Blob($this->db);
	$validate = new Validator($this->db);
	$post = $q->getParsedBody();
	
	if($post['type'] == 'site'){ return $r->withStatus(403); }

	$callback = $validate->asParam($post['callback']);
	
	$create = $blobModel->addBlob($post);
	
	//clean order of siblings
	$blobModel->cleanOrderValues($post['parent']);
	
	if(!!$callback && $create['statusCode'] == 200){


		return $r->withStatus($create['statusCode'])->withHeader('Location',ABSPATH.'/'.$callback.'/'); 
	}
	
	
	return $r->withStatus($create['statusCode'])->withJson($create);
	
})->setName('addBlob');



$app->get('/blob/{id}/delete[/]', function (Request $q, Response $r, array $args) {
	
	if(!isset($_SESSION['id'])){return $r->withStatus(403);}
	
	$adminModel = new Admin($this->db);
	$id = (int)$args['id'];
	
	if(!$adminModel->canDelete($id)){return $r->withStatus(403);}
	
	$blobModel = new Blob($this->db);
	$status = $blobModel->getBlobStatus($id);
	
	$validate = new Validator($this->db);
	$callback = $validate->asParam($_GET['callback']);
	
	if($status == -1){
		
		$parent = $blobModel->getBlobParent($id);

		$delete = $blobModel->deleteBlob($id);
		if(!$delete){return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>'delete_blob_error']);}
		$statusText = 'delete_blob_success';

		//clean order of siblings
		$blobModel->cleanOrderValues($parent);
		
	}
	else{
		$delete = $blobModel->setBlobStatus($id,'-1');
		if(!$delete){return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>'move_to_trash_error']);}
		$statusText = 'move_to_trash_success';
	}


	//callback
	if($callback == "adminRecycle"){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/recycle/?status=delete_success&id='.$id.'&statusField='.$status);
	} elseif($callback == "adminSitemap"){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/sitemap/?status=delete_success&id='.$id.'&statusField='.$status);
	} elseif($callback == "admin"){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/dashboard/?status=delete_success&id='.$id.'&statusField='.$status);
	} else {
		return $r->withStatus(200)->withJson(['status'=>'success','statusText'=>$statusText]); 
	}
	
	
})->setName('deleteBlob');




$app->get('/blob/{id}/status/{s}[/]', function (Request $q, Response $r, array $args) {
	
	if(!isset($_SESSION['id'])){return $r->withStatus(403);}
	
	$blobModel = new Blob($this->db);
	$id = (int)$args['id'];
	$statusField = (int)$args['status'];
	$setStatusField = $blobModel->setBlobStatus($id,$statusField);
	return $r->withStatus($setStatusField['statusCode'])->withJson($setStatusField);
	
})->setName('setBlobStatus');



$app->post('/blob/{id}/update[/]', function (Request $q, Response $r, array $args) {
	 
	if(!isset($_SESSION['id'])){return $r->withStatus(403);}
	
	$blobModel = new Blob($this->db);
	$validate = new Validator($this->db);
	
	
	//$post = $validate->validateArray($q->getParsedBody(), 'asString'); //TODO: improve for id,url,status,edited etc.
	$post = $q->getParsedBody();
	$id = (int)$args['id'];
	$callback = $validate->asParam($post['callback']);
	$update = $blobModel->updateBlob($id,$post);
	
	if(!!$callback && $update['statusCode'] == 200){
		
		//clean order of siblings - TODO: check if blob module has order
		if($post['type'] != "site" and $post['type'] != "user"){
			$blobModel->cleanOrderValues($post['parent']);
		}
		return $r->withStatus($update['statusCode'])->withHeader('Location',ABSPATH.'/'.$callback.'/'); 
	}
	
	return $r->withStatus($update['statusCode'])->withJson($update);
	
})->setName('updateBlob');
