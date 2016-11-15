/**
 *
 */
$(document).ready(function () {
		var text = 'Grid View';

		var back = "<div id='rub_back' style='position: fixed; width: 100%; height: 100%; top:0;left:0;background-color: #000; opacity: 0.7'></div>";
		$("#rub_onExitClose").parent().prepend(back);

		var p = $("#rub_onExitClose").data("pre_pop");

    $(".chosen-container").hide();

		$(p).each(function() {
			//console.log("populating from DB");
			$(this.rows).each(function(i, v) {
				var col = (this.col - 1);
				var list_q = ".rubricGradingList .rubricGradingRow:eq("+i+") .rubricGradingCell:eq("+col+")";
				var row_score = this.score;

                $(".rubricTable .rubricGradingRow:eq("+i+") td:eq("+col+") .grade_input, "+
                  list_q +" .grade_input, "+list_q + " .rubricCellRadio").each(function() {

										if(this.tagName == "INPUT") {
														if($(this).prop('type') == 'text') {
																$(this).prop("value", row_score);
														}

														if($(this).prop('type') == 'radio') {
																if(row_score != $(this).prop("value")) {
																		console.log("The saved rubric does not match the loaded rubric.");
																}

																$(this).prop("checked", true);
														}

														$(this).addClass("scoreSet");
										} else {
												console.log("ERROR: Non INPUT tag was parsed.");
										}
								});
			});
		});

		var checkInputs = function(o, el, score) {
			//	console.log("changed "+o.tagName+" and checking");
				if($(o).hasClass("scoreSet")) {

						el.addClass("scoreSet");

						if(el.prop('type') == "radio") {
									el.prop("checked", true);
						}
						if(el.prop('type') == "text") {
									el.prop("value", score);
						}

						//el.css('background-color', 'orange');
				} else {
						el.removeClass("scoreSet");

						if(el.prop('type') == "radio") {
									el.prop("checked", false);
						}
						if(el.prop('type') == "text") {
									el.	prop("value", "");
						}

						//el.css('background-color', 'green');
				}
		}

        var syncInputs = function(o) {


            if($(".active[role='tab']").attr("id") ==	 "gridViewTab") {
												var i = $(o).closest(".rubricTable").find(".grade_input").index(o);
                        var score = $(o).val();
												var el = $(".rubricGradingTable .grade_input:eq("+i+"), .rubricGradingTable .rubricCellRadio:eq("+i+")");
											//	console.log("Score: "+score);
												checkInputs(o, el, score);
            } else {
										var list = $(o).closest(".rubricGradingList").find(".rubricCellRadio");
										var i;
										if(list.length > 0) {
												i = $(list).index(o);
										} else {
												i = $(o).closest(".rubricGradingList").find(".grade_input").index(o);
										}
                        var score = $(o).val();
												var el = $(".rubricTable .grade_input:eq("+i+")");
                      	checkInputs(o, el, score);
            }
        }

		$("#listViewTab > a").click(function(e) {

			e.preventDefault();

			$("div[aria-labelledby='gridViewTab']").hide();
			$("div[aria-labelledby='listViewTab']").show();

			$(this).parent().attr({'aria-selected':'true', 'tabindex': '0'}).addClass('active');
			$("#gridViewTab").attr({'aria-selected':'false', 'tabindex': '-1'}).removeClass('active');

      //  syncInputs();
		});

		$("#gridViewTab > a").click(function(e) {

			e.preventDefault();

			$("div[aria-labelledby='gridViewTab']").show();
			$("div[aria-labelledby='listViewTab']").hide();

			$(this).parent().attr({'aria-selected':'true', 'tabindex': '0'}).addClass('active');;
			$("#listViewTab").attr({'aria-selected':'false', 'tabindex': '-1'}).removeClass('active');

        //  syncInputs();
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

		$('.grade_input, .rubricCellRadio').keyup(function(e){
			this.value = this.value.replace(/[^0-9]/g,'');
		}).change(function(e) {
			var range = $(this).data("range");
            if(typeof range !== 'undefined') {
								if(isNaN(range.min) || isNaN(range.max)) {
											alert("Error: numeric range not set "+JSON.stringify(range));
								}

                if(!isNaN(this.value)) {
                        if(parseInt(this.value) < parseInt(range.min)) {
                            this.value = range.min;
                        }
                        else if(parseInt(this.value) > parseInt(range.max)) {
                            this.value = range.max;
                        }
                }
            }

			$(this).addClass("scoreSet").addClass("s_s_n").css({'background-color': ''});

			$(this).closest("td, .rubricGradingCell").siblings("td, .rubricGradingCell").each(function() {
				$(this).find(".grade_input:not(.s_s_n)").val("").removeClass("scoreSet");
			});

			$(this).removeClass("s_s_n");


			syncInputs(e.target);
		});


		$("#rub_onExitClose").on("click", ".button-1", function(e) {
			e.preventDefault();

			var total = 0;
			var model = { rows: [] }; //, colLabels : [], rowLabels: [], maxValue: '0' };

			$(".rubricTable .scoreSet").each(function() {
				var r = $(this).closest("tr").index();
				var c = $(this).closest("td").index();

				if(isNaN($(this).val())) {
						error_track(model);
				}

				var score = parseInt( $(this).val() );

				model.rows[r] = { col: c, score: score };

				total += score;
			});
			var input_id = $("#rub_onExitClose").data("input_id");

			if(isNaN(total)) {
					error_track(model);
			}

			$("#show_score_"+input_id).text(total);
			$("#score_"+input_id).attr("value", total);
			$("#rubric_"+input_id).attr("value", JSON.stringify(model));

			$p = $("#rub_onExitClose").parent();

			$p.hide();

      $(".chosen-container").show();
		});

		var error_track = function(model) {
			var subject = encodeURIComponent("error in script");
			var body = encodeURIComponent("This is the data passed: \n\n"+JSON.stringify(model));

			document.write("There was an error processing your form, try returning to the course and clicking the link again, if this still does not work, please report this incident to the developer at <a href='mailto:paul.sijpkes@newcastle.edu.au?subject="+subject+"&body="+body+"'>The BOLD Team at UoN</a>.");
			return;
		}
});
