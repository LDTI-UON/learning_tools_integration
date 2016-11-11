<?php
namespace LTI\ExtensionHooks;

use \Defuse\Crypto\Crypto;
use \Defuse\Crypto\Key;
use \Defuse\Crypto\Exception as Ex;

// obtain this file securely at https://github.com/defuse/php-encryption/blob/master/docs/InstallingAndVerifying.md
require_once(PATH_THIRD.'/learning_tools_integration/libraries/php-encryption/defuse-crypto.phar');

class Encryption {

       static function generate_key() {
           try {
                 $key = Key::createNewRandomKey();
           } catch (Ex\CryptoTestFailedException $ex) {
                die('Cannot safely create a key');
            } catch (Ex\CannotPerformOperationException $ex) {
                die('Cannot safely create a key');
            }

          $ascii_safe = $key->saveToAsciiSafeString();

          return $ascii_safe;
      }

      static function encrypt($message, $key) {
          try {
                $__key = Key::loadFromAsciiSafeString($key);
                $ciphertext = Crypto::encrypt($message, $__key);
            } catch (Ex\CryptoTestFailedException $ex) {
                die('Cannot safely perform encryption');
            } catch (Ex\CannotPerformOperationException $ex) {
                die('Cannot safely perform encryption');
            }

         return $ciphertext;
      }

     static function decrypt($ciphertext, $key) {
         try {
              $__key = Key::loadFromAsciiSafeString($key);
              $decrypted = Crypto::decrypt($ciphertext, $__key);
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
     	//$cwd = PATH_THIRD.'learning_tools_integration/libraries/extension_hooks/classes';

     	if(!file_exists(__DIR__.'/secret') || !is_writable(__DIR__.'/secret')) {
            if(ee()->session->userdata('group_id') == 1) {
                  $process_name = posix_getpwuid(posix_geteuid())['name'];

                  $secret_dir = __DIR__.DIRECTORY_SEPARATOR.'secret';

                  mkdir($secret_dir);
                  chmod($secret_dir, 0700);

                  if(!file_exists(__DIR__.'/secret') || !is_writable(__DIR__.'/secret')) {
                      die("<pre>To the super user:\n\nPlease create the /secret folder in:\n\n \t".str_replace(SYSDIR, "#########", __DIR__)."\n\nand chmod to 700 ensure the current process ($process_name)\nowns this folder.</pre>");
                  }
            }
     	}

     	$unenc = $context_id;
     	$context_id = md5($context_id);
     	$base_dir = __DIR__."/secret/".$context_id;

     	if(!file_exists($base_dir)) {
     		mkdir($base_dir);
     	}

     	if(file_exists($base_dir."/secret.bin")) {
        $salt_key = file_get_contents($base_dir."/secret.bin");
     	} else {
     		$salt_key = static::generate_key();
        file_put_contents($base_dir."/secret.bin", $salt_key);
     	}

     	return $salt_key;
     }

}
