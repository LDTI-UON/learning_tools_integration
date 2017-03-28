/*
 * Numbers only
 *
 */
$(document).ready(function() {
	$('input#per_page').keyup(function (e) {
		if(e.keyCode==13){
			$("form#filters").submit();
		} else {
			this.value = this.value.replace(/[^0-9]/g,'');
			
		}
	});
	$('input#st_search').keyup(function (e) {
		if(e.keyCode==13){
			$("form#filters").submit();
		} else {
		this.value = this.value.replace(/[^0-9a-zA-Z]/g,'');
		}
	});
})	;
