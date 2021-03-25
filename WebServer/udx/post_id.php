<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body onLoad="document.g_id.submit();">
        <?php
			$user_info = get_userdata(get_current_user_id());
			$cityCode= $user_info->last_name; 
			$targetPage = '';
			
			if ( is_page( 'filelist' ) ) {
			    $targetPage = 'filelist.php';
				$parent = 'filelist';
				
			} elseif ( is_page( 'consistency' ) ) {
			    $targetPage = 'filelist.php';
			    $parent = 'consistency';
			} elseif ( is_page( 'register' ) ) {
			    $targetPage = 'register.php';
				$parent = 'register';
				
			} elseif ( is_page( 'public_release' ) ) {
			    $targetPage = 'CityGMLRelease.php';
				$parent = 'public_release';
				
			} elseif ( is_page( 'datadisplay' )) {
			    $targetPage = 'dataDisplay.php';
				$parent = 'datadisplay';
				
			} elseif ( is_page( 'release_manage' )) {
			    $targetPage = 'releaseManage.php';
				$parent = 'release_manage';
				
			} elseif ( is_page( 'topological_consistency' )) {
			    $targetPage = 'topologicalConsistency.php';
				$parent = 'topological_consistency';
				
			} elseif ( is_page( 'datareleace' )) {
			    $targetPage = 'dataDisplay.php';
			    $parent = 'datareleace';
			    
			}
			
			
			$targetPageUrl = 'https://*****.com/UDX/' . $targetPage;
			
			echo '<form method="post" name="g_id" action="' .$targetPageUrl. '" target="post_id">';
        ?>
           <input type="hidden" name="parent" value="<?php echo htmlentities($parent, ENT_QUOTES, 'UTF-8') ?>" /><br/>
           <input type="hidden" name="cityCode" value="<?php echo htmlentities($cityCode, ENT_QUOTES, 'UTF-8') ?>" /><br/>
        </form>
    </body>
</html>