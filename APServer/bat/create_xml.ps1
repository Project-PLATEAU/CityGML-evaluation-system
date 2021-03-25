try {
    $status = 0
    #ToString().Trim('"')でダブルクォーテーションを削除している
    [xml]$x = Get-Content $Args[2].ToString().Trim('"') -Encoding UTF8
    $fileSet = $x.featureLayer.metadata.dataSource.cityGMLSource.input.fileSet.dir = $Args[0].ToString().Trim('"')
    $fileName = $x.featureLayer.metadata.dataSource.cityGMLSource.input.fileSet.fileName.name = $Args[1].ToString().Trim('"')
    if($Args[3] -eq '2D'){
        $url = $x.featureLayer.metadata.terrainDataSource.quantizedMeshTerrainSource.url = 'http://*****/map/' + $Args[4].ToString().Trim('"') + '/public/datasource-data/Terrain'
    }
    $x.Save($Args[2].ToString().Trim('"'))
} catch{
    $status = 1
}finally {
    exit $status
}