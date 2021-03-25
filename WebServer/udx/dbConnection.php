<?php
function db($value){
	// postgreSQL開始
	$hostname = '*****';
	$dbname = '*****';
	$user = '*****';
	$password = '*****';
	
	$postgresql_con = pg_connect("host=$hostname  dbname=$dbname user=$user password=$password");

	if (!$postgresql_con) {
		//DBへの接続に失敗した場合
		return "DBConnectionError";
	}

	$result = pg_query($value);
	if (!$result) {
		//クエリの実行に失敗した場合
		return "queryError";
	}

	$close_flag = pg_close($postgresql_con);

	if (!$close_flag){
		//DB切断に失敗した場合
		return "connectionCloseError";
	}
	return "success";
}
?>