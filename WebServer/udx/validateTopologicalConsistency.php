<?php
    //ファイルの存在確認
    try{
        include_once("dbConnection.php"); //DB接続情報の読み込み
        include_once("dbSelect.php"); //DB接続情報の読み込み
        include_once("validateTopologicalConsistencyResultCheck.php"); //出力ログ情報の取得処理の読み込み
        include_once("logger.php"); //ログ出力クラスを取得
        include_once("config.php"); //ログ出力用コンフィグクラスを取得
        include_once("getEPSGCode.php");//EPSG取得
        $log = Logger::getInstance(); //ログ出力クラスのインスタンス生成

        //自治体IDを確認
        if(isset($_POST["cityCode"]) == true && isset($_POST["fileNameList"]) == true){
            $cityCode = (string) $_POST["cityCode"];
            $filelist = json_decode($_POST["fileNameList"]);

            $log->info('位相一貫性検証処理開始', $cityCode);

            //検証開始
            foreach($filelist as $fileName){
                $editedFileName = '"' . $fileName . '"';
//2022修正
                $logFilePath = '*****:/*****/htdocs/iUR_Data/' . $cityCode . '/ValidateTopologicalConsistencyLog/' . $fileName . '.txt';
                $errorZipFilePath = '*****:/*****/htdocs/iUR_Data/' . $cityCode . '/ValidateTopologicalConsistencyLog/' . $fileName . '_errors.zip';
//2022修正
                //ジョブストップ用定義ファイルの格納場所
                $stopFilePath = '*****:/*****/htdocs/udx/jobstop/';
                $stopAll = 'jobStopTopologicalConsistency_all.txt';
                $stopCityCode = 'jobStopTopologicalConsistency_' . $cityCode . '.txt';

                if (file_exists($stopFilePath . $stopAll) || file_exists($stopFilePath . $stopCityCode)) {
                    $log->error('処理中断ファイルが存在するため後続処理をスキップ',$cityCode);
                    
                    //DBへ検証失敗ステータス書き込み
//2022修正
                    db (" WITH getStatus AS(
                        select case when status = '9199' then '9299' 
                                when status = '2199' then '2299'
                                when status = '199' then '299' 
                                ELSE status 
                                end
                        FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."'
                        )
                        ,upsert AS (
                            UPDATE public.manage_regist_zip
                            SET status = (select * from getStatus) ,registdate = NOW()
                            WHERE userid = '". $cityCode .
                            "' AND zipname = '". $fileName .
                            "' RETURNING * 
                            )
                        INSERT INTO public.manage_regist_zip (userid, zipname, status,registdate )
                        SELECT '" . $cityCode . "','" . $fileName ."', (select * from getStatus) ,  NOW()
                        WHERE not exists (SELECT 1
                        FROM public.manage_regist_zip WHERE userid = '". $cityCode . "' and zipname = '". $fileName ."' ) LIMIT 1");//DBへの格納

                    file_put_contents($logFilePath, '位相検証処理を停止しているため、処理がスキップされました。');
                    
                    $zipArchive = new ZipArchive();
                    $zipArchive->open($errorZipFilePath, ZIPARCHIVE::CREATE);
                    $zipArchive->addFile($logFilePath, $fileName . '.txt');
                    $zipArchive->close();
                    
                    unlink($logFilePath);

                    $log->info('['. $fileName . ']の' .'ステータスを位相一貫性検証エラーに更新',$cityCode);
                    continue;
                }
                
                $log->info('位相一貫性検証処理バッチ実行 ファイル名：' . $fileName, $cityCode);
                
                $maxValidateCheck = 3210; //検証結果ファイル存在確認の最大回数　これを超えると失敗扱いとする
                $sleepSecond = 10; //検証ファイルが出力されたことを確認するインターバル(秒)
                $checkCount = 0; //検証結果ファイルの存在確認回数
                $log->info('ログファイルパス：' . $logFilePath, $cityCode);
                //過去の検証結果ファイルがあるなら削除する
                if(file_exists($logFilePath) === true){
                    if(unlink($logFilePath) === false){
                        //削除に失敗した際の処理
                    }
                }
                //過去のエラーログzipファイルがあるなら削除する
                if(file_exists($errorZipFilePath) === true){
                    if(unlink($errorZipFilePath) === false){
                        //削除に失敗した際の処理
                    }
                }

                
                $log->info('位相一貫性検証処理バッチ実行', $cityCode);
                //検証処理呼出しBATの実行
                $epsgCode = 'EPSG:'. getEPSGCode($cityCode);
                $log->info("cmd.exe /c exec_validate_topological_consistency.bat ${editedFileName} ${epsgCode} ${cityCode}", $cityCode);
                exec("cmd.exe /c exec_validate_topological_consistency.bat ${editedFileName} ${epsgCode} ${cityCode}");
                
                //検証結果ファイルが出力されるまで処理を待つ
                //2022 asistcom
                while(file_exists($logFilePath) === false && file_exists($errorZipFilePath) == false){
                    if($checkCount < $maxValidateCheck){
                        sleep($sleepSecond);
                        $checkCount = $checkCount + 1;
                    } else {
                        //最大確認回数を超えた場合の処理
                        echo json_encode('最大確認回数を超えました　　', JSON_UNESCAPED_UNICODE);
                        $log->warn('位相一貫性検証結果ファイルが既定の時間待機しても出力されなかったため処理を中断しました。ファイル名：' . $fileName, $cityCode);
                        break;
                    }
                }
                $log->info('位相一貫性検証処理', $cityCode);
                //検証結果ファイルの中を読み込み、検証結果を判定する
                if(validateTopologicalConsistencyResultCheck($fileName, $cityCode, 'ValidateTopologicalConsistencyLog') === true){
                    //検証の結果cityGMLとして妥当だった場合
                    $log->info('位相一貫性検証処理debug_status999', $cityCode);
                    //DBへ検証成功ステータス書き込み
//2022修正
                    db (" WITH getStatus AS(
                        select case when status = '9199' then '9999'
                                when status = '2199' then '2999' 
                                when status = '199' then '999' 
                                ELSE status 
                                end
                        FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."'
                        )
                        ,upsert AS (
                            UPDATE public.manage_regist_zip
                            SET status = (select * from getStatus) ,registdate = NOW()
                            WHERE userid = '". $cityCode .
                            "' AND zipname = '". $fileName .
                            "' RETURNING * 
                            )
                        INSERT INTO public.manage_regist_zip (userid, zipname,  status, registdate )
                        SELECT '" . $cityCode . "','" . $fileName ."', (select * from getStatus) ,  NOW() 
                        WHERE not exists (SELECT 1
                        FROM public.manage_regist_zip WHERE userid = '". $cityCode . "' and zipname = '". $fileName ."' ) LIMIT 1");//DBへの格納
                    echo json_encode('位相一貫性検証結果が妥当', JSON_UNESCAPED_UNICODE);
                    $log->info('位相一貫性検証処理の結果：妥当　ファイル名：' . $fileName, $cityCode);
                    $log->info('['. $fileName . ']の' .'ステータスを位相一貫性検証処理完了に更新',$cityCode);
                } else {
                    //検証の結果cityGMLとして妥当でない、または何らかのエラーにより正常に検証が行われていない場合
                    $log->info('位相一貫性検証処理debug_status299', $cityCode);
                    //DBへ検証失敗ステータス書き込み
//2022修正
                    db (" WITH getStatus AS(
                        select case when status = '9199' then '9299' 
                                when status = '2199' then '2299'
                                when status = '199' then '299' 
                                ELSE status 
                                end
                        FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."'
                        )
                        ,upsert AS (
                            UPDATE public.manage_regist_zip
                            SET status = (select * from getStatus) ,registdate = NOW()
                            WHERE userid = '". $cityCode .
                            "' AND zipname = '". $fileName .
                            "' RETURNING * 
                            )
                        INSERT INTO public.manage_regist_zip (userid, zipname, status,registdate )
                        SELECT '" . $cityCode . "','" . $fileName ."', (select * from getStatus) ,  NOW()
                        WHERE not exists (SELECT 1
                        FROM public.manage_regist_zip WHERE userid = '". $cityCode . "' and zipname = '". $fileName ."' ) LIMIT 1");//DBへの格納
                    
                    echo json_encode('位相一貫性検証結果が妥当でない', JSON_UNESCAPED_UNICODE);
                    $log->error('位相一貫性検証処理の結果：妥当でない ファイル名：' . $fileName, $cityCode);
                    $log->info('['. $fileName . ']の' .'ステータスを位相一貫性検証エラーに更新',$cityCode);
                }
            }
        }
    }catch(Exception $ex) {
        echo json_encode('例外が発生しました', JSON_UNESCAPED_UNICODE);
    }
?>
