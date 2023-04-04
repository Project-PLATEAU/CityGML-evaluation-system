<?php
    //自ホストからの要求か確認
    //$host = $_SERVER['HTTP_REFERER'];
    //$url = parse_url($host);
    //外部ホストからの場合処理を行わない
    //if(stristr($url['host'], "localhost")){
function dataPrivate($cityCode){
  try{ 
        include_once("dbConnection.php"); //DB接続情報の読み込み
        include_once("logger.php"); //ログ出力処理の読み込み
        include_once("config.php"); //ログ出力用のコンフィグ読み込み
        
        //ログ書き込み処理
        $log = Logger::getInstance();
        $log->info('データ非公開化開始',$cityCode);
        
        //ファイル元パス
        $delRelease = '*****:\\*****\\htdocs\\iUR_Data\\' .$cityCode. '\\OriginalData\\3DBuildings';
        $delTiles = '*****:\\*****\\htdocs\\map\\' .$cityCode. '\\public\\datasource-data';
        $delPath = '*****:\\*****\\htdocs\\iUR_Data\\' .$cityCode. '\\3DTiles\\3DBuildings';
        $configPath = '*****:\\*****\\htdocs\\map\\' .$cityCode. '\\private\\ConfigJsonTemplate\\';
        $outConfigPath = '*****:\\*****\\htdocs\\map\\' .$cityCode. '\\public\\';

        //コンフィグファイルのコピー
        if (copy($configPath, $outConfigPath)) {
            //コピー成功時の処理
            $log->info('コンフィグファイルの初期化成功',$cityCode);
        } else {
            //エラー処理
            $log->error('コンフィグファイルの初期化失敗',$cityCode);
            return 1;
        }
        
        
        //公開用地図に使用している3DTilesを削除する
        $errorFlag = false;
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($delTiles, RecursiveDirectoryIterator::CURRENT_AS_SELF),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        //各ファイルやフォルダに処理を行う
        foreach($items as $item){
            //パスに'Terrain'が含まれていない場合のみ処理を行う
            if(strpos($item->getPathname(), 'public/datasource-data\\Terrain\\') === false && $item->getPathname() !== '*****:\*****/htdocs/map/' . $cityCode . '/public/datasource-data\\Terrain' ){
                //ファイルかディレクトリかを判定して削除する
                if($item->isFile() || $item->isLink()){
                    if(unlink($item->getPathname()) === false){
                        $errorFlag = true;
                    }
                } elseif ($item->isDir() && !$item->isDot()){
                    if(rmdir($item->getPathname()) === false){
                        $errorFlag = true;
                    }
                }
            }
        }
        
        if($errorFlag === false){
            $log->info('公開用地図の3DTilesの削除成功',$cityCode);
        }else{
            //エラー処理
            $log->error('公開用地図の3DTilesの削除失敗',$cityCode);
            return 1;
        }
        

        
        //公開用のzip化された3DTilesを削除する
        $cmd = 'rd /s /q ' . $delRelease;
        $cmdret =  exec($cmd, $opt, $return_ver);
        
        if($return_ver == 0){
            //削除された3DBuildingsを作成する
            mkdir($delRelease, 0777);
            $log->info('公開用のzip化された3DTilesの削除成功',$cityCode);
        }else{
            //エラー処理
            $log->error('公開用のzip化された3DTilesの削除失敗',$cityCode);
            return 1;
        }
        
        //公開用のcityGMLファイルを削除する
        $cmd = 'rd /s /q ' . $delPath;
        $cmdret =  exec($cmd, $opt, $return_ver);
        
        if($return_ver == 0){
            //削除された3DBuildingsを作成する
            mkdir($delPath, 0777);
            $log->info('公開用のcityGMLファイル削除成功',$cityCode);
        }else{
            //エラー処理
            $log->error('公開用のcityGMLファイル削除失敗',$cityCode);
            return 1;
        }
        
        $log->info('データ非公開化終了',$cityCode);

        return 0;
  }catch (Exception $e) {
      return 1;
  }
}
    //}
?>
