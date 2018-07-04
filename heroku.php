<?php
$mongdbUri = getenv("MONGODB_URI");
if( $mongdbUri ){
    define( 'MONGODB_URI' ,  $mongdbUri );
}
//check if use mongodb the change function config cache and clean cache to mongodb  
if( extension_loaded("mongo") && defined('MONGODB_URI') ){
	set_time_limit(0);
    $needCleanCache = false;
    //check is call old clean cache method if need clean then clean mongdb cache 
    if( php_sapi_name() == "cli"  && isset($argv) && $argv ){
    	$argvCopy = $argv;
        array_shift($argvCopy);
        $action = str_replace(':', '_',array_shift($argvCopy));
        if($action == 'cache_clear'){
            $needCleanCache = true;
        }
    }else if(route::get_uri() == '/admin/cache' && isset( $_POST['clear'] ) && !is_null($_POST['clear']) ){
        $needCleanCache = true;
    }
    //clean cache 
    if($needCleanCache){
        MDBCache::deleteAll('cache');
    }
    
    //rewrite config && cache function
    function config($key) {
		static $configs = array();
		list($key, $file) = explode('@', $key, 2);
		$file = empty($file) ? 'base' : $file;

		//读取配置
		$configs[$file] = MDBCache::get( $file , 'config' );

		if (func_num_args() === 2) {
			$value = func_get_arg(1);
			//写入配置
			if (!empty($key)) {
				$configs[$file] = (array) $configs[$file];
				if (is_null($value)) {
					unset($configs[$file][$key]);
				} else {
					$configs[$file][$key] = $value;
				}

			} else {
				if (is_null($value)) {
				    MDBCache::set( $file , [] , 'config' );
				} else {
					$configs[$file] = $value;
				}

			}
			MDBCache::set( $file , $configs[$file] , 'config' );
			
		} else {
			//返回结果
			if (!empty($key)) {
				return $configs[$file][$key];
			}

			return $configs[$file];
		}
	}
	function cache($key, $value = null) {
		$file =  md5($key);
		if (is_null($value)) {
		    $cache = MDBCache::get( $file , 'cache' );
			return (array)$cache;
		} else {
		    MDBCache::set( $file , array(TIME, $value) , 'cache' );
			return array(TIME, $value);
		}
	}
	cache('test' , 'abc' , 'cache');
}
