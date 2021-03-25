<?php
    try{
        include_once("dataPrivate.php"); //非公開化処理の読み込み
        include_once("dbConnection.php"); //DB接続情報の読み込み
        include_once("logger.php"); //ログ出力処理の読み込み
        include_once("config.php"); //ログ出力用のコンフィグ読み込み
        
        //ログ書き込み処理
        $log = Logger::getInstance();
        
        if(isset($_POST["cityCode"]) == true && isset($_POST["privateFileNames"]) == true){
            $cityCode = $_POST["cityCode"];
            $fileNameArray = json_decode($_POST["privateFileNames"]);
            
            $ret = dataPrivate ($cityCode);
            
            if($ret == 0){
                $log->info('ステータス更新処理開始',$cityCode);
                
                foreach($fileNameArray as $fileName){
                    $status = '31'; //変換完了（非公開）までステータスを戻す
                    db (" WITH upsert AS (
                        UPDATE public.manage_regist_zip
                        SET status = '". $status ."' ,registdate = NOW()
                        WHERE userid = '". $cityCode .
                        "' AND zipname = '". $fileName .
                        "' RETURNING * )
                       INSERT INTO public.manage_regist_zip (userid, zipname,  status, registdate )
                       SELECT '" . $cityCode . "','" . $fileName ."',  '". $status ."' ,  NOW() From public.manage_regist_zip
                       WHERE not exists (SELECT userid, zipname, '" . $status . "' ,NOW()
                       FROM public.manage_regist_zip WHERE userid = '". $cityCode . "' and zipname = '". $fileName ."' ) LIMIT 1");//DBへの格納
                       
                       $log->info('ステータス：' .$status. 'でデータベースの更新に成功しました。',$cityCode);
                }
            }else{
                $log->error('データ非公開化に失敗しました。 ',$cityCode);
                return 1;
            }
            return 0;
        }
    } catch(Exception $ex){
        $log->error('データ非公開化に失敗しました。',$cityCode);
        return 1;
    }
?>
