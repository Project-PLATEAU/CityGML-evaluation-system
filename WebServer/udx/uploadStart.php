<?php
    //ファイルの存在確認
    try{
        include_once("dbConnection.php"); //DB接続情報の読み込み
        include_once("logger.php"); //ログ出力クラスを取得
        include_once("config.php"); //ログ出力用コンフィグクラスを取得
        $log = Logger::getInstance();//ログ出力クラスのインスタンス生成
    
        $status = '1'; //upload開始
        
        if(isset($_POST['uploadFileNameList']) && isset($_POST['cityCode'])){
            $uploadFileNameList = json_decode($_POST['uploadFileNameList']);
            $cityCode = $_POST['cityCode'];
            $log->info('ステータス更新開始',$cityCode);
            
            foreach($uploadFileNameList as $uploadFileName){
                db (" WITH upsert AS (
                            UPDATE public.manage_regist_zip
                            SET status = '". $status ."' ,registdate = NOW()
                            WHERE userid = '". $_POST['cityCode'] .
                            "' AND zipname = '". $uploadFileName .
                            "' RETURNING * 
                           )
                        INSERT INTO public.manage_regist_zip (userid, zipname,  status, registdate )
                        SELECT '" . $_POST['cityCode'] . "','" . $uploadFileName ."', '". $status ."' , NOW() From public.manage_regist_zip
                        WHERE not exists (SELECT userid, zipname, '" . $status . "', NOW()
                        FROM public.manage_regist_zip WHERE userid = '". $_POST['cityCode'] . "' and zipname = '". $uploadFileName ."' ) LIMIT 1");//DBへの格納
                        
                        $log->info('['. $uploadFileName . ']の' .'ステータスを' . $status . 'に更新',$cityCode);
            }
            echo json_encode('データベースのステータスを更新しました', JSON_UNESCAPED_UNICODE);
        } else {
            $log->info('POSTパラメータが正しくありません。',$cityCode);
            echo json_encode('POSTパラメータが正しくありません', JSON_UNESCAPED_UNICODE);
        }
    } catch(Exception $ex) {
    $log->info('アップロード処理に失敗しました' . $ex->getMessage(),$cityCode);
        echo json_encode($ex->getMessage(), JSON_UNESCAPED_UNICODE);
    }

?>