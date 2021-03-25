<?php
/**
 * 設定クラス
 */
class Config {
    const IS_LOGFILE = true; // ログファイル出力フラグ true=出力あり/false=なし
    const LOG_LEVEL = 3; // ログレベル 0=ERROR/1=WARN/2=INFO/3=DEBUG
    const LOGDIR_PATH = 'F:/LOG/'; // ログファイル出力ディレクトリ
    const LOGFILE_NAME = 'console'; // ログファイル名
    const LOGFILE_MAXSIZE = 10485760; // ログファイル最大サイズ（Byte）
    const LOGFILE_PERIOD = 7; // ログ保存期間（日）
}

?>