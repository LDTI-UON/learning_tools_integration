$.fn.removeText = function(){
  this.each(function(){

     // Get elements contents
     var $cont = $(this).contents();

      // Loop through the contents
      $cont.each(function(){
         var $this = $(this);

          // If it's a text node
          if(this.nodeType == 3){
            $this.remove(); // Remove it
          } else if(this.nodeType == 1){ // If its an element node
            $this.removeText(); //Recurse
          }
      });
  });
}

$(document).ready(function() { $('.rangeValue').removeText(); });
