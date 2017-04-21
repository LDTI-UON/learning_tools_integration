/**
 * @Author: ps158
 * @Date:   2017-04-19T15:39:03+10:00
 * @Last modified by:   ps158
 * @Last modified time: 2017-04-21T12:11:40+10:00
 */

$(document).bind("read_grade", "#read_lms_grade",
          function(e) {
            e.preventDefault();
                $.post(read_ACT, { segment: segment, url: url, srcid: srcid },

            function(data) {
                read_callback(data);
          });

        });
