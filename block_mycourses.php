<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/mycourses/locallib.php');


class block_mycourses extends block_base {

    public function init() {
	global $PAGE;
	$this->title = get_string("mycourses", "block_mycourses");
	$PAGE->requires->jquery();
	$PAGE->requires->jquery_plugin('migrate');
	$PAGE->requires->jquery_plugin('ui');
	$PAGE->requires->jquery_plugin('ui-css');
    }

    public function get_content() {
	global $DB;
	global $USER;

	if ($this->content !== null) {
	    return $this->content;
	    }

	$this->content = new stdClass;

	// Settings start out as being undefined; compensate for 'true' values.
	if (!isset($this->config->showteachers)) {
	    $this->config->showteachers = true;
	}

	$courses = block_mycourses_get_courses($this->config->showteachers,
					       $this->config->showsecretaries,
					       $this->config->showroles,
					       $this->config->showrecentactivity,
					       $this->config->showcategorypath,
					       $this->config->dividebycategory);
	$favourite_courses = array();
	foreach ($courses as $course) {
	    if ($course->isfavourite) {
		$c = clone $course;
		$c->semester = "favourites";
		$c->semester_name = get_string("favourites_tab", "block_mycourses");
		$favourite_courses[] = $c;
	    }
	}
	$all_courses = array_merge($favourite_courses, $courses);

	$this->content->text = '<div id="mycourses_container">';

	$searchbox = "<div><input type=\"text\" id=\"mycoursestextfilter\" placeholder=\"".get_string('search_text', 'block_mycourses')."\"/></div><script type=\"text/javascript\" src=\"/blocks/mycourses/filter.js\"></script>";
	$this->content->text .= $searchbox;

	// Make UL for semester category heading
	$last_sem_cat = '';
	$this->content->text .= '<ul id="semester_category_header">';

	foreach ($all_courses as $course) {
	    if ($course->semester != $last_sem_cat) {
		if (!$course->semester == null) {
		    $semester_html_id = $course->semester;
		} else {
		    $semester_html_id = 'uncategorized';
		}
		$this->content->text .= "<li><a href=\"#semester_category_$semester_html_id\">$course->semester_name</a></li>";
		$last_sem_cat = $course->semester;
	    }
	}
	//null case
	if ($courses ==null){
		//obs: should not be hard coded
		$currsemester = get_string('uncategorized', 'block_mycourses');
		$this->content->text .= "<li><a href=\"#tab-0\">$currsemester</a></li>";
	 }

	$this->content->text .= '</ul>';

	// Now make the course list
	$last_sem_cat = '';
	$last_cat_path = '';
	//null case
	if ($courses==null)
	    {$this->content->text .= "<div class=\"tab-0\" id=\"tab-0\">".get_string('nonecourse', 'block_mycourses');}
	foreach ($all_courses as $course) {
	    // Skip invisible courses (unless the user is allowed to see them).
	    if (!($course->visible or $course->mayviewhidden)) {
		continue;
	    }
	    $this->content->text .= block_mycourses_render_course($course,
								$last_sem_cat,
								$last_cat_path,
								$this->config);
	}
	$this->content->text .= "</div>\n</div>"; // Ending the final semester
                                                  // category and container div

	$this->content->text .= '<script type="text/javascript" src="/blocks/mycourses/defaulttab.js"></script>';
	$this->content->text .= '<script type="text/javascript" src="/blocks/mycourses/favourites.js"></script>';

	// TODO: Select the tab appropriate to the date.
	return $this->content;
    }

    public function cron() {
	global $DB;

	// Start by cleaning up. May cause a sliding window in indexing.
	$DB->delete_records('course_semester_category');
	$categories = array();
	foreach ($DB->get_records_sql('SELECT category, semester_category FROM {category_semester_category}') as $cat) {
	    $categories[] = array('category'=>$cat->category,
				  'semester_category'=>$cat->semester_category);
	}
	$cat_names = $DB->get_records_select_menu('course_categories');
	foreach ($categories as $cat) {
	    $sql = "WITH RECURSIVE t(id, path) AS (
                         SELECT id, path FROM mdl_course_categories
                          WHERE id = :category
                         UNION ALL
                         SELECT cc.id, cc.path FROM mdl_course_categories cc, t
                          WHERE cc.parent = t.id)
                    SELECT c.id, t.path
                      FROM mdl_course c, t
                     WHERE c.category = t.id";
	    $courses = $DB->get_records_sql($sql, $cat);
	    foreach ($courses as $course) {
		// The path is /-separated IDs; translate to /-separated names
		$path = array();
		foreach (explode('/', $course->path) as $path_id) {
		    $path[] = $cat_names[(int)$path_id];
		}
		$path = implode('/', $path);
		$DB->insert_record('course_semester_category',
				   array('semester_category'=>$cat['semester_category'],
					 'course'=>$course->id,
					 'path'=>$path), false);
	    }
	}

	return true;
    }
}

?>
