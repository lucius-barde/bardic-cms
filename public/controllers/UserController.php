<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;



/** GET LOGIN FORM **/

$app->get('/user/login[/]', function (Request $q, Response $r, $args) {
	
	//Check if users are allow to signup
	$blobModel = new Blob($this->db);
	$site = $blobModel->getSiteProperties();
    //Build view
    $params = [
		'ABSDIR'=>ABSDIR,
		'ABSPATH'=>ABSPATH,
		'action'=>'userLogin',
		'site'=>$site,
		'session' => $_SESSION
	];
	
    $r = $this->viewAdmin->render($r, "standard.html.twig", $params);
	return $r;
})->setName('userLogin');



/** LOG IN **/

$app->post('/user/login[/]', function (Request $q, Response $r) {
	
	
	//Check if users are allow to signup
	$blobModel = new Blob($this->db);
	$siteParamsQuery = $blobModel->getSiteProperties();
	$siteParams = json_decode($siteParamsQuery['params'],true);
	$allowUserSignup = $siteParams['allowUserSignup'];
	
	
    $post = $q->getParsedBody();
    $userModel = new User($this->db);
    $validate = new Validator($this->db);
    $callback = $validate->asParam($post['callback']);
    $user = $userModel->login($validate->asParam($post['userAdmin']['login']),$validate->asParam($post['userAdmin']['password']));
    if(!!$user && !$user['error']):
    
    	//Log something somewhere here.
    	$_SESSION['id'] = session_id(); //TO REMOVE ?
    	$_SESSION['user_id'] = session_id();
    	$_SESSION['login'] = $user['login'];
    	$_SESSION['status'] = 1; //TO REMOVE IN ALL APP
		$_SESSION['params']['level'] = 3; //TO REMOVE IN ALL APP
    	  
    	 
    	if($callback){
			return $r->withStatus(302)->withHeader('Location', ABSPATH.'/'.$callback.'/');
		} else {
			return $r->withStatus(200)->withJson(['status'=>'success','statusText'=>'user_login_success']);
		}
		
		
    elseif($user['error']):
		
		if($post['callbackIfError'] == true){
			
			///// Copy-paste from GET user/login
			//Check if users are allow to signup
			$blobModel = new Blob($this->db);
			$siteParamsQuery = $blobModel->getSiteProperties();
			$siteParams = json_decode($siteParamsQuery['params'],true);
			$allowUserSignup = $siteParams['allowUserSignup'];
			//Build view
			$params = [
				'ABSDIR'=>ABSDIR,
				'ABSPATH'=>ABSPATH,
				'action'=>'userLogin',
				'allowUserSignup'=>$allowUserSignup,
				'session' => $_SESSION,
				'status'=>'warning',
				'statusText'=>'user_login_incorrect_username'
			];
			///// End. TODO: route with $app->map() to avoid this copy/paste.
			
		}else{
			
			return $r->withStatus(200)->withJson(['status'=>'warning','statusText'=>'user_login_incorrect_username']);
			
		}
			
		$r = $this->viewAdmin->render($r, "standard.html.twig", $params);
		return $r;
		
    else:
    
		return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>'user_login_unknown_error']);
		
    endif;
    
    $params = [
		'ABSDIR'=>ABSDIR,
		'ABSPATH'=>ABSPATH,
		'action'=>'userLogin',
		'session'=>$_SESSION
	];
    $r = $this->viewAdmin->render($r, "standard.html.twig", $params);
    
	return $r;
})->setName('userPostLogin');



/** LOG OUT **/

$app->map(['GET', 'POST'], '/user/logout[/]', function (Request $q, Response $r) {
	session_destroy();
	unset($_SESSION);
	if($q->getMethod() == "POST"){
		$post = $q->getParsedBody();
		$validate = new Validator();
		$callback = $validate->asURL($post['callback']);
		if(isset($callback)){
		return $r->withStatus(302)->withHeader('Location', $callback);
		}
		return $r->withStatus(200)->withJson(['status'=>'success','statusText'=>'user_logout_success']);
	}else{
		return $r->withStatus(302)->withHeader('Location', ABSPATH);
	}
})->setName('userLogout');



/** GET SIGN UP FORM **/

$app->get('/user/signup[/]', function (Request $q, Response $r) {
	
	
	//Check if users are allow to signup
	$blobModel = new Blob($this->db);
	$site = $blobModel->getSiteProperties();
	$allowUserSignup = $site['params']['allowUserSignup'];

	
	if(!$allowUserSignup):
		$r->getBody()->write("<h3>404 - Not Found</h3>");
		return $r->withStatus(404);
	else:
		$params = [
			'ABSDIR' => ABSDIR,
			'ABSPATH' => ABSPATH,
			'site'=>$site,
			'action'=>'userSignup',
		];
		$r = $this->viewAdmin->render($r, "standard.html.twig", $params);
		return $r;
	endif;
	
})->setName('userSignup');



/** SIGN UP **/

$app->post('/user/signup[/]', function (Request $q, Response $r) {
	//Check if users are allow to signup
	$blobModel = new Blob($this->db);
	$site = $blobModel->getSiteProperties();
	
	if(!$site['params']['allowUserSignup']):
	
		$r->getBody()->write("<h3>403 - Forbidden</h3>");
		return $r->withStatus(403);
		
	else:

		$form = $q->getParsedBody();
		$userModel = new User($this->db);
		$validate = new Validator($this->db);
		$new = $form['userAdmin'];
		
		$new['login'] = $validate->asParam($new['login']);
		$new['email'] = $validate->asEmail($new['email']);
		$new['password'] = $validate->asString($new['password']);
		//$new['password_confirm'] = $validate->asString($new['password_confirm']);
		$errors = [];
		
		if($userModel->userExists($new['login'])){$errors[]='user.signupError.userExists';}
		if(!$new['login']){$errors[]='userSignup_invalid_login';}
		if(!$new['email']){$errors[]='userSignup_invalid_email';}
		if(!$new['password']){$errors[]='userSignup_invalid_password';}
		//if($new['password'] != $new['password_confirm']){$errors[]='user.signupError.password_mismatch';}
		
		
		if(sizeof($errors) > 0):
		
			$params = [ 
				'ABSDIR' => ABSDIR,
				'ABSPATH' => ABSPATH,
				'action'=>'userSignup',
				'status' => 'error',
				'statusText' => implode('<br />',$errors),
				'values'=>$new,
			];
			
			$r = $this->viewAdmin->render($r, "standard.html.twig", $params);
			return $r;
			
		else:
			
			$params = [
				'ABSDIR' => ABSDIR,
				'ABSPATH' => ABSPATH,
				'action'=>'userSignupValidate',
				'createUser' => $userModel->createUserBySignup($new), //User Creation
				'newUserData'=> $new,
			];
			
			
			$r = $this->viewAdmin->render($r, "standard.html.twig", $params);
			return $r;
			
		endif;
		
	endif; //endif allowuser signup
	
})->setName('userPostSignup');



/** SEND PASSWORD RESET EMAIL **/
$app->get('/user/password/reset/email/{userEmail}[/]', function (Request $q, Response $r) {
	
	$response = [
		'success' => false,
		'message' => 'Un email de récupération de mot de passe vous a été envoyé.'
	];

	if (!$userEmail = $q->getAttribute('userEmail')) {
		$response['message'] = 'Vous devez entrer une adresse email.';
		return $r->withJson($response);
	}
	
	$validate = new Validator($this->db);
	if (!$validate->asEmail($userEmail)) {
		$response['message'] = 'Vous devez entrer une adresse email valide.';
		return $r->withJson($response);
	}
		
    $userModel = new User($this->db);
	if (!$user = $userModel->findOneBy(['url' => $userEmail])) {
		$response['message'] = 'Aucun utilisateur n\'est lié à cette adresse email.';
		return $r->withJson($response);
	}

	$user['params'] = json_decode($user['params'], true);
	StoPasswordReset::generateToken($token, $tokenHash);
	$user['params']['passwordTokenHash'] = $tokenHash;
	$user['params']['passwordTokenCreation'] = time();
	
	$stmt = $this->db->prepare('UPDATE '.TBL.' SET params = :params WHERE id = :id');
	$queryParams = [':params' => json_encode($user['params']), ':id' => $user['id']];
	if (!$stmt->execute($queryParams)) {
		$response['message'] = 'Erreur interne. Réessayez, et contactez le webmaster si le problème persiste.';
		return $r->withJson($response);
	}

	if (!$name = $user['name']) {
		$name = $user['params']['fullName'] . ' ' . $user['params']['lastName'];
	}

	$logoCid = 1;
	$logo = [
		'path' => LRP_LOGO,
		'cid' => $logoCid
	];

	$mailer = new Mailer($this->view);
	$sent = DEV_MODE ?: $mailer->sendMail(
		[
			'email' => LRP_BOT_MAIL, 
			'name' => LRP_BOT_MAIL_NAME
		], 
		$user['url'],
		'Le Rucher Patriote - Récupération de mot de passe',
		'password-reset',
		[
			'ABSPATH' => ABSPATH,
			'logoSrc' => "cid:$logoCid",
			'token' => $token,
			'username' => $name
		], [], null, [$logo]
	);

	if (!$sent) {
		$response['message'] = 'Échec de l\'envoi de l\'email de récupération de mot de passe.';
		return $r->withJson($response);
	}

	$response['success'] = true;
	if (DEV_MODE) {
		$response['token'] = $token;
	}
	return $r->withJson($response);
	
})->setName('passwordResetMail');


/** GET PASSWORD FORM **/
$app->get('/user/password/reset/form/{token}[/]', function (Request $q, Response $r) {

	if (!$token = $q->getAttribute('token')) {
		return $r->withStatus(404)->write('Token non valide.');
	}

	if (!StoPasswordReset::isTokenValid($token)) {
		return $r->withStatus(404)->write('Token non valide.');
	}

	$tokenHash = StoPasswordReset::calculateTokenHash($token);

    $userModel = new User($this->db);
	if (!$user = $userModel->findOneBy(['params' => ['key' => 'passwordTokenHash', 'value' => $tokenHash]])) {
		return $r->withStatus(404)->write('Token non valide.');
	}

	$user['params'] = json_decode($user['params'], true);

	// Check whether the token has expired
	$creationDate = (new DateTime())->setTimestamp($user['params']['passwordTokenCreation']);
	if (StoPasswordReset::isTokenExpired($creationDate)) {
		return $r->withStatus(404)->write('Token expiré. Redemandez la réinitialisation de votre mot de passe.');
	}

    $params = [
		'ABSDIR' => ABSDIR,
		'ABSPATH' => ABSPATH,
		'action' => 'passwordReset',
		'session' => $_SESSION,
		'site'=> (new Blob($this->db))->getSiteProperties(),
		'token' => $token
	];
    return $this->view->render($r, "user.html.twig", $params);

})->setName('passwordResetFormGet');

/** POST PASSWORD FORM **/
$app->post('/user/password/reset/form/{token}[/]', function (Request $q, Response $r) {

	$errorMessage = 'Token non valide.';

	if (!$token = $q->getAttribute('token')) {
		return $r->withStatus(404)->write($errorMessage);
	}

	if (!StoPasswordReset::isTokenValid($token)) {
		return $r->withStatus(404)->write($errorMessage);
	}

	$tokenHash = StoPasswordReset::calculateTokenHash($token);

    $userModel = new User($this->db);
	if (!$user = $userModel->findOneBy(['params' => ['key' => 'passwordTokenHash', 'value' => $tokenHash]])) {
		return $r->withStatus(404)->write($errorMessage);
	}

	$user['params'] = json_decode($user['params'], true);

	// Check whether the token has expired
	$creationDate = (new DateTime())->setTimestamp($user['params']['passwordTokenCreation']);
	if (StoPasswordReset::isTokenExpired($creationDate)) {
		return $r->withStatus(404)->write('Token expiré. Redemandez la réinitialisation de votre mot de passe.');
	}

	$errorMessage = 'Erreur interne. Réessayez, et contactez le webmaster si le problème persiste.';

	$postData = $q->getParsedBody();
	if (!$password = $postData['password']) {
		return $r->withStatus(500)->write($errorMessage);
	}
	
	if (!$passwordConfirmation = $postData['passwordConfirmation']) {
		return $r->withStatus(500)->write($errorMessage);
	}

	$viewParams = [
		'ABSDIR' => ABSDIR,
		'ABSPATH' => ABSPATH,
		'action' => 'passwordReset',
		'session' => $_SESSION,
		'site'=> (new Blob($this->db))->getSiteProperties(),
		'token' => $token
	];

	if ($password !== $passwordConfirmation) {
		$viewParams['status'] = 'danger';
		$viewParams['statusText'] = 'Les mots de passe soumis ne sont pas identiques.';
		return $this->view->render($r, "user.html.twig", $viewParams);
	}

	$validate = new Validator($this->db);
	if (!$validate->asPassword($password)) {
		$viewParams['status'] = 'danger';
		$viewParams['statusText'] = 'Votre mot de passe doit contenir au moins un chiffre, une lettre minuscule, et une lettre majuscule.';
		return $this->view->render($r, "user.html.twig", $viewParams);
	}
	
	$user['params']['password'] = password_hash($password, PASSWORD_BCRYPT);
	$user['params']['passwordTokenHash'] = null;
	$user['params']['passwordTokenCreation'] = null;
	
	$stmt = $this->db->prepare('UPDATE '.TBL.' SET params = :params WHERE id = :id');
	$queryParams = [':params' => json_encode($user['params']), ':id' => $user['id']];
	if (!$stmt->execute($queryParams)) {
		return $r->withStatus(500)->write($errorMessage);
	}

	// At this point, we are sure the password reset is successful.
	$to = [$user['url']];
	if (isset($user['params']['alternateEmail']) && $validate->asEmail($user['params']['alternateEmail'])) {
		$to[] = $user['params']['alternateEmail'];
	}

	$logoCid = 1;
	$logo = [
		'path' => LRP_LOGO,
		'cid' => $logoCid
	];

	$username = $user['name'] ? $user['name'] : ($user['params']['lastName'] . ' ' . $user['params']['fullName']);

	$mailer = new Mailer($this->view);
	$mailer->sendMail(
		[
			'email' => LRP_BOT_MAIL, 
			'name' => LRP_BOT_MAIL_NAME
		],
		$to,
		'Le Rucher Patriote - Confirmation de changement de mot de passe',
		'password-reset-confirmation',
		[
			'ABSPATH' => ABSPATH,
			'contactFormUrl' => ABSPATH . $this->get('router')->pathFor('rucherContactGet'),
			'logoSrc' => "cid:$logoCid",
			'username' => $username
		], [], null, [$logo]
	);

	$viewParams['action'] = 'signin';
	$viewParams['status'] = 'success';
	$viewParams['statusText'] = 'Votre changement de mot de passe est désormais effectif.';
    return $this->view->render($r, "user.html.twig", $viewParams);

})->setName('passwordResetFormPost');
