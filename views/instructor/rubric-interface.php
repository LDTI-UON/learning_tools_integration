<style>
button#openRubric {
    margin-bottom: 2%;
    margin-left: 3%;
}
</style>
<div id='rubric_wrapper'>
<?= $form ?>
<div id='floating' style='position: absolute; left: 2%; top: 5%; width: 96%; height: auto; display: none'> </div>


<script type="text/javascript">

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
			$('div#floating').load('<?= $base_url ?>/rubric', {'no_reload': '1', 'id' : id}, function(data) {
				if(!session_expired(data)) {
					$(this).html(data).show();
				}
			});
		}

    var id;
    var show_scores_array = <?= $show_scores ?>;

    $("#rubric_wrapper").on("change", "select[name=\'rubrics\']", function(e) {
           id = $(e.target).find("option:selected").val();
           var show_scores = show_scores_array[id];
            if(id != "del") {
                $('#preview_btn').removeAttr("disabled");
                $("#attach").text("Attach");
                $("#show_scores").prop("checked", show_scores);
            } else {
                $('#preview_btn').prop("disabled", "disabled");
                $("#attach").text("Clear");
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
            $("#total_score").attr('disabled', 'disabled').after(" (<em>Overriden by rubric total score</em>)");
        });
        <?php endif; ?>
</script>
</div>
