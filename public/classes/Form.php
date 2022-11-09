<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Form{
	
	function __construct($db) {
		$this->db = $db;
  	}
  	
  	
  	function sendForm($post){
		
		$blobModel = new Blob($this->db);
		$post['form'] = $blobModel->getBlob($post['formData']['id']);
	    $page = $post['form']['parent'];
		$params = $post['form']['params'];
		
		
		
		if(!$post['form']['params']['mailto'] || !$post['formData']['email']){
			return ["status"=>"warning", "statusText"=>"Please fill in the required fields","callbackPageID"=>$page];
		}
		
		
		$mail = new PHPMailer(true);
		global $config;
		
		
		
				
		try {
			
			//Server settings
			$mail->SMTPDebug = $config['phpmailer']['smtpDebug'];                   				// Enable verbose debug output
			$mail->isSMTP();                                            // Send using SMTP
			$mail->Host       = $config['phpmailer']['host'];           // Set the SMTP server to send through
			$mail->SMTPAuth   = true;                                   // Enable SMTP authentication
			$mail->Username   = $config['phpmailer']['user'];           // SMTP username
			$mail->Password   = $config['phpmailer']['pass'];           // SMTP password
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
			$mail->Port       = 587;                                    // TCP port to connect to
			$mail->CharSet = 'UTF-8';
			$mail->isHTML(true);

			//Recipients 
			
			$mail->setFrom($config['phpmailer']['user'], $post['formData']['name']);
			$mail->addAddress($post['form']['params']['mailto']); // Add a recipient
			$mail->addReplyTo($post['formData']['email']); // Reply to sender

			$mail->Subject = 'New message from your website '.$_SERVER['HTTP_HOST'];
			$mail->Body = '<ul>';
			
			foreach($post['formData'] as $key => $field){
				$mail->Body .= '<li>'.$key.': '.$field.'</li>';
			} 
			$mail->Body .= '</ul>';
			
			
			if($mail->send()){
				return ["status"=>"success", "statusText"=>"Your message was successfully sent !","callbackPageID"=>$page];
			}else {
				return ["status"=>"warning", "statusText"=>"Error while sending the message. Error details: \"{$mail->ErrorInfo}\"","callbackPageID"=>$page];
			}
			
			
		} catch (Exception $e) {
			return ["status"=>"error", "statusText"=>"Unable to initialize the mailer module, the website's configuration may have an error. Details: \"{$mail->ErrorInfo}\"","callbackPageID"=>$page];
		}
		
		
	}
	
        
}
