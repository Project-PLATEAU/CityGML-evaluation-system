<?php
    try{
        include_once("logger.php"); //ログ出力処理の読み込み
        include_once("config.php"); //ログ出力用のコンフィグ読み込み
        include_once("3DtilesLockFileExistCheck.php"); //ロックファイル確認関数読み込み
        //ログ書き込み処理
        $log = Logger::getInstance();
        if(isset($_POST["cityCode"]) === true){
            $cityCode = (string) $_POST["cityCode"];
            
            //3DTilesZipファイルの保存ディレクトリ
            $outputedZipFilePath = "*****:/*****/htdocs/iUR_Data/" .$cityCode. "/3DTiles/3DBuildings/";
            //エラーファイルのフルパス
            $errorFilePath = "*****:/*****/htdocs/iUR_Data/" .$cityCode. "/3DTiles/3DBuildings/error.txt";
            
            $log->info('3DTiles配信前確認処理を開始',$cityCode);

            $outputedZipFile = glob($outputedZipFilePath . "*.zip");
            if(file_exists($errorFilePath) === true){
                //エラーファイルが存在する場合
                $log->warn('配信エラーが発生しているため配信停止はできません',$cityCode);
                $returnArray = array(
                    'result' => 'errorFileIsExist',
                );
                echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
                return;
            } else if($outputedZipFile === false){
                //zipファイルの取得に失敗した場合
                $log->error('3DTilesZipファイルのglobに失敗しました',$cityCode);
                $returnArray = array(
                    'result' => 'error',
                );
                echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
                return;
            } else if(tilesReleaseIsLocked($cityCode) === true){
                $log->warn('配信処理中のため配信停止はできません',$cityCode);
                $returnArray = array(
                    'result' => 'Locked',
                );
                echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
                return;
            } else if(count($outputedZipFile) === 0){
                //配信済zipファイルが存在しない場合　
                $log->warn('3DTilesが配信されていないため配信停止できません',$cityCode);
                $returnArray = array(
                    'result' => 'notReleased',
                );
                echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
                return;
            } else {
                $log->info('3DTiles配信停止処理可能',$cityCode);
                $returnArray = array(
                    'result' => 'OK',
                );
                echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
            }
        } else {
            //POST情報不足
            $returnArray = array(
                'result' => 'error',
            );
            echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
        }

        
        
    } catch(Exception $ex){
        $returnArray = array(
            'result' => 'catch',
        );
        echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
        if(isset($cityCode) === true){
            $log->error('3DTiles配信前確認処理で例外が発生しました。'. $ex ,$cityCode);
        }
    }
    

?>