try {
    $status = 0
    #ToString().Trim('"')�Ń_�u���N�H�[�e�[�V�������폜���Ă���
    $logFolder = $Args[0].ToString().Trim('"')
    #�G���[�t�@�C�����쐬����Ă��Ȃ��ꍇ�̂ݏ������s��
    if(-not (Test-Path $logFolder\error\error.txt)){
        #logs�t�H���_�����̑S�t�@�C�����擾
        $logList = Get-ChildItem "$logFolder\logs" -File
        #�擾�����t�@�C�����ɏ������s��
        :logLoop foreach($log in  $logList){
            #StreamReader�𗘗p���ă��O�t�@�C����ǂݍ���
            $file = New-Object System.IO.StreamReader($log.FullName, [System.Text.Encoding]::GetEncoding("utf-8"))
            #1�s���ǂݍ���ŏ�������
            while (($line = $file.ReadLine()) -ne $null){
                #"ERROR"�Ƃ��������񂪊܂܂�Ă���΁A�G���[�t�@�C�����쐬���ă��[�v�𔲂���
                if($line -clike "*ERROR*"){
                    #error�t�H���_��error.txt���쐬
                    New-Item $logFolder\error -ItemType Directory
                    New-Item $logFolder\error\error.txt -Value "CityGML�t�@�C���G���["
                    #�O�̈׃t�@�C���쐬��1�b�҂�
                    sleep(1)
                    #break���Ƀ��x�������w�肵�đ��d���[�v�𔲂���
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