<h3><?php echo date('l jS \of F Y h:i:s A'); ?></h3>

<p>Note that this action will log you out of the admin panel, as you are starting a new session as the selected user.</p>
<p>
<?php if(isset($user_message)) : ?>
    <?= $user_message; ?>
<?php else : ?>
  <?= form_open($action, array("id" => 'segments' /*, "target" => "_blank"*/), $form_hidden) ?>
    <?= $segment_dd; ?>
    <?= form_hidden('csrf_token', CSRF_TOKEN); ?>
    <?= form_hidden('site_id', 1); ?>
    <?= form_hidden('context_id', $context_id); ?><br>
    <?= form_hidden('username', $username); ?><br>
    <?= form_hidden('email', $email); ?><br>
    <?= form_submit('prepare', 'Prepare this launch for '.$username); ?>
<?= form_close(); ?>
<?php endif; ?>
</p>


<!--


-->
