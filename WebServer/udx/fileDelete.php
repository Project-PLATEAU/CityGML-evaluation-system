<?php
    //自ホストからの要求か確認
    //$host = $_SERVER['HTTP_REFERER'];
    //$url = parse_url($host);
    //外部ホストからの場合処理を行わない
    //if(stristr($url['host'], "localhost")){

        try{
            if(isset($_POST["cityCode"]) == true && isset($_POST["deleteFileNameArray"]) == true){
                $cityCode = $_POST["cityCode"];
                $deleteFileNameArray = $_POST["deleteFileNameArray"];
            } else {
                echo json_encode('POSTされるべき値がPOSTされませんでした', JSON_UNESCAPED_UNICODE);
                return;
            }
            include_once("dbConnection.php"); //DB接続情報の読み込み
            //削除に失敗したzipファイルのファイル名配列
            $failedFileNameArray = array();
            $resultArray = array();

            include_once("dbSelect.php");
            include_once("logger.php"); //ログ出力クラスを取得
            include_once("config.php"); //ログ出力用コンフィグクラスを取得
            $log = Logger::getInstance();//ログ出力クラスのインスタンス生成
            
            $log->info('ファイル削除処理開始', $cityCode);
            
            
            //削除対象ファイル名配列を生成
            $deleteFileNameArray = json_decode($deleteFileNameArray);
            
            //クエリ条件用にファイル名のin句生成
            $in_query = '';
            
            foreach($deleteFileNameArray as $toDeleteFileCheck){
                $delfile = trim(basename($toDeleteFileCheck));

                if($in_query == ''){
                    $in_query = '\''.$delfile.'\',';
                }else {
                    $in_query = $in_query.'\'' .$delfile.'\',' ;
                }
            }
            $in_query = rtrim($in_query,',');             //末尾のカンマを削除
            $in_query ='zipname in (' . $in_query . ')';  //～ zipname in (filename1,filename2)の形式にする
            
            $selRet = sel_query ("select count(status) from public.manage_regist_zip where  userid = '" .$cityCode . "' and status in ('1','19','199','9199','2199','1099','1299','1999','10000') and " . $in_query ,'Delete');
                      
            $delCount = $selRet['0']['delJobCount'];          //(値が0以外は削除を行わない)
            if(!$delCount == 0){
                $resultArray["result"] = "DoNotDelete";
                $log->warn('削除対象外のデータが選択されているため削除処理を行いません。', $cityCode);
                echo json_encode($resultArray, JSON_UNESCAPED_UNICODE);
                return;
            }
            
            //ここからzipファイルの削除
			//2022
            $allZipName = glob('*****:/*****/Data/' . $cityCode . '/OriginalData/3DBuildings/{*.zip,*.gml}', GLOB_BRACE);

            //ディレクトリとファイル名がPOSTされているか確認
            if($allZipName == false){
                //ファイル一覧の取得に失敗した場合
                $resultArray["result"] = "zipファイル一覧の取得に失敗";
                echo json_encode($resultArray, JSON_UNESCAPED_UNICODE);
                $log->error('ファイル削除に失敗しました ファイル名：' . basename($registedZipName), $cityCode);
                return;
            } else {
                //それ以外の場合は削除処理を開始
                
                $log->info('ステータス更新開始',$cityCode);
                
                foreach($deleteFileNameArray as $toDeleteFileName){
                    $delfilename = basename($toDeleteFileName);
                    $status = '10000'; //削除開始
                                //2022
                                db (" WITH upsert AS (
                                    UPDATE public.manage_regist_zip
                                    SET status = '". $status ."' ,registdate = NOW()
                                    WHERE userid = '". $cityCode .
                                    "' AND zipname = '". $delfilename .
                                    "' RETURNING * 
                                   )
                                   INSERT INTO public.manage_regist_zip (userid, zipname, status,registdate )
                                   SELECT '" . $cityCode . "','" . $delfilename ."',  '". $status ."' , NOW()
                                   WHERE not exists (SELECT 1
                                   FROM public.manage_regist_zip WHERE userid = '". $cityCode . "' and zipname = '". $delfilename ."' ) LIMIT 1");//DBへの格納
                                   
                                   $log->info('['. $delfilename . ']の' .'ステータスを' . $status . 'に更新',$cityCode);
                }
                
                //存在するファイル名とPOSTされたファイル名が合致した場合は削除を行う
                foreach($deleteFileNameArray as $toDeleteFileName){
                    $fileNameMatchFlg = false;
                    foreach($allZipName as $registedZipName){
                        if($toDeleteFileName === basename($registedZipName)){
                            $filename = basename($registedZipName);
                                
                            
                            if(unlink($registedZipName) === false){
                                //削除に失敗した場合、ファイル名を配列にpush
                                array_push($failedFileNameArray,  basename($registedZipName));
                                $log->error('ファイル削除に失敗しました ファイル名：' . basename($registedZipName), $cityCode);
                                $status = '20000'; //削除エラー
                                //2022
                                db (" WITH upsert AS (
                                    UPDATE public.manage_regist_zip
                                    SET status = '". $status ."' ,registdate = NOW()
                                    WHERE userid = '". $cityCode .
                                    "' AND zipname = '". $filename .
                                    "' RETURNING * 
                                   )
                                   INSERT INTO public.manage_regist_zip (userid, zipname,  status, registdate )
                                   SELECT '" . $cityCode . "','" . $filename ."',  '". $status ."' ,  NOW()
                                   WHERE not exists (SELECT 1
                                   FROM public.manage_regist_zip WHERE userid = '". $cityCode . "' and zipname = '". $filename ."' ) LIMIT 1");//DBへの格納
                                   
                                   $log->info('['. $filename . ']の' .'ステータスを' . $status . 'に更新',$cityCode);
                            } else {
                                //削除成功時はDBから情報を削除
                                db ("delete from public.manage_regist_zip where userid = '". $cityCode . "' and zipname = '". $filename ."'");
                                $log->info('ファイル削除に成功しました ファイル名：' . basename($registedZipName), $cityCode);
                            }
                            $fileNameMatchFlg = true;

                            break;
                        }
                    }
                    if($fileNameMatchFlg == false){
                        array_push($failedFileNameArray,  basename($toDeleteFileName));
                        $log->error('ファイル削除に失敗しました ファイル名：' . basename($toDeleteFileName), $cityCode);
                    }
                }
                
                if(count($failedFileNameArray) > 0){
                    $resultArray["result"] = "deleteError";
                    $resultArray["faildFileName"] = $failedFileNameArray;
                    $log->info('何らかのエラーにより削除できないzipファイルがありました。', $cityCode);
                    echo json_encode($resultArray, JSON_UNESCAPED_UNICODE);
                    return;
                } else {
                    //成功した場合
                    $resultArray["result"] = "success";
                    $log->info('すべての削除対象ファイルの削除処理が成功しました', $cityCode);
                    echo json_encode($resultArray, JSON_UNESCAPED_UNICODE);
                }
            }
        } catch(Exception $ex){
            echo json_encode($ex->getMessage(), JSON_UNESCAPED_UNICODE);
        }
?>