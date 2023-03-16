<?php
/** APP CONFIG **/
ini_set('display_errors',0);

$config = [
	
	//Slim PHP Options
	'displayErrorDetails' => true,
	'addContentLengthHeader' => false,
	
	//Database connection
	'db'=>[
		'host'=>"localhost", 
		//'host'=>"172.23.0.2:3306", //with Docker the IP is variable, should be something like "172.XX.0.Y:3306", check addresses in phpinfo.
		//'host2'=>"172.23.0.3:3306",
		//'host3'=>"172.23.0.4:3306",
		'user'=>"root",
		'pass'=>"root",
		'dbname'=>"opcms",
		'tbl'=>"opcmsdev"
	],

	//PHPMailer connection (for contact forms)
	'phpmailer'=>[
		'host' => "",
		'user' => "",
		'pass' => "",
		'smtpDebug' => 0
	],

	//ReCAPTCHA keys (for contact forms)
	'recaptcha'=>[
		'v3_site_key'=> "6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI", //public key (this one is a sandbox key, change with your own)
		'v3_secret_key'=> "6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe", //secret key (this one is a sandbox key, change with your own)
	],

	//Site properties
	'site'=>[
		'params'=>[
			'default_language'=>'en',
			'homelink'=>'home',
			'languages'=>[
				'en'=>'English'
			]
		]
	],

	//Admin login
	'user'=>[
		'login'=>'admin',
		'password'=>'admin'
	],

	//Site paths
	'abspath'=>'http://localhost:8080',
	'absdir'=>'/var/www/html',
	'template_dir'=>'themes/pico',
	'template_admin_dir'=>'themes/pico'
];


/*
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `opcmsdev` (
  `id` int(11) NOT NULL DEFAULT '0',
  `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paragraph',
  `url` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NULL,
  `content` text COLLATE utf8mb4_unicode_ci NULL,
  `parent` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `edited` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lang` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `translation_of` int(11) NULL,
  `params` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `opcmsdev`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `opcmsdev`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
*/