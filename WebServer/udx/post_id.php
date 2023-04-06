
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body onLoad="document.g_id.submit();">
<?php


			$user_info = get_userdata(get_current_user_id());
			//$cityCode = $user_info->last_name;
			$cityCode = $user_info->display_name;

			$targetPage = '';
			$parent = '';

			if ( is_page( 'filelist' ) ) {
				//データ変換・削除画面 (3DTilesなど)
			    $targetPage = 'filelist.php';
				$parent = 'filelist';
			} elseif ( is_page( 'consistency' ) ) {
				//書式・概念一貫性検証画面表示 ok
			    $targetPage = 'filelist.php';
			    $parent = 'consistency';
			} elseif ( is_page( 'register' ) ) {
				//データ登録 ok
			    $targetPage = 'register.php';
				$parent = 'register';
			} elseif ( is_page( 'public_release' ) ) {
				//CityGML 配信設定
			    $targetPage = 'CityGMLRelease.php';
				$parent = 'public_release';
			} elseif ( is_page( 'datadisplay' )) {
				//3D Tiles確認
			    $targetPage = 'dataDisplay.php';
				$parent = 'datadisplay';
			} elseif ( is_page( 'release_manage' )) {
				//CityGML 配信管理
			    $targetPage = 'releaseManage.php';
				$parent = 'release_manage';
			} elseif ( is_page( 'topological_consistency' )) {
				//位相一貫性検証 ok
			    $targetPage = 'topologicalConsistency.php';
				$parent = 'topological_consistency';
			} elseif ( is_page( 'datareleace' )) {
				//データ配信用
			    $targetPage = 'dataDisplay.php';
			    $parent = 'datareleace';
			}

			//2022
			$targetPageUrl = 'http://*****/udx/' . $targetPage;

			//2022 各種ページを表示する
			if($cityCode=="root"||$cityCode == "")
			{
				echo "[error]<br>ログインユーザーを確認してください。<br>cityCode:" . $cityCode;
			}
			else
			{
				echo '    <form method="post" name="g_id" action="' .$targetPageUrl. '" target="post_id">';
				echo '      <input type="hidden" id="governmment_parent" name="parent" value="' . htmlentities($parent, ENT_QUOTES, 'UTF-8') . '" />';
				echo '      <input type="hidden" id="governmment_citycode" name="cityCode" value="' . htmlentities($cityCode, ENT_QUOTES, 'UTF-8') . '" />';
				echo '    </form>';
			}
?>

    </form>
    </body>
</html>
