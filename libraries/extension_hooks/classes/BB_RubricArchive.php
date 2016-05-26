<?php
namespace LTI\ExtensionHooks;

class BB_RubricArchive {
  public static function unpack($path, $zip_file_name, $rubric_dir) {
      $zip = new ZipArchive;
    $res = $zip -> open($path.DIRECTORY_SEPARATOR.$zip_file_name);

    if ($res === TRUE) {
          // extract it to the path we determined above
          for ($i = 0; $i < $zip -> numFiles; $i++) {
              $filename = $zip -> getNameIndex($i);
              $fileinfo = pathinfo($filename);

              copy("zip://" . $path.DIRECTORY_SEPARATOR.$zip_file_name ."#". $filename, $rubric_dir . DIRECTORY_SEPARATOR . $fileinfo['basename']);
          }
      }
  $zip -> close();

  unlink($path.DIRECTORY_SEPARATOR.$zip_file_name);
  }
}
?>
