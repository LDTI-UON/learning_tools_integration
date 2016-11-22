<style>
button#openRubric {
    margin-bottom: 2%;
    margin-left: 3%;
}
</style>
<div id='rubric_wrapper'>
<?= $form ?>
<style>
button#openRubric {
    margin-bottom: 2%;
    margin-left: 3%;
}
#rubric_iframe {
  border: none;

}
#floating {
  overflow-x: hidden;
  overflow-y: auto;
}
</style>
<!-- <div id='floating' style='position: absolute; left: 2%; top: 5%; width: 995px; height: 800px; display: none'><iframe id='rubric_iframe' width='100%' height='100%' seamless='seamless'></iframe></div>-->


<script type="text/javascript">
    window.flashRow = function(input_id) {
        // placeholder function to allow instructor preview rubric to exit
        return;
    }

		var session_expired = function(data) {
			if( $(data).find('.session_expired').length > 0 ) {

				if(confirm('Your session has expired.\\n Please return to the course and click the link again'))
				{
						window.history(-1);
				}
			}

			return false;
		}

		var populate_rubric = function(id) {

      $.post('<?= $base_url ?>/rubric', {'no_reload': '1', 'id' : id}, function(data) {
            if(!session_expired(data)) {
                var doc = window.open().document;
                doc.open();
                doc.write(data);
                doc.close();
            }
      });

		}

    var id;
    var show_scores_array = <?= $show_scores ?>;

    $("#rubric_wrapper").on("change", "select[name=\'rubrics\']", function(e) {
           id = $(e.target).find("option:selected").val();
           var show_scores = show_scores_array[id];
            if(id != "del") {
                var score = id.split('|')[1];
                $('#preview_btn').prop("disabled", false);
                $("#attach").text("Attach");
                $("#show_scores").prop("checked", show_scores);
                $("input[name='total_score']").val(score);
                  $("#scoreOverride").show();
            } else {
                $('#preview_btn').prop("disabled", true);
                $("#attach").text("Clear");
                $("input[name='total_score']").val('100');
                $("#scoreOverride").hide();
                $("#total_score").prop('disabled', false);
            }
        }).on("click", "#preview_btn", function(e) {
            if(id != "del") {
              populate_rubric(id);
            }
            $('#sync_message').remove();
        }).on("click", 'button#attach', function(e) {

            $("#rub_loader").show();
            $("#loader_msg").text("");
            $.get("<?= $base_url ?>?rubric_id="+id, function(data) {
                $("#rub_loader").hide();
                $("#loader_msg").text(" completed.");
                   session_expired(data);
            });
        }).on('change', '#show_scores', function(e) {

          $("#rub_loader").show();
          $("#loader_msg").text("");
          var is_checked = $(e.target).is(':checked') ? "1" : "0";
          var key = id.split('|')[0];
          show_scores_array[key] = is_checked;
          console.log(id);

          $.post("<?= $base_url ?>?rubric_id="+id,
                      { "show_scores" : is_checked },
                function(data) {
                  $("#rub_loader").hide();
                  $("#loader_msg").text(" updated.");

                  session_expired(data);
          });

        });

        $("select[name=\'rubrics\']").trigger("change");

        <?php if ($disable_instructor_score_setting !== FALSE): ?>
        $(document).ready(function() {
            $("#total_score").prop('disabled', true).after("<em id=scoreOverride>(Overriden by rubric total score)</em>");
            var raw = $("select[name=\'rubrics\']").find("option:selected").val();
            var selected_score = raw.split('|')[1];
            $("#total_score").after("<input type='hidden' name='total_score' value='"+selected_score+"'>");
        });
        <?php endif; ?>
</script>
</div>
