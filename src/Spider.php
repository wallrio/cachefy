<?php
namespace cachefy;



use cachefy\Http as Http;
use cachefy\Minify as Minify;

class Spider{

	private $url,$minifyCss,$minifyJs,$mobile_css_noload_external;
	private $cssImg = '';
	private static $forceDomain = null,$config;
	

	public static function getExtension($filename){
		 $file_ext = explode('.',$filename);
		 $file_ext = array_filter($file_ext);
		 return end($file_ext);
		
	}

	public static function savePage($content = '',$url){

		$cacheDir = self::$config['cacheDir'];
		// $page = $this->page;
		$REQUEST_URI = isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:null;
		$SCRIPT_NAME = isset($_SERVER['SCRIPT_NAME'])?$_SERVER['SCRIPT_NAME']:null;
		$SERVER_NAME = isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:null;
		$SERVER_PROTOCOL = isset($_SERVER['SERVER_PROTOCOL'])?$_SERVER['SERVER_PROTOCOL']:null;
		$SERVER_PORT = isset($_SERVER['SERVER_PORT'])?$_SERVER['SERVER_PORT']:null;
		
		if(strpos($SERVER_PROTOCOL , 'HTTP/') != -1){
			if($SERVER_PORT === '80')
				$protocol = 'http';
			else
				$protocol = 'https';
		}

		$baseDir = dirname(dirname($SCRIPT_NAME));
		$domain = $protocol.'://'.preg_replace('#//#m', '/', $SERVER_NAME.'/'.$baseDir.'/');


		$page = str_replace($baseDir, '', $url); 
		$page = str_replace($domain, '', $url); 

		$filename = $cacheDir.DIRECTORY_SEPARATOR.$page;
			
		

		$dir = dirname($filename).DIRECTORY_SEPARATOR;

		if(!file_exists($dir)) 
			mkdir($dir,0777,true);


		// if($extension == '' || $extension == 'html' || $extension == 'php')
		// $content = '<!-- CacheFy : '.date("Y/m/d H:i:s", time()).' -->'."\n".$content;
	

		file_put_contents($filename,$content);
		return $content;
	}


	public function run($content,$domain,$config){

		set_time_limit(0);

		self::$config = $config;

		Spider::$forceDomain = isset($config['forceDomain'])?$config['forceDomain']:null;
		$this->minifyCss = isset($config['minifyCss'])?$config['minifyCss']:false;
		$this->minifyJs = isset($config['minifyJs'])?$config['minifyJs']:false;
		$this->minifyHtml = isset($config['minifyHtml'])?$config['minifyHtml']:false;
		$this->mobile_css_noload_external = isset($config['mobile_css_noload_external'])?$config['mobile_css_noload_external']:false;

		if( Spider::$forceDomain != null )
		$domain = Spider::$forceDomain;

		$callbackRep = function($match) use ($domain){

			$value = $match[0];

		
			$attrStringsPre = preg_replace('/href=["|\'](.*?)["|\']/is','',$value);
			$attrStrings = str_replace(array('<link','/>','>'),'',$attrStringsPre);
			// $valueNew2 = preg_match_all('/<(.*?)>/is',$valueNew,$newMatch2);
			$valueNew = preg_match_all('/href=["|\'](.*?)["|\']/is',$value,$newMatch);
			$url = $newMatch[1][0];

			if(strpos($url, 'https:')===false && strpos($url, 'http:')===false ){
				$url = $domain.$url;
			}

		
			$contentFile = Http::curl(array(
				'url'=>$url,
				'method'=>'get'
			));

			// get url without file
				$urlAdjust = $url;
				$urlAdjust = explode('?', $urlAdjust);
				$urlAdjust = $urlAdjust[0];
				$urlAdjust = dirname($urlAdjust);
		
			// replace files on URL()
				$dirTheme = $urlAdjust;
				$contentFile  = preg_replace_callback('/url\((.*?)\)/is',function($matches) use ($dirTheme,$domain){
					$val = $matches[1];
					if(strpos($val, 'http://') !== false || strpos($val, 'https://') !== false || strpos($val, 'data:') !== false){
						
						if($this->mobile_css_noload_external === true)
							$val = '';

						return 'url('.$val.')';
					}
					if(substr($val, 0,1)=='"'){
						$signal = '"';
					}else if(substr($val, 0,1)=='\''){
						$signal = '\'';
					}else{
						$signal = '';
					}


					if(Spider::$forceDomain != null){

						if(substr(Spider::$forceDomain, strlen(Spider::$forceDomain)-1,strlen(Spider::$forceDomain))=='/')
							Spider::$forceDomain= substr(Spider::$forceDomain, 0,strlen(Spider::$forceDomain)-1);

						$dirThemeNew = str_replace($domain, '', $dirTheme);
						$dirThemeNew = Spider::$forceDomain.'/'.$dirThemeNew;
					}else{
						$dirThemeNew = $dirTheme;
					}
					
					

					$val = str_replace(array('"','\''), '', $val);
					$val = $dirThemeNew.'/'.$val;
					return 'url('.$signal.$val.$signal.')';	
				},$contentFile);

				// replace @import-----------------------------

				$contentFile  = preg_replace_callback('/@import [\'|"](.*?)[\'|"];/is',function($matches) use ($dirTheme,$domain){
					
					$val = $matches[1];
					if(strpos($val, 'http://') !== false || strpos($val, 'https://') !== false || strpos($val, 'data:') !== false){
						
						if($this->mobile_css_noload_external === true)
							$val = '';

						return '@import "'.$val.'";';
					}

				
					if(Spider::$forceDomain != null){

						if(substr(Spider::$forceDomain, strlen(Spider::$forceDomain)-1,strlen(Spider::$forceDomain))=='/')
							Spider::$forceDomain= substr(Spider::$forceDomain, 0,strlen(Spider::$forceDomain)-1);

						$dirThemeNew = str_replace($domain, '', $dirTheme);
						$dirThemeNew = Spider::$forceDomain.'/'.$dirThemeNew;
					}else{
						$dirThemeNew = $dirTheme;
					}
					$val = str_replace(array('"','\''), '', $val);
					$val = $dirThemeNew.'/'.$val;

					return '@import "'.$val.'";';
				},$contentFile);



				$ifMinify = '';
			if($this->minifyCss === true){
				$ifMinify = 'data-minify="true"';
				$contentFile = Minify::css($contentFile);
			}
			

			// Spider::savePage($contentFile,$url);

			if(strpos($attrStrings, 'data-nowpcss')!== false)return $value;
			
			$html_style = '<style '.$attrStrings .' '.$ifMinify.' data-css-type="css-link" data-url="'.$url.'" >';
			$html_style .= $contentFile;
			$html_style .= '</style>';
	
			

			return $html_style; 
		};

		$content = preg_replace_callback('/<link[^>]*?rel=["|\']stylesheet["|\']*?[^>]*>/is', $callbackRep, $content);



		$callbackRep = function($match) use ($domain){

			$value = $match[0];

			$valueNew = preg_match_all('/src=["|\'](.*?)["|\']/is',$value,$newMatch);
			$attrStringsPre = preg_replace('/src=["|\'](.*?)["|\']/is','',$value);
			$valueNew = preg_match_all('/<script\b(.*?)*>/is',$attrStringsPre,$newMatch2);
			$attrStrings = str_replace(array('<script','/>','>'),'',$newMatch2[0][0]);

			

			if(count($newMatch[0]) > 0 ){
				$url = $newMatch[1][0];

				if(strpos($url, 'https:')===false && strpos($url, 'http:')===false ){
					$url = $domain.$url;
				}

				$contentFile = Http::curl(array(
					'url'=>$url,
					'method'=>'get'
				));

				$type = 'js-link';
				$dataUrl = 'data-url="'.$url.'"';
			}else{
				$dataUrl = '';
				$type = 'js-inline';
				$contentFile = $match[1];
				
			}

			$contentFile = str_replace('<iframe','&lt;iframe',$contentFile);
			$contentFile = str_replace('</script','&lt;/',$contentFile);

			
			$ifMinify = '';
			if($this->minifyJs === true){
				$ifMinify = 'data-minify="true"';
				$contentFile = Minify::js($contentFile);
			}

			if(strpos($attrStrings, 'data-nowpcss')!== false)return $value;

			$valueResult = '<script '.$attrStrings .' '.$ifMinify.' data-css-type="'.$type.'" '.$dataUrl.' >';
			$valueResult .= $contentFile;
			$valueResult .= '</script>';

			return $valueResult; 
		};

		$content = preg_replace_callback('#<script[^<]+?>(.*?)</script>#is', $callbackRep, $content);



		return $content;
	}

	


}