<?php
class API{
	
	function __construct($db) {
		$this->db = $db;
	}
	
	public function getAllRoutes(){
		global $app;
		$routes = $app->getContainer()->router->getRoutes();
		$output = [];
		foreach ($routes as $route) {
			$output[$route->getName()] = $route->getName().' : '.$route->getPattern(). ' '. implode(', ',$route->getMethods());
		}
		sort($output); //sort and preserve array keys
		return $output;
	}
	
}
