<?php
    //自ホストからの要求か確認
    //$host = $_SERVER['HTTP_REFERER'];
    //$url = parse_url($host);
    //外部ホストからの場合処理を行わない
    //if(stristr($url['host'], "localhost")){
        try{
            if(isset($_POST["cityCode"]) == true && isset($_POST["releaseStopFileNames"]) == true){
                $cityCode = $_POST["cityCode"];
                $releaseStopFileNames = $_POST["releaseStopFileNames"];
            } else {
                echo json_encode('POSTされるべき値がPOSTされませんでした', JSON_UNESCAPED_UNICODE);
                return;
            }

            $resultArray = array();
            
            include_once("logger.php"); //ログ出力クラスを取得
            include_once("config.php"); //ログ出力用コンフィグクラスを取得
            $log = Logger::getInstance();//ログ出力クラスのインスタンス生成
            
            $log->info('ファイル削除処理開始', $cityCode);
            
            
            //削除対象ファイル名配列を生成
            $releaseStopFileNames = json_decode($releaseStopFileNames);
            
            //ここからCityGMLファイルの削除 //2022修正
            $allCityGMLName = glob('*****:/*****/htdocs/iUR_Data/' . $cityCode . '/OriginalData/3DBuildings/{*.zip,*.gml}', GLOB_BRACE);
            if($allCityGMLName == false){
                //ファイル一覧の取得に失敗した場合
                $resultArray["result"] = "globFailed";
                echo json_encode($resultArray, JSON_UNESCAPED_UNICODE);
                $log->error('globに失敗したため配信停止できませんでした', $cityCode);
                return;
            } else {
                //それ以外の場合は削除処理を開始
                $errorFlag = false;
                //存在するファイル名とPOSTされたファイル名が合致した場合は削除を行う
                foreach($releaseStopFileNames as $toReleaseStopFileName){
                    $fileNameMatchFlg = false;
                    
                    foreach($allCityGMLName as $registedZipName){
                        if($toReleaseStopFileName === basename($registedZipName)){
                            if(unlink($registedZipName) === false){
                                //削除に失敗した場合
                                $errorFlag = true;
                                $log->error('配信停止に失敗しました ファイル名：' . basename($registedZipName), $cityCode);
                            } else {
                                //削除成功時
                                $log->info('配信停止に成功しました ファイル名：' . basename($registedZipName), $cityCode);
                            }
                            $fileNameMatchFlg = true;

                            break;
                        }
                    }
                    
                    if($fileNameMatchFlg == false){
                        $errorFlag = true;
                        $log->error('ファイル削除に失敗しました ファイル名：' . basename($toReleaseStopFileName), $cityCode);
                    }
                }
                
                if($errorFlag === true){
                    $resultArray["result"] = "error";
                    $log->info('何らかのエラーにより配信停止できないCityGMLファイルがありました。', $cityCode);
                    echo json_encode($resultArray, JSON_UNESCAPED_UNICODE);
                    return;
                } else {
                    //成功した場合
                    $resultArray["result"] = "success";
                    $log->info('すべての対象ファイルの配信停止処理が成功しました', $cityCode);
                    echo json_encode($resultArray, JSON_UNESCAPED_UNICODE);
                }
            }
        } catch(Exception $ex){
            echo json_encode($ex->getMessage(), JSON_UNESCAPED_UNICODE);
        }
?>