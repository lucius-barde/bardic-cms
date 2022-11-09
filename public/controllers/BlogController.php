<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;



/* BLOG CONTROLLER */
$blogQueryText = 'SELECT id, url, parent FROM '.TBL.' WHERE type = "blog" AND status > -1 LIMIT 0,1 ';
$blogQuery = $container['db']->query($blogQueryText);


$rewriter = ($blogQuery->fetch());
$blobModel = new Blob($container->db);
$siteParamsQuery = $blobModel->getSiteProperties();
$siteParams = json_decode($siteParamsQuery['params'],true);
//TODO
if($rewriter){
	$app->get('/'.$rewriter['url'].'[/]', function(Request $q, Response $r, $args){
        $path = str_replace('/','',$q->getUri()->getPath());
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/'.$path.'/page/1/');
		
	})->setName('blog');
	
	$app->get('/'.$rewriter['url'].'/page/{page}[/]', function(Request $q, Response $r, $args){
		
		
		$blobModel = new Blob($this->db);
		
		//dashboard paging
		$paged = 10;
		$page = (int)$args['page'] > 0 ? (int)$args['page'] : 1;
		
		//get total of blobs
		$blobCount = $blobModel->getAllBlobs(['type'=>'article','status'=>[0,1],'onlyCount'=>true]);
		
		//get total of pages
		$pageCount = ceil($blobCount / $paged);
		
		//get articles
		$blobs = $blobModel->getAllBlobs(['type'=>'article','status'=>[0,1],'orderby'=>'edited','order'=>'DESC','paged'=>$paged,'page'=>$page]);
		foreach($blobs as $key => $blob){
			$blobs[$key]['params'] = json_decode($blobs[$key]['params'],true);
			$blobs[$key]['meta']['authorData'] = $blobModel->getBlob($blobs[$key]['author']);
		}
		//get blog params
		$blogQuery = $blobModel->getAllBlobs(['type'=>'blog']);
		
		$adminModel = new Admin($this->db);

		
		//validate
		$validate = new Validator($this->db);
		$params = [
			'ABSPATH'=>ABSPATH,
			'ABSDIR'=>ABSDIR,
			'action'=>'readModule',
			'blob'=>$blogQuery[0],
			'blogArticles'=>$blobs,
			'blobCount'=>$blobCount,
			'paged'=>$paged,
			'page'=>$page,
			'pageCount'=>$pageCount,
			'session'=>$_SESSION,
			'site'=>$blobModel->getSiteProperties(),
			'sitemap'=>$adminModel->getSitemap(),
			'validate'=>$validate
		];
		
		$r = $this->view->render($r, "standard.html.twig", $params);
		return $r;
			
	})->setName('blogPage');
}
