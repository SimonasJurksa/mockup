<?php
// Change bucket using `default` variable.
if (isset($_GET['default']) && !empty($_GET['default'])) {
  $bucket = preg_replace('/[^A-Za-z\-0-9\/]/', '_', $_GET['default']);
  file_put_contents('config/bucket.json', json_encode(['default' => $bucket]));
}
