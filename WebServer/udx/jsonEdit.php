<?php
try{
    include_once("logger.php"); //ログ出力処理の読み込み
    include_once("config.php"); //ログ出力用のコンフィグ読み込み
    $log = Logger::getInstance();//ログ出力クラスインスタンス生成
    include_once("3DtilesLockFileExistCheck.php"); //ロックファイル確認関数読み込み
    include_once("dbConnection.php"); //DB接続情報の読み込み
    include_once("dbSelect.php"); //DB接続情報の読み込み 

    //パス設定 //2022修正
    $MapRoot = '*****:/*****/htdocs/map/';

    //自治体IDを取得してフォルダパス生成
    if(isset($_POST["cityCode"]) == true && isset($_POST["convertFileNames"]) == true){
        $cityCode = $_POST["cityCode"];
        $my3DMapPath = $MapRoot . $_POST["cityCode"] . '/private/';
        $convertFileNames = json_decode($_POST["convertFileNames"]);
    } else {
        //エラー
        $returnArray = array(
            'result' => 'noPostData',
        );
        echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
        $log->error('POSTされるべき値がPOSTされていません', $cityCode);
        return;
    }

    //ロックファイルの存在確認
    if(tilesReleaseIsLocked($cityCode) === true){
        $returnArray = array(
            'result' => '3DTilesReleaseIsLocked'
        );
        echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
        $log->warn('3DTilesの配信処理中は変換を行えません', $cityCode);
        return;
    }
    
    //Fドライブの空き容量が5%未満の場合、処理を行わない
	//2022
    if(disk_free_space("C:") / disk_total_space("C:") < 0.05){
        $returnArray = array(
            'result' => 'dataDriveCapacityOver'
        );
        echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
        $log->warn('データドライブの使用率が95%を超えています', $cityCode);
        return;
    }
    
    $log->info('変換ジョブ数確認開始',$cityCode);
    
    //自治体IDごとの処理数と全体でのJOB数を取得
    $selRet = sel_query ("with jobCount as(
    SELECT count(userid)as activeJobCount from (SELECT DISTINCT userid FROM manage_regist_zip where status in ('1099','1299','1999') and userid = '" . $cityCode . "')as a)
    , fileCount as(
    SELECT count(userid)as activeUserCount from (SELECT DISTINCT userid FROM manage_regist_zip where status in ('1099','1999'))as b)
    select * from jobCount cross join fileCount",'ConvertJob');
    
    $myConvertJobCount = $selRet['0']['activeJobCount'];          //自治体ID単位での処理数取得(値が0以外はすでに実行中)
    $activeConvertJobCount = $selRet['0']['activeUserCount'];        //全体でのジョブ数5ジョブ以上は待たせる
    
    //自身のジョブが既に実行されているかを確認する
    if($myConvertJobCount != 0){
        $returnArray = array(
            'result' => 'myJobIsActive',
            'myConvertJobCount' => $myConvertJobCount
        );
        echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
        $log->error('前回の変換処理が完了していないため処理を中断しました', $cityCode);
        return;
    }
    
    //全体のジョブの実行数が5未満であることを確認する
    if($activeConvertJobCount >= 5){
        $returnArray = array(
            'result' => 'activeJobCountOver',
            'activeConvertJobCount' => $activeConvertJobCount
        );
        echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
        $log->error('変換処理を行っているユーザが5人以上いるため処理を中断しました', $cityCode);
        return;
    }
    $log->info('変換ジョブ数確認終了',$cityCode);
    
    //クエリ条件用にファイル名のin句生成
    $in_query = '';
    foreach($convertFileNames as $convertFileName){
        $convertFile = trim(basename($convertFileName));
        
        if($in_query == ''){
            $in_query = '\''.$convertFile.'\',';
        }else {
            $in_query = $in_query.'\'' .$convertFile.'\',' ;
        }                    
    }
    $in_query = rtrim($in_query,',');             //末尾のカンマを削除
    $in_query ='zipname in (' . $in_query . ')';  //～ zipname in (filename1,filename2)の形式にする
    $selRet = sel_query ("select count(status) from public.manage_regist_zip where  userid = '" .$cityCode . "' and status in ('1','9', '2', '19', '29', '199','9199','1099','1999','1299','2199','10000') and " . $in_query ,'Convert');
    
    //選択されたデータに変換できないステータスのものが含まれていないか確認
    $incorrectStatusCount = $selRet['0']['incorrectStatus'];          //(値が0以外は変換を行わない)
    if(!$incorrectStatusCount == 0){
        $resultArray["result"] = "incorrectStatus";
        $log->warn('選択されたファイルに変換できないステータスのファイルが含まれています', $cityCode);
        echo json_encode($resultArray, JSON_UNESCAPED_UNICODE);
        return;
    }
    
   
    $log->info('地図表示用コンフィグファイル生成処理開始',$cityCode);
    
    //生成したフォルダパスにあるテンプレート用コンフィグファイルを読み込んで配列に変換
    $filePath = $my3DMapPath . 'ConfigJsonTemplate/config.json';
    $json = file_get_contents($filePath);
    $config = json_decode($json, true); //json形式のデータを連想配列の形式にする

    //変換対象ファイル名ごとにレイヤとlegendウィジェットの設定を追加
    foreach($convertFileNames as $tempFileName){
        $fileNameArray = array();
        $searchDem = '_dem_';
        $search2D = '_6668';


        if(strpos($tempFileName,$searchDem) !== false){
            //DEMの場合
            array_push($fileNameArray, $tempFileName);
        } elseif (strpos($tempFileName,$search2D) !== false) {
            //2Dの場合
            array_push($fileNameArray, $tempFileName);
        } else {
//2022修正
            //3Dの場合LOD1～LOD2,LOD2_Surfaceを追加する
            array_push($fileNameArray, $tempFileName . "_LOD1");
            array_push($fileNameArray, $tempFileName . "_LOD2");
            array_push($fileNameArray, $tempFileName . "_LOD2_Surface");
            array_push($fileNameArray, $tempFileName . "_LOD3");
            //array_push($fileNameArray, $tempFileName . "_LOD4");
            //array_push($fileNameArray, $tempFileName . "_LODALL");
        }


        foreach($fileNameArray as $fileName){
            //まずはレイヤ設定の追加
            //レイヤ設定を生成
            $layer = null;
            $layer = array(
                //ここから固定値設定
                'activeOnStartup' => false, //初期表示無効
                'allowPicking' => true, //クリック有効
                'copyright' => array( //コピーライト表示しない
                    'provider' => '',
                    'url' => '',
                    'year' => ''
                ),
                'hiddenObjectIds' => array(), //非表示オブジェクトなし
                'screenSpaceError' => 16, //非モバイル端末のオブジェクト表示距離 小さいほど遠くから見える
                'screenSpaceErrorMobile' => 32, //モバイル端末のオブジェクト表示距離 小さいほど遠くから見える
                'type' => 'vcs.vcm.layer.cesium.Buildings', //3Dビルディングであることを示す
                //ここまで固定値
                
                //以下ファイルごとの設定
                'exclusive' => $tempFileName, //同名ファイルの他レイヤとは排他制御を行う
                'datasourceId' => $fileName . '_ID', //仮の適当なID 地図表示に使用されない
                'name' => $fileName, //レイヤ名
                'url' => './datasource-data/' . $fileName . '/tileset.json', //データのパス
            );

            //3DのLOD1の場合のみ初期表示を有効化する
            if($fileName === $tempFileName . "_LOD1"){
                $layer['activeOnStartup'] = true;
            } elseif (strpos($tempFileName,$search2D) !== false) {
                //2Dの場合は3Dオブジェクトより前に描写されないようにする
                $layer['tilesetOptions'] = array(
                    'classificationType' => 0,
                );
            }


            //作成したレイヤ設定をpush
            array_push($config['layers'], $layer);
            
            
            //legendウィジェット設定の追加
            //legendウィジェット設定を生成
            $legend = null;
            $legend = array(
                'type' => 'vcs.vcm.widgets.legend.LayerItem', //最下層のアイテムであることを記述
                'layerName' => $fileName, //どのレイヤとリンクするかをレイヤ名で指定
                'title' => $fileName //画面表示用文字列 レイヤ名を指定
            );
            
            //widgetsは下記のように各ウィジェットを表す連想配列を持つ配列であり、その中でもlegendウィジェットは自身の要素の中の1つに、連想配列を持つ配列を持っている
            //"widgets": [
            //    {
            //        他のウィジェットを示す要素
            //    },
            //    {
            //        他のウィジェットを示す要素
            //    },
            //    {
            //        "type": "vcs.vcm.widgets.legend.Legend",
            //        "children": [
            //            {
            //                "type": "vcs.vcm.widgets.legend.LayerItem",
            //                "layerName": "レイヤ名",
            //                "title": "画面表示用文字列"
            //            },
            //            {
            //                他のlegendを示す要素
            //                ClusterItemなどの場合は更にchildren配列を持つ
            //            }
            //       ]
            //    }
            //]
            
            //widgetsの中からlegendウィジェットである要素を探し出し、その中の3D都市モデルを示すクラスタレイヤを探し、
            //そのchildren配列に先程作成した設定を追加する
            //foreach文の仮変数の頭に"&"を付けることで取り出した要素を直接編集できる
            foreach($config['widgets'] as &$widget){
                if($widget['type'] == 'vcs.vcm.widgets.legend.Legend'){
                    foreach($widget['children'] as &$cluster){
                        //3D都市モデル用Legendを探す
                        if(isset($cluster['title']) && $cluster['title'] === '3D都市モデル'){
                            //追加するlegendによって処理を分ける
                            if(strpos($tempFileName,$searchDem) !== false || strpos($tempFileName,$search2D) !== false){
                                //2DかDEMの場合
                                //ウィジェットの設定を追加
                                array_push($cluster['children'], $legend);
                            } else if($fileName === $tempFileName . "_LOD1"){
                                //3D_LOD1の場合
                                //GroupItemを作成する
                                $group = array(
                                    "clickable" => false, //クリックによる全選択無効
                                    "startOpen" => false, //初期表示時は閉じられた状態
                                    "infoUrl" => "", //URL情報なし
                                    "title" => $tempFileName, //表示名はファイル名をそのまま使用
                                    'type' => 'vcs.vcm.widgets.legend.GroupItem', //GroupItemであることを記述
                                    'children' => array(),
                                );

                                //GroupItemに最下層のlegendを追加
                                array_push($group['children'], $legend);
                                //ウィジェットの設定を追加
                                array_push($cluster['children'], $group);
                            } else {
                                //3D_LOD2～LOD2_Surfaceの場合
                                foreach($cluster['children'] as &$clusterChildren){
                                    if($clusterChildren['type'] === 'vcs.vcm.widgets.legend.GroupItem' &&
                                        isset($clusterChildren['title']) &&
                                        $clusterChildren['title'] === $tempFileName){
                                        //ファイル名が一致するGroupItemの場合
                                        //ウィジェットの設定を追加してbreakする
                                        array_push($clusterChildren['children'], $legend);
                                        break;
                                    }
                                }
                            }
                            break;
                        }
                    }
                    break;
                }
            }
        }
    }
    //json文字列に変換して書き込み マルチバイトUNICODEやスラッシュのエスケープをせず、整形して出力
    $config = json_encode($config, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    $jsonOutputResult = file_put_contents($my3DMapPath . 'config.json', $config); //同名ファイルは上書きされる
    
    if($jsonOutputResult !== false){
        $returnArray = array(
            'result' => 'OK',
        );
        echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
        $log->info('地図表示用コンフィグファイル生成処理に成功しました。', $cityCode);
        
        $status ='99';  //検証完了
        db ("UPDATE public.manage_regist_zip SET status = '" . $status  ."' where userid = '" .$cityCode . "' and status IN ('9099','2099')");//DBへの格納
        $log->info('データ変換時には書式検証済・変換完了と書式検証済・変換エラーのステータスを全て' . $status . 'に更新',$cityCode);
        
        $status ='999';  //検証完了
        db ("UPDATE public.manage_regist_zip SET status = '" . $status  ."' where userid = '" .$cityCode . "' and status IN ('9999','2999')");//DBへの格納
        $log->info('データ変換時には位相検証完了・変換完了と位相検証完了・変換エラーのステータスを全て' . $status . 'に更新',$cityCode);
        
        $status ='299';  //検証完了
        db ("UPDATE public.manage_regist_zip SET status = '" . $status  ."' where userid = '" .$cityCode . "' and status IN ('9299','2299')");//DBへの格納
        $log->info('データ変換時には位相検証エラー・変換完了と位相検証エラー・変換エラーのステータスを全て' . $status . 'に更新',$cityCode);
        
        //変換処理開始ステータスをDBに書き込む
        foreach($convertFileNames as $fileNameStatus){
            //2022修正
			$sql = " WITH getStatus AS(
                select case when status = '99' then '1099' 
                       when status = '999' then '1999' 
                       when status = '299' then '1299' 
                       when status = '9099' then '1099' 
                       when status = '9999' then '1999'
                       when status = '2999' then '1999'
                       when status = '2099' then '1099'
                       when status = '2299' then '1299'
                       ELSE status 
                       end
                FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileNameStatus ."'
                )
                ,upsert AS (
                       UPDATE public.manage_regist_zip
                       SET status = (select * from getStatus) ,registdate = NOW()
                       WHERE userid = '". $cityCode ."' AND zipname = '". $fileNameStatus ."' RETURNING * 
                )
                       INSERT INTO public.manage_regist_zip (userid, zipname,  status, registdate )
                       SELECT '". $cityCode ."','". $fileNameStatus ."',  (select * from getStatus) ,  NOW() From public.manage_regist_zip
                       WHERE not exists (SELECT 1
                       FROM public.manage_regist_zip WHERE userid = '". $cityCode ."' and zipname = '". $fileNameStatus ."' ) LIMIT 1";

		    $log->info($sql,$cityCode);

            db ($sql);//DBへの格納
                       
            $log->info('['. $fileNameStatus . ']の' .'ステータスを変換中に更新',$cityCode);
        }
        
    } else {
        $returnArray = array(
            'result' => 'jsonOutputFailed',
        );
        echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
        $log->error('地図表示用コンフィグファイルの出力に失敗しました。', $cityCode);
        return;
    }
    
} catch(Exception $ex){
    $log->error('地図表示用コンフィグファイル生成処理に失敗しました。' .$ex->getMessage() ,$cityCode);
}
?>