<?php
    if(isset($_POST['cityCode']) && isset($_POST['toUploadTotalSize'])){
        $path = 'F:\DATA/' . $_POST['cityCode'] . '/OriginalData/3DBuildings/*.*';//アップロード済ファイルの取得先フォルダパスと条件
        $toUploadTotalSize = intval($_POST['toUploadTotalSize']);
        $uploadFileNameList = json_decode($_POST['uploadFileNameList']);
        
        //アップロード済ファイルの情報をもつ連想配列
        $uploadedFileInfo = array(
            'fileName' => array(),
        );
        $uploadedTotalSize = 0;//アップロード済ファイルの合計サイズを保持する
        $maxTotalSize = 1024 * 1024 * 1024 * 15; //アップロード済ファイルの合計サイズがこれを超えているとエラー扱い
        $cityCode = (string) $_POST["cityCode"];//POSTされた自治体コード
        
        include_once("logger.php"); //ログ出力クラスを取得
        include_once("config.php"); //ログ出力用コンフィグクラスを取得
        $log = Logger::getInstance();//ログ出力クラスのインスタンス生成
        
        $log->info('登録ボタン押下', $cityCode);
        //処理開始ログを出力
        $log->info('アップロード済ファイルリスト取得処理開始', $cityCode);
        
        
        //アップロードファイル格納パスからファイルのフルパス取得
        $uploadFilePath = glob($path);

        
        //アップロード済ファイル名を持つ配列を作成しつつ、ファイルサイズの合計サイズを計算する
        foreach($uploadFilePath as $filepath){
             $uploadFileName = basename($filepath);
             $stat = stat($filepath);
             $uploadedTotalSize = $uploadedTotalSize + $stat['size'];
             array_push($uploadedFileInfo['fileName'], $uploadFileName);
        }
        
        //アップロード済ファイルの合計サイズが限界値を超えている場合、処理を終了して画面に情報を返す
        if(($uploadedTotalSize + $toUploadTotalSize) > $maxTotalSize){
            $returnArray = array(
                'result' => 'folderCapacityOver',
                'uploadedTotalSize' => ($uploadedTotalSize + $toUploadTotalSize)
            );
            $log->error('アップロード済ファイルの合計サイズが限界値を超えています',$cityCode);
            echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
            return;
        }
        //Fドライブの空き容量が5%未満(データ使用率が95%を超える)の場合
        if(disk_free_space("F:") / disk_total_space("F:") < 0.05){
            $returnArray = array(
                'result' => 'dataDriveCapacityOver'
            );
            echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
            $log->warn('データ領域の使用率が95%を超えています', $cityCode);
            return;
        }
        
        include_once("dbConnection.php"); //DB接続情報の読み込み
        include_once("dbSelect.php"); //DB接続情報の読み込み
        
        //JOB数チェック
        $log->info('実行中のJOB数を確認',$cityCode);
        
        //クエリ条件用にファイル名のin句生成
        $in_query = '';
        
        foreach($uploadFileNameList as $uploadFileName){
            $uploadFile = trim(basename($uploadFileName));
            
            if($in_query == ''){
                $in_query = '\''.$uploadFile.'\',';
            }else {
                $in_query = $in_query.'\'' .$uploadFile.'\',' ;
            }                    
        }
        $in_query = rtrim($in_query,',');             //末尾のカンマを削除
        $in_query ='zipname in (' . $in_query . ')';  //～ zipname in (filename1,filename2)の形式にする
        
        $selRet = sel_query ("with jobCount as(
        SELECT count(userid)as activeJobCount from (SELECT DISTINCT userid FROM public.manage_regist_zip where status = '1' and userid = '" . $cityCode . "')as a)
        , fileCount as(
                SELECT count(userid)as activeUserCount from (SELECT DISTINCT userid FROM public.manage_regist_zip where status = '1')as b)
        ,uploadCount as(
                SELECT count(userid)as uploadJobCount from (SELECT DISTINCT userid FROM public.manage_regist_zip where status IN('1','19','199','1999','1099','1299','2199','9199','10000') and userid = '" . $cityCode ."' and " . $in_query . ")as c)
        ,jobCountJoin as(select * from jobCount cross join fileCount) 		
        select * from jobCountJoin cross join uploadCount",'Upload');
        
        //自身の自治体が行っているアップロード処理数(値が0以外はすでに実行中)
        $myUploadJobCount = $selRet[0]['activeJobCount'];
        //全体でのアップロード実行数
        $activeUploadJobCount = $selRet[0]['activeUserCount'];
        
        $uploadCount = $selRet[0]['uploadJobCount'];
            
        //自身のジョブが既に実行されている場合、処理を終了して画面に情報を返す
        if($myUploadJobCount != 0){
            $returnArray = array(
                'result' => 'myJobIsActive',
                'myUploadJobCount' => $myUploadJobCount
            );
             $log->warn('他に検証・変換中のファイルと同名のファイルはアップロードできません',$cityCode);
             echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
            return;
           
        }
        
        //全体のアップロードの実行数が5以上の場合、処理を終了して画面に情報を返す
        if($activeUploadJobCount >= 5){
            $returnArray = array(
                'result' => 'activeJobCountOver',
                'activeUploadJobCount' => $activeUploadJobCount
            );
            $log->warn('実行中のJOB数が規定値に達しているためJOBの実行はできません。',$cityCode);
            echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
            return;
        }
        
        if($uploadCount != 0){
            $returnArray = array(
                'result' => 'fileIsUsed',
            );
             $log->warn('同名ファイルで上書き登録できないステータスの物があります。',$cityCode);
             echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
            return;
           
        }
        
        //ここまで通ればアップロード条件を満たしているため、
        $returnArray = $uploadedFileInfo;
        $returnArray['result'] = 'OK';
        echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
        $log->info('アップロードを開始します。',$cityCode);
        $log->info(json_encode($returnArray, JSON_UNESCAPED_UNICODE),$cityCode);
    } else {
    
    }
?>