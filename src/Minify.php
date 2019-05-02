<?php
namespace cachefy;

class Minify{

    public static function img($image, $quality = 10) {
        $dir = __DIR__.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR;
        $filename = $dir.time().'.png';

        @mkdir($dir,0777,true);



        file_put_contents($filename, $image);

        
         $info = getimagesize($filename);

         ob_start();

    if ($info['mime'] == 'image/jpeg'){
        $image = imagecreatefromjpeg($filename);
        imagejpeg($image, $filename, 1);
    }

    elseif ($info['mime'] == 'image/gif') 
        $image = imagecreatefromgif($filename);

    elseif ($info['mime'] == 'image/png'){
        $image = imagecreatefrompng($filename);        
        imagejpeg($image, null, 1);
    }

        $i = ob_get_contents();
        ob_end_clean();
      
        unlink($filename);

     return ($i);
    }

	public static function html($buffer) {
       $search = array(
            '/\>[^\S ]+/s',     
            '/[^\S ]+\</s',     
            // '/(\s)+/s',     
            // '/\s\s/s',
            '/<!--(.*?)-->/' 
            
        );
        $replace = array(
            '>',
            '<',
            // ' ',
            // '\\1',
            ' '
        );

        $buffer = preg_replace($search, $replace, $buffer);

        return $buffer;
    }

    public static function css2($content){        
        $buffer = $content;
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
        $buffer = str_replace(': ', ':', $buffer);
        $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);

        return $buffer;
    }

    public static function  css($input) {


   
    if(trim($input) === "") return $input;
    return preg_replace(
        array(
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~+]|\s*+-(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
            '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
            '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
            '#(background-position):0(?=[;\}])#si',            
            '#(?<=[\s:,\-])0+\.(\d+)#s',
         
            '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
            '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',         
            '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',       
            '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',    
            '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
        ),
        array(
            '$1',
            '$1$2$3$4$5$6$7',
            '$1',
            ':0',
            '$1:0 0',
            '.$1',
            '$1$3',
            '$1$2$4$5',
            '$1$2$3',
            '$1:0',
            '$1$2'
        ),
    $input);
}


    public static function js2($content){
      
        if(trim($content) === "") return $content;
        return preg_replace(
            array(             
                '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',          
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',        
                '#;+\}#',             
                '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',              
                '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i'
            ),
            array(
             
                '$1',
                '$1$2',
                '}',
                '$1$3',
                '$1.$3'
            ),
        $content);
    }

    public static function js($content){

      return preg_replace(
            array(
               
                '#\t#m',
                '#  #m',                
                // '#\/\/[\wа-я\s\'\;]*#m',            // remove  //   .....                
                // '#(\/\*[^*]*\*\/)|(\/\/[^*]*)#',   // remove /*   .....   */
            ),
            array(          
               
                ' ',
                ' ',                
                // ' ',
                // ' ',
                
            ),
        $content);

    }
    
}