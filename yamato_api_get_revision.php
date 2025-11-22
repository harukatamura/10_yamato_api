<?php
$tracking_number = $_GET['tracking_number'] ?? '';
// B2クラウドAPI呼び出しして最新Revisionを取得
// 実装例省略
echo json_encode(["id"=>$tracking_number."-最新"]);