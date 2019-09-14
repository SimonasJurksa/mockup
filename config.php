<?php
// Change bucket using `default` variable.
if (isset($_GET['bucket']) && !empty($_GET['bucket'])) {
  $bucket = preg_replace('/[^A-Za-z\-0-9\/]/', '_', $_GET['bucket']);
  $contents = json_encode(['bucket' => $bucket]);
  file_put_contents('config/bucket.json', $contents);
  echo $contents;
}
