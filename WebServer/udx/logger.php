<?php
/**
 * ログ
 */
class Logger {

    // ログレベル
    const LOG_LEVEL_ERROR = 0;
    const LOG_LEVEL_WARN = 1;
    const LOG_LEVEL_INFO = 2;
    const LOG_LEVEL_DEBUG = 3;

    private static $singleton;

    /**
     * インスタンスを生成する
     */
    public static function getInstance()
    {
        if (!isset(self::$singleton)) {
            self::$singleton = new Logger();
        }
        return self::$singleton;
    }

    /**
     * コンストラクタ
     */
    private function __construct() {
    }

    /**
     * ERRORレベルのログ出力する
     * @param string $msg メッセージ
     */
    public function error($msg ,$cityCode) {
        if(self::LOG_LEVEL_ERROR <= Config::LOG_LEVEL) {
            $this->out('ERROR', $msg ,$cityCode);
        }
    }

    /**
     * WARNレベルのログ出力する
     * @param string $msg メッセージ
     */
    public function warn($msg ,$cityCode) {
        if(self::LOG_LEVEL_WARN <= Config::LOG_LEVEL) {
            $this->out('WARN', $msg ,$cityCode);
        }
    }

    /**
     * INFOレベルのログ出力する
     * @param string $msg メッセージ
     */
    public function info($msg ,$cityCode) {
        if(self::LOG_LEVEL_INFO <= Config::LOG_LEVEL) {
            $this->out('INFO', $msg ,$cityCode);
        }
    }

    /**
     * DEBUGレベルのログ出力する
     * @param string $msg メッセージ
     */
    public function debug($msg ,$cityCode) {
        if(self::LOG_LEVEL_DEBUG <= Config::LOG_LEVEL) {
            $this->out('DEBUG', $msg ,$cityCode);
        }
    }

    /**
     * ログ出力する
     * @param string $level ログレベル
     * @param string $msg メッセージ
     */
    private function out($level, $msg, $cityCode) {
        try {
            if(Config::IS_LOGFILE && is_numeric( $cityCode)) {

            $pid = getmypid(); //プロセスID
            $time = date('Y/m/d H:i:s', time() +32400); //時差9時間(32400秒)を調整
            $logMessage = "[{$time}][{$pid}][{$level}] " . rtrim($msg) . "\r\n";
            $logFilePath = Config::LOGDIR_PATH . $cityCode . '_' . Config::LOGFILE_NAME . '.log';

            $result = file_put_contents($logFilePath, $logMessage, FILE_APPEND | LOCK_EX);
                if($result === false) {
                error_log('LogUtil::out error_log ERROR', 0);
                    return;
            }

            if(Config::LOGFILE_MAXSIZE < filesize($logFilePath)) {
                // ファイルサイズを超えた場合、リネームしてgz圧縮する
                $oldPath = Config::LOGDIR_PATH . $cityCode . '_' . Config::LOGFILE_NAME . '_' . date('YmdHis');
                $oldLogFilePath = $oldPath . '.log';
                rename($logFilePath, $oldLogFilePath);
                /*
                $gz = gzopen($oldPath . '.gz', 'w9');
                if($gz) {
                    gzwrite($gz, file_get_contents($oldLogFilePath));
                    $isClose = gzclose($gz);
                    if($isClose) {
                        unlink($oldLogFilePath);
                    } else {
                        error_log("gzclose ERROR.", 0);
                    }
                } else {
                    error_log("gzopen ERROR.", 0);
                }
                */

                /*
                // 古いログファイルを削除する
                $retentionDate = new DateTime();
                $retentionDate->modify('-' . Config::LOGFILE_PERIOD . ' day');
                if ($dh = opendir(Config::LOGDIR_PATH)) {
                    while (($fileName = readdir($dh)) !== false) {
                        $pm = preg_match("/" . preg_quote(Config::LOGFILE_NAME) . "_(\d{14}).*\.log/", $fileName, $matches);
                        if($pm === 1) {
                            $logCreatedDate = DateTime::createFromFormat('YmdHis', $matches[1]);
                            if($logCreatedDate < $retentionDate) {
                                unlink(Config::LOGDIR_PATH . '/' . $fileName);
                            }
                        }
                    }
                    closedir($dh);
                }
                */
            }
            }
        } catch (Throwable $th) {
        }
    }

    /**
     * 現在時刻を取得する
     * @return string 現在時刻
     */
    private function getTime() {
        $miTime = explode('.',microtime(true));
        $msec = str_pad(substr($miTime[1], 0, 3) , 3, "0");
        $time = date('Y-m-d H:i:s', $miTime[0]) . '.' .$msec;
        return $time;
    }
}