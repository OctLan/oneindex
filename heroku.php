<?php
$newCache = false;

$mongdbUri = getenv("MONGODB_URI");
if( $mongdbUri && extension_loaded("mongo") ){
    define( 'MONGODB_URI' ,  $mongdbUri );
    $newCache = 'MDBCache';
}
$mysqlUri = getenv("CLEARDB_DATABASE_URL");
if($mysqlUri){
	define( 'DATABASE_URL' ,  $mysqlUri );
	$newCache = 'MysqlCache';
}
//check if use new cache the change function config cache and clean cache to mongodb  
if(  $newCache ){
	$GLOBALS['__newCache'] = $newCache;
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
        $GLOBALS['__newCache']::deleteAll('cache');
    }
    
    //rewrite config && cache function
    function config($key) {
		static $configs = array();
		list($key, $file) = explode('@', $key, 2);
		$file = empty($file) ? 'base' : $file;

		//读取配置
		$configs[$file] = $GLOBALS['__newCache']::get( $file , 'config' );

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
				    $GLOBALS['__newCache']::set( $file , [] , 'config' );
				} else {
					$configs[$file] = $value;
				}

			}
			$GLOBALS['__newCache']::set( $file , $configs[$file] , 'config' );
			
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
		    $cache = $GLOBALS['__newCache']::get( $file , 'cache' );
			return (array)$cache;
		} else {
		    $GLOBALS['__newCache']::set( $file , array(TIME, $value) , 'cache' );
			return array(TIME, $value);
		}
	}
}
