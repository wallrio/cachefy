<?php
namespace cachefy;

class Filter{
	
	public static function init($filters,$content = ''){

		if(is_array($filters))
		foreach ($filters as $key => $value) {
			if($value == 'GoogleAnalyticsRemoveOnPageSpeed')
				$content = Filter::GoogleAnalyticsRemoveOnPageSpeed($content);
			if($value == 'GtmRemoveOnPageSpeed')
				$content = Filter::GtmRemoveOnPageSpeed($content);
		}

		return $content;
	}

	public static  function GoogleAnalyticsRemoveOnPageSpeed($content = ''){
        // set GoogleAnalytics Remove On PageSpeed
            $sourceReplaceAnalit= '#\(function\(i,s,o,g,r,a,m\)\{i\[\'GoogleAnalyticsObject#im';
            // $targetReplaceAnalit = 'if(navigator.userAgent.indexOf("Speed Insights") == -1) { (function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject';            
            $targetReplaceAnalit = 'window.addEventListener(\'mousemove\', function()  { if(typeof csswp_ganalytics === "boolean")return false; csswp_ganalytics = true; (function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject';            
            $content = preg_replace($sourceReplaceAnalit, $targetReplaceAnalit, $content);
            $sourceReplaceAnalit= '#ga\(\'send\'\, \'pageview\'\)\;#im';
            // $targetReplaceAnalit = 'ga(\'send\', \'pageview\');}';         
            $targetReplaceAnalit = 'ga(\'send\', \'pageview\');});';         
            $content = preg_replace($sourceReplaceAnalit, $targetReplaceAnalit, $content);

        return $content;
    }

    public static  function GtmRemoveOnPageSpeed($content = ''){        
        $sourceReplaceAnalit= '#\(function\(w,d,s,l,i\)\{#im';
        // $targetReplaceAnalit = 'if(navigator.userAgent.indexOf("Speed Insights") == -1) { (function(w,d,s,l,i){';            
        $targetReplaceAnalit = 'window.addEventListener("mousemove",function(){ if(typeof window.csswp_gtm === "boolean")return false; window.csswp_gtm = true;  (function(w,d,s,l,i){';            
        $content = preg_replace($sourceReplaceAnalit, $targetReplaceAnalit, $content,-1,$count);


        $sourceReplaceAnalit= '#dataLayer\',\'(.*)\'\);\<#im';
        // $targetReplaceAnalit = 'dataLayer\',\'$1\');}<';         
        $targetReplaceAnalit = 'dataLayer\',\'$1\');});<';         
        $content = preg_replace($sourceReplaceAnalit, $targetReplaceAnalit, $content);
        return $content;
    }

}