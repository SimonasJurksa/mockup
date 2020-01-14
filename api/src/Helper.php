<?php

/**
 * Class Helper
 */
class Helper {

  /**
   * @var array
   */
  private $config = [];

  /**
   * Helper constructor.
   */
  public function __construct() {
    // Check if file is provided.
    if (file_exists('config/settings.php')) {
      $this->setConfig(include 'config/settings.php');
    }
  }

  /**
   * @param $config
   */
  private function setConfig($config) {
    $this->config = $config;
  }

  /**
   * Fetches you configs if such provided.
   *
   * @param null $key
   *
   * @return mixed
   */
  public function getConfig($key, $defaultValue) {
    return ($this->config[$key]) ?? $defaultValue;
  }

  /**
   * Fetches you bucket name that will make subdir under response.
   * This needed when you get different response on the same request baset on
   * some variouse things - date/time, environment.
   *
   * @return mixed
   */
  public function getBucket() {
    return json_decode(file_get_contents('config/bucket.json'))->bucket;
  }

}
