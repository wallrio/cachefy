<?php
namespace cachefy;

class File{
  
public static function isJson($string) {
   json_decode($string);
   return (json_last_error() == JSON_ERROR_NONE);
  }

public static function rmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (is_dir($dir."/".$object))
           self::rmdir($dir."/".$object);
         else
           unlink($dir."/".$object); 
       } 
     }
     rmdir($dir); 
   } 
 }

}