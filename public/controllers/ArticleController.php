<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/* ARTICLE CONTROLLER */
$articlesQueryText = 'SELECT id, url, parent FROM '.TBL.' WHERE type = "article" AND status > -1 AND url IS NOT NULL AND url != "" ';
$articlesQuery = $container['db']->query($articlesQueryText);


$rewriters = ($articlesQuery->fetchAll());
$blobModel = new Blob($container->db);
$siteParamsQuery = $blobModel->getSiteProperties();
$siteParams = json_decode($siteParamsQuery['params'],true);


foreach($rewriters as $rewriter){
		
	$app->get('/article/'.$rewriter['url'].'[/]', function(Request $q, Response $r, $args){
		
		//The rewrite logic
        $path = $q->getUri()->getPath();
		$rewritePath = rtrim($path, '/'); //removes last trailing slash
        //check if is subpage
		$blobModel = new Blob($this->db);
        $rewritePathArr = array_values(array_filter(explode('/',$rewritePath))); 
        
        //Creates rewritePath according to languages (subpage to control for 3rd levels)
        
     
        $rewritePath = $rewritePathArr[sizeof($rewritePathArr)-1];
        //does the rewrite logic
		$rewriteQuery = $this->db->query('SELECT id FROM '.TBL.' WHERE type = "article" AND status > -1 AND url = "'.$rewritePath.'" LIMIT 1;');
		$rewriteRow = $rewriteQuery->fetch();
		$id = $rewriteRow['id'];
		
		
		//Display Blob (the following code should be the same as in /blob/{id}/)
		$adminModel = new Admin($this->db);
		$validate = new Validator($this->db);
		$pageModel = new Page($this->db);
		$blob = $blobModel->getBlob($id,$lang);
		$blockModel = new Block($this->db);
		
		if(!!$blob && $blob['type'] == 'article'):
			
			
			//Redirect to 404 if inactive
			if(!isset($_SESSION['id']) && $blob['status'] < 1):
				$r->getBody()->write("<h3>404 - Not Found</h3>");
				return $r->withStatus(404);
			endif;
			
			$site = $blobModel->getSiteProperties(); 
	
			$params = [ 
				"ABSPATH" => ABSPATH,
				"ABSDIR" => ABSDIR,
				"action" => "readModule",
				"author" => $blobModel->getBlob($blob['author']),
				"blob" => $blob,
				"blobModel" => $blobModel,
				"blocks" => $blockModel->getBlocks(),
				"isHome" => $pageModel->isHome($blob['id']),
				"session" => $_SESSION,
				"site" => $site,
				"sitemap" => $adminModel->getSitemap(),
				"translations" => $pageModel->getTranslations($blob['translation_of']),
				"type" => $blob['type'],
				"validate" => $validate
			];
			$r = $this->view->render($r, "standard.html.twig", $params);
			
		else:
			$r->getBody()->write("<h3>404 - Not Found</h3>");
			return $r->withStatus(404);
		endif;  

	})->setName('article-'.$rewriter['id']);
}
