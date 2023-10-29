<?php

namespace Drupal\h5p\H5PDrupal;

use \H5PDefaultStorage;
use \H5PCore;

class H5PDrupalFileStorage extends H5PDefaultStorage {

  protected $libraryPath;

  public function __construct($path, $alt_editor_path = NULL, $library_path = NULL) {
    if (isset($library_path)) {
      $this->libraryPath = $library_path;
    }
    else {
      $this->libraryPath = $path;
    }
    parent::__construct($path, $alt_editor_path);
  }

  /**
   * {@inheritdoc}
   */
  public function saveLibrary($library) {
    $dest = $this->libraryPath . '/libraries/' . H5PCore::libraryToString($library, TRUE);

    // Make sure destination dir doesn't exist
    H5PCore::deleteFileTree($dest);

    // Move library folder
    self::copyFileTree($library['uploadDirectory'], $dest);
  }


  /**
   * Fetch library folder and save in target directory.
   *
   * @param array $library
   *  Library properties
   * @param string $target
   *  Where the library folder will be saved
   * @param string $developmentPath
   *  Folder that library resides in
   */
  public function exportLibrary($library, $target, $development_path = NULL) {
    $folder = \H5PCore::libraryToString($library, TRUE);

    $srcPath = (!isset($development_path) ? \rtrim($this->libraryPath, '/') . '/libraries/' . $folder : $development_path);
    self::copyFileTree($srcPath, "{$target}/{$folder}");
  }

  /**
   * Recursive function for copying directories.
   *
   * 1-1 copy from the parent class because it's private.
   *
   * @param string $source
   *  From path
   * @param string $destination
   *  To path
   * @return boolean
   *  Indicates if the directory existed.
   *
   * @throws Exception Unable to copy the file
   */
  private static function copyFileTree($source, $destination) {
    if (!self::dirReady($destination)) {
      throw new \Exception('unabletocopy');
    }

    $ignoredFiles = self::getIgnoredFiles("{$source}/.h5pignore");

    $dir = opendir($source);
    if ($dir === FALSE) {
      trigger_error('Unable to open directory ' . $source, E_USER_WARNING);
      throw new \Exception('unabletocopy');
    }

    while (false !== ($file = readdir($dir))) {
      if (($file != '.') && ($file != '..') && $file != '.git' && $file != '.gitignore' && !in_array($file, $ignoredFiles)) {
        if (is_dir("{$source}/{$file}")) {
          self::copyFileTree("{$source}/{$file}", "{$destination}/{$file}");
        }
        else {
          copy("{$source}/{$file}", "{$destination}/{$file}");
        }
      }
    }
    closedir($dir);
  }

  /**
   * Recursive function that makes sure the specified directory exists and
   * is writable.
   *
   * 1-1 copy because it's private.
   *
   * @param string $path
   * @return bool
   */
  private static function dirReady($path) {
    if (!file_exists($path)) {
      $parent = preg_replace("/\/[^\/]+\/?$/", '', $path);
      if (!self::dirReady($parent)) {
        return FALSE;
      }

      mkdir($path, 0777, true);
    }

    if (!is_dir($path)) {
      trigger_error('Path is not a directory ' . $path, E_USER_WARNING);
      return FALSE;
    }

    if (!is_writable($path)) {
      trigger_error('Unable to write to ' . $path . ' – check directory permissions –', E_USER_WARNING);
      return FALSE;
    }

    return TRUE;
    }

 /**
  * Retrieve array of file names from file.
  *
  * 1-1 copy due to private.
  *
  * @param string $file
  * @return array Array with files that should be ignored
  */
  private static function getIgnoredFiles($file) {
    if (file_exists($file) === FALSE) {
      return array();
    }

    $contents = file_get_contents($file);
    if ($contents === FALSE) {
      return array();
    }

    return preg_split('/\s+/', $contents);
  }


}
