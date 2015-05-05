/*
Copyleft 2013, Karsten Kryger, Aalborg University
*/

$(document).ready(function() {

$('#mycourses_container').tabs();

// Append counter to tabs

$('#semester_category_header').find('li[role="tab"] a').each(function() {
  var tmptxt = $(this).text();
  $(this).attr('orgtext', tmptxt);
});

// Update list on change of tab

$('#semester_category_header').find('li[role="tab"]').click(function() { mycoursesupdatelist(); });

$('#mycoursestextfilter').change(function() {
mycoursesupdatelist()
}).keyup( function () {
       // fire the above change event after every letter
       $(this).change();
   });
});

// Main function for updating courselist based on filter

function mycoursesupdatelist() {

 $('.coursebox').show();
 $('.coursebox').removeClass('mymoodlematch');

 // Use text filter
 var filter = $('#mycoursestextfilter').val().toLowerCase();
 if (filter) {
    $('.coursebox').each(function() {
      var ctitle = $(this).find('.title a').text().toLowerCase();
      var cpath = '';
      cpath = $(this).find('.category_path').text().toLowerCase();
      var cteachers = '';
      cteachers = $(this).find('.teacher_info').text().toLowerCase();
      if (ctitle.indexOf(filter) >= 0 || cpath.indexOf(filter) >= 0 || cteachers.indexOf(filter) >= 0) {
        $(this).closest('.coursebox').addClass('mymoodlematch');
      } else {
        // Hide if course does not match text filter
        $(this).closest('.coursebox').hide();
      }
    });

    // Update tabs with number of hits

    $('.semester_category').each(function() {
      var numcoursesvisible = parseInt($(this).find('.mymoodlematch').size());
      var semesterid = $(this).attr('id');
      var tabselector = 'a[href="#' + semesterid + '"]';
      var orgtext = $('#semester_category_header').find(tabselector).attr('orgtext');
      var newtext = orgtext + ' (' + numcoursesvisible + ')';
      $('#semester_category_header').find(tabselector).text(newtext);

    });
  
    // Hide headers without matched courses
	
	$('.category_header').each(function() {
      var countvisible = $(this).nextUntil('h3', '.mymoodlematch').length;
      if (countvisible == 0) {
        $(this).hide();
      } else {
        $(this).show();
      }
    });
  
 } else {
   // Restore tabs to original text
   $('#semester_category_header').find('li[role="tab"] a').each(function() {
     $(this).text($(this).attr('orgtext'));
   });
   
   // Show all headers
   
   $('.category_header').show();
 }
}
