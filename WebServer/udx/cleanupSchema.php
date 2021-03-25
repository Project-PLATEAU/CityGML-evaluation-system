<?php
function cleanupSchema($cityCode){
	// postgreSQL開始
	$hostname = '*****';
	$dbname = '*****';
	$user = '*****';
	$password = '*****';
	
	$sql = 'SELECT citydb_' .$cityCode. '.cleanup_schema()';
	
	$postgresql_con = pg_connect("host=$hostname  dbname=$dbname user=$user password=$password");

    //DBへの接続が成功したか確認
	if (!$postgresql_con) {
	    return false;
	}

    $result = pg_query($sql);
    //クエリの実行が正常に終了したか確認
	if (!$result) {
	    return false;
	}

	$close_flag = pg_close($postgresql_con);
    //DBとの接続を切断できたか確認
	if (!$close_flag){
	    return false;
	}
	return true;
}
?>