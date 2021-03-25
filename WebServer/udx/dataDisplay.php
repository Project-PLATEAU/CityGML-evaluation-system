<!DOCTYPE html>
<STYLE type="text/css">
.table{
    border-collapse:collapse;
    margin-left: auto;
    margin-bottom: 10px;
    float: top;
}
.table td{
    border:1px solid black;
    padding:3px;
    height:25px;
}
.description{
    width:200px;
}
.URL{
    width:1000px;
}

.btnLeft{
    text-align: left;
	float: left;
}
#content .container{
 margin-left:0
 margin-right:0
}
#fullScreenButton{
    margin: 10px 50px 10px 0px;
    padding: 5px 10px;
    top: 5px;
    font-size: 1em;
    cursor: pointer;
    background-color: #1aa1ff;
    color: #FFF;
    border-radius: 10px;
    border: 100px;
    font-weight: bold;
}
#releaseButton{
    margin: 10px 0;
    padding: 5px 10px;
    top: 5px;
    font-size: 1em;
    cursor: pointer;
    background-color: #1aa1ff;
    color: #FFF;
    border-radius: 10px;
    border: 100px;
    font-weight: bold;
}
#releaseStopButton{
    margin: 10px 0;
    padding: 5px 10px;
    top: 5px;
    font-size: 1em;
    cursor: pointer;
    background-color: #1aa1ff;
    color: #FFF;
    border-radius: 10px;
    border: 100px;
    font-weight: bold;
}
</style>

<?php
    session_start(); //セッションを開始

    if(!isset($_SESSION["cityCode"])){ //issetでセッションを確認
        $cityCode = $_POST["cityCode"];
        $_SESSION["cityCode"] = $cityCode; //セッションにkeyとvalueをセット
    }else{
        if(isset($_POST["cityCode"])){
            $cityCode =  $_POST["cityCode"]; //再アクセス時
        }else{
            $cityCode =  $_SESSION["cityCode"]; //再アクセス時
            echo '読み込み中です。　しばらくお待ちください。';
            return;
        }
    }
    
    include_once("logger.php"); //ログ出力処理の読み込み
    include_once("config.php"); //ログ出力用のコンフィグ読み込み
    //ログ書き込み処理
    $log = Logger::getInstance();
    
    if(preg_match('#/UDX/datadisplay/\z#' , $_SERVER["HTTP_REFERER"]) === 1){
        $log->info('3D Tiles閲覧・配信画面表示',$cityCode);
    }else{
        $log->info('位置正確度検証画面表示',$cityCode);
    }
    
	$link = 'https://*****.com/map/' . htmlspecialchars($cityCode, ENT_QUOTES, 'UTF-8') . '/private';

    $replace = '<iframe id="inlineFrameMap" title="Map" allow="fullscreen" width="100%" height="950" min-height="1000" margin-left="0" margin-right="0" src="' .  $link .'"></iframe>';
    
    echo '<div class="btnLeft">';
    echo '<input type="button" id="fullScreenButton" value="全画面表示" onclick="onClickFullScreen()" >';
    echo '　<input type="button" id="releaseButton" value="配信" onclick="onClick3DTilesRelease()"/>　';
    echo '<input type="button" id="releaseStopButton" value="配信停止" onclick="onClick3DTilesReleaseStop()"/>　';
    echo '</div>';

    //ロックファイル名のフルパス
    $lockFilePath = "*****:/*****/htdocs/iUR_Data/" .$cityCode. "/3DTiles/3DBuildings/lock.txt";

    //エラーファイル名のフルパス
    $errorFilePath = "*****:/*****/htdocs/iUR_Data/" .$cityCode. "/3DTiles/3DBuildings/error.txt";

    $outputedZipFile = glob("*****:/*****/htdocs/iUR_Data/" .$cityCode. "/3DTiles/3DBuildings/*.zip");

    echo '<TABLE class="table"><TR><TD class="description">公開用WebGISのURL</TD><TD class="URL">';

    //配信状況の確認
    if(file_exists($errorFilePath) === true){
        //エラーファイルが存在した場合
        $webGISURL ='';
        $downloadURL = '配信エラーが発生しました。再度配信を行っていただくか、管理者に連絡してください。';
    } else if(file_exists($lockFilePath) === true){
        //ロックファイルが存在した場合
        $webGISURL ='配信処理中';
        $downloadURL = '配信処理中';
    } elseif(count($outputedZipFile) === 1){
        //配信済のZIPが存在した場合
        $tempWebGISURL ="https://*****.com/map/" . $cityCode . "/public";
        $webGISURL = "<a href='$tempWebGISURL' target='_blank' rel='noopener noreferrer'>" . $tempWebGISURL . "</a>";
        
        $tempDownloadURL = "https://*****.com/iUR_DATA/" . $cityCode . "/3DTiles/3DBuildings/" . basename($outputedZipFile["0"]);
        $downloadURL = "<a href='$tempDownloadURL'>" . $tempDownloadURL . "</a>";
    } else {
        //その他の場合
        $webGISURL ='3D Tiles未配信';
        $downloadURL = '3D Tiles未配信';
    }

    echo $webGISURL;
    echo '</TD></TR><TR><TD>3D TilesダウンロードURL</TD><TD>';
    echo $downloadURL;
    echo '</TD></TR></TABLE>';

    if(is_numeric(htmlspecialchars($cityCode, ENT_QUOTES, 'UTF-8'))){
        echo $replace;
    }
    echo '<script type="text/javascript">
        function onClickFullScreen(){
            var mapElement = document.getElementById("inlineFrameMap");
            mapElement.requestFullscreen();
        }
    </script>';


?>

<script type="text/javascript">
    var cityCode = window.parent.governmment_id;

    function onClick3DTilesRelease(){
        postLog(cityCode,'info','3DTiles配信ボタン押下');
        if(confirm("3D Tiles配信を開始します。\r\nよろしいですか？") === true){
            postLog(cityCode,'info','3DTiles配信確認ダイアログでOK押下');
        } else {
            postLog(cityCode,'info','3DTiles配信確認ダイアログでキャンセル押下');
            return;
        }

        var lockCheckXhr = new XMLHttpRequest();        
        var lockCheckData = new FormData();
        lockCheckData.append("cityCode", cityCode);
        var cancelFlag = false;

        lockCheckXhr.onreadystatechange = function () {
            //正常にレスポンスが返ってきたらレスポンステキストを表示
            switch (this.readyState) {
                case 0:
                case 1:
                case 2:
                case 3:
                    break;
                case 4:
                    if (this.status == 200) {
                        var response = JSON.parse(lockCheckXhr.responseText);
                        switch(response["result"]){
                            case "Locked" :
                                alert("前回の配信処理が完了していません。\r\n配信処理終了までお待ちください。");
                                cancelFlag = true;
                                break;
                            case　"myConvertJobIsActive" :
                                alert("変換処理中は3D Tiles配信はできません。\r\n変換処理終了までお待ちください。");
                                cancelFlag = true;
                                break;
                            case　"dataSourceDataIsEmpty" :
                                alert("3D Tilesファイルが存在しません。\r\nデータ変換を行ってください。");
                                cancelFlag = true;
                                break;
                            case "error" :
                                alert("システムエラーにより配信を開始できませんでした");
                                cancelFlag = true;
                                break;
                            case "notLocked" :
                                break;
                            default:
                                alert("システムエラーにより配信を開始できませんでした");
                                cancelFlag = true;
                                break;
                        }
                        
                    } else {
                        console.log("受信失敗　ステータス：" + lockCheckXhr.statusText);
                    }
                    break;
            }
        }
        
        lockCheckXhr.open("POST", "3DtilesLockCheck.php", false);
        lockCheckXhr.send(lockCheckData);

        if(cancelFlag === true){
            return;
        }

        var release3DTilesData = new FormData();
        release3DTilesData.append("cityCode", cityCode);
        var release3DTilesXhr = new XMLHttpRequest();
        
        release3DTilesXhr.open("POST", "3DtilesRelease.php", true);
        release3DTilesXhr.send(release3DTilesData);
        alert("3D Tiles配信処理を開始しました");
        location.reload();
    }

    //配信停止ボタン押下時処理
    function onClick3DTilesReleaseStop(){
        postLog(cityCode,'info','3DTiles配信停止ボタン押下');
        //確認ダイアログ表示
        if(confirm("3D Tiles配信を停止します。\r\nよろしいですか？") === false){
            postLog(cityCode,'info','3DTiles配信停止確認ダイアログでキャンセル押下');
            return;
        }
        postLog(cityCode,'info','3DTiles配信停止確認ダイアログでOK押下');
        //画面をロック
        screenLock();
        //配信停止処理を呼び出す
        setTimeout(tilesReleaseStop, 500);
    }

    //配信停止処理
    function tilesReleaseStop(){
        var preReleaseStopData = new FormData();
        preReleaseStopData.append("cityCode", cityCode);
        var preReleaseStopXhr = new XMLHttpRequest();
        var cancelFlag = false;
        
        //ステータス変更時の動作を規定
        preReleaseStopXhr.onreadystatechange = function () {
            //正常にレスポンスが返ってきたらレスポンステキストを表示
            switch (this.readyState) {
                case 0:
                case 1:
                case 2:
                case 3:
                    break;
                case 4:
                    if (this.status == 200) {
                        var response = JSON.parse(preReleaseStopXhr.responseText);
                        switch(response["result"]){
                            case "OK" :
                                break;
                            case "error" :
                                alert("3D Tilesの配信停止でエラーが発生しました。");
                                cancelFlag = true;
                                break;
                            case "catch" :
                                alert("3D Tilesの配信停止でエラーが発生しました。");
                                cancelFlag = true;
                                break;
                            case "Locked" :
                                alert("前回の配信処理が完了していません。\r\n配信処理終了までお待ちください。");
                                cancelFlag = true;
                                break;
                            case "notReleased" :
                                alert("3D Tilesは配信されていません。");
                                cancelFlag = true;
                                break;
                            case "errorFileIsExist" :
                                alert("配信エラーが発生しています。\r\n再度配信を行うか、管理者にお問い合わせください。");
                                cancelFlag = true;
                                break;
                            default:
                                postLog(cityCode,'error','3DTiles配信停止前確認処理でXHRのレスポンスが正常ではありませんでした');
                                alert("システムエラーが発生しました。\r\n管理者にお問い合わせください。");
                                cancelFlag = true;
                                break;
                        }
                        
                    } else {
                        console.log("受信失敗　ステータス：" + preReleaseStopXhr.statusText);
                    }
                    delete_dom_obj("screenLock");
                    break;
            }
        }
        
        preReleaseStopXhr.open("POST", "pre3DtilesReleaseStop.php", false);
        preReleaseStopXhr.send(preReleaseStopData);
        
        if(cancelFlag === true){
            delete_dom_obj("screenLock");
            return;
        }
        
        
        var releaseStopData = new FormData();
        releaseStopData.append("cityCode", cityCode);
        var releaseStopXhr = new XMLHttpRequest();

        //ステータス変更時の動作を規定
        releaseStopXhr.onreadystatechange = function () {
            //正常にレスポンスが返ってきたらレスポンステキストを表示
            switch (this.readyState) {
                case 0:
                case 1:
                case 2:
                case 3:
                    break;
                case 4:
                    if (this.status == 200) {
                        var response = JSON.parse(releaseStopXhr.responseText);
                        switch(response["result"]){
                            case "success" :
                                alert("3D Tilesの配信停止に成功しました。");
                                location.reload();
                                break;
                            case "error" :
                                alert("3D Tilesの配信停止でエラーが発生しました。");
                                break;
                            case "catch" :
                                alert("3D Tilesの配信停止でエラーが発生しました。");
                                break;
                            default:
                                break;
                        }
                        
                    } else {
                        console.log("受信失敗　ステータス：" + releaseStopXhr.statusText);
                    }
                    delete_dom_obj("screenLock");
                    break;
            }
        }
        alert("3D Tiles配信停止処理を開始しました");
        releaseStopXhr.open("POST", "3DtilesReleaseStop.php", false);
        releaseStopXhr.send(releaseStopData);
    }

    //画面ロック関数
    function screenLock(){
        // 画面ロック用のdivを生成
        var element = document.createElement('div'); 
        element.id = "screenLock"; 
        // 画面ロックのスタイル
        element.style.height = '100%'; 
        element.style.left = '0px'; 
        element.style.position = 'fixed';
        element.style.top = '0px';
        element.style.backgroundColor = "rgba(128,128,128, 0.5)";
        element.style.width = '100%';
        element.style.zIndex = '9998';
     
        var objBody = window.parent.document.getElementsByTagName("body").item(0); 
        objBody.appendChild(element);

        var display = window.parent.document.getElementById("wpadminbar");
        display.style.display = "none";
        
    }

    /*DOMオブジェクトの親からDOMオブジェクトを削除する処理
        id_name 削除したいDOMオブジェクトのID
    */
    function delete_dom_obj(id_name){
        var dom_obj = window.parent.document.getElementById(id_name);
        var dom_obj_parent = dom_obj.parentNode;
        dom_obj_parent.removeChild(dom_obj);
        var display = window.parent.document.getElementById("wpadminbar");
        display.style.display = "block";
    }
    
    /*ログ出力関数
        
    */
    function postLog(cityCode, type, message){
        var logData = new FormData();
            logData.append("cityCode", cityCode);
            logData.append("type", type);
            logData.append("message", message);

        var xhrPostLog = new XMLHttpRequest();

        //ステータス変更時の動作を規定
        xhrPostLog.onreadystatechange = function () {
            //正常にレスポンスが返ってきたらレスポンステキストを表示
            //postLog(window.parent.governmment_id,'info','ログ出力テスト');

            switch (this.readyState) {
                case 0:
                    break;
                case 1:
                    break;
                case 2:
                    break;
                case 3:
                    break;
                case 4:
                    if (this.status == 200) {
                    } else {
                    }
                    break;
            }
        }

        xhrPostLog.open("POST", "postLog.php");
        xhrPostLog.send(logData);
    }
</script>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title></title>
</head>
<body>

</body>
</html>