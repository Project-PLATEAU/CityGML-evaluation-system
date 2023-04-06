<?php
    //ファイルの存在確認
    try{
        include_once("dbConnection.php"); //DB接続情報の読み込み
        include_once("dbSelect.php"); //DB接続情報の読み込み
        include_once("logger.php"); //ログ出力クラスを取得
        include_once("config.php"); //ログ出力用コンフィグクラスを取得
        $log = Logger::getInstance();//ログ出力クラスのインスタンス生成
        $status = '19'; //検証開始ステータス

        //自治体IDを確認
            if(isset($_POST["cityCode"]) == true && isset($_POST["fileNameList"]) == true){
                $cityCode = (string) $_POST["cityCode"];
                $filelist = json_decode($_POST["fileNameList"]);

                //JOB数チェック
                $log->info('実行中のJOB数を確認',$cityCode);
                
                //自治体IDごとの処理数と全体でのJOB数を取得
                $selRet = sel_query ("with jobCount as(
                SELECT count(userid)as activeJobCount from (SELECT DISTINCT userid FROM public.manage_regist_zip where status = '19' and userid = '" . $cityCode . "')as a)
                , fileCount as(
                SELECT count(userid)as activeUserCount from (SELECT DISTINCT userid FROM public.manage_regist_zip where status = '19')as b)
                select * from jobCount cross join fileCount",'validate');
                
                //自身の自治体が行っているアップロード処理数(値が0以外はすでに実行中)
                $myValidateJobCount = $selRet[0]['activeJobCount'];
                //全体でのアップロード実行数
                $activeValidateJobCount = $selRet[0]['activeUserCount'];
                    
                //自身のジョブが既に実行されている場合、処理を終了して画面に情報を返す
                if($myValidateJobCount != 0){
                    $returnArray = array(
                        'result' => 'myJobIsActive',
                        'myValidateJobCount' => $myValidateJobCount
                    );
                     $log->warn('すでにJOBが実行されているため実行できません。',$cityCode);
                     echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
                    return;
                   
                }
                
                //全体のアップロードの実行数が5以上の場合、処理を終了して画面に情報を返す
                if($activeValidateJobCount >= 5){
                    $returnArray = array(
                        'result' => 'activeJobCountOver',
                        'activeValidateJobCount' => $activeValidateJobCount
                    );
                    $log->warn('実行中のJOB数が規定値に達しているためJOBの実行はできません。',$cityCode);
                    echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
                    return;
                }
                
                 //クエリ条件用にファイル名のin句生成
                $in_query = '';
                 
                foreach($filelist as $validateCheck){
                    $checkname = trim(basename($validateCheck));
                    
                    if($in_query == ''){
                        $in_query = '\''.$checkname.'\',';
                    }else {
                        $in_query = $in_query.'\'' .$checkname.'\',' ;
                    }                    
                }
                $in_query = rtrim($in_query,',');             //末尾のカンマを削除
                $in_query ='zipname in (' . $in_query . ')';  //～ zipname in (filename1,filename2)の形式にする
                
                //画面ごとのステータス単位でチェックするのを分けてメッセージを変えるべきかも。
                $selRet = sel_query ("select count(status) from public.manage_regist_zip where  userid = '" .$cityCode . "' and status in ('1','2','19','199','9199','999','9999','9299','299','1099','1299','1999','9099','2999','2099','2199','2299','10000') and " . $in_query ,'fileCheck');
                 
                $checkCount = $selRet['0']['ConvertCount'];
                if($checkCount > 0){
                    $resultArray["result"] = "DoNotValidate";
                    $log->warn('検証を開始出来ないステータスのファイルが選択されているため検証処理を行いません。', $cityCode);
                    echo json_encode($resultArray, JSON_UNESCAPED_UNICODE);
                    return;
                }

                $log->info('検証開始ステータス書き込み開始', $cityCode);
                $status = '19'; //検証開始ステータス
                //DBに検証開始ステータス書き込み
                foreach($filelist as $putFileName){
                    db (" WITH upsert AS (
                            UPDATE public.manage_regist_zip
                            SET status = '". $status ."' ,registdate = NOW()
                            WHERE userid = '". $cityCode .
                            "' AND zipname = '". $putFileName .
                            "' RETURNING * 
                           )
                        INSERT INTO public.manage_regist_zip (userid, zipname,  status, registdate )
                        SELECT '" . $cityCode . "','" . $putFileName ."',  '". $status ."' , NOW()
                        WHERE not exists (SELECT 1
                        FROM public.manage_regist_zip WHERE userid = '". $cityCode . "' and zipname = '". $putFileName ."' ) LIMIT 1");//DBへの格納
                        
                        $log->info('['. $putFileName . ']の' .'ステータスを' . $status . 'に更新',$cityCode);
                }

                $returnArray = array(
                    'result' => 'OK',
                );
                $log->info('検証前チェック結果妥当',$cityCode);
                echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
            }
        }catch(Exception $ex) {
            echo json_encode('例外が発生しました', JSON_UNESCAPED_UNICODE);
        }
?>
