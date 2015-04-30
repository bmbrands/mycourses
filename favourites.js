function favourite(elm) {
    var courseid = elm.parentNode.parentNode.id.split('-')[1];
    if (elm.src.indexOf('Star.png') > 0) {
	// Do unfavourite
	$.ajax('/blocks/mycourses/favourites_xml.php?action=unfavourite&course='+courseid);
	elm.src = '/blocks/mycourses/Star-bw.png';
    } else {
	// Do favourite
	$.ajax('/blocks/mycourses/favourites_xml.php?action=favourite&course='+courseid);
	elm.src = '/blocks/mycourses/Star.png';
    }
}
