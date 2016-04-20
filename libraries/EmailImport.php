<?php
class EmailImport {

private $username;
private $password;
private $context_id;
private $hostname;

private $email_subject;
private $email_from;

private $domain;

private $cache_path;

function __construct($username, $password, $context_id, $email_subject = '', $email_from = '', $cache_path = NULL, $domain = 'outlook.office365.com:993') {
      if($cache_path === NULL) {
      	$this->cache_path = PATH_THIRD."learning_tools_integration/cache/";
      }

	  $this->username = $username;
      $this->password = $password;
      $this->context_id = $context_id;
      $this->domain = $domain;

      $this->cache_path = $cache_path;
      $this->email_from = $email_from;
      $this->email_subject = $email_subject;

      $this->hostname = "{".$this->domain."/imap/ssl/authuser=".$this->username."}INBOX";
}

function imap_auth() {
    /* try to connect */
    $inbox = imap_open($this->hostname, $this->username, $this->password, OP_READONLY);
	$error = -1;

    if(FALSE === $inbox) {
        $message = imap_last_error();
        $needle = 'AUTHENTICATE';

    	if(strpos($message, $needle) !== FALSE) {
        	// bad AUTHENTICATE
        	$error = 1;
    	} else {
    		$error = 2;
    	}

    	return $error;
    }

    return $inbox;
}

/* fetches the latest group export CSV from Outlook 365. */
function fetch_export_csv_from_outlook() {

if(empty($this->email_from) || empty($this->email_subject)) {
	return FALSE;
}
/* connect to outlook */
$inbox = $this->imap_auth();

if($inbox === FALSE) {
    return FALSE;
}

$imported_emails = array();

$cache = $this->cache_path.'/email_cache.bin';

if(file_exists($cache)) {
	$imported_emails = unserialize(file_get_contents($cache));

	// remove emails older than one day
	foreach($imported_emails as $uid => $time) {
		if((time() - $time) > (24 * 60 * 60)) {
			unset($imported_emails[$uid]);
		}
	}
}

$status = imap_status($inbox, $this->hostname, SA_ALL);

$date = date('d M Y', strtotime('-1 day', time())); // previous day's emails
/* grab emails */
$emails = imap_search($inbox,'SUBJECT "'.$this->email_subject.'" FROM "'.$this->email_from.'" SINCE "'.$date.'"', SE_UID);

/* if emails are returned, cycle through each... */
if($emails) {

    /* begin output var */
    $output = '';

    /* put the newest emails on top */
    rsort($emails);
    $attachments = array();

    /* for every email... */
    foreach($emails as $email_number) {
    	if(!array_key_exists($email_number, $imported_emails)) {
    		if (php_sapi_name() === "cli")  {
    			print "importing email $email_number.\n";
    		}

        $imported_emails[$email_number] = time();

    	$msgno = imap_msgno($inbox, $email_number);
        $headers = imap_headerinfo($inbox, $msgno);
        $structure = imap_fetchstructure($inbox, $email_number, FT_UID);


        if(isset($structure->parts) && count($structure->parts)) {
            var_dump($structure);
       for($i = 0; $i < count($structure->parts); $i++) {

        $attachments[$i] = array(
            'is_attachment' => false,
            'filename' => '',
            'name' => '',
            'attachment' => ''
        );

        if($structure->parts[$i]->ifdparameters) {
            foreach($structure->parts[$i]->dparameters as $object) {
                if(strtolower($object->attribute) == 'filename') {
                    $attachments[$i]['is_attachment'] = true;
                    $attachments[$i]['filename'] = $object->value;
                }
            }
        }

        if($structure->parts[$i]->ifparameters) {
            foreach($structure->parts[$i]->parameters as $object) {
                if(strtolower($object->attribute) == 'name') {
                    $attachments[$i]['is_attachment'] = true;
                    $attachments[$i]['name'] = $object->value;
                }
            }
        }

        if($attachments[$i]['is_attachment']) {
            $msgno = imap_msgno($inbox, $email_number);
            $attachments[$i]['attachment'] = imap_fetchbody($inbox, $msgno, $i+1);
            if($structure->parts[$i]->encoding == 3) { // 3 = BASE64
                $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
            }
            elseif($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
            }
          }
           }
        }
      }
    }
}

$cdata = serialize($imported_emails);
 echo ">>>> cp: ".$this->cache_path."\n";
    print "cache val: ".$cache;

file_put_contents($cache, $cdata);

if (php_sapi_name() === "cli")  {
	print "\n";
}

$attachment = null;

if(!empty($attachments)) {
    // get as attachment from email
    $attachment = & $attachments[1]['attachment'];
} else {
    // import from email body
    $attachment = null;
}

if($attachment) {
    $zipfile = fopen($this->cache_path.$this->context_id."_data.zip", 'w+');
    if(is_resource($zipfile)) {
        fwrite($zipfile, $attachment);
        fclose($zipfile);

        $zip = zip_open($this->cache_path.$this->context_id."_data.zip");
    }

    if(isset($zip) && is_resource($zip)) {

        $entry = NULL;
        $path = "";
        do {
            $entry = zip_read($zip);
            $haystack = strtolower(zip_entry_name($entry));
        } while ($entry && strpos($haystack, 'groupmembers.csv') === FALSE);

        if(!empty($entry)) {
             $path = $this->cache_path.$this->context_id."_groupmembers.csv";
             $csvfile = fopen($path, 'w+');
             $entry_content = zip_entry_read($entry, zip_entry_filesize($entry));
             fwrite($csvfile, $entry_content);
             fclose($csvfile);
        }
    }
    /* close the connection */
    imap_close($inbox);
}

return isset($path)? $path : FALSE;
}
}
