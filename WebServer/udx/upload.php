<?php
    //ファイルの存在確認
    try{
        include_once("dbConnection.php"); //DB接続情報の読み込み
        $status = '1'; //upload開始
        include_once("logger.php"); //ログ出力クラスを取得
        include_once("config.php"); //ログ出力用コンフィグクラスを取得
        $log = Logger::getInstance();//ログ出力クラスのインスタンス生成

        if(isset($_FILES['toUploadFile']) && isset($_POST['cityCode'])){
            $cityCode = $_POST['cityCode'];
            //2022
            $uploaddir = '*****:/*****/Data/' . $_POST['cityCode'] . '/OriginalData/3DBuildings/';
            $returnValue = array();
            $dataArray = array();
            $size = '';
            $log->info('アップロード処理開始', $cityCode);
            //アップロードされたファイル数だけループで処理を行う
            for($i =0; $i < count($_FILES['toUploadFile']['name']); $i++){          
                
                //空のファイルに命名規則にのっとったファイル名を付けたファイルをアップロードさせないよう
                //アップロードされたファイルのサイズが0ならエラーとする
                if($_FILES['toUploadFile']['size'][$i] === 0){
                    $status = '2'; //upload失敗
                    
                    $log->error('ファイルサイズが0です　ファイル名：' . $_FILES['toUploadFile']['name'][$i], $cityCode);
                    
                    array_push($dataArray , [$_FILES['toUploadFile']['name'][$i] , 'アップロード失敗']);
                    //2022
                    $sqlResult = db (" WITH upsert AS (
                        UPDATE manage_regist_zip
                        SET status = '". $status ."' ,registdate = NOW()
                        WHERE userid = '". $_POST['cityCode'] .
                        "' AND zipname = '". $_FILES['toUploadFile']['name'][$i] .
                        "' RETURNING * 
                        )
                    INSERT INTO manage_regist_zip (userid, zipname, status, registdate )
                    SELECT '" . $_POST['cityCode'] . "','" . $_FILES['toUploadFile']['name'][$i] ."', '". $status ."' ,  NOW()
                    WHERE not exists (SELECT 1
                    FROM manage_regist_zip WHERE userid = '". $_POST['cityCode'] . "' and zipname = '". $_FILES['toUploadFile']['name'][$i] ."' ) LIMIT 1");//DBへの格納
                    $log->info('アップロード失敗['. $_FILES['toUploadFile']['name'][$i] . ']の' .'ステータスを' . $status . 'に更新' . $sql,$cityCode);

                    //DBクエリが成功したか判定
                    switch($sqlResult){
                        case "success": 
                        case "connectionCloseError":
                            //正常終了した場合あるいはクエリは成功したがコネクションクローズに失敗した場合
                            $log->debug('$sqlResult === true,$status:' . $status,$cityCode);
                            $log->info('DBステータス更新成功['. $_FILES['toUploadFile']['name'][$i] . ']の' .'ステータスを' . $status . 'に更新'. $sql,$cityCode);
                            break;
                        case "queryError":
                        case "DBConnectionError":
                            //クエリ実行に失敗した場合あるいはDBコネクションに失敗した場合
                            $log->debug('$sqlResult === false,$status:' . $status,$cityCode);
                            $log->error('DBステータス更新失敗['. $_FILES['toUploadFile']['name'][$i] . ']の' .'ステータスを' . $status . 'に更新',$cityCode);
                            break;
                        default:
                            break;
                    }
                    continue;
                }
                
                // 実際にアップロードされたファイルかの確認
                $result = false; //変数を初期化
                if(is_uploaded_file($_FILES['toUploadFile']['tmp_name'][$i])){
                    //アップロードステータスを確認する
                    if($_FILES['toUploadFile']['error'][$i] === UPLOAD_ERR_OK){
                        //アップロードステータスが正常なら一時フォルダからアップロード先フォルダにファイルを移動する
                        $result = move_uploaded_file($_FILES['toUploadFile']['tmp_name'][$i], $uploaddir . basename($_FILES['toUploadFile']['name'][$i]));
                    }
                }
                
                //ファイルアップロード結果の確認
                if($result == true){
                    //正常にファイルのアップロードが成功した場合
                    $status = '9'; //upload成功
                    $log->info('アップロード一時ファイル移動成功　ファイル名：' . $_FILES['toUploadFile']['name'][$i], $cityCode);
                    array_push($dataArray , [$_FILES['toUploadFile']['name'][$i] , 'アップロード成功']);
		    //2022
                    $sqlResult = db (" WITH upsert AS (
                        UPDATE manage_regist_zip
                        SET status = '". $status ."' ,registdate = NOW()
                        WHERE userid = '". $_POST['cityCode'] .
                        "' AND zipname = '". $_FILES['toUploadFile']['name'][$i] .
                        "' RETURNING * 
                        )
                    INSERT INTO manage_regist_zip (userid, zipname,  status, registdate )
                    SELECT '" . $_POST['cityCode'] . "','" . $_FILES['toUploadFile']['name'][$i] ."', '". $status ."' ,  NOW()
                    WHERE not exists (SELECT 1
                    FROM manage_regist_zip WHERE userid = '". $_POST['cityCode'] . "' and zipname = '". $_FILES['toUploadFile']['name'][$i] ."' ) LIMIT 1");//DBへの格納
                    $log->info($sql, $cityCode);
                    //DBクエリが成功したか判定
                    switch($sqlResult){
                        case "success": 
                        case "connectionCloseError":
                            //正常終了した場合あるいはクエリは成功したがコネクションクローズに失敗した場合
                            $log->debug('$sqlResult === true,$status:' . $status,$cityCode);
                            $log->info('DBステータス更新成功['. $_FILES['toUploadFile']['name'][$i] . ']の' .'ステータスを' . $status . 'に更新 $sql',$cityCode);
                            break;
                        case "queryError":
                        case "DBConnectionError":
                            //クエリ実行に失敗した場合あるいはDBコネクションに失敗した場合
                            $log->debug('$sqlResult === false,$status:' . $status,$cityCode);
                            $log->error('DBステータス更新失敗['. $_FILES['toUploadFile']['name'][$i] . ']の' .'ステータスを' . $status . 'に更新 $sql',$cityCode);
                            break;
                        default:
                            break;
                    }
                    
                } else {
                    //ファイル移動を試みていない、または移動に失敗した場合
                    $status = '2'; //upload失敗
                    
                    $log->error('アップロード一時ファイル移動失敗　ファイル名：' . $_FILES['toUploadFile']['name'][$i], $cityCode);
                    
                    array_push($dataArray , [$_FILES['toUploadFile']['name'][$i] , 'アップロード失敗']);
                    //2022
                    $sqlResult = db (" WITH upsert AS (
                        UPDATE manage_regist_zip
                        SET status = '". $status ."' ,registdate = NOW()
                        WHERE userid = '". $_POST['cityCode'] .
                        "' AND zipname = '". $_FILES['toUploadFile']['name'][$i] .
                        "' RETURNING * 
                        )
                    INSERT INTO manage_regist_zip (userid, zipname,  status, registdate )
                    SELECT '" . $_POST['cityCode'] . "','" . $_FILES['toUploadFile']['name'][$i] ."', '". $status ."' ,  NOW()
                    WHERE not exists (SELECT 1
                    FROM manage_regist_zip WHERE userid = '". $_POST['cityCode'] . "' and zipname = '". $_FILES['toUploadFile']['name'][$i] ."' ) LIMIT 1");//DBへの格納
                    
                    //DBクエリが成功したか判定
                    switch($sqlResult){
                        case "success": 
                        case "connectionCloseError":
                            //正常終了した場合あるいはクエリは成功したがコネクションクローズに失敗した場合
                            $log->debug('$sqlResult === true,$status:' . $status,$cityCode);
                            $log->info('DBステータス更新成功['. $_FILES['toUploadFile']['name'][$i] . ']の' .'ステータスを' . $status . 'に更新',$cityCode);
                            break;
                        case "queryError":
                        case "DBConnectionError":
                            //クエリ実行に失敗した場合あるいはDBコネクションに失敗した場合
                            $log->debug('$sqlResult === false,$status:' . $status,$cityCode);
                            $log->error('DBステータス更新失敗['. $_FILES['toUploadFile']['name'][$i] . ']の' .'ステータスを' . $status . 'に更新',$cityCode);
                            break;
                        default:
                            break;
                    }
                }
            }
            

            //アップロード後のファイル合計容量取得開始
            //2022
            $getTotalSizePath = '*****:/*****\Data/' . $cityCode . '/OriginalData/'; //容量取得対象パス
            //COMオブジェクト生成
            $obj = new COM ( 'scripting.filesystemobject' );
            if(is_object($obj)){
                //フォルダ情報取得
                $ref = $obj->getfolder ( $getTotalSizePath );
                
                //フォルダ合計サイズ取得
                $totalSize = $ref->size;
                
                //バイトで取得されるので単位を付与
                if(empty($totalSize) === false){
                    switch($totalSize){
                        case ($totalSize >= (1024 * 1024 * 1024)):
                            $size = '現在の使用容量：' . number_format($totalSize / (1024 * 1024 * 1024), 1) . 'GB / 5GB';
                            break;
                        case ($totalSize >= 1024 * 1024):
                            $size = '現在の使用容量：' . number_format($totalSize / (1024 * 1024), 1) . 'MB / 5GB';
                            break;
                        case ($totalSize >= 1024):
                            $size = '現在の使用容量：' . number_format($totalSize / 1024, 1) . 'KB / 5GB';
                            break;
                        case ($totalSize >= 1):
                            $size = '現在の使用容量：' . $totalSize . 'Byte / 5GB';
                            break;
                        default:
                            $size = '現在の使用容量：0Byte / 5GB';
                            break;
                    }
                } else {
                    $size = '現在の使用容量：0Byte / 5GB';
                }
                
                $obj = null;
            } else {
                $log->error('ファイル容量取得エラー', $cityCode);
            }
            array_push($returnValue, array('size' => $size, 'resultArray' => $dataArray));
                                
            echo json_encode($returnValue, JSON_UNESCAPED_UNICODE);
            $log->info('アップロード処理終了', $cityCode);
        } else {
            echo json_encode('アップロードされたファイルが存在しないか、POSTされるべき値がPOSTされていません', JSON_UNESCAPED_UNICODE);
        }
    } catch(Exception $ex) {
        echo json_encode($ex->getMessage(), JSON_UNESCAPED_UNICODE);
        $log->error('アップロード処理において例外が発生しました。　エラー内容：' . $ex->getMessage(), $cityCode);
    }

?>