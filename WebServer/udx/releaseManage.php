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
    padding:3px;
    height:25px;
}
.file{
    width:850px;
}
.url{
    width:400px;
}
.size{
    width:150px;
}
.date{
    width:200px;
}
.status{
    width:280px;
}
.check{
    width:7px;
}
.sortbtn_up{
    width:20px;
    height:20px;
    margin-left: 10px;
    font-size: 1em;
    background-color: #1aa1ff;
    color: #FFF;
    border-radius: 3px;
    border: 0;
    text-align:center;
    padding:0;
}
.sortbtn_up:hover{
    background-color:#1a6aff;
}
.sortbtn_down{
    width:20px;
    height:20px;
    margin: 1px;
    font-size: 1em;
    background-color: #1aa1ff;
    color: #FFF;
    border-radius: 3px;
    border: 0;
    text-align:center;
    padding:0;
}
.sortbtn_down:hover{
    background-color:#1a6aff;
}
#releaseStopButton{
    margin: 10px 0;
    padding: 5px 10px;
    top: 20px;
    font-size: 1em;
    cursor: pointer;
    background-color: #1aa1ff;
    color: #FFF;
    border-radius: 10px;
    border: 100px;
    font-weight: bold;
}
#releaseStopButton:hover{
    background-color:#1a6aff;
}
#allCheckButton{
    margin: 10px 0;
    padding: 5px 10px;
    top: 20px;
    font-size: 1em;
    cursor: pointer;
    background-color: #1aa1ff;
    color: #FFF;
    border-radius: 10px;
    border: 100px;
    font-weight: bold;
}
#allCheckButton:hover{
    background-color:#1a6aff;
}
#allUncheckButton{
    margin: 10px 50px 10px 0px;
    padding: 5px 10px;
    top: 20px;
    font-size: 1em;
    cursor: pointer;
    background-color: #1aa1ff;
    color: #FFF;
    border-radius: 10px;
    border: 100px;
    font-weight: bold;
}
#allUncheckButton:hover{
    background-color:#1a6aff;
}
div.btnLeft{
	    text-align: left;
	    float: left;
}
div.btnRight{
    	text-align: right;
    	width:1390px;
    	padding-top:30px;
}
.cb{
  /* floatを解除 */
  clear: both;
}
p.filename {
  word-break: break-all;
  margin:0;
  width:850px;
}
p.downloadURL{
    word-break: break-all;
    width:400px;
}
</style>
<?php
try{
    //直リンクでアクセスした場合はワードプレスにリダイレクト
    if(!isset($_SERVER["HTTP_REFERER"])){
    echo $_SERVER["REQUEST_URI"];
        //header('Location:https://*****.com/UDX/release_manage/');
    }
    
    include_once("dbSelect.php"); //DB接続情報の読み込み
    include_once("logger.php"); //ログ出力処理の読み込み
    include_once("config.php"); //ログ出力用のコンフィグ読み込み
    include_once("dbConnection.php"); //DB接続情報の読み込み
    $log = Logger::getInstance();
    
    session_start(); //セッションを開始

    if(!isset($_SESSION["cityCode"])){ //issetでセッションを確認
        $cityCode = $_POST["cityCode"];
        $_SESSION["cityCode"]=$cityCode; //セッションにkeyとvalueをセット
    }else{
        if(isset($_POST["cityCode"])){
            $cityCode =$_POST["cityCode"]; //再アクセス時
        }else{
            $cityCode = $_SESSION["cityCode"]; //再アクセス時
        }
    }
    
    $log->info('CityGML配信管理画面表示開始',$cityCode);
    $status ='2';  //アップロードエラー
    db ("UPDATE public.manage_regist_zip SET status = '" . $status  ."' where userid = '" .$cityCode . "' and status = '1'");//DBへの格納
    $log->info('初期表示時のアップロード開始中ステータスを全て' . $status . 'に更新',$cityCode);
    
    $file_path = 'F:\Apache24/htdocs/iUR_Data/' . $cityCode . '/OriginalData\3DBuildings';
    $result = glob($file_path .'/{*.zip,*.gml}', GLOB_BRACE);
    
    //1ページのリスト上に表示させる件数の設定
    define('MAX','1000');
    
    $filelists = array();
    
    $status = 0;
    
    foreach($result as $filepath){
        $filename = basename($filepath);
        $stat = stat($filepath);
        $datetime = date('Y/m/d H:i:s',$stat['mtime'] +32400);//9時間の時差があるため9時間分の秒数を足す
        $filebyte = $stat['size'];

        $filesize = number_format(round($filebyte / 1024,0)) .'KB';
        $URL = "https://*****.com/iUR_Data/" . $cityCode . "/OriginalData/3DBuildings/" . $filename;

        $filearray = array('file_name' => $filename, 'downloadURL' => $URL ,'file_size' => $filesize,'file_date' => $datetime,'file_path' => $filepath,'file_byte' => $filebyte);
        array_push($filelists,$filearray);
    }
    
    
    echo '<div class="btnLeft">';
    echo '<input type="button" id="allCheckButton" value="全選択" onclick="onClickAllCheck()"/>　';
    echo '<input type="button" id="allUncheckButton" value="全解除" onclick="onClickAllUncheck()"/>　';
    echo '<input type="button" id="releaseStopButton" value="配信停止" onclick="onClickReleaseStop()"/>　';
    echo '</div>';
    
    echo '<div class="cb"></div>';
    
    if(count($filelists) == 0 ) {
        echo '<form name="filelist"><TABLE class="table">
        <TH class="file">ファイル名<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(1)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(2)"/></TH>
        <TH class="url">ダウンロードURL</TH>
        <TH class="size">データ容量<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(3)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(4)"/></TH>
        <TH class="date">配信日時<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(5)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(6)"/></TH>';
        
        $log->warn('配信されているファイルが存在しません。',$cityCode);
        return;
    } else {
    }
    
    
    
    //初期表示時は更新日の降順（日付の新しい）でソートする
    
    $param = $_SERVER["QUERY_STRING"];
    
    if($param == null){
        $sorttype = 5;
        //初期表示時はセッションストレージの内容をリセットする
        echo '<script>sessionStorage.clear();</script>';
    }else{
        //パラメータが存在する場合はパラメータ内のソートIDを取得する
        $sorttype = mb_substr($param,8,1);
    }
    
    switch($sorttype){
            case 1 : //ソート対象：ファイル名
            case 2 : 
                $sorttarget = 'file_name';
                break;
                
            case 3 : //ソート対象：容量
            case 4 : 
                $sorttarget = 'file_byte';
                break;
                
            case 5 : //ソート対象：更新日
            case 6 :
                $sorttarget = 'file_date';
                break;
                
            default: //どれでもない場合
                $sorttarget = 'file_date';
                break;
    }
    //並び替え対象のソートを設定
    foreach((array)$filelists as $sortkey => $value){
        $sort[$sortkey] = $value[$sorttarget];
    }
    
    //ZIPファイルが存在しない場合はソートしない
    if(!empty($result)){
        //昇順/降順の判定とソートを行う
        switch($sorttype){
            case 1 :
            case 3 :
            case 5 :
                //降順でソート
                array_multisort($sort,SORT_ASC, $filelists);
                break;
            case 2 :
            case 4 :
            case 6 :
                //昇順でソート
                array_multisort($sort,SORT_DESC, $filelists);
                break;
        }
    }
    //対象フォルダ内に存在するZIPファイル数の取得
    $filelists_num = count($filelists);
    
    //ZIPファイル数を1ページの表示件数で割った値（全ページ数）
    $max_page = ceil($filelists_num / MAX);
    
    if(!isset($_GET['page_id'])){
        $now = 1;
    }else{
        $now = $_GET['page_id'];
    }
    
    $start_no = ($now - 1) * MAX;
    
    $disp_data = array_slice($filelists, $start_no, MAX, true);
    
    echo '<form name="filelist"><TABLE class="table">
    <TH class="check"></TH>
    <TH class="file">ファイル名<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(1)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(2)"/></TH>
    <TH class="url">ダウンロードURL</TH>
    <TH class="size">データ容量<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(3)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(4)"/></TH>
    <TH class="date">配信日時<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(5)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(6)"/></TH>';
    
    foreach($disp_data as $val){
        echo '<TR><TD><input type="checkbox" name="files" value="' .$val['file_name'].'"></TD><TD><p class="filename">'.$val['file_name']. '</p></TD><TD><p class="downloadURL"><a download href="' . $val["downloadURL"] . '">' .$val["downloadURL"]. '</a></p></TD><TD align="right">' .$val['file_size'].  '</TD><TD align="center">'.$val['file_date'].  '</TD></TR>';
    }
    
    echo '</TABLE></form>';    
    
    echo '全件数　'. $filelists_num. '件　';
    
    echo '<a href=\'https://*****.com/UDX/releaseManage.php?sort_id=' .$sorttype. '&page_id=1\'><<</a>　';
    
    if($now > 1){
        echo '<a href=\'https://*****.com/UDX/releaseManage.php?sort_id=' .$sorttype. '&page_id=' .($now - 1).'\'>前へ</a>　';
    }else{
        echo '前へ'.'　';
    }
    
    $disppage_be = $now - 3;//現在のページから前に表示するページ番号の数
    $disppage_af = $now + 3;//現在のページから後に表示するページ番号の数
    
    for($i =1; $i <= $max_page; $i++){
        if($i == $now){
            echo $now.'　';
        }else{
            if($i >= $disppage_be && $i <= $disppage_af){
                echo '<a href=\'https://*****.com/UDX/releaseManage.php?sort_id=' .$sorttype. '&page_id=' . $i. '\'>'. $i. '</a>　';
            }
        }
    }
    
    if($now < $max_page){
        echo '<a href=\'https://*****.com/UDX/releaseManage.php?sort_id=' .$sorttype. '&page_id=' .($now + 1).'\'>次へ</a>　';
    }else{
        echo '次へ'.'　';
    }
    
    echo '<a href=\'https://*****.com/UDX/releaseManage.php?sort_id=' .$sorttype. '&page_id=' . $max_page .'\'>>></a>　';
    
    
    $log->info('CityGML配信管理画面の表示に成功しました。',$cityCode);
} catch(Exception $ex){
    $log->error('CityGML配信管理画面の表示に失敗しました。' .$ex->getMessage() ,$cityCode);
}
?>
<!--ここからスクリプト--> 

<script type="text/javascript">        
        function onClickSort(sort_id) {
            var url_param = location.search;
            
            if(url_param == ''){
                window.location.href = 'https://*****.com/UDX/releaseManage.php?sort_id=' + sort_id + '&page_id=' + 1;
            }else{
                url_param = url_param.slice(-1);
                window.location.href = 'https://*****.com/UDX/releaseManage.php?sort_id=' + sort_id + '&page_id=' + url_param;
            }
        }
        
    //各チェックボックスにchangeイベント時の処理を設定

    var checkBox = document.filelist.files;
    if(typeof checkBox.length === "undefined"){
        checkBox.addEventListener("change", function(){
            onChangeCheckBox(this.checked, this.value);
        });
    } else {
        for(var i = 0; i < checkBox.length; i++){
            checkBox[i].addEventListener("change", function(){
                onChangeCheckBox(this.checked, this.value);
            });
        }
    }
    checkBox = null;

    
    //セッションストレージの内容を読み取ってチェック済みのチェックボックスをチェックする
    if(sessionStorage.getItem("checkedFileNameList") != null){
        var checkList = JSON.parse(sessionStorage.getItem("checkedFileNameList"));
        
        for(var checkBox of document.filelist.files){
            for(var checkedFileName of checkList){
                if(checkBox.value == checkedFileName){
                    checkBox.checked = true;
                }
            }
        }
    }
    
    //全選択ボタンを押した際の処理
    function onClickAllCheck(){
        var allCheckBox = document.filelist.files;
        
        
        if(typeof allCheckBox.length === "undefined"){
            //チェックボックスが1つしかない場合
            //チェックされていない場合のみ処理を行う
            if(allCheckBox.checked == false){
                allCheckBox.checked = true;
                onChangeCheckBox(true, allCheckBox.value);
            }
        } else {
            //チェックボックスが複数ある場合
            for(var i = 0; i < allCheckBox.length; i++){
                //チェックされていない場合のみ処理を行う
                if(allCheckBox[i].checked == false){
                    allCheckBox[i].checked = true;
                    onChangeCheckBox(true, allCheckBox[i].value);
                }
            }
        }
    }
    
    //全解除ボタンを押した際の処理
    function onClickAllUncheck(){
        var allCheckBox = document.filelist.files;
        
        if(typeof allCheckBox.length === "undefined"){
            //チェックボックスが1つしかない場合
            //チェックされている場合のみ処理を行う
            if(allCheckBox.checked === true){
                allCheckBox.checked = false;
                onChangeCheckBox(false, allCheckBox.value);
            }
        } else {
            //チェックボックスが複数ある場合
            for(var i = 0; i < allCheckBox.length; i++){
                //チェックされている場合のみ処理を行う
                if(allCheckBox[i].checked === true){
                    allCheckBox[i].checked = false;
                    onChangeCheckBox(false, allCheckBox[i].value);
                }
            }
        }
    }
    
    //チェックボックスがクリックされた際の処理
    function onChangeCheckBox(checked, fileName){
        
        var checkedFileNameList = sessionStorage.getItem("checkedFileNameList");
        
        //sessionStorageに値がセットされているか確認
        if(checkedFileNameList == null){
            //値がセットされていない場合はファイル名配列として空の配列を使用
            checkedFileNameList = [];
        } else {
            //値が入っていればparseして使用
            checkedFileNameList = JSON.parse(checkedFileNameList);
        }
        
        //チェックが付けられたのか外されたのか判定
        if(checked == true){
            //チェックされた場合はチェックボックスに対応したファイル名を配列にpush
            checkedFileNameList.push(fileName);
        } else {
            //チェックが外された場合はチェックボックスに対応したファイル名を除く配列を生成して代入
            checkedFileNameList = checkedFileNameList.filter(checkedFileNameList => checkedFileNameList !== fileName);
        }
        
        //JSON文字列にしてsessionStorageに保存
        sessionStorage.setItem("checkedFileNameList", JSON.stringify(checkedFileNameList));
        
    
    }
    
    //配信停止ボタンを押下した際の処理
    function onClickReleaseStop(){
        var releaseFileNameList = sessionStorage.getItem("checkedFileNameList");
        //セッションストレージの中身がなければチェックを促す
        if(releaseFileNameList == null || JSON.parse(releaseFileNameList).length == 0){
            postLog(window.parent.governmment_id, 'warn', '配信停止対象件数が0件');
            alert("配信停止対象を選択して下さい。");
            return;
        }
        //早めに画面をロック
        screenLock();
        setTimeout(releaseStop, 500, JSON.parse(releaseFileNameList), window.parent.governmment_id);
        
        var display = window.parent.document.getElementById("wpadminbar");
        display.style.display = "none";
    
    }
    
    /* zipやgmlファイルを配信停止する関数
    releaseStopFileNames     ：配信停止したいファイル名文字列を持つ配列 nullなら削除しない
    */
    function releaseStop(releaseStopFileNames, cityCode) {
        if (window.confirm(releaseStopFileNames.length + "個のファイルを配信停止します。よろしいですか？") == false) {
            postLog(window.parent.governmment_id, 'info', '配信停止確認でキャンセル押下');
            delete_dom_obj("screenLock");
            return;
        } else {
            postLog(window.parent.governmment_id, 'info', '配信停止確認でOK押下');
        }
        var releaseStopFileData = new FormData();
        if (releaseStopFileNames !== null) {
            releaseStopFileData.append("releaseStopFileNames", JSON.stringify(releaseStopFileNames));
            releaseStopFileData.append("cityCode", cityCode);
        }

        var xhr = new XMLHttpRequest();

        //ステータス変更時の動作を規定
        xhr.onreadystatechange = function () {
            //正常にレスポンスが返ってきたらレスポンステキストを表示
            switch (this.readyState) {
                case 0:
                case 1:
                case 2:
                case 3:
                    break;
                case 4:
                    if (this.status == 200) {
                        var response = JSON.parse(xhr.responseText);
                        switch(response["result"]){
                            case "success" :
                                alert("配信停止に成功しました");
                                break;
                            case "error" :
                                alert("配信停止に失敗したファイルがありました");
                                break;
                            default:
                                alert("正常に配信停止処理を行えませんでした");
                                break;
                        }
                    } else {
                        console.log("受信失敗　ステータス：" + xhr.statusText);
                    }
                    break;
            }
        }

        xhr.open("POST", "CityGMLReleaseStop.php", false);
        xhr.send(releaseStopFileData);
        
        //セッションストレージから選択リストを削除する
        sessionStorage.removeItem("checkedFileNameList");
        //ロック用divを削除
        delete_dom_obj("screenLock");
        location.reload();
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
            //postLog(window.parent.governmment_id, 'info', 'ログ出力テスト');

            switch (this.readyState) {
                case 0:
                case 1:
                case 2:
                case 3:
                    break;
                case 4:
                    if (this.status == 200) {
                    } else {
                        console.log("受信失敗　ステータス：" + xhrPostLog.statusText);
                    }
                    break;
            }
        }

        xhrPostLog.open("POST", "postLog.php");
        xhrPostLog.send(logData);
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
</script>