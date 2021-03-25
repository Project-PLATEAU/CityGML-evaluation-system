<?php
//$logFileName…ログファイル名（サンプル.txtのように拡張子付き）
//$cityCode…自治体ID
//$logType…ログの種類（validateなら検証、など）
function validateTopologicalConsistencyResultCheck($logFileName, $cityCode, $logType){
    include_once("logger.php"); //ログ出力処理の読み込み
    include_once("config.php"); //ログ出力用のコンフィグ読み込み
    $log = Logger::getInstance();//ログ出力クラスのインスタンス生成

    $log->info('【validateTopologicalConsistencyResultCheck開始】',$cityCode);

    //最大三回検証結果ファイルの読み込みを試みる
    for($i = 0; $i < 3; $i++){
        $validateResults = file('F:\\Apache24\\htdocs\\iUR_Data\\' . $cityCode . '/' . $logType . '/' . $logFileName  .'.txt', FILE_IGNORE_NEW_LINES);
        if($validateResults !== false){
            break;
        }
        sleep(5);
    }

    //ファイルが正常に読み込めていれば、内容を読み取って判定を行う
    if($validateResults !== false){
        $log->info('F:\\Apache24\\htdocs\\iUR_Data\\' . $cityCode . '/' . $logType . '/' . $logFileName .'_errors.zip',$cityCode);
        if(file_exists('F:\\Apache24\\htdocs\\iUR_Data\\' . $cityCode . '/' . $logType . '/' . $logFileName .'_errors.zip')){
            return false;
        }else{
            return true;
        }
        //何らかの理由により検証エラーを吐かなかった場合
        $log->error('位相一貫性検証結果ファイルにエラーも成功も記述されていませんでした',$cityCode);
        return false;
    }
    
    //検証結果ファイルが読み込めなかった場合の処理
    $log->error('位相一貫性検証結果ファイルを読み込めませんでした',$cityCode);
    return false;
}
?>