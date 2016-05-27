<?php
namespace LTI\ExtensionHooks;

use \Defuse\Crypto\Crypto;
use \Defuse\Crypto\Exception as Ex;

require_once PATH_THIRD.'learning_tools_integration/libraries/defuse/autoload.php';

class Encryption {

       static function generate_key() {
           try {
                 $key = Crypto::createNewRandomKey();
           } catch (Ex\CryptoTestFailedException $ex) {
                die('Cannot safely create a key');
            } catch (Ex\CannotPerformOperationException $ex) {
                die('Cannot safely create a key');
            }

          return $key;
      }

      static function encrypt($message, $key) {
          try {
                $ciphertext = Crypto::encrypt($message, $key);
            } catch (Ex\CryptoTestFailedException $ex) {
                die('Cannot safely perform encryption');
            } catch (Ex\CannotPerformOperationException $ex) {
                die('Cannot safely perform encryption');
            }

         return $ciphertext;
      }

     static function decrypt($ciphertext, $key) {
         try {
            $decrypted = Crypto::decrypt($ciphertext, $key);
        } catch (Ex\InvalidCiphertextException $ex) { // VERY IMPORTANT
            /*
                return false on bad ciphertext. this can then
                be used to deal with the compromised data
            */
             return FALSE;
        } catch (Ex\CryptoTestFailedException $ex) {
            die('Cannot safely perform decryption');
        } catch (Ex\CannotPerformOperationException $ex) {
            die('Cannot safely perform decryption');
        }
        return $decrypted;
     }

     static function get_salt($context_id) {
     	$salt_key = NULL;
     	$cwd = PATH_THIRD.'/learning_tools_integration/libraries';

     	if(!file_exists($cwd.'/secret') || !is_writable($cwd.'/secret')) {
            if(ee()->session->userdata('group_id') == 1) {
                  $process_name = posix_getpwuid(posix_geteuid())['name'];
                die("<pre>To the super user:\n\nPlease create the /secret folder in:\n\n \t".str_replace(SYSDIR, "#########", dirname(__FILE__))."\n\nand chmod to 700 ensure the current process ($process_name)\nowns this folder.</pre>");
            }
     	}

     	$unenc = $context_id;
     	$context_id = md5($context_id);
     	$base_dir = $cwd."/secret/".$context_id;

     	if(!file_exists($base_dir)) {
     		mkdir($base_dir);
     	}

     	if(file_exists($base_dir."/secret.bin")) {
     		$key_file = fopen($base_dir."/secret.bin", 'rb');
     		$salt_key = fread($key_file, filesize($base_dir."/secret.bin"));
     		fclose($key_file);
     	} else {
     		$key_file = fopen($base_dir."/secret.bin", 'wb');
     		$salt_key = static::generate_key();
     		fwrite($key_file, $salt_key);
     		fclose($key_file);
     	}

     	return $salt_key;
     }

}
