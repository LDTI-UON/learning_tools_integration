
<?php
	if(isset($email_settings)) {
		echo $email_settings;
	}
?>

<?= !empty($error) && $is_instructor === true && !( !empty($state) && $state['hideError'] ) ?
            "<div class='errorBox' style='max-width: 500px; border: thin solid red; color: red;
                                        display: block; margin: 5px;padding: 5px;
                            font-family:arial,helvetica,sans-serif;font-weight:bold;text-align:justify'> $error
             <a href='#' id='hide_error' style='font-size: 10px'>Don't show this again on this device.</a></div>": "" ?>
