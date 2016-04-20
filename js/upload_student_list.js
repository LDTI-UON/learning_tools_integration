/**
 * Show hide
 */
$(document).ready(function() {
		   $('#manualUploadInfo').hide();

	       $('a#manual').click(function(e) {
                    e.preventDefault();
                    $('#manualUploadInfo').toggle();
           });
});
