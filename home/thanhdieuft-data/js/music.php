<?php
$td_unicode =  JSON_UNESCAPED_UNICODE;
$td_formatspace = JSON_UNESCAPED_SLASHES;
$ThanhDieu_List = [
    "//files.catbox.moe/sly2n7.mp3" => "Cho em 1 lần yêu",
    "//thanhdieu.com/files/td1.mp3" => "Giày cao gót màu đỏ",
];

// Xử lý mỗi liên kết trong mảng để định dạng lại
$formattedLinks = array_map(function($link, $name) {
    return [
        'music' => str_replace('\/', '/', $link),
        'name' => $name,
    ];
}, $ThanhDieu_List, array_keys($ThanhDieu_List));

$index = array_rand($formattedLinks);
$LinkData = $formattedLinks[$index];
$response = [
    'name' => $LinkData['music'],
    'music' => $LinkData['name'],
];
header('Content-Type: application/json');
echo json_encode($response, $td_formatspace | $td_unicode);
?>