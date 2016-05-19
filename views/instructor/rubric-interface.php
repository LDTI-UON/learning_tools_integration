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

        $("#rubric_wrapper").on("change", "select[name=\'rubrics\']", function(e) {
            var id = $(e.target).find("option:selected").val();

            $cb = $("#preview_cb:checked");

            if(id != "del") {
                $("#attach").text("Attach");
                if($cb.length > 0) {
                    populate_rubric(id);
                }
            } else {
               // $("#preview_cb, label[for='preview_cb'], #attach, label[for='attach']").hide();
                $("#attach").text("Clear");
            }
        }).on("change", "#preview_cb", function(e) {
            $('select[name=\'rubrics\']').trigger("change");
        }).on("click", 'button#attach', function(e) {
            var id = $("select[name=\'rubrics\'] option:selected").val();
            $("#rub_loader").show();
             $("#loader_msg").text("");
            $.get("<?= $base_url ?>?rubric_id="+id, function(data) {
                $("#rub_loader").hide();
                $("#loader_msg").text(" completed.");
                   session_expired(data);
            });
        });
        <?php if ($disable_instructor_score_setting !== FALSE): ?>
        $(document).ready(function() {
            $("#total_score").attr('disabled', 'disabled').after(" (<em>Overriden by rubric total score</em>)");
        });
        <?php endif; ?>
</script>
</div>
