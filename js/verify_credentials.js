
$(document).ready(function() {
		$("input[name='verify']").attr('disabled', 'disabled');

		$('.numbersOnly').keyup(function () {
		    this.value = this.value.replace(/[^0-9\.]/g,'');
		});

		$.fn.validEmail = function() {
			var str_email = "<?= $this->email ?>";
			var parts = str_email.split("@");
			var disp = parts[1];
			var email_username = maskEmail(parts[0]);

			$("#sn_dm1").remove();

			if($(this).val().toLowerCase() != str_email.toLowerCase()) {
				$(this).addClass('not_matched');
				$("input[name='verify']").after("<p id=sn_dm1 >The email address does not match the address given to us by Blackboard ("+email_username+"@"+disp+").</p>");
				$("input[name='verify']").attr('disabled', 'disabled');
				return false;
			} else {
				$(this).removeClass('not_matched');
				$("input[name='verify']").removeAttr('disabled');
				return true;
			}

			return this;
		}

		$('input#email').on("change", function() {
			$(this).validEmail();
		});

		$("input#student_id2").on("change", function() {
			$("#sn_dm2, #sn_dm3").remove();
			$("input").removeClass("not_matched");

			var myVal = $(this).val();
			if(myVal != $("input#student_id1").val()) {
				$(this).addClass("not_matched");
				$("input#student_id1,input#student_id2").addClass("not_matched");
				$("input[name='verify']").after("<p id=sn_dm2>Student numbers don't match!</p>");
				$("input[name='verify']").attr('disabled', 'disabled');
			} else {
				if($('input#email').validEmail() === true) {
					$("input[name='verify']").removeAttr('disabled');
				}
			}

			if(myVal.length != 7) {
				$("input#student_id1,input#student_id2").addClass("not_matched");
				$("input[name='verify']").after("<p id=sn_dm3>Student numbers must be 7 digits long.</p>");
			}
		});


		$("input#staff_numberplate2").on("change", function() {
			var myVal = $(this).val();
			$("#sn_dm2").remove();
			$("input").removeClass("not_matched");

			if(myVal != $("input#staff_numberplate1").val()) {
				$(this).addClass("not_matched");
				$("input#staff_numberplate1,input#staff_numberplate2").addClass("not_matched");
				$("input[name='verify']").after("<p id=sn_dm2>Staff IDs don't match!</p>");
				$("input[name='verify']").attr('disabled', 'disabled');
			} else {
				if($('input#email').validEmail() === true) {
					$("input[name='verify']").removeAttr('disabled');
				}
			}


		});

		function maskEmail(str) {
	        var a = str.split('.');
	        var masked = "";
	        for (w in a) {
	            if(w > 0 && w < a.length) {
	                masked += ".";
	            }
	            var b = a[w].split('');
	            var d = "";
	            var i = 0;
	            for (c in b) {
	                if (i++ > 0) {
	                    d += "#";
	                } else {
	                    d = b[c];
	                }
	            }
	            masked += d;

	        }
	        return masked;
	    }
	});

