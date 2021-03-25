<?php
function create_zip($path, $zipfile)
{
  try{
	$za = new ZipArchive();
	$za->open($zipfile, ZIPARCHIVE::CREATE);
	zipSub($za, $path);
	$za->close();
	
	return true;//成功
  }catch (Exception $e) {
    return false;//失敗
  }
}
function zipSub($za, $path, $parentPath = '')
{
	$dh = opendir($path);
	while (($entry = readdir($dh)) !== false) {
		if ($entry == '.' || $entry == '..') {
		} else {
			$localPath = $parentPath.$entry;
			$fullpath = $path.'/'.$entry;
			if (is_file($fullpath)) {
				$za->addFile($fullpath, $localPath);
			} else if (is_dir($fullpath)) {
				$za->addEmptyDir($localPath);
				zipSub($za, $fullpath, $localPath.'/');
			}
		}
	}
	closedir($dh);
}
?>