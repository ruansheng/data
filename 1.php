<?php

/**
有效：
    1、志愿者上报的日期要在项目发起前3天至后一天
    2、第一条都满足，优先上报时间早的为有效
    3、如果前两条满足，以归属地为准
    4、前三条符合，优先绑定

    同一链接最多产生一份有效
 */

function cronLoadData() {
    $file   = './data.csv';
    $handle = fopen($file, 'r');
    if (!$handle) {
        die("can not open $file");
    }

    $total = 0;
    while (!feof($handle)) {
        $line   = fgets($handle, 1024);
        $rows = explode(';', $line);
        print_r($rows);
        // 判断是否存在 存在则增量更新 不存在则添加

        $total++;
    }
    fclose($handle);
    echo "total:" . $total . PHP_EOL;
}

function writeFirstCate($row) {
    $mongo = new MongoBase('127.0.0.1', 27017, 'data', 'old_data');
    $mongo->insert($row);
}

function getMongoCount() {
    $mongo = new MongoBase('127.0.0.1', 27017, 'data', 'old_data');
    return $count = $mongo->count([]);
}

function writeMongo($list) {
    $mongo = new MongoBase('127.0.0.1', 27017, 'data', 'old_data');
    foreach($list as $row) {
        $query = [
            'project_id' => $row['project_id']
        ];
        $mongo->update($query, $row, true);
    }
}

cronLoadData();