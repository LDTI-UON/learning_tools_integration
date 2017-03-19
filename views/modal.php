<div id='modal' style='display:none'>
  <?= $instructions ?>
  <?= $form ?>
</div>

<script>
var lti_M = lti_M || { message: $('#modal').html() };

<?php if(isset($callback_f)): ?>
  var callback_f = <?= $callback_f ?>;
<?php endif; ?>//jshint ignore:line

<?php if (isset($button_label)): ?>
  var b = { '<?= $button_type ?>': { 'label': '<?=$button_label?>' }};
  lti_M.buttons = b || null; //jshint ignore:line
  lti_M.callback = callback_f;
<?php endif; ?>

<?php if (isset($size)): ?>
  lti_M.size = '<?= $size ?>' || null;
<?php endif; ?>

$(document).ready(function() {
    bootbox.alert(lti_M);
});

<?php if (isset($onHidden)): ?> //jshint ignore:line
  $("#modal").on('hidden.bs.modal', function () {//jshint ignore:line
    <?php if(isset($onHidden)) echo $onHidden; ?> //jshint ignore:line
  });//jshint ignore:line
<?php endif; ?> //jshint ignore:line

</script>
