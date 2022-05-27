<?php

namespace Drupal\h5p;

use Drupal\h5p\H5PDrupal\H5PDrupal;
use H5PMetadata;

class H5PLibrarySynchronizer {

  /**
   * Sync libraries from the libraries directory to the database cache.
   *
   * @param bool $force_dependency_save
   *   (optional) Whether to save dependencies even for libraries that are
   *   already installed. Defaults to FALSE.
   */
  public static function syncLibraries($force_dependency_save = FALSE) {
    $libraries_dir = H5PDrupal::getH5PLibrariesPath();
    $file_system = \Drupal::service('file_system');
    $scan = $file_system->scanDirectory($libraries_dir, '/library.json/');
    $h5p_validator = H5pDrupal::getInstance('validator');
    $h5p_drupal = H5PDrupal::getInstance();
    $h5p_core = H5pDrupal::getInstance('core');

    $new_ones = 0;
    $old_ones = 0;
    // H5P uploads are designed to deal with zips, however in our case we
    // already have an unzipped library in the libraries directory. So, use
    // what we can from the API but otherwise duplicate some basic logic here.
    // Where a temporary upload directory is required, either pass the library
    // directory so that files can be found, or workaround things like deletion.
    foreach ($scan as $uri => $object) {
      $absolute_uri = DRUPAL_ROOT . '/' .  $uri;
      $library_string = basename(dirname($uri));
      $library_data = $h5p_validator->getLibraryData($library_string, dirname($absolute_uri), dirname($absolute_uri, 2));
      if ($library_data) {
        $libraries[$library_string] = $library_data;
      }
    }
    foreach ($libraries as $library_string => &$library) {
      // Logic borrowed from H5PStorage::saveLibraries().
      //
      // Assume new library
      $library_id = $h5p_drupal->getLibraryId($library['machineName'], $library['majorVersion'], $library['minorVersion']);

      $new = TRUE;
      if ($library_id) {
        // Found old library
        $library['libraryId'] = $library_id;

        if ($h5p_drupal->isPatchedLibrary($library)) {
          // This is a newer version than ours. Upgrade!
          $new = FALSE;
        }
        else {
          $library['saveDependencies'] = $force_dependency_save;
          // This is an older version, no need to save.
          continue;
        }
      }

      // Indicate that the dependencies of this library should be saved.
      $library['saveDependencies'] = TRUE;

      // Convert metadataSettings values to boolean & json_encode it before saving
      $library['metadataSettings'] = isset($library['metadataSettings']) ?
        H5PMetadata::boolifyAndEncodeSettings($library['metadataSettings']) :
        NULL;
      $h5p_drupal->saveLibraryData($library, $new);

      // Remove cached assets that uses this library
      if ($h5p_core->aggregateAssets && isset($library['libraryId'])) {
        $removedKeys = $h5p_drupal->deleteCachedAssets($library['libraryId']);
        $h5p_drupal->deleteCachedAssets($removedKeys);
      }

      if ($new) {
		   $new_ones++;
      }
		  else {
        $old_ones++;
      }
		}

    // Go through the libraries again to save dependencies.
    $library_ids = [];
    foreach ($libraries as &$library) {
      if (!$library['saveDependencies']) {
        continue;
      }

      // Remove any old dependencies
      $h5p_drupal->deleteLibraryDependencies($library['libraryId']);

      // Insert the different new ones
      if (isset($library['preloadedDependencies'])) {
        $h5p_drupal->saveLibraryDependencies($library['libraryId'], $library['preloadedDependencies'], 'preloaded');
      }
      if (isset($library['dynamicDependencies'])) {
        $h5p_drupal->saveLibraryDependencies($library['libraryId'], $library['dynamicDependencies'], 'dynamic');
      }
      if (isset($library['editorDependencies'])) {
        $h5p_drupal->saveLibraryDependencies($library['libraryId'], $library['editorDependencies'], 'editor');
      }

      $library_ids[] = $library['libraryId'];
    }

    // Make sure libraries dependencies, parameter filtering
    // and export files get regenerated for all content using
    // these libraries.
    if (!empty($library_ids)) {
      $h5p_drupal->clearFilteredParameters($library_ids);
    }

    // @todo: logging

  }
}
