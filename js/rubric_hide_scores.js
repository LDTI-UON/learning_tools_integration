$.fn.removeText = function(numeric){

  // Safari doesn't allow inline function variable assignment
  if(typeof numeric === 'undefined') {
      numeric = false;
  }

  this.each(function(){

     // Get elements contents
     var $cont = $(this).contents();

      // Loop through the contents
      $cont.each(function(){
         var $this = $(this);

          // If it's a text node
          if(this.nodeType == 3){
            // only remove numeric values
            if(numeric == true) {
                  if(!isNaN($this.text()) || $this.text().includes('%')){
                      $this.remove();
                  }
            } else {
                    $this.remove(); // Remove it
            }
          } else if(this.nodeType == 1){ // If its an element node
                $this.removeText(numeric); //Recurse
          }
      });
  });
}

$(document).ready(function() { $('.rangeValue').removeText(); $('.rubricGradingCell').removeText(true); $('tr.rubricGradingRow th > p, div.rubricGradingRow h4 > p').removeText(); });
