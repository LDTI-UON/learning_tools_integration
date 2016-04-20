<h1 id='download_header'>Your download will begin in 3 seconds</h1>
<script>
(function(){
	var count = 2;
	var terval = setInterval(function() {
		var header = document.getElementById('download_header');
		header.innerHTML = "Your download will begin in "+count+" seconds";
		if(count-- == 0) {
			// execute both close and back, will work depending on context
			document.writeln("<h1>Thanks, <?= $screen_name ?> and good luck!</h1><p><a href='javascript:window.close();history.back()'>Close</a></p>")
			clearInterval(terval);
			document.target = '_blank';
			document.location = '<?= $current_uri ?>/do_download?f=<?= $filename ?>&i=<?= $iv ?>&t=<?= $type ?>';
		}
	}, 1000);

})();
</script>

