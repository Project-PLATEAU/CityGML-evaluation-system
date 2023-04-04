<?php
//$logFileName…ログファイル名（サンプル.txtのように拡張子付き）
//$cityCode…自治体ID
//$logType…ログの種類（validateなら検証、など）
function validateResultCheck($logFileName, $cityCode, $logType){
    include_once("logger.php"); //ログ出力処理の読み込み
    include_once("config.php"); //ログ出力用のコンフィグ読み込み
    $log = Logger::getInstance();//ログ出力クラスのインスタンス生成

    $log->info('【validateResultCheck開始】',$cityCode);

    //最大三回検証結果ファイルの読み込みを試みる
    for($i = 0; $i < 3; $i++){
        $validateResults = file('*****:/*****/htdocs/iUR_Data/' . $cityCode . '/' . $logType . '/' . $logFileName, FILE_IGNORE_NEW_LINES);
        if($validateResults !== false){
	        foreach($validateResults as $result){
	            if(preg_match('/^\[[0-2][0-9]:[0-5][0-9]:[0-5][0-9] ERROR\]/', $result) === 1 || preg_match('/^\[[0-2][0-9]:[0-5][0-9]:[0-5][0-9] WARN\]/', $result) === 1){
	                //検証した結果XMLが妥当でない場合の処理
	                return false;
	            //2022 EMFの出力ログに対応
				//} else if (preg_match('/^\[[0-2][0-9]:[0-5][0-9]:[0-5][0-9] INFO\] Data validation finished./', $result) === 1){
				} else if (preg_match('/^\[[0-2][0-9]:[0-5][0-9]:[0-5][0-9] INFO\] Data validation successfully finished./', $result) === 1){
	                //検証した結果XMLが妥当である場合の処理
	                return true;
	            }
	        }
	        //何らかの理由により検証エラーを吐かなかった場合
	        $log->error('検証結果ファイルにエラーも成功も記述されていませんでした。再読込します。',$cityCode);
        }
        sleep(5);
    }
    //検証結果ファイルが読み込めなかった場合の処理
    $log->error('検証結果ファイルを読み込めませんでした',$cityCode);
    return false;
}
?>