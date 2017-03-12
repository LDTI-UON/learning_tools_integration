$(document).ready(function() {
  console.log('ready');
      setTimeout(
          function() {
            $.post(ghost.base_url+"?ACT="+ghost.act,
            {
                k : ghost.k,
                l : ghost.l
            },
            function(data) {
              data = JSON.parse(data);
                  $("#msg").html(data.message);
            });
          }, 1000);
});
