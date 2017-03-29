<?= $form ?> <!-- // jshint ignore:line -->

<script type="text/javascript">
    var app = app || {};

    window.flashRow = function(input_id) {
        // placeholder function to allow instructor preview rubric to exit
        return;
    };

		var session_expired = function(data) {
			if( $(data).find('.session_expired').length > 0 ) {

				if(bootbox.alert('Your session has expired.\\n Please return to the course and click the link again', function() {
						window.history(-1);
				}));

			}

			return false;
		};

		var populate_rubric = function(id) {

      $.post('<?= $base_url ?>/rubric', {'no_reload': '1', 'id' : id }, function(data) {
        if(!session_expired(data)) {

            if($("#rubric_container").length === 0 && ! app.rubric_container) {
                  $("body").prepend("<div id=rubric_container></div");
            } else if(app.rubric_container) {
                  $("body").prepend(app.rubric_container);
            }

            $("#rubric_container").html(data).trigger("rubric_loaded").show();
            $(".contentPane").css({ margin: 0 });

            $(".container-fluid").hide();
        }
      });

		};

    var id;
    var show_scores_array =
        <?= $show_scores ?>; // jshint ignore:line

$(document).on("change", "select[name=\'rubrics\']", function(e) {
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
              id = $("#rubric_dd").find("option:selected").val();

              populate_rubric(id);
            }
            $('#sync_message').remove();
        }).on("click", 'button#attach', function(e) {

            $("#rub_loader").show();
            $("#loader_msg").text("");
            $.get("<?= $base_url ?>?rubric_id="+id, function(data) { // jshint ignore:line
                $("#rub_loader").hide();
                $("#loader_msg").text(" completed.");
                   session_expired(data);
            });
        }).on('change', '#show_scores', function(e) {

          $("#rub_loader").show();
          $("#loader_msg").text("");
          var id = $("select[name=\'rubrics\'] option:selected").val();
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

//$("select[name=\'rubrics\']").trigger("change");

<?php if ($disable_instructor_score_setting !== FALSE): ?> // jshint ignore:line
$(document).ready(function() {
    $("#total_score").prop('disabled', true).after("<em id=scoreOverride>(Overriden by rubric total score)</em>");
    var raw = $("select[name=\'rubrics\']").find("option:selected").val();
    var selected_score = raw.split('|')[1];
    $("#total_score").after("<input type='hidden' name='total_score' value='"+selected_score+"'>");
});
<?php endif; ?> // jshint ignore:line
</script>
