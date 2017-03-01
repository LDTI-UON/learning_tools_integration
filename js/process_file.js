$(document).ready(function() {
   $("a.process_file").click(
           function(e) {
                e.preventDefault();
                var filename = $(e.target).data('filename');

                var loader_url = "%loaderurl%";

                $(e.target).after("&nbsp;<img width=20 id=\"process_loader\" src=\""+loader_url+"\" alt=\"Processing...\"></img><span id='poutput'>Working...</span>");
                $.post("%suburl%", { process : filename }, function(data, status, xhr) {
                	$("#process_loader").remove();
                    if(status === "success") {
                    	$(e.target).remove();
                        $("#poutput").html(data.feedback);
                    } else {
                        bootbox.alert(status);
                    }
                }, "json");
    });
});


