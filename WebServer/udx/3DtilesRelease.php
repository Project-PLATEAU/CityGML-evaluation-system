<?php
    try{
        include_once("logger.php"); //ログ出力処理の読み込み
        include_once("config.php"); //ログ出力用のコンフィグ読み込み
        //ログ書き込み処理
        $log = Logger::getInstance();

        if(isset($_POST["cityCode"]) === true){
            $cityCode = basename((string) $_POST["cityCode"]);
            
            //Zipファイルの出力先ディレクトリ
            $outputZipFilePath = "*****:/*****/htdocs/iUR_Data/" .$cityCode. "/3DTiles/3DBuildings/";
            //変換後の3DTilesが保存されているディレクトリ
            $datasourceDataPath = "*****:/*****/htdocs/map/" . $cityCode . "/private/datasource-data/";
            //変換後の3DTilesのコピー先ディレクトリ
            $toCopyPath = "*****:/*****/htdocs/map/" . $cityCode . "/public/datasource-data/";
            //出力されるZipファイル名のフルパス
            $outputZipFileName = $outputZipFilePath . "3DTiles_" . date('Ymd_His', time() +32400) . ".zip";
            //ロックファイル名のフルパス
            $lockFilePath = "*****:/*****/htdocs/iUR_Data/" .$cityCode. "/3DTiles/3DBuildings/lock.txt";
            //config.jsonのパス
            $configJsonPath = "*****:/*****/htdocs/map/" . $cityCode . "/private/config.json";

            
            $log->info('3DTiles配信処理開始',$cityCode);

            //ロックファイルの生成を試みる
            $log->info('ロックファイル生成開始',$cityCode);
            if(file_put_contents($lockFilePath, "LockFile") === false){
                //ロックファイルの生成に失敗した場合
                $log->error('ロックファイルの作成に失敗しました',$cityCode);
                createErrorFile($cityCode);
                return;
            } else {
                ////ロックファイルの生成に成功した場合
                $log->info('ロックファイルの作成に成功しました',$cityCode);
            }



            //3DTilesをpublicにコピーする
            $log->info('3DTilesをpublicにコピー開始',$cityCode);
            
            //robocopy前にcityCodeの形式をチェックする
            //5桁の整数のみ許可する
            if(strlen($cityCode) === 5 && ctype_digit($cityCode) === true){
                // robocopyのミラーオプションを使用し、publicのdatasource-dataをprivateのdatasource-dataの内容に合わせる
                exec("cmd.exe /c robocopy $datasourceDataPath $toCopyPath /MIR /r:2 /w:10");
                $log->info('3DTilesをpublicにコピー終了',$cityCode);
            } else {
                $log->error('3DTilesをpublicへミラーリングする処理をスキップ',$cityCode);
                //エラーファイルの生成
                createErrorFile($cityCode);
                //ロックファイルの削除
                unlink($lockFilePath);
                return;
            }
            

            //config.jsonをpublicにコピーする
            $log->info('config.jsonをpublicにコピー開始',$cityCode);
            if(copy($configJsonPath, preg_replace("/private/", "public", $configJsonPath,1)) === true){
                //config.jsonコピー成功
                $log->info('config.jsonをpublicにコピー成功',$cityCode);
            } else {
                //config.jsonコピー失敗
                $log->error('config.jsonをpublicにコピー失敗',$cityCode);
                //エラーファイルの生成
                createErrorFile($cityCode);
                //ロックファイルの削除
                unlink($lockFilePath);
                return;
            }

            $log->info('既存の3DTiles圧縮ファイルの削除処理開始',$cityCode);

            $deleteError = false;
            foreach(glob($outputZipFilePath . "*.zip") as $filePath){
                if(unlink($filePath) === false){
                    $deleteError = true;
                } else {
                    
                }
            }

            if($deleteError === true){
                $log->error('既存の3DTiles圧縮ファイルの削除処理失敗。',$cityCode);
                //エラーファイルの生成
                createErrorFile($cityCode);
                //ロックファイルの削除
                unlink($lockFilePath);
                return;
            } else {
                $log->info('既存の3DTiles圧縮ファイルの削除処理成功。',$cityCode);
            }

            $log->info('3DTiles圧縮処理開始。',$cityCode);

            if(create3DTilesZip($datasourceDataPath, $outputZipFileName) === true){
                //Zip作成成功
                $log->info('3DTiles圧縮処理に成功',$cityCode);
            } else {
                //Zip作成失敗
                $log->error('3DTiles圧縮処理に失敗',$cityCode);
                //エラーファイルの生成
                createErrorFile($cityCode);
                //ロックファイルの削除
                unlink($lockFilePath);
                return;
            }

        } else {
            //POST情報不足
        }

        if(unlink($lockFilePath) === false){
            $log->error('ロックファイルの削除に失敗しました。手動でロックファイルを削除する必要があります。',$cityCode);
            //エラーファイル生成
            createErrorFile($cityCode);
            return;
        }

        $log->info('3DTiles配信処理が終了しました',$cityCode);
    } catch(Exception $ex){
        $returnArray = array(
            'result' => 'catch',
        );
        echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
        $log->error('3DTiles配信処理に失敗しました。'. $ex ,$cityCode);
        
    }
    
    //zipファイルを作成して保存する関数
    function create3DTilesZip($datasourceDataPath, $outputZipFileName){
        try{
            $zipArchive = new ZipArchive();
            $zipArchive->open($outputZipFileName, ZIPARCHIVE::CREATE);
            addToZip($zipArchive, $datasourceDataPath);
            $zipArchive->close();
      
            return true;//成功
        }catch (Exception $e) {
            return false;//失敗
        }
    }

    //与えられたディレクトリパス内のファイルを再帰的に圧縮対象に追加する関数
    //但し、【Terrain】フォルダは除外される
    function addToZip($zipArchive, $path, $parentPath = ''){
        $dh = opendir($path);
        while (($entry = readdir($dh)) !== false) {
            if ($entry == '.' || $entry == '..') {
                //エントリから「.」と「..」は除外する
            } elseif($entry === "Terrain") {
                //ディレクトリ名が"Terrain"なら圧縮対象としない
            } else {
                $localPath = $parentPath.$entry;
                $fullpath = $path.'/'.$entry;
                if (is_file($fullpath)) {
                    $zipArchive->addFile($fullpath, $localPath);
                } else if (is_dir($fullpath)) {
                    $zipArchive->addEmptyDir($localPath);
                    addToZip($zipArchive, $fullpath, $localPath.'/');
                }
            }
        }
        closedir($dh);
    }

    //エラーファイル生成関数
    function createErrorFile($cityCode){
        //エラーファイル名のフルパス
        $errorFilePath = "*****:/*****/htdocs/iUR_Data/" .$cityCode. "/3DTiles/3DBuildings/error.txt";
        //グローバルのログ出力インスタンス取得
        global $log;

        //エラーファイルの生成
        if(file_put_contents($errorFilePath, "ErrorFile") === false){
            //エラーファイルの生成に失敗した場合
            $log->error('エラーファイルの作成に失敗しました',$cityCode);
        } else {
            ////エラーファイルの生成に成功した場合
            $log->info('エラーファイルの作成に成功しました',$cityCode);
        }
    }
?>