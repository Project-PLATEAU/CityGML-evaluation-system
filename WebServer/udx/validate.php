<?php
    //ファイルの存在確認
    try{
        include_once("dbConnection.php"); //DB接続情報の読み込み
        include_once("dbSelect.php"); //DB接続情報の読み込み
        include_once("validateResultCheck.php"); //出力ログ情報の取得処理の読み込み
        include_once("logger.php"); //ログ出力クラスを取得
        include_once("config.php"); //ログ出力用コンフィグクラスを取得
        $log = Logger::getInstance();//ログ出力クラスのインスタンス生成
        $status = '19'; //検証開始ステータス
        
        //自治体IDを確認
            if(isset($_POST["cityCode"]) == true && isset($_POST["fileNameList"]) == true){
                $cityCode = (string) $_POST["cityCode"];
                $filelist = json_decode($_POST["fileNameList"]);
                
                $log->info('検証処理開始', $cityCode);
                
                //検証開始
                foreach($filelist as $fileName){
                    $editedFileName = '"' . $fileName . '"';
                    
                    //書式検証ログ出力先
                    $logFilePath = 'F:\\Apache24\\htdocs\\iUR_Data\\' . $cityCode . '/ValidateLog/' . $fileName . '.txt';

                    //ジョブストップ用定義ファイルの格納場所
                    $stopFilePath = '*****:/*****/htdocs/udx/jobstop/';
                    $stopAll = 'jobStopValidate_all.txt';
                    $stopCityCode = 'jobStopValidate_' . $cityCode . '.txt';

                    if (file_exists($stopFilePath . $stopAll) || file_exists($stopFilePath . $stopCityCode)) {
                        $log->error('処理中断ファイルが存在するため後続処理をスキップ',$cityCode);
                        
                        $status = '29';//検証失敗ステータス
                        
                        //DBへ検証失敗ステータス書き込み
                        db (" WITH upsert AS (
                                UPDATE public.manage_regist_zip
                                SET status = '". $status ."' ,registdate = NOW()
                                WHERE userid = '". $cityCode .
                                "' AND zipname = '". $fileName .
                                "' RETURNING * 
                                )
                            INSERT INTO public.manage_regist_zip (userid, zipname, status,registdate )
                            SELECT '" . $cityCode . "','" . $fileName ."', '". $status ."' ,  NOW() From public.manage_regist_zip
                            WHERE not exists (SELECT userid, zipname, '" . $status . "' ,NOW()
                            FROM public.manage_regist_zip WHERE userid = '". $cityCode . "' and zipname = '". $fileName ."' ) LIMIT 1");//DBへの格納
                        
                        file_put_contents($logFilePath, '書式検証処理を停止しているため、処理がスキップされました。');
                        
                        $log->info('['. $fileName . ']の' .'ステータスを' . $status . 'に更新',$cityCode);
                        continue;
                    }
                    
                    $log->info('検証処理バッチ実行 ファイル名：' . $fileName, $cityCode);
                    
                    $maxValidateCheck = 1070; //検証結果ファイル存在確認の最大回数　これを超えると失敗扱いとする
                    $sleepSecond = 10; //検証ファイルが出力されたことを確認するインターバル(秒)
                    $checkCount = 0; //検証結果ファイルの存在確認回数
                    $log->info('ログファイルパス：' . $logFilePath, $cityCode);
                    //過去の検証結果ファイルがあるなら削除する
                    if(file_exists($logFilePath) === true){
                        if(unlink($logFilePath) === false){
                            //削除に失敗した際の処理
                        }
                    }
                    
                    //検証処理呼出しBATの実行
                    exec("cmd.exe /c exec_validate.bat ${editedFileName} ${cityCode}");
                    
                    
                    //検証結果ファイルが出力されるまで処理を待つ
                    while(file_exists($logFilePath) === false){
                        if($checkCount < $maxValidateCheck){
                            sleep($sleepSecond);
                            $checkCount = $checkCount + 1;
                        } else {
                            //最大確認回数を超えた場合の処理
                            echo json_encode('最大確認回数を超えました　　', JSON_UNESCAPED_UNICODE);
                            $log->warn('検証結果ファイルが既定の時間待機しても出力されなかったため処理を中断しました。ファイル名：' . $fileName, $cityCode);
                            break;
                        }
                    }
                    
                    //検証結果ファイルの中を読み込み、検証結果を判定する
                    if(validateResultCheck($fileName . '.txt',$cityCode,'ValidateLog') === true){
                        //検証の結果cityGMLとして妥当だった場合
                        $status = '99';//検証完了ステータス
                        
                        //DBへ検証成功ステータス書き込み
                        db (" WITH upsert AS (
                                UPDATE public.manage_regist_zip
                                SET status = '". $status ."' ,registdate = NOW()
                                WHERE userid = '". $cityCode .
                                "' AND zipname = '". $fileName .
                                "' RETURNING * 
                               )
                            INSERT INTO public.manage_regist_zip (userid, zipname,  status, registdate )
                            SELECT '" . $cityCode . "','" . $fileName ."', '". $status ."' ,  NOW() From public.manage_regist_zip
                            WHERE not exists (SELECT userid, zipname, '" . $status . "', NOW()
                            FROM public.manage_regist_zip WHERE userid = '". $cityCode . "' and zipname = '". $fileName ."' ) LIMIT 1");//DBへの格納
                        echo json_encode('検証結果が妥当', JSON_UNESCAPED_UNICODE);
                        $log->info('検証処理の結果：妥当　ファイル名：' . $fileName, $cityCode);
                        $log->info('['. $fileName . ']の' .'ステータスを' . $status . 'に更新',$cityCode);
                    } else {
                        //検証の結果cityGMLとして妥当でない、または何らかのエラーにより正常に検証が行われていない場合
                        $status = '29';//検証失敗ステータス
                        
                        //DBへ検証失敗ステータス書き込み
                        db (" WITH upsert AS (
                                UPDATE public.manage_regist_zip
                                SET status = '". $status ."' ,registdate = NOW()
                                WHERE userid = '". $cityCode .
                                "' AND zipname = '". $fileName .
                                "' RETURNING * 
                               )
                            INSERT INTO public.manage_regist_zip (userid, zipname, status,registdate )
                            SELECT '" . $cityCode . "','" . $fileName ."', '". $status ."' ,  NOW() From public.manage_regist_zip
                            WHERE not exists (SELECT userid, zipname, '" . $status . "' ,NOW()
                            FROM public.manage_regist_zip WHERE userid = '". $cityCode . "' and zipname = '". $fileName ."' ) LIMIT 1");//DBへの格納
                        
                        echo json_encode('検証結果が妥当でない', JSON_UNESCAPED_UNICODE);
                        $log->error('検証処理の結果：妥当でない ファイル名：' . $fileName, $cityCode);
                        $log->info('['. $fileName . ']の' .'ステータスを' . $status . 'に更新',$cityCode);
                    }
                }
            }
        }catch(Exception $ex) {
            echo json_encode('例外が発生しました', JSON_UNESCAPED_UNICODE);
        }
?>
