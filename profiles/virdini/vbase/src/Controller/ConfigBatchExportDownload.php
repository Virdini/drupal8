<?php

namespace Drupal\vbase\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\system\FileDownloadController;

/**
 * Provides a 'ConfigBatchExportDownload'
 */
class ConfigBatchExportDownload extends ControllerBase {

  public function download() {
    $fileDownloadController = new FileDownloadController();
    $request = new Request(['file' => 'config.tar.gz']);
    return $fileDownloadController->download($request, 'temporary');
  }

}
