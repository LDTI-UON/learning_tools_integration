<?php
/* this is a list of direct ACT parameters as used by EE for AJAX calls.
*   in context of this module ACT requests can be called directly without LTI
*/
// direct ACT services bypass lti completely, allows service provision for some features
$LTI_ACT_services = array("write_rubric" => "libraries/extension_hooks/blackboard/rubrics/ACT/write_rubric.php");

?>
