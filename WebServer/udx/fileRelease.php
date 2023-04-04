<?php
    //自ホストからの要求か確認
    //$host = $_SERVER['HTTP_REFERER'];
    //$url = parse_url($host);
    //外部ホストからの場合処理を行わない
    //if(stristr($url['host'], "localhost")){
    try{
        include_once("logger.php"); //ログ出力処理の読み込み
        include_once("config.php"); //ログ出力用のコンフィグ読み込み

        if(isset($_POST["cityCode"]) == true && isset($_POST["releaseFileNames"]) == true){
            //自治体IDを確認
            $cityCode = (string) $_POST["cityCode"];

            //ログ書き込み処理
            $log = Logger::getInstance();
            $log->info('データ配信処理開始',$cityCode);
            
            $releaseFileNames = json_decode($_POST["releaseFileNames"]);

            $errorFlag = false;
            
            $log->info('CityGMLファイルのコピー開始',$cityCode);
            
            //CityGMLファイルのコピー
            foreach($releaseFileNames as $releaseFileName){
                //ファイル元パス //2022修正
                $cityGMLPath = '*****:/*****/Data/' .$cityCode. '/OriginalData/3DBuildings/' . $releaseFileName;
                //ファイルコピー先 //2022修正
                $forCopyPath = '*****:/*****/htdocs/iUR_Data/' .$cityCode. '/OriginalData/3DBuildings/' . $releaseFileName;

            $log->info($cityGMLPath,$cityCode);
            $log->info($forCopyPath,$cityCode);

                if (copy($cityGMLPath, $forCopyPath) === true) {
                    $log->info('CityGMLファイルのコピー成功 ファイル名[' . $releaseFileName . ']',$cityCode);
                } else {
                    //エラー処理
                    $log->error('CityGMLファイルのコピー失敗 ファイル名[' . $releaseFileName . ']',$cityCode);
                    $errorFlag = true;
                }
            }
       
            if($errorFlag === false){
                $returnArray = array(
                    'result' => 'success',
                );
            } else {
                $returnArray = array(
                    'result' => 'error',
                );
            }
            echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
        }
    } catch(Exception $ex){
        $log->error('データ公開処理でエラーが発生しました。' .$ex->getMessage() ,$cityCode);
        
        $returnArray = array(
            'result' => 'catch',
        );
        echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
    }
    //}
?>
