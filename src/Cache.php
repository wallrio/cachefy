<?php

namespace cachefy;

use cachefy\Spider as Spider;
use cachefy\Http as Http;
use cachefy\File as File;
use cachefy\Filter as Filter;
use cachefy\Optimage as Optimage;

class Cache{
		
	private $content,$domain,$config,$sourceDir;

	function __construct(){}

	public function config(array $config){
		$this->config = $config;
	}

	public function startBuffer(){

		
		
		$REDIRECT_URL = isset($_SERVER['REDIRECT_URL'])?$_SERVER['REDIRECT_URL']:null;
		$REQUEST_URI = isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:null;
		$SCRIPT_NAME = isset($_SERVER['SCRIPT_NAME'])?$_SERVER['SCRIPT_NAME']:null;
		$REQUEST_SCHEME = isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:null;
		$SERVER_PROTOCOL = isset($_SERVER['SERVER_PROTOCOL'])?$_SERVER['SERVER_PROTOCOL']:null;
		$SERVER_NAME = isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:null;
		$SERVER_PORT = isset($_SERVER['SERVER_PORT'])?$_SERVER['SERVER_PORT']:null;
		
		if(strpos($SERVER_PROTOCOL , 'HTTP/') != -1){
			if($SERVER_PORT === '80')
				$protocol = 'http';
			else
				$protocol = 'https';
		}
		

		$baseDir = dirname($SCRIPT_NAME);

		$domain = $protocol.'://'.preg_replace('#//#m', '/', $SERVER_NAME.'/'.$baseDir.'/');	


		$page = str_replace($baseDir, '', $REQUEST_URI); 
		if($page == '' || $page == '/')$page='home';

		$this->page = $page;
		$this->domain = $domain;
		

		$cleanCache = isset($this->config['cleanCache'])?$this->config['cleanCache']:false;
		if($cleanCache === true){
			$this->cleanCache();
		}


		ob_start();
	}

	public function cleanCache(){
		$cacheDir = $this->config['cacheDir'];
		$page = $this->page;
		$sourceDir = $this->config['sourceDir'];
		$this->sourceDir = $sourceDir;

		
		File::rmdir($cacheDir);

		
	}

	public function endBuffer(){
		$content = ob_get_contents();
		ob_end_clean();


		$this->content = $content;
	}

	public function savePage($content = '',$extension = null){

		$cacheDir = $this->config['cacheDir'];
		$page = $this->page;

	
		$filenamePre = $cacheDir.DIRECTORY_SEPARATOR.$page;
		
		// if($extension == '' || $extension == 'html')
			// $filename = $cacheDir.DIRECTORY_SEPARATOR.$page.'.html';
		// else
			$filename = $cacheDir.DIRECTORY_SEPARATOR.$page;

		$dir = dirname($filename);
		if(!file_exists($dir)) @mkdir($dir,0777,true);


		if($extension == '' || $extension == 'html' || $extension == 'php')
		$content = '<!-- CacheFy : '.date("Y/m/d H:i:s", time()).' -->'."\n".$content;

		@file_put_contents($filename,$content);
		return $content;
	}

	public function getPage($extension = null){
		$cacheDir = $this->config['cacheDir'];
		$page = $this->page;
		$sourceDir = isset($this->config['sourceDir'])?$this->config['sourceDir']:getcwd();		
		$this->sourceDir = $sourceDir;

		// if($extension != '' && $extension != 'html')
			$filename = $cacheDir.DIRECTORY_SEPARATOR.$page;
		// else
			// $filename = $cacheDir.DIRECTORY_SEPARATOR.$page.'.html';

		/*$filename = str_replace('//', '/', $filename);
		$filename_lastModified = filemtime($filename);
		
		
		$stat = stat($sourceDir);*/
		// echo 'Modification time: ' . $stat['mtime']; // will show unix time stamp.
		// echo 'Size: ' . $stat['size']; // in bytes.

		/*if( $stat['mtime'] > $filename_lastModified){
			return false;
		}*/
		
		if(file_exists($filename)){			
			return file_get_contents($filename);
		}else
			return false;
	}


	public function getExtension($filename){

		if(strpos($filename, '.')=== false){
			return '';
		}
	

		 $file_ext = explode('.',$filename);		 
		 $file_ext = array_filter($file_ext);

		 return end($file_ext);
		
	}

	public function flush(){
		
		$REQUEST_URI = isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:null;
		$REDIRECT_URL = isset($_SERVER['REDIRECT_URL'])?$_SERVER['REDIRECT_URL']:null;

		
		$extension = $this->getExtension($REQUEST_URI);
		


		$spider = new Spider();
		
		$resultPage = $this->getPage($extension);

		
		if($resultPage){
			$content = $resultPage;		
		}else{

			$content = $this->content;
			$content = $spider->run($content,$this->domain,$this->config);


			if($this->config['minifyHtml'] === true && ( $extension == 'html' || $extension == '' || $extension == 'php') )
                $content = Minify::html($content);

            if($this->config['minifyCss'] === true &&  $extension == 'css')
                $content = Minify::css($content);

            if($this->config['minifyJs'] === true &&  $extension == 'js')
                $content = Minify::js($content);

            if($this->config['optimizeimage'] === true ){
            	$dirCacheImageOpt = dirname($this->config['cacheDir']).DIRECTORY_SEPARATOR."optimage".DIRECTORY_SEPARATOR;
            	// mkdir($dirCacheImageOpt,0777,true);
            	$pageOpt = $this->domain;
            	// echo $pageOpt;
            	$mobileoptimizeString = 'desktop';

            	

            	Optimage::deleteExtractDir($dirCacheImageOpt.DIRECTORY_SEPARATOR."extract");
    
			    if(file_exists($dirCacheImageOpt."opt.zip")){
			        unlink($dirCacheImageOpt."opt.zip");
			    }
			    
                Optimage::download(
			        'https://www.googleapis.com/pagespeedonline/v3beta1/optimizeContents?url='.$pageOpt.'&strategy='.$mobileoptimizeString,
			        $dirCacheImageOpt.DIRECTORY_SEPARATOR."opt.zip",
			        $this->domain
			    );

			    Optimage::extract(
			        $dirCacheImageOpt.DIRECTORY_SEPARATOR."opt.zip",
			        $dirCacheImageOpt.DIRECTORY_SEPARATOR."extract".DIRECTORY_SEPARATOR
			    );

			     Optimage::createFileWithDirectory(
			        $dirCacheImageOpt.DIRECTORY_SEPARATOR."extract",
			        $this->domain,
			        $this->domain
			    );
            }



         

            $regexReplace = isset($this->config['regexReplace'])?$this->config['regexReplace']:null;
            if($regexReplace != null){
	            $sourceReplace = isset($regexReplace[0])?$regexReplace[0]:'';
	            $targetReplace = isset($regexReplace[1])?$regexReplace[1]:'';
	            $content = preg_replace($sourceReplace, $targetReplace, $content);
	        }


	        $filters = isset($this->config['filters'])?$this->config['filters']:null;

	        $content = Filter::init($filters,$content);

            

			$content = $this->savePage($content,$extension);
		}
		
		die($content);
		// echo $content;

	}
}