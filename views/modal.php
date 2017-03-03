<div id='modal' style='display:none'>
  <?= $form ?>
</div>

<script>
  $(document).ready(function() {
      bootbox.alert({
        size: "large",
        message: $('#modal').html(),
        callback: function() {
              $("#modal").remove();
        }
  });
});
</script>
