<!DOCTYPE html>
<STYLE type="text/css">

.table{
    border-collapse:collapse;
}
.table th, .table td{
    border:1px solid #0094ff;
}

.table th{
    background-color:#ADD8FF;
    padding:10px;
}
.table td{
    background-color:#d1f5f5;
    padding:5px;
}
.file{
    width:800px;
}
.size{
    width:100px;
}
.date{
    width:180px;
}
.upResult{
    width:150px;
}
#fileSelect{
    display:none;
}
#selectLabel{
    display: inline-block;
    margin: 10px 50px 10px 0px;
    padding: 5px 10px;
    font-size: 1em;
    background-color: #1aa1ff;
    color: #FFF;
    cursor: pointer;
    border-radius: 10px;
    border: 0;
    transition: 0.3s;
    font-weight: bold;
}
#selectLabel:hover{
    background-color: #064fda;
}
#uploadButton{
    margin: 10px 10px;
    padding: 5px 10px;
    top: 20px;
    font-size: 1em;
    cursor: pointer;
    background-color: #1aa1ff;
    color: #FFF;
    border-radius: 10px;
    border: 100px;
    font-weight: bold;
    height:35px;
}
#uploadButton:hover{
    background-color: #064fda;
}
div.btnLeft{
	    text-align: left;
	    float: left;
}
div.btnRight{
    	text-align: right;
    	width:1315px;
    	padding-top:30px;
}
.cb{
  /* floatを解除 */
  clear: both;
}
.flex{
    display: flex;
    margin:30px 10px -50px 10px
}
p.filename {
  word-break: break-all;
  width: 800px;
  margin:0;
}
</style>


<?php
    ini_set('display_errors', 0);
    //直リンクでアクセスした場合はワードプレスにリダイレクト
    if(!isset($_SERVER["HTTP_REFERER"])){
        header('Location:https://*****.com/UDX/register');
    }
    
    session_start(); //セッションを開始

    if(!isset($_SESSION["cityCode"])){ //issetでセッションを確認
        $cityCode = $_POST["cityCode"];
        $_SESSION["cityCode"]=$cityCode; //セッションにkeyとvalueをセット
    }else{
        if(isset($_POST["cityCode"])){
            $cityCode =$_POST["cityCode"]; //再アクセス時
        }else{
            $cityCode = $_SESSION["cityCode"]; //再アクセス時
            echo '読み込み中です。 しばらくお待ちください';
            return;
        }
        
    }
    
    include_once("logger.php"); //ログ出力クラスを取得
    include_once("config.php"); //ログ出力用コンフィグクラスを取得
    $log = Logger::getInstance();//ログ出力クラスのインスタンス生成
    include_once("dbConnection.php"); //DB接続情報の読み込み
    
    //
    if(isset($_POST["cityCode"])){
        $log->info('登録画面表示開始',$cityCode);
    }
    
    $status ='2';  //アップロードエラー
    db ("UPDATE public.manage_regist_zip SET status = '" . $status  ."' where userid = '" .$cityCode . "' and status = '1'");//DBへの格納
    $log->info('初期表示時のアップロード開始中ステータスを全て' . $status . 'に更新',$cityCode);
    $log->info('自治体コード:' . $cityCode ,$cityCode);
    //登録系のHTML要素を出力
    echo '<div class="btnLeft">';
    echo '<div class="flex">';
    echo '<form method="post" id="uploadForm"  action="upload.php" enctype="multipart/form-data">';
    echo '<input type="hidden" name="MAX_FILE_SIZE" value="2147483648">';
    echo '<label for="fileSelect" id="selectLabel">ファイル選択</label>';
    echo '<p><input type="file" id="fileSelect" name="toUploadFile[]" value="ファイル選択" onclick="onClickFileSelect(this)"  onchange="selectFiles(this);" accept="application/x-zip-compressed,.gml"  multiple/></p>';
    echo '</form>';     
    echo '<input type="button" id="uploadButton" value="登録" onclick="onClickUpload()">';
    echo '</div></div>';
    
    //ここからアップロード済ファイルサイズの取得
    $getTotalSizePath = 'F:\DATA/' . $cityCode . '/OriginalData/';
    //COMオブジェクト生成
    $obj = new COM ( 'scripting.filesystemobject' );
    if(is_object($obj)){
        //フォルダ情報取得
        $ref = $obj->getfolder ( $getTotalSizePath );
        echo '<div class="btnRight">';
        //フォルダ合計サイズ取得
        $totalSize = $ref->size;
        
        //バイトで取得されるので単位を付与
        if(empty($totalSize) === false){
            switch($totalSize){
                case ($totalSize >= (1024 * 1024 * 1024)):
                    echo '<p id="sizeDisplay">現在の使用容量：' . number_format($totalSize / (1024 * 1024 * 1024), 1) . 'GB / 5GB';
                    break;
                case ($totalSize >= 1024 * 1024):
                    echo '<p id="sizeDisplay">現在の使用容量：' . number_format($totalSize / (1024 * 1024), 1) . 'MB / 5GB';
                    break;
                case ($totalSize >= 1024):
                    echo '<p id="sizeDisplay">現在の使用容量：' . number_format($totalSize / 1024, 1) . 'KB / 5GB';
                    break;
                case ($totalSize >= 1):
                    echo '<p id="sizeDisplay">現在の使用容量：' . $totalSize . 'Byte / 5GB';
                    break;
                default:
                    echo '<p id="sizeDisplay">現在の使用容量：0Byte / 5GB';
                    break;
            }
        } else {
            echo '<p id="sizeDisplay">現在の使用容量：0Byte / 5GB';
        }
        echo '</p>';
        $obj = null;
    } else {
        echo 'ファイル容量取得エラー';
    }
    echo '</div>';
    echo '<div class="cb"></div>';
    //ここまでアップロード済ファイルサイズの取得
    
    //テーブル生成
    echo '<TABLE id="selectFileTable" class="table"><TH class="file">ファイル名</TH><TH  class="size">データ容量</TH><TH  class="date">更新日時</TH><TH  class="upResult">アップロード結果</TH>';
    echo '<TR><TD>ファイルを選択してください</TD><TD>　</TD><TD>　</TD><TD>　</TD></TR>';
    echo '</TABLE>';    


    echo '<br/><br/>';
    
    if(isset($_POST["cityCode"])){
        $log->info('登録画面表示終了',$cityCode);
    }
?>

<!--ここからスクリプト-->

<script type="text/javascript">
    //アップロードを試行したかを持つグローバル変数
    var glovalUploaded = false,
        maxFileCount = 100,//最大ファイル選択数
        maxFileSize = 2 * 1024 * 1024 * 1024, //最大ファイルサイズ 2GB
        totalMaxFileSize = 2 * 1024 * 1024 * 1024, //合計最大ファイルサイズ 2GB
        maxFileSizeString = "2GB", //最大ファイルサイズの表示用文字列
        totalMaxFileSizeString = "2GB"; //合計最大ファイルサイズの表示用文字列
    
    function onClickFileSelect(evt){
        postLog(window.parent.governmment_id, "info", "ファイル選択ボタン押下");
    }
    
    //ファイル選択ダイアログで選択した後の処理を行う関数
    function selectFiles(inputElement) {
        var fileList = inputElement.files,
            fileCount = fileList.length, //選択されたファイル数
            selectFileTable = document.getElementById("selectFileTable"),//選択したファイルを表示する表を取得
            i = 0,
            sizeOverFlg = false,
            sizeOverFileNames = "",
            sizeOverCount = 0,
            totalSize = 0;

        //表を一度初期化する
        selectFileTableReset();

        // ファイル数チェック
        if (fileCount > maxFileCount) {
            formReset("uploadForm");
            alert("選択できるファイルは" + maxFileCount + "個までです。 再度選択してください");
            postLog(window.parent.governmment_id, "warn", "ファイル最大選択数超過");
            return;
        }
                
        //選択した各ファイルを処理
        for (i = 0; i < fileCount; i++) {
            var file = fileList[i],
                size = file.size,
                type = file.type,
                name = file.name,
                updateDateEpoc = file.lastModified;
                
                totalSize += size;
                
                if(i > 0){
                    let newRow = selectFileTable.insertRow();
                    let newCell = newRow.insertCell();
                    newCell = newRow.insertCell();
                    newCell = newRow.insertCell();
                    newCell = newRow.insertCell();
                }
                
                
            // zipやgml以外のファイルが選択されていた場合は再選択を促す
            if (type != "application/x-zip-compressed" && name.endsWith(".gml") === false) {
                formReset("uploadForm");
                selectFileTableReset();
                alert("cityGMLファイル以外のファイルが選択されています。 再度選択してください。");
                postLog(window.parent.governmment_id, "warn", "非対応の拡張子を選択");
                return;
            }

            //拡張子用のピリオドが1つまでであることの確認
            var tempName = name.split(".");
            if(tempName.length !== 2){
                postLog(window.parent.governmment_id,'warn',　'ファイル名にピリオドは1つまでです：ファイル名:' + name);
                formReset("uploadForm");
                selectFileTableReset();
                alert("命名規則に従っていないファイルが選択されました。 再度選択してください。");
                return;
            }

            //"_"により3つか4つに分かれていることの確認
            tempName = tempName[0].split("_");         
            if(tempName.length !== 3 && tempName.length !== 4){
                postLog(window.parent.governmment_id,'warn',　'ファイル名を"_"で分割した結果、要素数が3でも4でもありませんでした。ファイル名:' + name);
                formReset("uploadForm");
                selectFileTableReset();
                alert("命名規則に従っていないファイルが選択されました。 再度選択してください。");
                return;
            }

            //メッシュコードの確認
            var regexp = new RegExp(/^[0-9]*$/);
            if(regexp.test(tempName[0]) === false || tempName[0].length > 11 || tempName[0].length === 0){
                //半角数字以外が含まれているか、長さが11バイトを超えているまたは0の場合
                postLog(window.parent.governmment_id,'warn',　'標準メッシュコードの形式が正しくありませんでした。ファイル名:' + name);
                formReset("uploadForm");
                selectFileTableReset();
                alert("命名規則に従っていないファイルが選択されました。 再度選択してください。");
                return;
            }

            //空間参照系の確認
            if(tempName[2] !== "6668" && tempName[2] !== "6697"){
                postLog(window.parent.governmment_id,'warn',　'空間参照系が"6668"でも"6697"でもありませんでした。ファイル名:' + name);
                formReset("uploadForm");
                selectFileTableReset();
                alert("命名規則に従っていないファイルが選択されました。 再度選択してください。");
                return;
            }     

            //追加の識別子が存在する場合は識別子を確認
            if(tempName.length === 4){
                regexp = new RegExp(/^[0-9a-zA-Z-]*$/);
                if(regexp.test(tempName[3]) === false || tempName[3].length === 0){
                    //「半角英数字と半角ハイフン」以外の文字が含まれているか、長さが0の場合
                    postLog(window.parent.governmment_id,'warn',　'追加の識別子に半角英数字と半角ハイフン以外の文字が入力されたか、何も入力されていません。ファイル名:' + name);
                    formReset("uploadForm");
                    selectFileTableReset();
                    alert("命名規則に従っていないファイルが選択されました。 再度選択してください。");
                    return;
                }
            }

            //ファイル名に空白が含まれていないことを確認する
            if(name.indexOf(" ") !== -1){
                postLog(window.parent.governmment_id,'warn',　'ファイル名に空白が含まれています。ファイル名:' + name);
                formReset("uploadForm");
                selectFileTableReset();
                alert("命名規則に従っていないファイルが選択されました。 再度選択してください。");
                return;
            }

            //ファイル名に括弧が含まれていないことを確認する
            if(name.indexOf("(") !== -1 || name.indexOf(")") !== -1){
                postLog(window.parent.governmment_id,'warn',　'ファイル名に括弧が含まれています。ファイル名:' + name);
                formReset("uploadForm");
                selectFileTableReset();
                alert("命名規則に従っていないファイルが選択されました。 再度選択してください。");
                return;
            }

            
            //地物タイプの確認
            switch(tempName[1]){
                case "bldg" :
                case "tran" :
                case "luse" :
                case "fld" :
                case "tnm" :
                case "lsld" :
                case "urf" :
                case "dem" :
                //以下は仕様書に記載されていないCityGMlとiUR定義のもの
                case "frn" :
                case "grp" :
                case "gen" :
                case "tun" :
                case "veg" :
                case "wtr" :
                case "uro" :
                case "urt" :
                case "urg" :
                    break;
                default :
                    postLog(window.parent.governmment_id,'warn',　'地物を示す接頭辞が正しくありません。ファイル名:' + name);
                    formReset("uploadForm");
                    selectFileTableReset();
                    alert("命名規則に従っていないファイルが選択されました。 再度選択してください。");
                    return;
            }
            

            // ファイルサイズが規定内であることを確認する
            if (size > maxFileSize) {
                sizeOverFlg = true;
                sizeOverCount++;
            }
            
            //表の1列目にファイル名を設定
            selectFileTable.rows[i + 1].cells[0].innerHTML = "<p class=\"filename\">" + name + "</p>";

            //ファイルサイズ加工
            var sizeString = "";
            //KBあるいはByteの場合
            sizeString = Math.round(size / 1024).toLocaleString("ja-JP")+ "KB";

            //表の2列目にファイルサイズを設定
            selectFileTable.rows[i + 1].cells[1].innerText = sizeString;
            selectFileTable.rows[i + 1].cells[1].align = "right";

            //更新日時を加工
            var updateDate = new Date(updateDateEpoc);
            var year = updateDate.getFullYear(),
                month = ("0" + (updateDate.getMonth() + 1)).slice(-2),
                day = ("0" + updateDate.getDate()).slice(-2),
                hours = ("0" + updateDate.getHours()).slice(-2),
                minutes = ("0" + updateDate.getMinutes()).slice(-2),
                seconds = ("0" + updateDate.getSeconds()).slice(-2);

            //表の3列目に更新日時を設定
            selectFileTable.rows[i + 1].cells[2].innerText = year + "/" + month + "/" + day + " " + hours + ":" + minutes + ":" + seconds;

            //表の4列目にアップロード状態を設定
            selectFileTable.rows[i + 1].cells[3].innerText = "未アップロード";
        }

        // ファイルサイズ制限を超えたものがあれば
        if (sizeOverFlg == true) {
            formReset("uploadForm");
            selectFileTableReset();
            alert(maxFileSizeString + "を超えたファイルが" + sizeOverCount + "個選択されました。 再度選択してください");
            postLog(window.parent.governmment_id, "warn", "単一ファイルサイズ制限超過");
            return;
        }
        
        // ファイルサイズ制限を超えたものがあれば
        if (totalSize > totalMaxFileSize) {
            formReset("uploadForm");
            selectFileTableReset();
            alert("ファイルの合計サイズが" + totalMaxFileSizeString + "を超えています。 再度選択してください");
            postLog(window.parent.governmment_id, "warn", "ファイルの合計サイズ超過");
            return;
        }
        glovalUploaded = false;
    }

    //アップロード用のファイル一覧をリセットする関数
    function selectFileTableReset() {
        var selectFileTable = document.getElementById("selectFileTable");
        var fileTableRowCount = selectFileTable.rows.length - 1; //ヘッダ行を除いた表の行数
        var fileTableColumnCount = 4; //表の列数
        var defaultString = "ファイルを選択してください";
        
        //2行目以降を削除する
        for (var i = 0; i < fileTableRowCount; i++) {
            if(selectFileTable.rows.length > 2){
                selectFileTable.deleteRow(fileTableRowCount - 1 - i);
            }
        }
        
        //1行目をリセットする
        if(fileTableRowCount > 0){
            for (var j = 0; j < fileTableColumnCount; j++) {
                if (j == 0) {                //一番左上のセルに初期表示用の文字列を入れる
                    selectFileTable.rows[1].cells[j].innerText = defaultString;
                } else {
                    //それ以外は空白を入れる
                    selectFileTable.rows[1].cells[j].innerText = "　";
                }
            }
        }
    }



    /* 渡されたIDを持つフォームをリセットする関数
        formId  ：リセット対象フォームのID
    */
    function formReset(formId) {
        document.getElementById(formId).reset();
        glovalUploaded = false;
    }

    function onClickUpload(){
        //早めに画面をロック
        screenLock();
        setTimeout(upload, 500);
        
        var display = window.parent.document.getElementById("wpadminbar");
        display.style.display = "none";
    }
     



    //ファイルをアップロードする関数
    function upload(event) {
        
        var data = new FormData();
        var xhrData = new XMLHttpRequest();
        var toOverWriteFileNameList = [];
        var fileInput = document.querySelector("#fileSelect");
        var cancelFlg = false;
        var cityCode;
        

        
        if (window.self == window.parent) {
            //iframeで読み込まれていない場合は固定値
            cityCode = "001";
        } else {
            //iframeで読み込まれている場合はID取得
            cityCode = window.parent.governmment_id;
        }
        if (glovalUploaded == true) {
            alert("連続してアップロードできません。ファイルを再選択してください");
            //ロック用divを削除
            delete_dom_obj("screenLock");
            postLog(window.parent.governmment_id, "warn", "連続アップロード試行");
            return;
        }

        //ファイルが選択されていなければ選択を促す
        if (fileInput.files.length == 0) {
            alert("ファイルが選択されていません");
            //ロック用divを削除
            delete_dom_obj("screenLock");
            postLog(window.parent.governmment_id, "warn", "ファイル未選択");
            return;
        }
        
        //登録確認
        if (window.confirm(fileInput.files.length + "個のファイルを登録します。よろしいですか？") == false) {
            //ロック用divを削除
            delete_dom_obj("screenLock");
            postLog(window.parent.governmment_id, "info", "登録確認ダイアログでキャンセルを押下");
            return;
        }
        
        
        //アップロードするファイル名の配列を生成する
        //と同時に合計サイズを取得する
        var uploadFileNameList = [];
        var toUploadTotalSize = 0;
        for(var i =0; i < fileInput.files.length; i++){
            uploadFileNameList.push(fileInput.files[i]["name"]);
            toUploadTotalSize += fileInput.files[i]["size"];
        }
        data.append("uploadFileNameList",JSON.stringify(uploadFileNameList));
        

        //ステータス変更時の動作を規定
        //まずはファイルが上書きされないか確認する
        xhrData.onreadystatechange = function () {

            switch (this.readyState) {
                case 0:
                case 1:
                case 2:
                case 3:
                    break;
                case 4:
                    if (this.status == 200) {
                        var resultArray = JSON.parse(xhrData.responseText);
                        
                        switch(resultArray["result"]){
                            case "OK" :
                                // 上書きされるファイルがないか確認
                                for (var i = 0; i < resultArray["fileName"].length; i++) {
                                    for (var j = 0; j < uploadFileNameList.length; j++) {
                                        //アップロード済ファイルに同名のものがあった場合ファイル名を取得してフラグを立てる
                                        if (resultArray["fileName"][i] == uploadFileNameList[j]) {
                                            //上書きファイルリストにファイル名を追加
                                            toOverWriteFileNameList.push(uploadFileNameList[j]);
                                        }
                                    }
                                }
                                //上書きフラグが立っていれば上書き確認を行う
                                
                                if(toOverWriteFileNameList.length > 0){
                                    if (window.confirm("同名ファイルが既に存在します。すべて上書きしてもよろしいですか？") == false) {
                                        postLog(window.parent.governmment_id, "info", "上書き確認ダイアログでキャンセルを押下");
                                        cancelFlg = true;
                                    }
                                }

                                break;
                            case "folderCapacityOver" :
                                alert("アップロード先の容量が足りません");
                                cancelFlg = true;
                                postLog(window.parent.governmment_id, "warn", "アップロード先容量不足");
                                break;
                            case "dataDriveCapacityOver" :
                                alert("データ領域の使用率が95%を超えています。 データ削除を実行してください。");
                                cancelFlg = true;
                                postLog(window.parent.governmment_id, "warn", "データドライブの使用率が95%を超えています");
                                break;
                            case "myJobIsActive" :
                                alert("既にアップロード処理を行っています。アップロード処理が終わるまでお待ちください。");
                                cancelFlg = true;
                                postLog(window.parent.governmment_id, "warn", "アップロード処理実行中");
                                break;
                            case "activeJobCountOver" :
                                alert("他に" + resultArray["activeValidateJobCount"] + "人のユーザがアップロード処理を行っています。　他のユーザのアップロード処理が終了するまでお待ちください");
                                cancelFlg = true;
                                postLog(window.parent.governmment_id, "warn", "アップロード実行ユーザ数上限超過");
                                break;
                            case "fileIsUsed" :
                                alert("他に検証・変換中のファイルと同名のファイルはアップロードできません");
                                cancelFlg = true;
                                postLog(window.parent.governmment_id, "warn", "処理中のファイルと同名ファイルアップロード試行");
                                break;
                            default:
                                cancelFlg = true;
                                postLog(window.parent.governmment_id, "error", "getfilelist.phpのレスポンステキストが正しくない");
                                break;
                        }
                    } else {
                        //エラー時の処理
                        console.log("受信失敗(アップロード済ファイル取得処理)　ステータス：" + xhrData.statusText);
                    }
                    break;
            }
        }

        data.append("cityCode", cityCode);
        data.append("toUploadTotalSize", toUploadTotalSize.toString(10));

        xhrData.open("POST", "getFilelist.php", false);
        xhrData.send(data);
        // キャンセルフラグが立っていれば処理を中断する
        if (cancelFlg == true) {
            //ロック用divを削除
            delete_dom_obj("screenLock");
            return;
        }



        //アップロード前にDBのステータスをアップロード開始に変更する
        var uploadStartFormData = new FormData();
        uploadStartFormData.append("uploadFileNameList", JSON.stringify(uploadFileNameList));
        uploadStartFormData.append("cityCode", cityCode);

        var uploadStartXhr = new XMLHttpRequest();

        //ステータス変更時の動作を規定
        uploadStartXhr.onreadystatechange = function () {

            switch (this.readyState) {
                case 0:
                case 1:
                case 2:
                case 3:
                    break;
                case 4:
                    if (this.status == 200) {
                    } else {
                        //エラー時の処理
                        console.log("受信失敗(ステータス更新処理)　ステータス：" + uploadStartXhr.statusText);
                    }
                    break;
            }
        }

        uploadStartXhr.open("POST", "uploadStart.php", false);
        try{
            uploadStartXhr.send(uploadStartFormData);
        } catch(error){
            postLog(window.parent.governmment_id, "error", "アップロード対象のファイルが見つからないなどの理由でエラーが発生しました");
            alert("アップロード対象のファイルが見つからないなどの理由でエラーが発生しました");
            delete_dom_obj("screenLock");
            return;
        } 
        

        //ここから実際のアップロード
        var uploadFormData = new FormData(document.getElementById("uploadForm"));
        uploadFormData.append("cityCode", cityCode);

        var xhr = new XMLHttpRequest();

        //ステータス変更時の動作を規定
        xhr.onreadystatechange = function () {
            var responseJson;

            switch (this.readyState) {
                case 0:
                case 1:
                case 2:
                case 3:
                    break;
                case 4:
                    if (this.status == 200) {
                        //レスポンスはJson文字列で返ってくる
                        responseJson = JSON.parse(xhr.responseText);
                        
                        //アップロード後のフォルダサイズを画面に反映させる
                        var sizeDisplay = document.getElementById("sizeDisplay");
                        sizeDisplay.innerText = responseJson[0].size;
                        
                        //アップロード結果を表示する
                        displayUploadResult(responseJson[0].resultArray);
                         //アップロード試行済みとする
                        glovalUploaded = true;
                        //ロック用divを削除
                        delete_dom_obj("screenLock");

                    } else {
                        console.log("受信失敗(アップロード処理)　ステータス：" + xhr.statusText);
                    }
                    break;
            }
        }

        //表示をアップロード中に変更する
        tableChange("selectFileTable", "アップロード待機中", 3, true);
        //プログレスパーを作成
        createProgress();
        
        xhr.open("POST", "upload.php", true);

        xhr.upload.addEventListener("progress", function(event){
            //POSTの進行度を取得して％に変換する
            var percent = (event.loaded / event.total * 100).toFixed(1);
            if(percent == 100){
                //アップロードが完了したら一時フォルダから本来のアップロード先にファイルが置かれるのを待つ
                window.parent.document.getElementById("progressLabel").innerText = "アップロードしたファイルを移動しています\nページを閉じずにそのままお待ちください"
                window.parent.document.getElementById("progressBar").removeAttribute("value");
            }else{
                //アップロード未完了であれば進行度を更新
                window.parent.document.getElementById("progressBar").value = percent;
            }
        }); 
        xhr.send(uploadFormData);
    }


    /*アップロード結果を表に表示する関数
    nameAndResult       ：配列の中にファイル名とアップロード結果を持つ配列が入った二次元配列
    */
    function displayUploadResult(nameAndResult) {
        var selectFileTable = document.getElementById("selectFileTable");
        var fileNameColumn = 0; //ファイル名列番号(1列目)
        var resultColumn = 3; //アップロード結果列番号(4列目)
        var fileTableRowCount = selectFileTable.rows.length - 1; //ヘッダ行を除いた表の行数

        //ファイル名が合致する行のアップロード結果列にアップロード結果を入れる
        for (var i = 0; i < fileTableRowCount; i++) {
            if (selectFileTable.rows[i + 1].cells[fileNameColumn].innerText == nameAndResult[i][0]) {
                selectFileTable.rows[i + 1].cells[resultColumn].innerText = nameAndResult[i][1];
            }
        }
        formReset("uploadForm");

    }


    /* tableの指定列を書き換える関数
    tableId             ：テーブルのID
    overWriteString     ：上書きする文字列
    columnNumber        ：上書き対象の列番号
    needCheck           ：trueならファイル名に".zip"や".gml"が含まれている列のみ書き換える
    */
    function tableChange(tableId, overWriteString, columnNumber, needCheck) {
        var table = document.getElementById(tableId);
        var fileNameColumn = 0; //ファイル名の列番号
        var fileTableRowCount = table.rows.length - 1; //ヘッダ行を除いた表の行数

        for (var i = 0; i < fileTableRowCount; i++) {
            if (needCheck == true) {
                if (table.rows[i + 1].cells[fileNameColumn].innerText.endsWith(".zip") == false
                        || table.rows[i + 1].cells[fileNameColumn].innerText.endsWith(".gml") == false) {
                    break;
                }
            }
            table.rows[i + 1].cells[columnNumber].innerText = overWriteString;
        }
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
        
    }
    
    //プログレスバーを生成してスクリーンロックに追加
    function createProgress(){
        var divElement = document.createElement('div');
        divElement.id = "progressDiv";
        
        divElement.style.width = "550px";
        divElement.style.height = "200px";
        divElement.style.position = 'absolute';
        divElement.style.top =  "49%";
        divElement.style.left =  "49%";
        divElement.style.transform = "translate(-50%, -50%)";
        divElement.style.zIndex = '9999';
                
        // プログレスバーを生成
        var progressElement = document.createElement('progress'); 
        progressElement.id = "progressBar";
        
        progressElement.max = "100";
        progressElement.value = "0";
        
        
        // プログレスバーのスタイル設定
        progressElement.style.width = "500px";
        progressElement.style.height = "40px";
        //表示場所設定
        progressElement.style.zIndex = '9999';
        
        //プログレスバー用のラベル設定
        var labelElement = document.createElement('label');
        labelElement.id = "progressLabel";
        labelElement.setAttribute('for', 'progressBar');
        labelElement.appendChild(document.createTextNode("アップロード進行中\nページを閉じずにそのままお待ちください"));
        labelElement.style.color =  "white";
        labelElement.style.fontSize = "25px";
        labelElement.style.whiteSpace =  "pre";
        labelElement.style.textShadow = "0 0 7px black";
        //表示場所設定
        labelElement.style.zIndex = '9999';
        
        
        //画面ロックオブジェクトにプログレスバー追加
        var screenLockElement = window.parent.document.getElementById("screenLock");
        divElement.appendChild(labelElement);
        divElement.appendChild(progressElement);
        screenLockElement.appendChild(divElement);
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

        xhrPostLog.open("POST", "postLog.php");
        xhrPostLog.send(logData);
    }
</script>
