<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.mangadex.org/manga?title=Solo");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "MancoSync/1.0 (mancosync-development@gmail.com)");
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); // Force HTTP/1.1

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Curl Error: ' . curl_error($ch) . "\n";
    echo 'Curl Error Code: ' . curl_errno($ch) . "\n";
} else {
    echo "Response:\n" . substr($response, 0, 1000) . "\n";
}
curl_close($ch);
