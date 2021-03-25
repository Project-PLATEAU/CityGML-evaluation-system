<?php
function sel_query($value,$type){
	// postgreSQL開始
	$hostname = '*****';
	$dbname = '*****';
	$user = '*****';
	$password = '*****';
	
	$array = [];

	$postgresql_con = pg_connect("host=$hostname  dbname=$dbname user=$user password=$password");

	if (!$postgresql_con) {
	    echo '接続失敗です。'.pg_last_error();
	}

	$result = pg_query($value);
	if (!$result) {
	    echo 'クエリーが失敗しました。'.pg_last_error();
	}else{
	   for ($i = 0 ; $i < pg_num_rows($result) ; $i++){
           $rows = pg_fetch_array($result, NULL, PGSQL_NUM);
           
           switch($type){
            case 'listStatus' :
                //filelist用
                array_push($array, ["name" => $rows['0'], "status" => $rows['1']]);
                break;
            case 'ConvertJob' :
            case 'validate' :
                array_push($array, ["activeJobCount" => $rows['0'], "activeUserCount" => $rows['1']]);
                break;
            case 'dataDisplay' :
                //dataDisplay用
                array_push($array, ["userid" => $rows['0'], "publicurl" => $rows['1']]);
                break;
            case 'Delete' :
                //dataDisplay用
                array_push($array, ["delJobCount" => $rows['0']]);
                break;
            case 'Convert' :
                //dataDisplay用
                array_push($array, ["incorrectStatus" => $rows['0']]);
                break;
            case 'Upload' :
                array_push($array, ["activeJobCount" => $rows['0'], "activeUserCount" => $rows['1'], "uploadJobCount" => $rows['2']]);
                break;
            case 'fileCheck' :
                array_push($array, ["ConvertCount" => $rows['0']]);
                break;
            case 'runningFileCheck' :
                array_push($array, ["RunningFileCount" => $rows['0']]);
                break;
            case 'CityGMLReleace';
                array_push($array, ["myConvertJobCount" => $rows['0']]);
                break;
           }
           
       }
	}

	$close_flag = pg_close($postgresql_con);

	if (!$close_flag){
	    echo '切断に失敗しました。';
	}
	
	return $array;
}
?>