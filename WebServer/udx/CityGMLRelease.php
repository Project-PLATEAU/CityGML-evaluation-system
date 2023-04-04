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
    width:650px;
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
#releaseButton{
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
#releaseButton:hover{
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
  width:650px;
}
</style>
<?php
try{
    //直リンクでアクセスした場合はワードプレスにリダイレクト
    if(!isset($_SERVER["HTTP_REFERER"])){
    echo $_SERVER["REQUEST_URI"];
        //header('Location:http://*****/udx/public_release/');
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
    
    $log->info('データ配信画面表示開始',$cityCode);
    $status ='2';  //アップロードエラー
    db ("UPDATE public.manage_regist_zip SET status = '" . $status  ."' where userid = '" .$cityCode . "' and status = '1'");//DBへの格納
    $log->info('初期表示時のアップロード開始中ステータスを全て' . $status . 'に更新',$cityCode);
    
    $selRet = sel_query("select zipname,status From public.manage_regist_zip where userid = '" .$cityCode . "'",'listStatus');//自治体IDに紐づくファイル名、ステータスをDBから取得
    //2022修正
    $file_path = '*****:/*****/Data/' . $cityCode . '/OriginalData/3DBuildings';
    $result = glob($file_path .'/{*.zip,*.gml}', GLOB_BRACE);
    
    //1ページのリスト上に表示させる件数の設定
    define('MAX','1000');
    
    $filelists = array();
    
    $status = 0;            
    
    foreach($result as $filepath){
        $filename = basename($filepath);
        $stat = stat($filepath);
        $datetime = date('Y/m/d H:i:s',$stat['mtime']);
        $filebyte = $stat['size'];
        $count = strlen(intval($filebyte / 1024));
        $keyIndex = array_search($filename , array_column($selRet, 'name'));//対象フォルダにあるZIP名とDBから取得したファイル名で比較

        if($keyIndex !== false ) {
            //比較してどちらにも存在した場合はDBから取得したステータスを取得
            $arraydata = $selRet[$keyIndex];
            $status = $arraydata['status'];
        } else {
            //取得できなかった場合は0（未登録）を指定する
         	$status = 0;
        }
         
        switch($status){
            case 0 :
                $status = '未登録';
                break;
            case 1 :
                $status = '登録中';
                break;
            case 9 :
                $status = '未検証';
                break;
            case 2 :
                $status = '登録エラー';
                break;
            case 19 :
                $status = '書式検証中';
                break;
            case 29 :
                $status = '書式検証エラー';
                break;
            case 99 :                
                $status = '書式検証完了';            
                break;
            case 199 :
                $status = '位相検証中・未変換';
                break;
            case 999 :
                $status = '位相検証完了・未変換';
                break;
            case 299 :
                $status = '位相検証エラー・未変換';
                break;
            case 1099 :
                $status = '書式検証済・変換中';
                break;
            case 1299 :
                $status = '位相検証エラー・変換中';
                break;
            case 1999 :
                $status = '位相検証完了・変換中';
                break;
            case 9099 :
                $status = '書式検証済・変換完了';
                break;
            case 9199 :
                    $status = '位相検証中・変換完了';
                break;
            case 9299 :
                $status = '位相検証エラー・変換完了';
                break;
            case 9999 :
                $status = '位相検証完了・変換完了';
                break;
            case 2999 :
                $status = '位相検証完了・変換エラー';
                break;
            case 2099 :
                $status = '書式検証済・変換エラー';
                break;
            case 2199 :
                $status = '位相検証中・変換エラー';
                break;
            case 2299 :
                $status = '位相検証エラー・変換エラー';
                break;
            case 10000 :
                $status = '削除中';
                break;
            case 20000 :
                $status = '削除エラー';
                break;
        }
         
        switch($count){
            case 1 :
            case 2 :
            case 3 :
            case 4 :
            case 5 :
            case 6 :
            case 7 :
            case 8 :
            case 9 :
                $filesize = number_format(round($filebyte / 1024,0)) .'KB';
                break;
         }
        $filearray = array('file_name' => $filename,'file_size' => $filesize,'file_date' => $datetime,'file_path' => $filepath,'file_byte' => $filebyte ,'status' => $status);
        array_push($filelists,$filearray);
    }
    
    
    echo '<div class="btnLeft">';
    echo '<input type="button" id="allCheckButton" value="全選択" onclick="onClickAllCheck()"/>　';
    echo '<input type="button" id="allUncheckButton" value="全解除" onclick="onClickAllUncheck()"/>　';
    echo '<input type="button" id="releaseButton" value="配信" onclick="onClickRelease()"/>　';
    echo '</div>';

    echo '<div class="cb"></div>';
    
    if(count($filelists) == 0 ) {
        echo '<form name="filelist"><TABLE class="table">
        <TH class="file">ファイル名<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(1)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(2)"/></TH>
        <TH class="size">データ容量<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(3)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(4)"/></TH>
        <TH class="date">登録日時<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(5)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(6)"/></TH>
        <TH class="status">状態<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(7)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(8)"/></TH>';
        
        $log->warn('データ配信できるファイルが存在しません。',$cityCode);
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
                
            case 7 : //ソート対象：状態
            case 8 :
                $sorttarget = 'status';
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
            case 7 :
                //降順でソート
                array_multisort($sort,SORT_ASC, $filelists);
                break;
            case 2 :
            case 4 :
            case 6 :
            case 8 :
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
    <TH class="size">データ容量<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(3)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(4)"/></TH>
    <TH class="date">登録日時<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(5)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(6)"/></TH>
    <TH class="status">状態<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(7)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(8)"/></TH>';
    
    foreach($disp_data as $val){
        echo '<TR><TD><input type="checkbox" name="files" value="' .$val['file_name'].'"></TD><TD><p class="filename">'.$val['file_name']. '</p></TD><TD align="right">' .$val['file_size'].  '</TD><TD align="center">'.$val['file_date'].  '</TD><TD align="center">'.$val['status'].  '</TD></TR>';
    }
    
    echo '</TABLE></form>';    
    
    echo '全件数　'. $filelists_num. '件　';
    //2022修正
    echo '<a href=\'http://*****/udx/CityGMLRelease.php?sort_id=' .$sorttype. '&page_id=1\'><<</a>　';
    
    if($now > 1){
	//2022修正
        echo '<a href=\'http://*****/udx/CityGMLRelease.php?sort_id=' .$sorttype. '&page_id=' .($now - 1).'\'>前へ</a>　';
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
				//2022修正
                echo '<a href=\'http://*****/udx/CityGMLRelease.php?sort_id=' .$sorttype. '&page_id=' . $i. '\'>'. $i. '</a>　';
            }
        }
    }
    
    if($now < $max_page){
		//2022修正
        echo '<a href=\'http://*****/udx/CityGMLRelease.php?sort_id=' .$sorttype. '&page_id=' .($now + 1).'\'>次へ</a>　';
    }else{
        echo '次へ'.'　';
    }
    //2022修正
    echo '<a href=\'http://*****/udx/CityGMLRelease.php?sort_id=' .$sorttype. '&page_id=' . $max_page .'\'>>></a>　';
    
    
    $log->info('データ配信画面の表示に成功しました。',$cityCode);
} catch(Exception $ex){
    $log->error('データ配信画面の表示に失敗しました。' .$ex->getMessage() ,$cityCode);
}
?>
<!--ここからスクリプト--> 

<script type="text/javascript">        
    //ソートボタンが押下された際の処理
    function onClickSort(sort_id) {
        var url_param = location.search;
	    //2022修正
        if(url_param == ''){
            window.location.href = 'http://*****/udx/CityGMLRelease.php?sort_id=' + sort_id + '&page_id=' + 1;
        }else{
            url_param = url_param.slice(-1);
            window.location.href = 'http://*****/udx/CityGMLRelease.php?sort_id=' + sort_id + '&page_id=' + url_param;
        }
    }
        
    //各チェックボックスにchangeイベント時の処理を設定
    if(document.filelist.files !== null){
        var checkBox = document.filelist.files;
        //チェックボックスが複数存在するか確認
        if(typeof checkBox.length === "undefined"){
            //チェックボックスが単一の場合
            checkBox.addEventListener("change", function(){
                onChangeCheckBox(this.checked, this.value);
            });
        } else {
            //チェックボックスが複数の場合
            for(var i = 0; i < checkBox.length; i++){
                checkBox[i].addEventListener("change", function(){
                    onChangeCheckBox(this.checked, this.value);
                });
            }
        }
        checkBox = null;
    }
    
    //セッションストレージの内容を読み取ってチェック済みのチェックボックスをチェックする
    if(sessionStorage.getItem("checkedFileNameList") != null){
        var checkList = JSON.parse(sessionStorage.getItem("checkedFileNameList"));
        //チェックボックスとセッションストレージのファイル名を比較し、同一のものがあればチェックを入れる
        //なおバグが内在しており、チェックボックスが一つの場合はチェックが入らない
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
        if(checked === true){
            //チェックされた場合はチェックボックスに対応したファイル名を配列にpush
            checkedFileNameList.push(fileName);
        } else {
            //チェックが外された場合はチェックボックスに対応したファイル名を除く配列を生成して代入
            checkedFileNameList = checkedFileNameList.filter(checkedFileNameList => checkedFileNameList !== fileName);
        }
        
        //JSON文字列にしてsessionStorageに保存
        sessionStorage.setItem("checkedFileNameList", JSON.stringify(checkedFileNameList));
        
    
    }
    
    //配信ボタンを押下した際の処理
    function onClickRelease(){
        var governmment_id = window.parent.document.getElementById('governmment_citycode').value;
        var releaseFileNameList = sessionStorage.getItem("checkedFileNameList");

        //セッションストレージの中身がなければチェックを促す
        if(releaseFileNameList == null || JSON.parse(releaseFileNameList).length == 0){
            postLog(governmment_id, 'warn', '配信対象件数が0件');
            alert("配信対象を選択して下さい。");
            return;
        }
        
        //早めに画面をロック
        screenLock();
        //画面表示更新のため0.5秒遅らせて配信確認ダイアログ表示処理を呼ぶ
        setTimeout(releaseConfirm, 500, JSON.parse(releaseFileNameList), governmment_id);
    }
    
    //配信確認ダイアログ表示処理
    function releaseConfirm(releaseFileNameArray, cityCode){
        //確認ダイアログ表示
        if (window.confirm(releaseFileNameArray.length + "個のファイルを配信します。よろしいですか？\r\nなお、同名ファイルが存在した場合は上書きされます。") == false) {
            //キャンセルを選択した場合
            postLog(window.parent.governmment_id, 'info', '配信確認でキャンセル押下');
            //ロック用divを削除
            delete_dom_obj("screenLock");
            return;
        } else {
            //OKを選択した場合
            postLog(window.parent.governmment_id, 'info', '配信確認でOK押下');
            //valが不定のプログレスバーを作成する
            createProgress();
            //画面表示更新のため0.5秒遅らせて配信処理を呼ぶ
            setTimeout(fileRelease, 500, releaseFileNameArray, cityCode);
        }
    }
    
    //ファイル配信処理
    function fileRelease(releaseFileNameArray, cityCode){
        var releaseFileData = new FormData();
        if (releaseFileNameArray !== null) {
            releaseFileData.append("releaseFileNames", JSON.stringify(releaseFileNameArray));
            releaseFileData.append("cityCode", cityCode);
        }
        
        var xhrRelease = new XMLHttpRequest();
        //ステータス変更時の動作を規定
        xhrRelease.onreadystatechange = function () {
            //正常にレスポンスが返ってきたらレスポンステキストを表示
            switch (this.readyState) {
                case 0:
                case 1:
                case 2:
                case 3:
                    break;
                case 4:
                    if (this.status == 200) {
                        var resultArray = JSON.parse(xhrRelease.responseText);
						console.log(resultArray);
                        switch(resultArray["result"]){
                            case "success":
                                alert("配信設定に成功しました。\r\n配信されたファイルはCityGML配信管理画面で確認できます。");
                                break;
                            case "error":
                            case "catch":
                                alert("配信設定に失敗したファイルがありました。\r\nCityGML配信管理画面で確認してください。");
                                break;
                            default:
                                postLog(window.parent.governmment_id, 'warn', 'fileRelease.phpから帰ってくる値が不正');
                                break;
                        }
                    } else {
                        console.log("受信失敗(配信処理)　ステータス：" + xhrRelease.statusText);
                    }

                    //セッションストレージから選択リストを削除する
                    sessionStorage.removeItem("checkedFileNameList");

                    //ロック用divを削除
                    delete_dom_obj("screenLock");
                    
                    //画面を再表示する
                    location.reload();
                    break;
            }
        }

        xhrRelease.open("POST", "fileRelease.php", true);
        xhrRelease.send(releaseFileData);
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

        var display = window.parent.document.getElementById("wpadminbar");
		if(display!=null)
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
		if(display!=null)
        	display.style.display = "block";
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
        
        
        // プログレスバーのスタイル設定
        progressElement.style.width = "500px";
        progressElement.style.height = "40px";
        //表示場所設定
        progressElement.style.zIndex = '9999';
        
        //プログレスバー用のラベル設定
        var labelElement = document.createElement('label');
        labelElement.id = "progressLabel";
        labelElement.setAttribute('for', 'progressBar');
        labelElement.appendChild(document.createTextNode("配信設定中です\r\nページを閉じずにそのままお待ちください"));
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
</script>