/*
 * Numbers only
 *
 */
$(document).ready(function() {
	var dsbl = function() {
				$('input#per_page, input#st_search').prop('disabled', true).css({'opacity': 0.3});
	};

	$('input#per_page').keyup(function (e) {
		if(e.keyCode==13){
			if(e.target.value > 200) {
					e.target.value = 200;
			}
			$("form#filters").submit();
			dsbl();
		} else {
			e.target.value = e.target.value.replace(/[^0-9]/g,'');
		}
	});
	$('input#st_search').keyup(function (e) {
		if(e.keyCode==13){
			$("form#filters").submit();
			dsbl();
		} else {
			e.target.value = e.target.value.replace(/[^0-9a-zA-Z ]/g,'');
		}
	});
})	;
