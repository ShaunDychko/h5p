<?php

namespace Drupal\h5p\Drush\Commands;

use Composer\InstalledVersions;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A Drush commandfile.
 */
final class H5pCommands extends DrushCommands {

  use AutowireTrait;

  /**
   * Constructs a H5pCommands object.
   */
  public function __construct(
    private readonly Token $token,
    private ModuleExtensionList $moduleExtensionList,
  ) {
    parent::__construct();
  }

  /**
   * Copy assets, such as CSS and JS files, from the h5p/h5p-core and
   *   h5p/h5p-editor composer packages into the h5p module directory.
   */
  #[CLI\Command(name: 'h5p:copy-assets')]
  #[CLI\FieldLabels(labels: [
    'source' => 'Source',
    'destination' => 'Destination',
    'type' => 'Type',
  ])]
  #[CLI\DefaultTableFields(fields: ['source', 'destination', 'type'])]
  #[CLI\Usage(name: 'h5p:copy-assets', description: 'Copy assets, such as CSS and JS files, from the h5p/h5p-core and h5p/h5p-editor composer packages into the h5p module directory.')]
  public function copyAssets(): RowsOfFields {
    $filesystem = new Filesystem();
    $rows = [];

    // Specifications are keyed by composer project name.
    $specifications = [
      'h5p-core' => [
        'directories' => [
          'js',
          'fonts',
          'images',
          'styles',
        ],
        'files' => [
          'embed.php',
        ],
        'module_name' => 'h5p',
      ],
      'h5p-editor' => [
        'directories' => [
          'ckeditor',
          'images',
          'language',
          'libs',
          'scripts',
          'styles/css',
        ],
        'files' => [],
        'module_name' => 'h5peditor',
      ],
    ];
    foreach ($specifications as $project_name => $params) {
      $source_root = InstalledVersions::getInstallPath("h5p/$project_name");
      $destination_root = Drupal::root() . '/' . $this->moduleExtensionList->getPath($params['module_name']) . '/assets/' . $project_name;
      foreach ($params['directories'] as $directory) {
        $source_path = "$source_root/$directory";
        $destination_path = "$destination_root/$directory";
        $filesystem->mirror($source_path, $destination_path);
        $rows[] = [
          'source' => $source_path,
          'destination' => $destination_path,
          'type' => 'Directory',
        ];
      }
      // Copy files.
      foreach ($params['files'] as $file) {
        $source_path = "$source_root/$file";
        $destination_path = "$destination_root/$file";
        $filesystem->copy($source_path, $destination_path, TRUE);
        $rows[] = [
          'source' => $source_path,
          'destination' => $destination_path,
          'type' => 'File',
        ];
      }
    }
    $this->logger()->success(dt('Copied the following directories and files:'));
    return new RowsOfFields($rows);
  }

  /**
   * An example of the table output format.
   */
  #[CLI\Command(name: 'h5p:token', aliases: ['token'])]
  #[CLI\FieldLabels(labels: [
    'group' => 'Group',
    'token' => 'Token',
    'name' => 'Name',
  ])]
  #[CLI\DefaultTableFields(fields: ['group', 'token', 'name'])]
  #[CLI\FilterDefaultField(field: 'name')]
  public function token($options = ['format' => 'table']): RowsOfFields {
    $all = $this->token->getInfo();
    foreach ($all['tokens'] as $group => $tokens) {
      foreach ($tokens as $key => $token) {
        $rows[] = [
          'group' => $group,
          'token' => $key,
          'name' => $token['name'],
        ];
      }
    }
    return new RowsOfFields($rows);
  }

}
