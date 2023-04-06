<?php
    try{
        include_once("logger.php"); //ログ出力処理の読み込み
        include_once("config.php"); //ログ出力用のコンフィグ読み込み
        //ログ書き込み処理
        $log = Logger::getInstance();
        if(isset($_POST["cityCode"]) === true){
            $cityCode = (string) $_POST["cityCode"];
            
            //3DTilesZipファイルの保存ディレクトリ
            $outputedZipFilePath = "*****:/*****/htdocs/iUR_Data/" .$cityCode. "/3DTiles/3DBuildings/";
            //テンプレートconfig.jsonのパス
            $configJsonPath = "*****:/*****/htdocs/map/" . $cityCode . "/private/ConfigJsonTemplate/config.json";
            //config.jsonのコピー先パス
            $copyConfigJsonPath = "*****:/*****/htdocs/map/" . $cityCode . "/public/config.json";
            //publicのdatasource-dataフォルダのパス
            $publicDataSourcePath = '*****:/*****/htdocs/map/' .$cityCode. '/public/datasource-data';

            //配信済みの圧縮された3DTilesを取得
            $outputedZipFile = glob($outputedZipFilePath . "*.zip");
            //正常に取得できたか確認
            if($outputedZipFile === false){
                $log->error('3DTilesZipファイルのglobに失敗しました',$cityCode);
                $returnArray = array(
                    'result' => 'error',
                );
                echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
                return;
            }

            $log->info('config.json初期化処理開始',$cityCode);

            //空の地図表示設定ファイルを公開用の地図フォルダに上書きコピー
            if(copy($configJsonPath, $copyConfigJsonPath) === true){
                //config.jsonコピー成功
                $log->info('config.json初期化処理成功',$cityCode);
            } else {
                //config.jsonコピー失敗
                $log->error('config.json初期化処理失敗',$cityCode);
            }

            
            //公開用地図に使用している3DTilesを削除する
            $log->info('公開用地図の3DTilesの削除開始',$cityCode);
            $errorFlag = false;
            $items = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($publicDataSourcePath, RecursiveDirectoryIterator::CURRENT_AS_SELF),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            //datasource-dataフォルダ配下の各フォルダ・ファイルに対し削除処理実行
            foreach($items as $item){
                //パスに'Terrain'が含まれていない場合のみ処理を行う
                if(strpos($item->getPathname(), 'public/datasource-data/Terrain/') === false && $item->getPathname() !== '*****:/*****/htdocs/map/' . $cityCode . '/public/datasource-data/Terrain' ){
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
            
            //正常に削除できたか確認
            if($errorFlag === false){
                $log->info('公開用地図の3DTilesの削除成功',$cityCode);
            }else{
                //エラー処理
                $log->error('公開用地図の3DTilesの削除失敗',$cityCode);
            }

            $log->info('圧縮済み3DTiles削除処理開始',$cityCode);

            //配信済みの圧縮された3DTilesを削除
            $deleteError = false; //削除エラーフラグ
            foreach($outputedZipFile as $filePath){
                if(unlink($filePath) === false){
                    $deleteError = true;
                }
            }

            //正常に削除できたか確認
            if($deleteError === true){
                $log->error('圧縮済み3DTiles削除処理失敗。',$cityCode);
                $returnArray = array(
                    'result' => 'error',
                );
                echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
            } else {
                $log->info('圧縮済み3DTiles削除処理成功。',$cityCode);
                $returnArray = array(
                    'result' => 'success',
                );
                echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
            }

            $log->info('3DTiles配信停止処理が終了しました。',$cityCode);

        } else {
            //POST情報不足
            $returnArray = array(
                'result' => 'error',
            );
            echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
        }

        
        
    } catch(Exception $ex){
        $returnArray = array(
            'result' => 'catch',
        );
        echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
        if(isset($cityCode) === true){
            $log->error('3DTiles配信停止処理に失敗しました。'. $ex ,$cityCode);
        }
    }
    

?>