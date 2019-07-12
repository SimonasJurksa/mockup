<?php
//
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//ini_set('html_errors', 1);
//
//error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
require_once 'src/Mockup.php';
require_once 'src/Helper.php';

$mockup = new Mockup();
if (!$mockup->isMockup()) {
  $mockup->saveMockup();
}
$mockup->respondMockup();
