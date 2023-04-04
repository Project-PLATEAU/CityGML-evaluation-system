<?php
    //自ホストからの要求か確認
    //$host = $_SERVER['HTTP_REFERER'];
    //$url = parse_url($host);
    //外部ホストからの場合処理を行わない
    //if(stristr($url['host'], "localhost")){
    try{
        include_once("logger.php"); //ログ出力処理の読み込み
        include_once("config.php"); //ログ出力用のコンフィグ読み込み
        include_once("dbConnection.php"); //DB接続情報の読み込み
        include_once("cleanupSchema.php"); //DB接続情報の読み込み
        //ログ書き込み処理
        $log = Logger::getInstance();
		
		//パス設定
        $mapRoot = '*****:/*****/htdocs/map/';

        if(isset($_POST["cityCode"]) == true && isset($_POST["convertFileNames"]) == true){
            //自治体IDを確認
            $cityCode = (string) $_POST["cityCode"];
            //コンバートリスト
            $convertFileNames = json_decode($_POST["convertFileNames"]);

            //処理開始
            $log->info('データ変換開始',$cityCode);
            
            include_once("dbConnection.php"); //DB接続情報の読み込み
            include_once("dbSelect.php"); //DB接続情報の読み込み

            //JOB数チェック
            $log->info('実行中のJOB数を確認',$cityCode);
            //自治体IDごとの処理数と全体でのJOB数を取得
            $selRet = sel_query ("with jobCount as(
            SELECT count(userid)as activeJobCount from (SELECT DISTINCT userid FROM public.manage_regist_zip where status in ('1099','1299','1999') and userid = '" . $cityCode . "')as a)
            , fileCount as(
            SELECT count(userid)as activeUserCount from (SELECT DISTINCT userid FROM public.manage_regist_zip where status in ('1099','1299','1999'))as b)
            select * from jobCount cross join fileCount",'Convert');

            $jobCount = $selRet['0']['activeJobCount'];          //自治体ID単位での処理数取得(値が0以外はすでに実行中)
            $userCount = $selRet['0']['activeUserCount'];        //全体でのジョブ数5ジョブ以上は待たせる

            //自身のジョブが既に実行されているかを確認する
            if($jobCount <> 0){
               $log->warn('すでにJOBが実行されているため実行できません。',$cityCode);
               return;
            }
            
            //全体のジョブの実行数が5未満であることを確認する
            if($userCount >= 5){
               $log->warn('実行中のJOB数が規定値に達しているためJOBの実行はできません。',$cityCode);
               return;
            }

            //非公開用地図に使用している3DTilesを削除する
            $log->info('変換開始前の公開用地図3DTilesの削除開始',$cityCode);
            $errorFlag = false;
			//2022
            $delTilesPath = $mapRoot . $cityCode . '/private/datasource-data';

            $items = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($delTilesPath, RecursiveDirectoryIterator::CURRENT_AS_SELF),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            //各ファイルやフォルダに処理を行う
            foreach($items as $item){
                //パスに'Terrain'が含まれていない場合のみ処理を行う
                if(strpos($item->getPathname(), 'private/datasource-data/Terrain/') === false && $item->getPathname() !== $delTilesPath . '/Terrain' ){
                    //ファイルかディレクトリかを判定して削除する
                    if($item->isFile() || $item->isLink()){
                        if(unlink($item->getPathname()) === false){
                            $errorFlag = true;
                        }
                    } elseif ($item->isDir() && !$item->isDot()){
                        if(rmdir($item->getPathname()) === false){
                            $errorFlag = true;
                        }
                    }
                }
            }

            if($errorFlag === false){
                $log->info('変換開始前の公開用地図3DTilesの削除成功',$cityCode);
            }else{
                //エラー処理
                $log->error('変換開始前の公開用地図3DTilesの削除失敗',$cityCode);
            }

            //変換処理対象の情報を持った配列を作成する
            $convertErrorFlag = false;
            $pathArrayArray = array();
            //$varietyOfFiletype = array('3D_LOD1', '3D_LOD2', '3D_LOD2_Surface', '3D_LOD3', '3D_LOD4', '3D_ALL'); 
            //$extensionOfLod = array('_LOD1.txt', '_LOD2.txt', '_LOD2_Surface.txt', '_LOD3.txt', '_LOD4.txt', '_LODALL.txt');
            $varietyOfFiletype = array('3D_LOD1', '3D_LOD2', '3D_LOD2_Surface', '3D_LOD3'); 
            $extensionOfLod = array('_LOD1.txt', '_LOD2.txt', '_LOD2_Surface.txt', '_LOD3.txt');

            foreach($convertFileNames as $fileName){
                //ファイル種別識別用文字列
                $searchDem = '_dem_';
                $search2D = '_6668';

                if(strpos($fileName,$searchDem) !== false){
                    //DEMの場合                    
                    array_push($pathArrayArray, convertFunc($cityCode, 'DEM', $fileName, '.txt'));
                }else if(strpos($fileName,$search2D) !== false){
                    //2Dの場合
                    array_push($pathArrayArray, convertFunc($cityCode, '2D', $fileName, '.txt'));
                }else{
                   //3Dの場合
                   for($i = 0 ; $i<count($varietyOfFiletype) ; $i++){
                        array_push($pathArrayArray, convertFunc($cityCode, $varietyOfFiletype[$i], $fileName, $extensionOfLod[$i]));
                        //$log->info('debug用'. $varietyOfFiletype[$i]. '-'. $extensionOfLod[$i], $cityCode); 
                   }                   
                }
            }
            unset($fileName);

            //先に検索用データベースを初期化する
            $log->info('スキーマの初期化開始',$cityCode);
            if(cleanupSchema($cityCode) === false){
                $convertErrorFlag = true;
                $log->error('スキーマの初期化失敗',$cityCode);
            } else {
                $log->info('スキーマの初期化成功',$cityCode);
            }
            
            //各ファイル毎に変換開始（3Dは1ファイルにつき複数回変換する）
            foreach($pathArrayArray as $pathArray){
                $fileName = $pathArray['fileName'];

                $log->info($pathArray['fileName'] . '(' . $pathArray['fileType'] . ')の変換開始',$cityCode);

                //ジョブストップ用定義ファイルの格納場所
				//2022
                $stopFilePath = '*****:/*****/htdocs/udx/jobstop/';
                $stopAll = 'jobStopConvert_all.txt';
                $stopCityCode = 'jobStopConvert_' . $cityCode . '.txt';

                //処理停止用ファイルが存在するなら変換処理をスキップ
                if (file_exists($stopFilePath . $stopAll) || file_exists($stopFilePath . $stopCityCode)) {

                    $log->error('処理中断ファイルが存在するため後続処理をスキップ',$cityCode);
					//2022 SQL修正
                    db (" WITH getStatus AS(
                        select case when status = '1099' then '2099' 
                                when status = '1999' then '2999' 
                                when status = '1299' then '2299' 
                                ELSE status 
                                end
                        FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."'
                        )
                        ,upsert AS (
                                UPDATE public.manage_regist_zip
                                SET status = (select * from getStatus) ,registdate = NOW()
                                WHERE userid = '". $cityCode ."' AND zipname = '". $fileName ."' RETURNING * 
                        )
                                INSERT INTO public.manage_regist_zip (userid, zipname, status, registdate )
                                SELECT '". $cityCode ."','". $fileName ."',  (select * from getStatus) ,  NOW()
                                WHERE not exists (SELECT 1
                                FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."' ) LIMIT 1");//DBへの格納
                        
                    $log->error('変換スキップ['. $fileName . ']の' .'ステータスを変換エラーに更新',$cityCode);
                    continue;
                }
                if($convertErrorFlag === true){
				//2022 SQL修正
				$sql =" WITH getStatus AS(
                        select case when status = '1099' then '2099' 
                                when status = '1999' then '2999' 
                                when status = '1299' then '2299' 
                                ELSE status 
                                end
                        FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."'
                        )
                        ,upsert AS (
                                UPDATE public.manage_regist_zip
                                SET status = (select * from getStatus) ,registdate = NOW()
                                WHERE userid = '". $cityCode ."' AND zipname = '". $fileName ."' RETURNING * 
                        )
                                INSERT INTO public.manage_regist_zip (userid, zipname, status, registdate )
                                SELECT '". $cityCode ."','". $fileName ."',  (select * from getStatus) ,  NOW()
                                WHERE not exists (SELECT 1
                                FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."' ) LIMIT 1";

                    //別ファイルの変換に失敗している場合変換をスキップする
                    db ($sql);//DBへの格納
                        
                    $log->error('変換スキップ['. $fileName . ']の' .'ステータスを変換エラーに更新',$cityCode);
                    continue;
                }
            
                $batResultFilePath = $pathArray["batResultFilePath"];
                $convertResultFilePath = $pathArray["convertResultFilePath"];
                $fileType = $pathArray["fileType"];
                $editFileName = '"' . $fileName . '"';
                $maxBatCheck = 8640; //バッチ実行ログファイル存在確認の最大回数　これを超えると失敗扱いとする
                $batSleepSecond = 10; //バッチ実行ログファイルが出力されたことを確認するインターバル(秒)
                $batCheckCount = 0; //バッチ実行ログファイルの存在確認回数
                $maxConvertCheck = 8640; //変換ログファイル存在確認の最大回数　これを超えると失敗扱いとする
                $convertSleepSecond = 10; //変換ログファイルが出力されたことを確認するインターバル(秒)
                $convertCheckCount = 0; //変換ログファイルの存在確認回数
                
                //過去のバッチ実行ログファイルがあるなら削除する
                if(file_exists($batResultFilePath) === true){
                    if(unlink($batResultFilePath) === false){
                        //削除に失敗した際の処理
                        $log->error('過去のバッチ実行ログファイルの削除に失敗',$cityCode);
                    }
                }
                
                //過去の変換ログファイルがあるなら削除する
                if(file_exists($convertResultFilePath) === true){
                    if(unlink($convertResultFilePath) === false){
                        //削除に失敗した際の処理
                        $log->error('過去の変換ログファイルの削除に失敗',$cityCode);
                    }
                }

                //3DはLOD1の場合のみデータベースへのインポートを実行する
                if($pathArray['fileType'] === '3D_LOD1' || $pathArray['fileType'] === '2D'){
                    $log->info('データベースへの登録バッチ実行',$cityCode);
                    //DBへデータ登録処理の呼出し。とりあえず同期化せずに対応する
                    exec("cmd.exe /c exec_import.bat $editFileName $cityCode");
					echo "実行開始 : cmd.exe /c exec_import.bat $editFileName $cityCode<br>";
                }
                
                //変換処理を実行する
                $log->info("変換バッチ実行:cmd.exe /c exec_convert.bat $editFileName $cityCode $fileType ",$cityCode);
                exec("cmd.exe /c exec_convert.bat $editFileName $cityCode $fileType ");
				//echo "変換バッチ実行 : cmd.exe /c exec_convert.bat $editFileName $cityCode $fileType<br>";

                //バッチ実行ログファイルが出力されるまで処理を待つ
                $log->info('バッチ実行ログファイル出力待ち開始',$cityCode);

                while(true){
                    //バッチ実行ログファイルが存在したらループを抜ける
                    if (file_exists($batResultFilePath) === true) {
                        $log->info('バッチ実行ログファイル検知',$cityCode);
                        break;
                    }

                    if($batCheckCount < $maxBatCheck){
                        sleep($batSleepSecond);
                        $batCheckCount = $batCheckCount + 1;
                    } else {
                        //最大確認回数を超えた場合エラーフラグを立ててループを抜ける
                        $convertErrorFlag = true;
                        $log->error('バッチ実行ログファイルが既定の時間内に出力されませんでした',$cityCode);
                        break;
                    }
                }

                //バッチ実行ログファイルを読み込み
                $batResultStatus = "notReaded";
                for($i = 0; $i < 3; $i++){
                    $log->info('バッチ実行ログファイル読込実行',$cityCode);
                    $batResults = file($batResultFilePath, FILE_IGNORE_NEW_LINES);
                    
                    if($batResults !== false){
                        //バッチ実行ログファイルの1行目の内容を取得
                        $log->info('バッチ実行ログファイルの1行目の内容を取得',$cityCode);
                        $batResultString = $batResults[0];
                        if(strpos($batResultString, 'error') !== false){
                            $batResultStatus = "error";
                            break;
                        } else if (strpos($batResultString, 'success') !== false){
                            $batResultStatus = "success";
                            break;
                        }
                        $log->warn('バッチ実行ログファイルにエラーも成功も記述されていないため、再読込します',$cityCode);
                    }
                    sleep(5);
                }

                //バッチ実行ログファイルの内容を確認
                if($batResultStatus === "notReaded"){
                    //バッチ実行ログの取得に失敗した場合
                    //3Dの場合はLOD2_Surfaceの処理が完了するまでDBを更新しない
                    //if($pathArray['fileType'] === '3D_LOD2_Surface' || 
                    if($pathArray['fileType'] === '3D_LOD3' || 
                        $pathArray['fileType'] === 'DEM' ||
                        $pathArray['fileType'] === '2D'){
                        db (" WITH getStatus AS(
                            select case when status = '1099' then '2099' 
                                    when status = '1999' then '2999' 
                                    when status = '1299' then '2299' 
                                    ELSE status 
                                    end
                            FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."'
                            )
                            ,upsert AS (
                                    UPDATE public.manage_regist_zip
                                    SET status = (select * from getStatus) ,registdate = NOW()
                                    WHERE userid = '". $cityCode ."' AND zipname = '". $fileName ."' RETURNING * 
                            )
                                    INSERT INTO public.manage_regist_zip (userid, zipname,  status, registdate )
                                    SELECT '". $cityCode ."','". $fileName ."',  (select * from getStatus) ,  NOW() From public.manage_regist_zip
                                    WHERE not exists (SELECT 1
                                    FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."' ) LIMIT 1");//DBへの格納
                        
                        $log->error('バッチ実行ログの取得に失敗したため変換失敗['. $fileName . ']の' .'ステータスを変換エラーに更新',$cityCode);
                    } else {
                        $log->error('バッチ実行ログの取得に失敗したため変換失敗['. $fileName . '] '.$pathArray['fileType'].'のためDB更新なし',$cityCode);
                    }                        
                    
                    $convertErrorFlag = true;
                    continue;
                } else if($batResultStatus === 'error'){
                    //エラーが記載されていた場合は変換失敗とする
                    //3Dの場合はLOD2_Surfaceの処理が完了するまでDBを更新しない
                    //if($pathArray['fileType'] === '3D_LOD2_Surface' || 
                    if($pathArray['fileType'] === '3D_LOD3' || 
                        $pathArray['fileType'] === 'DEM' ||
                        $pathArray['fileType'] === '2D'){
                        //変換処理に失敗した場合
                        db (" WITH getStatus AS(
                            select case when status = '1099' then '2099' 
                                    when status = '1999' then '2999' 
                                    when status = '1299' then '2299' 
                                    ELSE status 
                                    end
                            FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."'
                            )
                            ,upsert AS (
                                    UPDATE public.manage_regist_zip
                                    SET status = (select * from getStatus) ,registdate = NOW()
                                    WHERE userid = '". $cityCode ."' AND zipname = '". $fileName ."' RETURNING * 
                            )
                                    INSERT INTO public.manage_regist_zip (userid, zipname,  status, registdate )
                                    SELECT '". $cityCode ."','". $fileName ."',  (select * from getStatus) ,  NOW() From public.manage_regist_zip
                                    WHERE not exists (SELECT 1
                                    FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."' ) LIMIT 1");//DBへの格納
                        
                        $log->error('バッチ実行ログにエラーが書かれていたため変換失敗['. $fileName . ']の' .'ステータスを変換エラーに更新',$cityCode);
                    } else {
                        $log->error('バッチ実行ログにエラーが書かれていたため変換失敗['. $fileName . '] '.$pathArray['fileType'].'のためDB更新なし',$cityCode);
                    }      
                    
                    $convertErrorFlag = true;
                    continue;
                } else if ($batResultStatus === 'success'){
                    $log->info('変換ログファイル出力待ち開始',$cityCode);
                    //変換ログファイルが出力されるまで処理を待つ
                    while(true){
                        //変換ログファイルが存在したらループを抜ける
                        if(file_exists($convertResultFilePath) === true){
                            $log->info('変換ログファイル検知',$cityCode);
                            break;
                        }

                        if($convertCheckCount < $maxConvertCheck){
                            sleep($convertSleepSecond);
                            $convertCheckCount = $convertCheckCount + 1;
                        } else {
                            //最大確認回数を超えた場合エラーフラグを立ててループを抜ける
                            $convertErrorFlag = true;
                            $log->error('変換ログファイルが既定の時間内に出力されませんでした',$cityCode);
                            break;
                        }
                    }
                    
                    //再度変換ログファイルの存在確認
                    if(file_exists($convertResultFilePath) === true){
                        $convertLogIsSuccess = false;
                        for($i = 0; $i < 3; $i++){
                            $log->info('変換ログファイル読み込み実行',$cityCode);
                            $convertResults = file($convertResultFilePath, FILE_IGNORE_NEW_LINES);
                            
                            if($convertResults !== false){
                                //変換ログファイルの内容を解析
                                $log->info('変換ログファイル内容解析開始',$cityCode);
                                foreach($convertResults as $convertResult){
                                    if(preg_match('/^\[[0-2][0-9]:[0-5][0-9]:[0-5][0-9] ERROR\]/', $convertResult) === 1 || preg_match('/^\[[0-2][0-9]:[0-5][0-9]:[0-5][0-9] WARN\]/', $convertResult) === 1){
                                        //変換した結果エラーなどが発生した場合の処理
                                        $log->error('変換ログファイルにエラーが記述されていました',$cityCode);
                                        break 2;
                                    } else if (preg_match('/X3DMGenerator CityGML to tiles conversion successfully finished/', $convertResult) === 1){
                                        //変換した結果正常に変換に成功した場合の処理
                                        //変換に成功した場合
                                        $convertLogIsSuccess = true;
                                        //3Dの場合はLOD2_Surfaceの処理が完了するまでDBを更新しない
                                        //if($pathArray['fileType'] === '3D_LOD2_Surface' || 
                                         if($pathArray['fileType'] === '3D_LOD3' || 
                                            $pathArray['fileType'] === 'DEM' ||
                                            $pathArray['fileType'] === '2D'){
											//2022 SQL修正
											$sql = " WITH getStatus AS(
                                                select case when status = '1099' then '9099' 
                                                        when status = '1999' then '9999' 
                                                        when status = '1299' then '9299' 
                                                        ELSE status 
                                                        end
                                                FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."'
                                                )
                                                ,upsert AS (
                                                        UPDATE public.manage_regist_zip
                                                        SET status = (select * from getStatus) ,registdate = NOW()
                                                        WHERE userid = '". $cityCode ."' AND zipname = '". $fileName ."' RETURNING * 
                                                )
                                                        INSERT INTO public.manage_regist_zip (userid, zipname,  status, registdate )
                                                        SELECT '". $cityCode ."','". $fileName ."',  (select * from getStatus) ,  NOW() From public.manage_regist_zip
                                                        WHERE not exists (SELECT 1
                                                        FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."' ) LIMIT 1";
											$log->info($sql,$cityCode);
                                            db ($sql);//DBへの格納

                                            $log->info('変換成功['. $fileName . ']の' .'ステータスを変換完了に更新',$cityCode);
                                        } else {
                                            $log->info('変換成功['. $fileName . '] '.$pathArray['fileType'].'のためDB更新なし',$cityCode);
                                        }   
                                        break 2;
                                    }
                                }
                                $log->warn('変換ログにエラーも成功も記述されていないため再読込します',$cityCode);
                            }
                            sleep(5);
                        }

                        //変換ログファイルを最後まで解析しても成功が確認できなかった場合
                        if($convertLogIsSuccess === false){
                            //3Dの場合はLOD2_Surfaceの処理が完了するまでDBを更新しない
                            //if($pathArray['fileType'] === '3D_LOD2_Surface' || 
                            if($pathArray['fileType'] === '3D_LOD3' || 
                                $pathArray['fileType'] === 'DEM' ||
                                $pathArray['fileType'] === '2D'){
                                db (" WITH getStatus AS(
                                    select case when status = '1099' then '2099' 
                                            when status = '1999' then '2999' 
                                            when status = '1299' then '2299' 
                                            ELSE status 
                                            end
                                    FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."'
                                    )
                                    ,upsert AS (
                                            UPDATE public.manage_regist_zip
                                            SET status = (select * from getStatus) ,registdate = NOW()
                                            WHERE userid = '". $cityCode ."' AND zipname = '". $fileName ."' RETURNING * 
                                    )
                                            INSERT INTO public.manage_regist_zip (userid, zipname,  status, registdate )
                                            SELECT '". $cityCode ."','". $fileName ."',  (select * from getStatus) ,  NOW() From public.manage_regist_zip
                                            WHERE not exists (SELECT 1
                                            FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."' ) LIMIT 1");//DBへの格納
                                
                                $log->error('変換成功フラグが立っていないため変換失敗['. $fileName . ']の' .'ステータス変換エラーに更新',$cityCode);
                            } else {
                                $log->error('変換成功フラグが立っていないため変換失敗['. $fileName . '] '.$pathArray['fileType'].'のためDB更新なし',$cityCode);
                            }
                            $convertErrorFlag = true;
                        }
                    } else {
                        //変換ログが存在しなかった場合
                        //3Dの場合はLOD2_Surfaceの処理が完了するまでDBを更新しない
                        //if($pathArray['fileType'] === '3D_LOD2_Surface' || 
                        if($pathArray['fileType'] === '3D_LOD3' || 
                            $pathArray['fileType'] === 'DEM' ||
                            $pathArray['fileType'] === '2D'){
                            db (" WITH getStatus AS(
                                select case when status = '1099' then '2099' 
                                        when status = '1999' then '2999' 
                                        when status = '1299' then '2299' 
                                        ELSE status 
                                        end
                                FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."'
                                )
                                ,upsert AS (
                                        UPDATE public.manage_regist_zip
                                        SET status = (select * from getStatus) ,registdate = NOW()
                                        WHERE userid = '". $cityCode ."' AND zipname = '". $fileName ."' RETURNING * 
                                )
                                        INSERT INTO public.manage_regist_zip (userid, zipname,  status, registdate )
                                        SELECT '". $cityCode ."','". $fileName ."',  (select * from getStatus) ,  NOW() From public.manage_regist_zip
                                        WHERE not exists (SELECT 1
                                        FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileName ."' ) LIMIT 1");//DBへの格納
                            
                            $log->error('変換ログが存在しなかったため変換失敗['. $fileName . ']の' .'ステータスを変換エラーに更新',$cityCode);
                        } else {
                            $log->error('変換ログが存在しなかったため変換失敗['. $fileName . '] '.$pathArray['fileType'].'のためDB更新なし',$cityCode);
                        }   
                        
                        $convertErrorFlag = true;
                        continue;
                    }
                }
            }
        } else {
            //POST情報不足
            $log->error('正常にPOSTされませんでした',$cityCode);
        }
        $log->info('データ変換処理が終了しました。',$cityCode);
    } catch(Exception $ex){
        $log->error('変換処理に失敗しました。'. $ex ,$cityCode);
    }

    function convertFunc($cityCode, $varietyOfFiletype, $fileNameOfLod, $extensionOfLod){
        $convertInfo = array(
            'batResultFilePath' => 'C:/bat/ConvertLog/batResult/' . $cityCode . '_' . $fileNameOfLod . $extensionOfLod , 
            'convertResultFilePath' => 'C:/bat/ConvertLog/convertResult/' . $cityCode . '_' . $fileNameOfLod . $extensionOfLod ,
            'fileType' => $varietyOfFiletype,
            'fileName' => $fileNameOfLod,
        );
        return $convertInfo;
    }
?>