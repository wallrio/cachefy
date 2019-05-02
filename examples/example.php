<?php


require "vendor/autoload.php";

use cachefy\Cache as Cache;
$cache = new Cache();
$cache->config(array(		
	'cleanCache'=>false,
	// 'forceDomain'=>'http://domain.com/',
	// 'sourceDir'=>__DIR__.DIRECTORY_SEPARATOR.'application',
	'cacheDir'=>getcwd().DIRECTORY_SEPARATOR.'cache',
	// 'minifyCss'=>false,
	// 'minifyJs'=>true,
	// 'minifyHtml'=>true,
	// 'cacheimage'=>false,
	// 'mobile_css_noload_external'=>true
));
$cache->startBuffer(); // inicia o buffer

echo BUFFER_YOUR_SITE;

$cache->endBuffer(); // finaliza o buffer
$cache->flush(); // imprime o conteúdo efetivamente

