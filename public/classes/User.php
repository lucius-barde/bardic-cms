<?php 

//Model User - Login and stuff

class User{
	function __construct($db) {
		$this->db = $db;
  	}
  	
  	function login($login,$password){
		global $config;
		//Verify password
		if($login == $config['user']['login'] && $password == $config['user']['password']){
			return ['status'=>'ok','login'=>$login];
		}
		return ['error'=>'user.warning.incorrectPasswordForUsername'];
  	}
  	
  	function createUserBySignup($new){
		//$newLogin = $new['login'];
		$newPassword = password_hash($new['password'],PASSWORD_BCRYPT);
		$params = [
			'password'=>$newPassword,
			'level'=>0,
			'status'=>0
		];
		$params = json_encode($params);

		$userVerify = $this->db->prepare('SELECT * FROM '.TBL.' WHERE type = "user" AND url = :email LIMIT 1;');
		$userVerify->execute([':email'=>$new['email']]);
		$userAlreadyExists = $userVerify->fetch();
		if(!!$userAlreadyExists){
			return false;
		}else{
			
			$sql = $this->db->prepare('INSERT INTO '.TBL.' (id,type,url,name,content,parent,status,author,edited,params) VALUES (NULL, "user", :url, :name, "", 0, 0, 0, NOW(),:params)');
			$sql->execute([':name'=>$new['login'],':url'=>$new['email'], ':params'=>$params]);
			
			if(!!$sql):
				return true;
			else:
				return false;
			endif;
		}

		
	}
  	
  	function userExists($login){
  		$sql = $this->db->query('SELECT * FROM '.TBL.' WHERE type = "user" AND content LIKE "%'.$login.'%"');
  		$row = $sql->fetch();
  		if(sizeof($row) > 0){
			return $row;
		}
		return false;
	}
  	
}
