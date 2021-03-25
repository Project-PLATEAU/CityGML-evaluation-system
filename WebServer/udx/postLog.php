<?php
    try{
        include_once("logger.php"); //ログ出力処理の読み込み
        include_once("config.php"); //ログ出力用のコンフィグ読み込み
        //ログ書き込み処理
        $log = Logger::getInstance();
        
        if(isset($_POST["cityCode"]) == true && isset($_POST["type"]) == true && isset($_POST["message"]) == true){
            //自治体IDを確認
            $cityCode = (string) $_POST["cityCode"];
            $message = (string) $_POST["message"];
            
            switch ($_POST["type"]) {
                    case "info":
                        $log->info($message,$cityCode);
                        break;
                    case "error":
                        $log->error($message,$cityCode);
                        break;
                    case "warn":
                        $log->warn($message,$cityCode);
                        break;
                    default:
                        $log->debug($message,$cityCode);
                        break;
                    
            }
        }
    } catch(Exception $ex){
        $log->error('ログの出力に失敗しました。'. $ex ,$cityCode);
    }
?>
