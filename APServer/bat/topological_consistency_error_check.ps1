try {
    $status = 0
    #ToString().Trim('"')でダブルクォーテーションを削除している
    $logFolder = $Args[0].ToString().Trim('"')
    #エラーファイルが作成されていない場合のみ処理を行う
    if(-not (Test-Path $logFolder\error\error.txt)){
        #logsフォルダ直下の全ファイルを取得
        $logList = Get-ChildItem "$logFolder\logs" -File
        #取得したファイル毎に処理を行う
        :logLoop foreach($log in  $logList){
            #StreamReaderを利用してログファイルを読み込む
            $file = New-Object System.IO.StreamReader($log.FullName, [System.Text.Encoding]::GetEncoding("utf-8"))
            #1行ずつ読み込んで処理する
            while (($line = $file.ReadLine()) -ne $null){
                #"ERROR"という文字列が含まれていれば、エラーファイルを作成してループを抜ける
                if($line -clike "*ERROR*"){
                    #errorフォルダとerror.txtを作成
                    New-Item $logFolder\error -ItemType Directory
                    New-Item $logFolder\error\error.txt -Value "CityGMLファイルエラー"
                    #念の為ファイル作成を1秒待つ
                    sleep(1)
                    #break文にラベル名を指定して多重ループを抜ける
                    break logLoop
                }

            }
        }
    }
} catch{
    $status = 1
}finally {
    exit $status
}