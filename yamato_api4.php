<?php
$html = file_get_contents('./response_debug.html'); // またはAPIレスポンス文字列

$doc = new DOMDocument();
libxml_use_internal_errors(true); // HTMLの警告を無視
$doc->loadHTML($html);
libxml_clear_errors();

// タイトルを取得
$title = $doc->getElementsByTagName('title')->item(0)->textContent;
echo "タイトル: $title\n";

// bodyのテキストを取得
$body = $doc->getElementsByTagName('body')->item(0);
$bodyText = $body->textContent;
echo "本文テキスト:\n$bodyText\n";

// scriptタグの内容を取得
$scripts = $doc->getElementsByTagName('script');
foreach ($scripts as $script) {
    $jsContent = $script->textContent;
    echo "---- script ----\n";
    echo $jsContent . "\n";
}
?>