<?php

namespace cachefy;

use cachefy\zip\ZipCss as ZipCss;
// require_once "zip/src/Zip.php";

class Optimage{

	public static function deleteExtractDir($dir) {
        if (is_dir($dir)) {
         $objects = scandir($dir);
         foreach ($objects as $object) {
           if ($object != "." && $object != "..") {
             if (is_dir($dir."/".$object))
               self::deleteExtractDir($dir."/".$object);
             else
               unlink($dir."/".$object);
           }
         }
        rmdir($dir);
        }
    }
    
	public static function extract($filePath, $extractPath){
		if(!file_exists($extractPath)) mkdir($extractPath,0777,true);
		$zip = new ZipCss();
		$zip->unzip_file($filePath);
		$zip->unzip_to($extractPath);
	}

	public static function download($file_url, $save_to,$domain){
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 0); 
		curl_setopt($ch,CURLOPT_URL,$file_url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_REFERER, $domain);
		$file_content = curl_exec($ch);
		curl_close($ch);
 
		$downloaded_file = fopen($save_to, 'w');
		fwrite($downloaded_file, $file_content);
		fclose($downloaded_file);
 
	}

	public static function scanR($dir){
		$listFiles = scandir($dir);
		foreach ($listFiles as $key => $value) {
			if($value == '.' || $value == '..')
				unset($listFiles[$key]);
		}

		$newArray = array();
		foreach ($listFiles as $key => $value) {
			if(is_file($dir.DIRECTORY_SEPARATOR.$value)){
				$newArray[] = str_replace('//', '/', $dir.DIRECTORY_SEPARATOR.$value);
			}else{
				$pre = self::scanR($dir.DIRECTORY_SEPARATOR.$value);
				foreach ($pre as $key2 => $value2) {
					$newArray[] = $value2;
				}
			}

		}

		return $newArray;
	}

	public static function restoreImages($extractPath,$createDir,$wpContentUrl){
		
		$extractPath = str_replace('//', '/', $extractPath);

		$cacheDir = dirname($extractPath);
		$listFiles = self::scanR($extractPath);

		foreach ($listFiles as $key => $value) {
			$dir = $value;
			$dir = str_replace($extractPath, '', $dir);
			// echo $value.'---'.$createDir.'/'.$dir.'<br>';
			$dirTo = str_replace('//', '/', $createDir.'/'.$dir );
			$dirBase = dirname($dirTo);
			if(!file_exists($dirBase))@mkdir($dirBase,0777,true);
			copy($value, $dirTo);
		}
		
	/*	echo '<pre>';
		print_r($listFiles);
		echo '</pre>';*/
	}

	public static function createFileWithDirectory($extractPath,$createDir,$wpContentUrl){

		$cacheDir = dirname($extractPath);
		$filePath = $extractPath.DIRECTORY_SEPARATOR.'MANIFEST';


		$content = @file_get_contents($filePath);

		$contentArray = explode("\n", $content);

		foreach ($contentArray as $key => $value) {
			if(substr($value, 0,5) !== 'image')
				unset($contentArray[$key]);
		}

		$contentArray = array_values($contentArray);

		foreach ($contentArray as $key => $value) {
			$item = explode(': ', $value);
			$link = $item[1];
						
			$link = urldecode($link);
			
			$namePre = $item[0];			
			$namePre = substr($namePre, 6);

			$name = basename($link);
		
			$folder = str_replace($wpContentUrl, '', $link);
			$folder = dirname($folder);
			
			if(!file_exists($createDir.$folder)) @mkdir($createDir.$folder,0777,true);


			// cria o diretório de backup com o diretório do arquivo
			if(!file_exists($cacheDir.DIRECTORY_SEPARATOR.'backup'.DIRECTORY_SEPARATOR.$folder))
				@mkdir($cacheDir.DIRECTORY_SEPARATOR.'backup'.DIRECTORY_SEPARATOR.$folder,0777,true);

			// caso o arquivo não exista no diretorio de backup, então ele é copiado
			if(!file_exists($cacheDir.DIRECTORY_SEPARATOR.'backup'.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$name))
				copy(
					$createDir.$folder.DIRECTORY_SEPARATOR.$name,
					 $cacheDir.DIRECTORY_SEPARATOR.'backup'.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$name
				);

			// copia a imagem otimizada para o diretorio do wordpress
			copy( str_replace('//', '/', $extractPath.DIRECTORY_SEPARATOR.'image'.DIRECTORY_SEPARATOR.$namePre),
				str_replace('//', '/',$createDir.$folder.DIRECTORY_SEPARATOR.$name)
			);


		}

	}

}