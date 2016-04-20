/**
 *
 */
$(document).ready(function() {
		var text = 'Grid View';

		var back = "<div id='rub_back' style='position: fixed; width: 100%; height: 100%; top:0;left:0;background-color: #000; opacity: 0.7'></div>";
		$("#rub_onExitClose").parent().prepend(back);

		$("#listViewTab > a").click(function(e) {
			e.preventDefault();

			$("div[aria-labelledby='gridViewTab']").hide();
			$("div[aria-labelledby='listViewTab']").show();

			$(this).parent().attr({'aria-selected':'true', 'tabindex': '0'}).addClass('active');
			$("#gridViewTab").attr({'aria-selected':'false', 'tabindex': '-1'}).removeClass('active');

			//$(this).css(":after, :before");
		});
		$("#gridViewTab > a").click(function(e) {
			e.preventDefault();

			$("div[aria-labelledby='gridViewTab']").show();
			$("div[aria-labelledby='listViewTab']").hide();

			$(this).parent().attr({'aria-selected':'true', 'tabindex': '0'}).addClass('active');;
			$("#listViewTab").attr({'aria-selected':'false', 'tabindex': '-1'}).removeClass('active');
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
		}).focusout(function(e) {
			var range = $(this).data("range");

			if(!isNaN(this.value)) {
					if(parseInt(this.value) < parseInt(range.min)) {
						this.value = range.min;
					}
					else if(parseInt(this.value) > parseInt(range.max)) {
						this.value = range.max;
					}
			}

			$(this).closest("td, .rubricGradingCell").siblings("td, .rubricGradingCell").each(function() {
				$(this).find(".grade_input").val("");
			});
		});

		$("#rub_onExitClose").on("click", ".button-1", function(e) {
			e.preventDefault();
				$("#rub_onExitClose").parent().hide();
		});

});
