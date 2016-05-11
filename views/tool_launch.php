<?php
# ------------------------------
# START CONFIGURATION SECTION
#
date_default_timezone_set('Australia/Sydney');

$launch_data = array(
    "user_id" => $launch_params->user_id,
    "roles" => $launch_params->is_instructor == 1 ? "urn:lti:role:ims/lis/Instructor" : "urn:lti:role:ims/lis/Student",
    "resource_link_id" => 'test_link_launch',
    "resource_link_title" => "Test Launch for: ".$username,
    "resource_link_description" => "This is a test launch of this tool from the LTI plugin in ExpressionEngine",
    "lis_person_name_full" => $username,
    "lis_person_name_family" => $username,
    "lis_person_name_given" => $username,
    "lis_person_contact_email_primary" => $email,
    "lis_person_sourcedid" => "test123",
    "context_id" => $launch_params->context_id,
    "context_title" => "Test Launch for ".$launch_params->context_label,
    "context_label" => $launch_params->context_label,
    "tool_consumer_instance_guid" => $launch_params->tool_consumer_instance_guid,
    "tool_consumer_instance_description" => "ExpressionEngine LTI Plugin",
    "context_label" => "Localhost Test",
    "lti_message_type" => "basic-lti-launch-request",
    "launch_presentation_return_url" => $launch_url,
    "ext_lms" => "bb-1.2.4-ee-testing",
    "custom_test_launch" => "lti_ee",
    "csrf_token" => CSRF_TOKEN,
);


// custom data

$launch_data['custom_vle_username'] = $username;
$launch_data['custom_vle_coursename'] = "Local Testing";
$launch_data['custom_debug'] = "true";
#
# END OF CONFIGURATION SECTION
# ------------------------------

$now = new DateTime();

$launch_data["lti_version"] = "LTI-1p0";

# Basic LTI uses OAuth to sign requests
# OAuth Core 1.0 spec: http://oauth.net/core/1.0/

$launch_data["oauth_callback"] = "about:blank";
$launch_data["oauth_consumer_key"] = $key;
$launch_data["oauth_version"] = "1.0";
$launch_data["oauth_nonce"] = uniqid('', true);
$launch_data["oauth_timestamp"] = $now->getTimestamp();
$launch_data["oauth_signature_method"] = "HMAC-SHA1";

# In OAuth, request parameters must be sorted by name
$launch_data_keys = array_keys($launch_data);
sort($launch_data_keys);

$launch_params = array();
foreach ($launch_data_keys as $key) {
  array_push($launch_params, $key . "=" . rawurlencode($launch_data[$key]));
}

$base_string = "POST&" . urlencode($launch_url) . "&" . rawurlencode(implode("&", $launch_params));
$secret = urlencode($secret) . "&";
$signature = base64_encode(hash_hmac("sha1", $base_string, $secret, true));

?>

<html>
<head></head>
<body>
<h3><?php echo date('l jS \of F Y h:i:s A'); ?> <br><?= $launch_url;?></h3>
<form target='_blank' id="ltiLaunchForm" name="ltiLaunchForm" method="POST" action="<?php printf($launch_url); ?>">
<?php foreach ($launch_data as $k => $v ) { ?>
    <input type="hidden" name="<?php echo $k ?>" value="<?php echo $v ?>">
<?php } ?>
    <input type="hidden" name="oauth_signature" value="<?php echo $signature ?>">
    <button type="submit">Launch</button>
</form>

</body>

</html>
