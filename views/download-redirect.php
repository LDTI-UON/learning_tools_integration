<div style='background-color: rgba(255,255,255, 0.5)'><h1 id='download_header'>Your download will begin in 3 seconds</h1></div>
<script>
(function(){
	var count = 2;
	var terval = setInterval(function() {
		var header = document.getElementById('download_header');
		header.innerHTML = "Your download will begin in "+count+" seconds";
		if(count-- == 0) {
			document.writeln("<div style='background-color: rgba(255,255,255, 0.5)'><h1>Thanks, <?= $screen_name ?> and good luck!</h1><p><a href='<?= $return_url ?>'>Close</a></p></div>")
			clearInterval(terval);
			document.target = '_blank';
			document.location = '<?= $current_uri ?>/<?= $download_redirect ?>?f=<?= $filename ?>&i=<?= $iv ?>&t=<?= $type ?>&s=<?= $segment ?>';
		}
	}, 1000);
})();
</script>
