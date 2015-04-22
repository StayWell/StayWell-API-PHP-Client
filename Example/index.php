<!DOCTYPE html>
<html>
<?php
    require_once("../Client/SwApiClient.class.php");
?>
<head>
    <meta charset="utf-8" />
    <title>PHP Test</title>
</head>
<body>
<?php

//$client = new SwApiClient('[copy application ID here]', '[copy application secret here]');
$client = new SwApiClient('e9046a49-aa58-42eb-82ec-a2cb0160c9ad', 'M4ufAu9qyOHqRlfkgocer4CJPMZAOACwWmtLlY2BssUAuMfUtLMxa4IJZB0kpzAU', 'http://10.10.20.158/kswapi');

// search for diabetes articles:

$searchResult = $client->searchContent(0, 10, array('query' => 'diabetes'));

if (count($searchResult->Items) === 0) {
    echo 'No results';
    return;
}

// display the first search result:

$contentItem = $searchResult->Items[0];

$contentResult = $client->getContent($contentItem->Bucket->Slug, $contentItem->Slug);

// display all segments (the entire body)

foreach ($contentResult->Segments as $segment) {
    echo $segment->Body;
}

?>
</body>
</html>