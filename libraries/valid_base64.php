<?php
function validBase64($string)
{
  $decoded = base64_decode($string, true);

  // Check if there is no invalid character in string
  if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string)) return false;

  // Decode the string in strict mode and send the response
  if (!base64_decode($string, true)) return false;

  // Encode and compare it to original one
  if (base64_encode($decoded) != $string) return false;

  return true;
}
?>
