/**
 *
 */
$(document).ready(function () {
		var text = 'Grid View';

		var back = "<div id='rub_back' style='position: fixed; width: 100%; height: 100%; top:0;left:0;background-color: #000; opacity: 0.7'></div>";
		$("#rub_onExitClose").parent().prepend(back);

		var p = $("#rub_onExitClose").data("pre_pop");

        $(".chosen-container").show();

		$(p).each(function() {
			$(this.rows).each(function(i, v) {
				var col = (this.col - 1);

                $(".rubricTable .rubricGradingRow:eq("+i+") td:eq("+col+") .grade_input, \
                    .rubricGradingList .rubricGradingRow:eq("+i+") .rubricGradingCell:eq("+col+") .grade_input").attr("value", this.score).addClass("scoreSet");
			});
		});


        var syncInputs = function() {
            if($(".active").attr("id") === "gridViewTab") {
                 $(".rubricTable .grade_input").each(function(i) {
                        var score = $(this).val();

                        if($(this).hasClass("scoreSet")) {
                            $(".rubricGradingList .grade_input:eq("+i+")").attr("value", score).addClass("scoreSet");
                        } else {
                            $(".rubricGradingList .grade_input:eq("+i+")").attr("value", '').removeClass("scoreSet");
                        }
                });
            } else {
                    $(".rubricGradingList .grade_input").each(function(i) {
                        var score = $(this).val();

                        if($(this).hasClass("scoreSet")) {

                            $(".rubricTable .grade_input:eq("+i+")").attr("value", score).addClass("scoreSet");

                        } else {
                            $(".rubricTable .grade_input:eq("+i+")").attr("value", '').removeClass("scoreSet");
                        }
                });
            }
        }

		$("#listViewTab > a").click(function(e) {

			e.preventDefault();

			$("div[aria-labelledby='gridViewTab']").hide();
			$("div[aria-labelledby='listViewTab']").show();

			$(this).parent().attr({'aria-selected':'true', 'tabindex': '0'}).addClass('active');
			$("#gridViewTab").attr({'aria-selected':'false', 'tabindex': '-1'}).removeClass('active');

            //syncInputs();
		});

		$("#gridViewTab > a").click(function(e) {

			e.preventDefault();

			$("div[aria-labelledby='gridViewTab']").show();
			$("div[aria-labelledby='listViewTab']").hide();

			$(this).parent().attr({'aria-selected':'true', 'tabindex': '0'}).addClass('active');;
			$("#listViewTab").attr({'aria-selected':'false', 'tabindex': '-1'}).removeClass('active');

           // syncInputs();
		});

		$("#rubricToggleDesc").change(function(e) {
				if($(this).is(":checked")) {
					$(".rubricGradingRow .description").show();
				} else {
					$(".rubricGradingRow .description").hide();
				}
		});

		$("#rubricToggleFeedback").change(function(e) {
			if($(this).is(":checked")) {
				$(".rubricGradingRow .feedback").show();
			} else {
				$(".rubricGradingRow .feedback").hide();
			}
		});

		$('.grade_input').keyup(function(e){
			this.value = this.value.replace(/[^0-9]/g,'');
		}).change(function(e) {
			var range = $(this).data("range");

            if(typeof range !== 'undefined') {
                if(!isNaN(this.value)) {
                        if(parseInt(this.value) < parseInt(range.min)) {
                            this.value = range.min;
                        }
                        else if(parseInt(this.value) > parseInt(range.max)) {
                            this.value = range.max;
                        }
                }
            }

			$(this).addClass("scoreSet").addClass("s_s_n").css({'background-color': ''});;

			$(this).closest("td, .rubricGradingCell").siblings("td, .rubricGradingCell").each(function() {
				$(this).find(".grade_input:not(.s_s_n)").val("").removeClass("scoreSet");
			});

			$(this).removeClass("s_s_n");

            syncInputs();
		});


		$("#rub_onExitClose").on("click", ".button-1", function(e) {
			e.preventDefault();

			var total = 0;
			var model = { rows: [] };

			$(".rubricTable .scoreSet").each(function() {
				var r = $(this).closest("tr").index();
				var c = $(this).closest("td").index();
				var score = parseInt( $(this).val() );

				model.rows[r] = { col: c, score: score };

				total += score;
			});
			var input_id = $("#rub_onExitClose").data("input_id");

			$("#show_score_"+input_id).text(total);
			$("#score_"+input_id).attr("value", total);
			$("#rubric_"+input_id).attr("value", JSON.stringify(model));

			$p = $("#rub_onExitClose").parent();

			$p.hide();

            $(".chosen-container").show();
		});

});
