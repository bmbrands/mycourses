function getcookie(name) {
    var cookies = document.cookie.split(';');
    for (var i=0; i < cookies.length; i++) {
	if (cookies[i].indexOf(name) === 0) {
	    return cookies[i].split('=')[1];
	}
    }
}

$(document).ready(function() {
    $('#semester_category_header').find('li[role="tab"]').click(
        function() {
	    document.cookie = 'lasttab=' + $(this).find('a').attr('id');
	});
    $('#'+getcookie('lasttab')).trigger('click');
}
)
