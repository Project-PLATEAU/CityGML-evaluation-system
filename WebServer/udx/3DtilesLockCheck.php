<?php
    try{
        include_once("logger.php"); //ログ出力処理の読み込み
        include_once("config.php"); //ログ出力用のコンフィグ読み込み
        include_once("3DtilesLockFileExistCheck.php"); //ロックファイル確認関数読み込み
        include_once("dbConnection.php"); //DB接続情報の読み込み
        include_once("dbSelect.php"); //DB接続情報の読み込み    
        //ログ書き込み処理
        $log = Logger::getInstance();

        if(isset($_POST["cityCode"]) === true){
            $cityCode = (string) $_POST["cityCode"];
            //ロックファイルのフルパス
            $lockFilePath = "*****:/*****/htdocs/iUR_Data/" .$cityCode. "/3DTiles/3DBuildings/lock.txt";
            //エラーファイルのフルパス
            $errorFilePath = "*****:/*****/htdocs/iUR_Data/" .$cityCode. "/3DTiles/3DBuildings/error.txt";

            //過去のエラーファイル削除
            if(file_exists($errorFilePath) === true){
                if(unlink($errorFilePath) === false){
                    //エラーファイル削除に失敗した場合
                    $log->error('エラーファイルの削除に失敗しました',$cityCode);
                    $returnArray = array(
                        'result' => 'error',
                    );
                    echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
                    return;
                }
            }
            
            //自治体IDごとの変換処理数を取得
            $selRet = sel_query ("SELECT count(userid)as myConvertJobCount from (SELECT DISTINCT userid FROM public.manage_regist_zip where status in ('1099','1299','1999') and userid = '" . $cityCode . "') as a",'CityGMLReleace');
            
            //自治体ID単位での変換処理数取得(値が0以外はすでに実行中)
            $myConvertJobCount = $selRet['0']['myConvertJobCount'];
            
            //自身の変換処理が実行中でないかを確認する
            if($myConvertJobCount != 0){
                $returnArray = array(
                    'result' => 'myConvertJobIsActive',
                );
                echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
                $log->warn('前回の変換処理が完了していないため3DTiles配信処理を中断しました', $cityCode);
                return;
            }

            //変換後の3DTilesが保存されているディレクトリパス
            $datasourceDataPath = "*****:/*****/htdocs/map/" . $cityCode . "/private/datasource-data/";

            //"Terrain"フォルダしかない場合は配信しない
            if(count(glob($datasourceDataPath . "*", GLOB_ONLYDIR)) <= 1){
                $returnArray = array(
                    'result' => 'dataSourceDataIsEmpty',
                );
                echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
                $log->warn('変換後の3DTilesファイルがないため3DTiles配信処理を中断しました', $cityCode);
                return;
            };
            
            $log->info('ロックファイル存在確認処理開始',$cityCode);
            
            //ロックファイルの存在を確認する
            if(tilesReleaseIsLocked($cityCode) === true){
                //ロックファイルが存在した場合
                $log->info('ロックファイルが存在しました',$cityCode);
                $returnArray = array(
                    'result' => 'Locked',
                );
                echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
                return;
            } else {
                //ロックファイルが存在しない場合
                $log->info('ロックファイルが存在しないため配信可能',$cityCode);
                $returnArray = array(
                    'result' => 'notLocked',
                );
                echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
                return;
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
        $log->error('ロックファイルの作成処理に失敗しました。'. $ex ,$cityCode);
        
    }
    
?>