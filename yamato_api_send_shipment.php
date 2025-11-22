<?php
set_time_limit(300);

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if(!$data || !isset($data['feed']['entry'])){
    http_response_code(400);
    echo json_encode(["error"=>"データ不正"]);
    exit;
}

function sendToB2Cloud($batch){
    $ch = curl_init("https://b2-cloud.example.com/b2/p/new?issue_editA");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($batch, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ["http_code"=>$httpCode,"response"=>$response];
}

// 最新Revision取得＆リトライ
function sendWithRetry($batch){
    $result = sendToB2Cloud($batch);
    if($result['http_code']==409){
        foreach($batch['feed']['entry'] as &$entry){
            // 実運用では get_revision.php で最新Revision取得
            $entry['id'] = $entry['id']."-最新";
        } unset($entry);
        $result = sendToB2Cloud($batch);
    }
    return $result;
}

$result = sendWithRetry($data);

header("Content-Type: application/json");
echo json_encode($result);