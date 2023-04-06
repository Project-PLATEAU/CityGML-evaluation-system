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
.log{
    width:130px;
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
#deleteButton{
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
#deleteButton:hover{
    background-color:#1a6aff;
}
#convertButton{
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
#convertButton:hover{
    background-color:#1a6aff;
}
#validateButton{
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
#validateButton:hover{
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
    	width:100%;
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
  margin:0;
}
TABLE{
    width:100%
}
</style>
<?php
try{

    $cityCode= "0";

    //直リンクでアクセスした場合はワードプレスにリダイレクト
    if(!isset($_SERVER["HTTP_REFERER"])){
    	echo $_SERVER["REQUEST_URI"];
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
        }
    }

    if(!isset($_SESSION["parent"])){ //issetでセッションを確認
        $parent = $_POST["parent"];
        $_SESSION["parent"]=$parent; //セッションにkeyとvalueをセット
    }else{
        if(isset($_POST["parent"])){
            $parent =$_POST["parent"]; //再アクセス時
        }else{
            $parent = $_SESSION["parent"]; //再アクセス時
        }
    }

    include_once("dbSelect.php"); //DB接続情報の読み込み
    include_once("logger.php"); //ログ出力処理の読み込み
    include_once("config.php"); //ログ出力用のコンフィグ読み込み
    include_once("dbConnection.php"); //DB接続情報の読み込み
    
    //ログ書き込み処理
    $log = Logger::getInstance();
    
    $param = $_SERVER["QUERY_STRING"];
    $pageName ='';

    if($param == null){
        if($parent === 'filelist'){
            $pageName = '1';
        }else{
            $pageName = '2';
        }
    }else{
        $pageName = mb_substr($param,15,1);
    }
    
    if($pageName === '1'){
        $log->info('データ変換・削除画面の表示開始',$cityCode);
    }else{
        $log->info('書式・概念一貫性検証画面表示',$cityCode);
    }
    
    $log->info('自治体コード:' . $cityCode ,$cityCode);
    $status ='2';  //アップロードエラー
    //2022修正
    db ("UPDATE public.manage_regist_zip SET status = '" . $status  ."' where userid = '" .$cityCode . "' and status = '1'");//DBへの格納
    $log->info('初期表示時のアップロード開始中ステータスを全て' . $status . 'に更新',$cityCode);
    //2022修正
    $selRet = sel_query("select zipname,status From manage_regist_zip where userid = '" .$cityCode . "'",'listStatus');//自治体IDに紐づくファイル名、ステータスをDBから取得

    //2022修正
    $file_path = '*****:/*****/Data/' . $cityCode . '/OriginalData\3DBuildings';
 
    $result = glob($file_path .'/{*.zip,*.gml}', GLOB_BRACE); 
    
    //1ページのリスト上に表示させる件数の設定
    define('MAX','1000');
    
    $filelists = array();
    
    $status = 0;
    
    foreach($result as $filepath){
         $filename = basename($filepath);
         $stat = stat($filepath);
         //2022修正 サーバー側(PHP)の設定により修正
         //$datetime = date('Y/m/d H:i:s',$stat['mtime'] +32400);//9時間の時差があるため9時間分の秒数を足す
         $datetime = date('Y/m/d H:i:s',$stat['mtime']);
         $filebyte = $stat['size'];
         $count = strlen(intval($filebyte / 1024));
         $keyIndex = array_search($filename , array_column($selRet, 'name'));//対象フォルダにあるZIP名とDBから取得したファイル名で比較
         
         if($keyIndex !== false ) {
             //比較してどちらにも存在した場合はDBから取得したステータスを取得
             $arraydata = $selRet[$keyIndex];
             $status = $arraydata['status'];
         }else{
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
                if($pageName === '1'){
                    $status = '未変換';
                }else{
                    $status = '書式検証完了';
                }
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
    
    if(count($filelists) == 0 ) {
        $deleteAttribute = ' disabled style="background-color: gray"';
        $convertAttribute = ' disabled style="background-color: gray"';
        $validateAttribute = ' disabled style="background-color: gray"';
    } else {
        $deleteAttribute = "";
        $validateAttribute = "";
        $convertAttribute = "";
    }
    //リファラによって表示するボタンを変更する
        if($pageName == '1'){
            echo '<div class="btnLeft">';
            echo '<div class="flex">';
            echo '<input type="button" id="allCheckButton" value="全選択" onclick="onClickAllCheck()"/>　';
            echo '<input type="button" id="allUncheckButton" value="全解除" onclick="onClickAllUncheck()"/>　';
            echo '<input type="button" id="convertButton" value="変換"' . $convertAttribute .' onclick="onClickConvert()"/>　';
            echo '<input type="button" id="deleteButton" value="削除"' . $deleteAttribute .' onclick="onClickDelete()"/>　';
            echo '</div></div>';
        } else {
            echo '<div class="btnLeft">';
            echo '<div class="flex">';
            echo '<input type="button" id="allCheckButton" value="全選択" onclick="onClickAllCheck()"/>　';
            echo '<input type="button" id="allUncheckButton" value="全解除" onclick="onClickAllUncheck()"/>　';
            echo '<input type="button" id="validateButton" value="検証"' . $validateAttribute .' onclick="onClickValidate()"/>　';
            echo '</div></div>';
        }
    
    
    //ここからアップロード済ファイルサイズの取得
    //2022修正
    $getTotalSizePath = '*****:/*****/Data/' . $cityCode . '/OriginalData/';
    //COMオブジェクト生成
    $obj = new COM ( 'scripting.filesystemobject' );
    if(is_object($obj)){
        //フォルダ情報取得
        $ref = $obj->getfolder ( $getTotalSizePath );
        
        //フォルダ合計サイズ取得
        $totalSize = $ref->size;
        echo '<div class="btnRight">';
        //バイトで取得されるので単位を付与
        if(empty($totalSize) === false){
            switch($totalSize){
                case ($totalSize >= (1024 * 1024 * 1024)):
                    echo '<p>現在の使用容量：' . number_format($totalSize / (1024 * 1024 * 1024), 1) . 'GB / 5GB';
                    break;
                case ($totalSize >= 1024 * 1024):
                    echo '<p>現在の使用容量：' . number_format($totalSize / (1024 * 1024), 1) . 'MB / 5GB';
                    break;
                case ($totalSize >= 1024):
                    echo '<p>現在の使用容量：' . number_format($totalSize / 1024, 1) . 'KB / 5GB';
                    break;
                case ($totalSize >= 1):
                    echo '<p>現在の使用容量：' . $totalSize . 'Byte / 5GB';
                    break;
                default:
                    echo '<p>現在の使用容量：0Byte / 5GB';
                    break;
            }
        } else {
            echo '<p>現在の使用容量：0Byte / 5GB';
        }
        echo '</p>';
        $obj = null;
    } else {
        echo 'ファイル容量取得エラー';
    }
    echo '</div>';
    echo '<div class="cb"></div>';
    //ここまでアップロード済ファイルサイズの取得
    
    if(count($filelists) == 0 ) {
        echo '<form name="filelist"><TABLE class="table">
        <TH class="file">ファイル名<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(1)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(2)"/></TH>
        <TH class="size">データ容量<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(3)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(4)"/></TH>
        <TH class="date">登録日時<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(5)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(6)"/></TH>
        <TH class="status">状態<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(7)"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(8)"/></TH>
        <TH class="log">書式検証ログ</TH><TH></TH>';
        
        $log->warn('表示対象ファイルが存在しません。',$cityCode);
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
    <TH class="file">ファイル名<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(1,' .$pageName. ')"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(2,' .$pageName. ')"/></TH>
    <TH class="size">データ容量<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(3,' .$pageName. ')"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(4,' .$pageName. ')"/></TH>
    <TH class="date">登録日時<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(5,' .$pageName. ')"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(6,' .$pageName. ')"/></TH>
    <TH class="status">状態<input type="button" class="sortbtn_up" value="▲" onclick="onClickSort(7,' .$pageName. ')"/><input type="button" class="sortbtn_down" value="▼" onclick="onClickSort(8,' .$pageName. ')"/></TH>
    <TH class="log">書式検証ログ</TH>';
     
     //ここでログファイルのURLを動的につくってあげる
    foreach($disp_data as $val){
        //ログファイル場所を生成
        if($val['status'] == '未登録' 
        || $val['status'] == '登録中' || $val['status'] == '登録エラー' 
        || $val['status'] == '未検証' || $val['status'] == '書式検証中'){
            $download = 'ダウンロード';
        }else{
        	//2022修正
            //$outlog = 'https://*****.com/iUR_Data/'.$cityCode . '\\ValidateLog\\' . $val['file_name'] . '.txt';
            $outlog = 'http://*****/iUR_Data/'.$cityCode . '/ValidateLog/' . $val['file_name'] . '.txt';         
            $download = '<a href="' .$outlog. '" download>ダウンロード</a>';
        }
        echo '<TR><TD><input type="checkbox" name="files" value="' .$val['file_name'].'"></TD><TD><p class="filename">'.$val['file_name']. '</p></TD><TD align="right">' .$val['file_size'].  '</TD><TD align="center">'.$val['file_date'].  '</TD><TD align="center">'.$val['status'].  '</TD><TD align="center">' .$download. '</TD></TR>';
    }
    
    echo '</TABLE></form>';    
    
    echo '全件数　'. $filelists_num. '件　';
    //2022修正
    echo '<a href=\'http://*****/udx/filelist.php?sort_id=' .$sorttype. '&page=' .$pageName. '&page_id=1\'><<</a>　';
    
    if($now > 1){
    	//2022修正
        echo '<a href=\'http://*****/udx/filelist.php?sort_id=' .$sorttype. '&page=' .$pageName. '&page_id=' .($now - 1). '\'>前へ</a>　';
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
                echo '<a href=\'http://*****/udx/filelist.php?sort_id=' .$sorttype. '&page=' .$pageName. '&page_id=' . $i. '\'>'. $i. '</a>　';
            }
        }
    }
    
    if($now < $max_page){
    	//2022修正
        echo '<a href=\'http://*****/udx/filelist.php?sort_id=' .$sorttype. '&page=' .$pageName. '&page_id=' .($now + 1). '\'>次へ</a>　';
    }else{
        echo '次へ'.'　';
    }
    //2022修正
    echo '<a href=\'http://*****/udx/filelist.php?sort_id=' .$sorttype. '&page=' .$pageName. '&page_id=' . $max_page . '\'>>></a>　';
    
    if($pageName === '1'){
        $log->info('データ変換・削除画面の表示終了',$cityCode);
    }else{
        $log->info('書式・概念一貫性検証画面の表示終了',$cityCode);
    }
    
    
} catch(Exception $ex){
    $log->error('データ変換・削除画面の表示に失敗しました。' .$ex->getMessage() ,$cityCode);
}
?>
<!--ここからスクリプト--> 

<script type="text/javascript">
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
    

    //削除ボタンを押下した際の処理
    function onClickDelete() {
        var deleteFileNameArray = [];
        var file = document.filelist.files;
        
        if(typeof file.length  === "undefined"){
            if (file.checked === true) {
                deleteFileNameArray.push(file.defaultValue);
            }
        } else {
            for (var i = 0; i < file.length; i++) {
                if (file[i].checked === true) {
                    deleteFileNameArray.push(file[i].value);
                }
            }
        }

		//2022
		//var governmment_id = window.parent.governmment_id;
        var governmment_id = window.parent.document.getElementById('governmment_citycode').value;

        if(deleteFileNameArray.length == 0){
            alert("削除対象を選択して下さい。");
            postLog(governmment_id, 'warn', '削除対象件数が0件');
            return;
        } else {
            if(confirm("削除しますがよろしいですか？") === true){ 
                postLog(governmment_id, 'info', '削除確認ダイアログでOK押下');
                screenLock();
                var display = window.parent.document.getElementById("wpadminbar");

				if(display!=null)
         	       display.style.display = "none";

                setTimeout(fileDelete, 500, deleteFileNameArray, governmment_id);

            } else {
                postLog(governmment_id, 'info', '削除確認ダイアログでキャンセル押下');
            }
        }
    }
    
    //ソートボタンを押下した際の処理
    function onClickSort(sort_id,pageName) {

		//2022
		//var governmment_id = window.parent.governmment_id;
        var governmment_id = window.parent.document.getElementById('governmment_citycode').value;
        var url_param = location.search;
        postLog(governmment_id, 'info', 'ソートボタン押下');

        if(url_param == ''){
            //2022修正
            window.location.href = 'http://*****/udx/filelist.php?sort_id=' + sort_id  +'&page=' + pageName + '&page_id=' + 1;
        }else{
            url_param = url_param.slice(-1);
            //2022修正
            window.location.href = 'http://*****/udx/filelist.php?sort_id=' + sort_id + '&page=' + pageName + '&page_id=' + url_param;
        }
    }
    
    /* zipやgmlファイルを削除する関数
    deleteFileNameArray     ：削除したいファイル名文字列を持つ配列 nullなら削除しない
    */
    function fileDelete(deleteFileNameArray, cityCode) {
        var responseJson = null;
        var deleteFileData = new FormData();
        if (deleteFileNameArray !== null) {
            deleteFileData.append("deleteFileNameArray", JSON.stringify(deleteFileNameArray));
            deleteFileData.append("cityCode", cityCode);
        }

        var xhr = new XMLHttpRequest();

        //ステータス変更時の動作を規定
        xhr.onreadystatechange = function () {
            var nameAndResult = [];
            var key = "："; //レスポンスからファイル名と結果を分けるための固定文字列
            var keyPosition = 0;
            var resultText = ""; //アップロード結果
            var fileName = ""; //ファイル名

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
                            case "DoNotDelete" :
                                alert("処理中のファイルが選択されているため、削除できませんでした");
                                break;
                            case "success" :
                                alert("削除に成功しました");
                                sessionStorage.removeItem("checkedFileNameList");
                                location.reload();
                                break;
                            case "deleteError" :
                                alert("削除に失敗しました");
                                sessionStorage.removeItem("checkedFileNameList");
                                location.reload();
                                break;
                            default:
                                break;
                        }
                    } else {
                        console.log("受信失敗　ステータス：" + xhr.statusText);
                    }
                    break;
            }
        }

        xhr.open("POST", "fileDelete.php", false);
        xhr.send(deleteFileData);
        
        //ロック用divを削除
        delete_dom_obj("screenLock");
        var display = window.parent.document.getElementById("wpadminbar");
		if(display!=null)
        	display.style.display = "block";
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
    
    //変換ボタンを押下した際の処理
    function onClickConvert(){

		//2022
		//var governmment_id =window.parent.governmment_id;
        var governmment_id = window.parent.document.getElementById('governmment_citycode').value;

        var convertFileNameList = sessionStorage.getItem("checkedFileNameList");
        //セッションストレージの中身がなければチェックを促す
        if(convertFileNameList == null || JSON.parse(convertFileNameList).length == 0){
            postLog(governmment_id, 'warn', '変換対象件数が0件');
            alert("変換対象を選択して下さい。");
            return;
        }


        //変換処理を呼ぶ
        fileConvert(JSON.parse(convertFileNameList), governmment_id);
    }
    
    //ファイル変換処理
    function fileConvert(convertFileNameArray, cityCode){

		//2022
		//var governmment_id =window.parent.governmment_id;
		var governmment_id = window.parent.document.getElementById('governmment_citycode').value;

        if (window.confirm(convertFileNameArray.length + "個のファイルを変換します。よろしいですか？\r\nまた3DTile変換時には既存の3DTileファイルは削除されます") == false) {
            postLog(governmment_id, 'info', '変換確認でキャンセル押下');
            return;
        } else {
            postLog(governmment_id, 'info', '変換確認でOK押下');
            screenLock();
            var display = window.parent.document.getElementById("wpadminbar");
            if(display!=null)
              display.style.display = "none";
        }
        var cancelFlg = false;
        var responseJson = null;
        var convertFileData = new FormData();
        if (convertFileNameArray !== null) {
            convertFileData.append("convertFileNames", JSON.stringify(convertFileNameArray));
            convertFileData.append("cityCode", cityCode);
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
                        var resultArray = JSON.parse(xhr.responseText);
                        switch(resultArray["result"]){
                            //ログはjsonEdit.phpで出力しているのでここでは出力させない
                            case "OK" :
                                break;
                            case "noPostData" :
                                alert("正しくPOSTされていません");
                                cancelFlg = true;
                                break;
                            case "myJobIsActive" :
                                alert("既に変換処理を行っています。変換処理が終わるまでお待ちください。");
                                cancelFlg = true;
                                break;
                            case "activeJobCountOver" :
                                alert("他に" + resultArray["activeConvertJobCount"] + "人のユーザが変換処理を行っています。　他のユーザの変換処理が終了するまでお待ちください");
                                cancelFlg = true;
                                break;
                            case "jsonOutputFailed" :
                                alert("地図表示用コンフィグファイルの出力に失敗しました。");
                                cancelFlg = true;
                                break;
                            case "incorrectStatus" :
                                alert("選択されたファイルに変換できないステータスのファイルが含まれています");
                                cancelFlg = true;
                                break;
                            case "dataDriveCapacityOver" :
                                alert("データ領域の使用率が95%を超えています。 データ削除を実行してください。");
                                cancelFlg = true;
                                break;
                            case "3DTilesReleaseIsLocked" :
                                alert("前回の3DTiles配信処理が完了していません。\r\n配信処理終了までお待ちください。");
                                cancelFlg = true;
                                break;
                            default:
                                cancelFlg = true;
                                break;
                        }
                    } else {
                        console.log("受信失敗　ステータス：" + xhr.statusText);
                        cancelFlg = true;
                        postLog(governmment_id, 'error', '変換処理でエラー');
                    }
                    break;
            }
        }

        xhr.open("POST", "jsonEdit.php", false);
        xhr.send(convertFileData);

        if(cancelFlg === true){
            postLog(governmment_id, 'warn', '変換処理中断');
            //ロック用divを削除
            delete_dom_obj("screenLock");
            var display = window.parent.document.getElementById("wpadminbar");
			if(display!=null)
            	display.style.display = "block";
            return;
        }
        
        
        var xhrConvert = new XMLHttpRequest();
        //ステータス変更時の動作を規定
        xhrConvert.onreadystatechange = function () {
            //正常にレスポンスが返ってきたらレスポンステキストを表示
            switch (this.readyState) {
                case 0:
                case 1:
                case 2:
                case 3:
                    break;
                case 4:
			        alert("fileConvert:" + xhrConvert.statusText);
                    if (this.status == 200) {
                    } else {
                        console.log("受信失敗(変換処理)　ステータス：" + xhrConvert.statusText);
                    }
                    break;
            }
        }
        xhrConvert.open("POST", "fileConvert.php", true);
        xhrConvert.send(convertFileData);
        sessionStorage.removeItem("checkedFileNameList");
        
        //ロック用divを削除
        delete_dom_obj("screenLock");
        
        var display = window.parent.document.getElementById("wpadminbar");
		if(display!=null)
           	display.style.display = "block";
        
        alert("変換処理を開始しました");
        location.reload();
    }
    
    //検証ボタンを押下した際の処理
    function onClickValidate(){
        var validateFileNameList = sessionStorage.getItem("checkedFileNameList");
        //セッションストレージの中身がなければチェックを促す
        if(validateFileNameList == null || JSON.parse(validateFileNameList).length == 0){
            alert("検証対象を選択して下さい。");
            return;
        }

		//2022
		//var governmment_id =window.parent.governmment_id;
        var governmment_id = window.parent.document.getElementById('governmment_citycode').value;

        //検証処理を呼ぶ
        fileValidate(JSON.parse(validateFileNameList), governmment_id);
    }
    
    //ファイル検証処理
    function fileValidate(validateFileNameArray, cityCode){            
        if (window.confirm(validateFileNameArray.length + "個のファイルを検証します。よろしいですか？") == false) {
            return;
        }
        //画面をロック
        screenLock();
        //上部のツールバーを非表示
        var display = window.parent.document.getElementById("wpadminbar");
		if(display!=null)
           	display.style.display = "block";
        
        var cancelFlg = false;
 
        var preValidateFileData = new FormData();
        if (validateFileNameArray !== null) {
            preValidateFileData.append("fileNameList", JSON.stringify(validateFileNameArray));
            preValidateFileData.append("cityCode", cityCode);
        }

        var xhrPreValidate = new XMLHttpRequest();

        //ステータス変更時の動作を規定
        xhrPreValidate.onreadystatechange = function () {
            switch (this.readyState) {
                case 0:
                case 1:
                case 2:
                case 3:
                    break;
                case 4:
                    if (this.status == 200) {
                        //アップロード用フォームをリセットする
                        var resultArray = JSON.parse(xhrPreValidate.responseText);
                        
                        switch(resultArray["result"]){
                            case "OK" :
                                break;
                            case "myJobIsActive" :
                                alert("既に検証処理を行っています。検証処理が終わるまでお待ちください。");
                                cancelFlg = true;
                                break;
                            case "activeJobCountOver" :
                                alert("他に" + resultArray["activeValidateJobCount"] + "人のユーザが検証処理を行っています。　他のユーザの検証処理が終了するまでお待ちください");
                                cancelFlg = true;
                                break;
                            case "DoNotValidate" :
                                alert("検証対象に検証を開始出来ないステータスのファイルが選択されています。検証対象から除外して検証処理実行してください");
                                cancelFlg = true;
                                break;
                            default:
                                cancelFlg = true;
                                break;
                        }
                    } else {
                        console.log("受信失敗(検証処理)　ステータス：" + xhrValidate.statusText);
                    }
                    break;
            }
        }

        console.log(preValidateFileData);
        xhrPreValidate.open("POST", "preValidateCheck.php", false);
        xhrPreValidate.send(preValidateFileData);

        
        //ロック用divを削除
        delete_dom_obj("screenLock");
        var display = window.parent.document.getElementById("wpadminbar");
		if(display!=null)
           	display.style.display = "block";
        
        if(cancelFlg === true){
            return;
        }

        var validateFileData = new FormData();
        if (validateFileNameArray !== null) {
            validateFileData.append("fileNameList", JSON.stringify(validateFileNameArray));
            validateFileData.append("cityCode", cityCode);
        }
        
        var xhrValidate = new XMLHttpRequest();

        //ステータス変更時の動作を規定
        xhrValidate.onreadystatechange = function () {
            switch (this.readyState) {
                case 0:
                case 1:
                case 2:
                case 3:
                    break;
                case 4:
                    if (this.status == 200) {
						alert("処理が完了しました");
                    } else {
                        console.log("受信失敗(検証処理)　ステータス：" + xhrValidate.statusText);
                    }
                    break;
            }
        }
        
        console.log(validateFileData);
        xhrValidate.open("POST", "validate.php");
        xhrValidate.send(validateFileData);
        sessionStorage.removeItem("checkedFileNameList");
        alert("検証処理を開始しました。");
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
		if(display!=null)
        	display.style.display = "block";
    }
</script>