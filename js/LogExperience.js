function LogExperience(verb, verb_display, object_name, object_description) {

			var jdata = JSON.stringify({
				endpoint: "<?= $this->endpoint ?>",
				username: "<?= $this->username ?>",
				password: "<?= $this->password ?>",
				screen_name: "<?= $this->screen_name ?>",
				email: "<?= $this->email ?>",
				verb: verb,
				verb_display: verb_display,
				name: object_name,
				description: object_description
			});

			jQuery.post("/tincan/send.php", {jsonData: jdata }, function(data) {
					console.log("Tincan response: "+data);
			});
}; /* end LogExperience */
