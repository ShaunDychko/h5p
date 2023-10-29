<?php

namespace Drupal\h5p\Commands;

use Drupal\h5p\H5PLibrarySynchronizer;
use Drush\Commands\DrushCommands;

/**
 * Defines Drush commands for H5P.
 */
class H5pCommands extends DrushCommands {

  /**
   * Synchronizes the H5P libraries.
   *
   * @command h5p:sync-libraries
   *
   * @option force-dependency-save
   *   Re-saves the dependency information even for libraries that are already
   *   installed.
   *
   * @usage drush h5p:sync-libraries
   *   Synchronizes the H5P libraries.
   *
   * @aliases h5p-sl
   */
  public function syncLibraries($options = ['force-dependency-save' => FALSE]) {
    H5PLibrarySynchronizer::syncLibraries($options['force-dependency-save']);
  }

}
