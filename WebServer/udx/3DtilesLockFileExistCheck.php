<?php
    //3DTiles配信処理を行っているかを返す
    //ロックファイルの有無で判定し、ロックファイルがある場合は処理中とする
    function tilesReleaseIsLocked($cityCode){
        //ロックファイル名のフルパス
		//2022
        $lockFilePath = "*****:/*****/htdocs/iUR_Data/" .$cityCode. "/3DTiles/3DBuildings/lock.txt";

        //ファイルが存在すればtrue,存在しなければfalse
        $returnValue = file_exists($lockFilePath);
        return $returnValue;
    }
?>