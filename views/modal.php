<div id='modal' style='display:none'>
  <?= $instructions ?>
  <?= $form ?>
</div>

<script>
var lti_M = lti_M || { message: $('#modal').html() };

<?php if (isset($buttons)): ?>
  lti_M.callback = <?= $callback ?> || null;
<?php endif; ?>

<?php if (isset($buttons)): ?>
  lti_M.buttons = <?= $buttons ?> || null;
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

var onSubmit = function(){
  //  Process form data
  $("#modal").hide();
}

var formEle = $("#modal form");
formEle.on('submit', onSubmit);
</script>
