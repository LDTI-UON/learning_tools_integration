/**
 * @Author: ps158
 * @Date:   2017-04-20T11:27:41+10:00
 * @Last modified by:   ps158
 * @Last modified time: 2017-04-21T12:23:48+10:00
 */

 $(document).bind("write_grade", "#write_lms_grade",
         function(e) {
             e.preventDefault();
             
           var grade = $(e.target).data("grade");
           if(grade > 1) {
               grade = grade / 100;
           }
           $.post(write_ACT, { grade: grade, segment: segment, url: url, srcid: srcid },
             function(data) {
                 write_callback(data);
           });
 });
