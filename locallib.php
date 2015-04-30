<?php

function block_mycourses_get_courses($showteachers,
				     $showsecretaries,
				     $showroles,
				     $showrecentactivity,
				     $showcategorypath,
				     $dividebycategory) {
    global $USER, $DB;

    // Basis for getting courses: /lib/enrollib.php: enrol_get_my_courses()
    if (isset($USER->loginascontext)
	and $USER->loginascontext->contextlevel == CONTEXT_COURSE) {
        // list _only_ this course - anything else is asking for trouble...
        $only_this_course = "AND courseid = :loginas";
	$loginas = $USER->loginascontext->instanceid;
    } else {
	$only_this_course = "";
	$loginas = false;
    }
    // Ordering: order by semester with courses not in a semester
    // category being ordered first, secondarily ordered alphabetically
    // by course name. If the user wants to group courses by category
    // path, use that as second order sorting column and course name
    // as tertiary.
    if ($dividebycategory) {
	$order = 'sc.name DESC NULLS FIRST, csc.path, c.fullname';
    } else {
	$order = 'sc.name DESC NULLS FIRST, c.fullname';
    }
    $sql = "WITH RECURSIVE categories (id) AS (
                 SELECT cc.id
                   FROM {role_assignments} ra
                   JOIN {role} r ON ra.roleid = r.id
                   JOIN {role_capabilities} rc ON r.id = rc.roleid
                   JOIN {context} ctx ON ra.contextid = ctx.id
                   JOIN {course_categories} cc ON ctx.instanceid = cc.id
                  WHERE ra.userid = :userid3
                    AND rc.capability = :capability2
                    AND ctx.contextlevel = :categorycontextlevel
                  UNION ALL
                 SELECT cc2.id FROM {course_categories} cc2
                   JOIN categories ON categories.id = cc2.parent)
            SELECT c.id,
                   c.shortname,
                   c.fullname,
                   c.visible AS visible,
                   ctx.id AS ctxid,
                   ctx.path AS ctxpath,
                   ctx.depth AS ctxdepth,
                   ctx.contextlevel AS ctxlevel,
                   CASE WHEN sc.name IS NULL THEN 'uncategorized' ELSE sc.name END AS semester,
                   CASE WHEN csc.path IS NULL THEN 'uncategorized' ELSE TRIM(LEADING '/' FROM csc.path) END AS category_path,
                   CASE WHEN mf.id IS NULL THEN 0 ELSE 1 END AS isfavourite,
                   CASE WHEN
                   EXISTS (SELECT 1
                             FROM {role_assignments} ra
                             JOIN {role} r ON ra.roleid = r.id
                             JOIN {role_capabilities} rc ON r.id = rc.roleid
                            WHERE ra.userid = :userid2
                              AND ra.contextid = ctx.id
                              AND rc.capability = :capability1
                            UNION
                            SELECT 1
                              FROM categories
                             WHERE c.category = categories.id
                          )
                   THEN 1 ELSE 0 END AS mayviewhidden
              FROM {course} c
              JOIN (SELECT DISTINCT e.courseid
                      FROM {enrol} e
                      JOIN {user_enrolments} ue ON (ue.enrolid = e.id
                                                AND ue.userid = :userid1)
                     WHERE ue.status = :active
                       AND e.status = :enabled
                       AND ue.timestart < :now
                       AND (ue.timeend = 0 OR ue.timeend > :now2)
               ) en ON (en.courseid = c.id)
              LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :coursecontextlevel)
              LEFT JOIN {course_semester_category} csc ON csc.course = c.id
              LEFT JOIN {semester_categories} sc ON csc.semester_category = sc.id
              LEFT JOIN {mycourses_favourites} mf ON (mf.\"user\" = :userid4
                                                  AND c.id = mf.course)
              WHERE c.id <> :siteid
                $only_this_course
           ORDER BY $order";
    $params = array('siteid'=>SITEID,
		    'loginas'=>$loginas,
		    'userid1'=>$USER->id,
		    'userid2'=>$USER->id,
		    'userid3'=>$USER->id,
		    'userid4'=>$USER->id,
		    'now'=>round(time(), -2),
		    'now2'=>round(time(), -2),
		    'active'=>ENROL_USER_ACTIVE,
		    'enabled'=>ENROL_INSTANCE_ENABLED,
		    'coursecontextlevel'=>CONTEXT_COURSE,
		    'categorycontextlevel'=>CONTEXT_COURSECAT,
		    'capability1'=>'moodle/course:viewhiddencourses',
		    'capability2'=>'moodle/course:viewhiddencourses'
		    );

    $courses = $DB->get_records_sql($sql, $params);

    // If the main site appears as a course, remove it from the list.
    $site = get_site();
    if (array_key_exists($site->id,$courses)) {
        unset($courses[$site->id]);
    }

    $course_ids = array();
    foreach ($courses as $c) {
	$course_ids[] = $c->id;
        if (isset($USER->lastcourseaccess[$c->id])) {
            $courses[$c->id]->lastaccess = $USER->lastcourseaccess[$c->id];
        } else {
            $courses[$c->id]->lastaccess = 0;
        }
	// Fix semester name
	if ($c->semester == 'uncategorized') {
	    $c->semester_name = get_string('uncategorized', 'block_mycourses');
	} else {
	    $c->semester_name = get_string(substr($c->semester, -1),
					   'block_mycourses')
		                . substr($c->semester, 0, 2);
	}
	// Do the multi-language stuff that Moodle won't
	$c->fullname = block_mycourses_language_filter($c->fullname);
	$c->category_path = block_mycourses_language_filter($c->category_path, '/', '/(?<=[^<])\//u');

	if ($showrecentactivity) {
	    $sql = "SELECT 'forum' AS module, count(*) AS count
                      FROM mdl_forum_posts p
                      JOIN {forum_discussions} d ON d.id = p.discussion
                      JOIN {forum} f             ON f.id = d.forum
                     WHERE f.course = :course1
                       AND p.created > :time1
                       AND p.created > EXTRACT(EPOCH FROM NOW() -
                                                   INTERVAL '30 days')
                    UNION
                    SELECT 'assignments' AS module, count(*) AS count
                      FROM {assign}
                     WHERE course = :course2
                       AND allowsubmissionsfromdate > :time2
                       AND duedate > EXTRACT(EPOCH FROM NOW() -
                                             INTERVAL '30 days')";
	    $updates = $DB->get_records_sql_menu($sql,
						 array('course1'=>(int)$c->id,
						       'time1'=>$courses[$c->id]->lastaccess,
						       'course2'=>(int)$c->id,
						       'time2'=>$courses[$c->id]->lastaccess));
	    $courses[$c->id]->forum_activity = (int)$updates['forum'];
	    $courses[$c->id]->assignment_activity = (int)$updates['assignments'];
	}

    } // End loop over courses.

    $ids = implode(', ', $course_ids);

    if ($showteachers and !empty($course_ids)) {
	// Get teachers for courses; role id 3 is that of teacher, and
	// ctx.instanceid is course.id

	// WARNING: UCKING FUGGLY!
	// Because Moodle's query functions require that the first
	// column contains unique values, we need to create an
	// aggregate that allows us to fetch all teachers for all
	// courses in one fell swoop. This is PostgreSQL specific.
	// CREATE OR REPLACE FUNCTION comma_join(text, text) RETURNS text AS 'SELECT CASE WHEN $1 <> '''' THEN $1 || '', '' || $2 ELSE $2 END;' LANGUAGE SQL RETURNS NULL ON NULL INPUT;
	// CREATE AGGREGATE comma_join (TEXT) (SFUNC=comma_join, STYPE=text, INITCOND='');
	// We could pre-generate and cache it instead.
	$sql = "SELECT ctx.instanceid AS course,
                       comma_join(u.firstname || ' ' || u.lastname) AS names
                  FROM {user} u
                  JOIN {role_assignments} ra ON u.id = ra.userid
                  JOIN {context} ctx ON ra.contextid = ctx.id
                 WHERE ra.roleid = 3
                   AND ctx.instanceid IN ($ids)
                 GROUP BY ctx.instanceid
                 ORDER BY ctx.instanceid";
	$teachers = $DB->get_records_sql($sql);
	foreach ($teachers as $teacher) {
	    $courses[$teacher->course]->teachers = $teacher->names;
	}
    }

    if ($showsecretaries and !empty($course_ids)) {
	// TODO: merge with teacher info above.
	$sql = "SELECT ctx.instanceid AS course,
                       comma_join(u.firstname || ' ' || u.lastname) AS names
                  FROM {user} u
                  JOIN {role_assignments} ra ON u.id = ra.userid
                  JOIN {context} ctx ON ra.contextid = ctx.id
                 WHERE ra.roleid = 10
                   AND ctx.instanceid IN ($ids)
                 GROUP BY ctx.instanceid
                 ORDER BY ctx.instanceid";
	$secretaries = $DB->get_records_sql($sql);
	foreach ($secretaries as $secretary) {
	    $courses[$secretary->course]->secretaries = $secretary->names;
	}
    }

    if ($showroles and !empty($course_ids)) {
	$sql = "SELECT ctx.instanceid AS course,
                       comma_join(r.name) AS names
                  FROM {role_assignments} ra
                  JOIN {role} r ON ra.roleid = r.id
                  JOIN {context} ctx ON ra.contextid = ctx.id
                 WHERE ra.userid = $USER->id
                   AND ctx.instanceid IN ($ids)
                 GROUP BY ctx.instanceid
                 ORDER BY ctx.instanceid";
	$roles = $DB->get_records_sql($sql);
	foreach ($roles as $role) {
	    $courses[$role->course]->roles = $role->names;
	}
    }

    return $courses;
}

function block_mycourses_render_course($course, &$last_sem_cat, $last_cat_path,
				       $config) {
    $ret = '';

    // Clean up category path name
    if ($course->category_path == 'uncategorized') {
        $course->category_path = get_string('uncategorized', 'block_mycourses');
    }

    // Separate into blocks per semester category. Since
    // they're sorted by semester, we can do it the simple
    // way:
    if ($course->semester != $last_sem_cat) {
	if ($last_sem_cat == '') {
	    $ret .= "<div class=\"semester_category\" id=\"semester_category_$course->semester\">";
	} else {
	    if (!$course->semester == null) {
		$semester_html_id = $course->semester;
	    } else {
		$semester_html_id = 'uncategorized';
	    }
	    $ret .= "</div><div class=\"semester_category\" id=\"semester_category_$semester_html_id\">";
	}
	$last_sem_cat = $course->semester;
    }

    // Same as above, but with category paths. This is optional.
    if ($config->dividebycategory and
	$course->category_path != $last_cat_path) {
	$ret .= "<h3 class=\"category_header\">$course->category_path</h3>";
	$last_cat_path = $course->category_path;
    }

    // Favourites functionality
    $favourites = '<span class="favourites">';
    if ($course->isfavourite) {
	$favourites .= '<img id="mc-favourite-' . $course->id . '" class="mycourses-favourite" src="/blocks/mycourses/Star.png" onclick="favourite(this);" />';
    } else {
	$favourites .= '<img id="mc-favourite-' . $course->id . '" class="mycourses-favourite" src="/blocks/mycourses/Star-bw.png" onclick="favourite(this);" />';
    }
    $favourites .= '</span>';

    // If a course is marked as invisible, it should be
    // displayed differently.
    if ($course->visible) {
	$visibility = "";
	$visibility_klaphammer = ""; //TODO: ARGH!
    } else {
	$visibility = ' dimmed_text';
	$visibility_klaphammer = " style=\"color: #BBB !important\"";
    }
    // Displaying teacher information
    if ($config->showteachers) {
	if (!isset($course->teachers)) {
	    $course->teachers = get_string('none', 'block_mycourses');
	}
	$teacher_info = "<div class=\"teacher_info\"><b>".get_string('teacherlabel', 'block_mycourses').":</b> <span class=\"value\">$course->teachers</span></div>";
    } else {
	$teacher_info = '';
    }
    // Displaying secretary information
    if ($config->showsecretaries) {
	if (!isset($course->secretaries)) {
	    $course->secretaries = get_string('none', 'block_mycourses');
	}
	$secretary_info = "<div class=\"teacher_info\"><b>".get_string('secretarylabel', 'block_mycourses').":</b> <span class=\"value\">$course->secretaries</span></div>";
    } else {
	$secretary_info = '';
    }
    // Displaying role information
    if ($config->showroles) {
	if (!isset($course->roles)) {
	    $course->roles = get_string('none', 'block_mycourses');
	}
	$role_info = "<div class=\"teacher_info\"><b>".get_string('rolelabel', 'block_mycourses').":</b> <span class=\"value\">$course->roles</span></div>";
    } else {
	$role_info = '';
    }
    // Displaying category path information
    if ($config->showcategorypath) {
	$categorypath = "<div class=\"category_path\">$course->category_path</div>";
    } else {
	$categorypath = '';
    }
    // Displaying activity information
    if ($config->showrecentactivity) {
	$recentactivity = '<div class="recentactivity">';
	/*
	if (isset($course->course_activity)) {
	    $recentactivity .= "<span><img class=\"iconlarge\" src=\"/theme/image.php/aalborg/mod_assign/1395824888/icon\" title=\"Assignment\" alt=\"Assignment\"/> " . get_string('recentcourseactivity', 'block_mycourses') . " ($course->course_activity)</span>";
	}
	*/
	if ($course->forum_activity > 0) {
	    $recentactivity .= "<span><img class=\"iconlarge\" src=\"/theme/image.php/aalborg/mod_forum/1395824888/icon\" title=\"Forum\" alt=\"Forum\"/> " . get_string('recentforumactivity', 'block_mycourses') . " ($course->forum_activity)</span>";
	}
	if ($course->assignment_activity > 0) {
	    $recentactivity .= "<span><img class=\"iconlarge\" src=\"/theme/image.php/aalborg/mod_assignment/1395824888/icon\" title=\"Assignments\" alt=\"Forum\"/> " . get_string('recentassignmentactivity', 'block_mycourses') . " ($course->assignment_activity)</span>";
	}
	$recentactivity .= '</div>';
    } else {
	$recentactivity = '';
    }
    // TODO: handle other modules than courses?
    return $ret . "<div id=\"course-$course->id\" class=\"box coursebox$visibility\">$favourites<h2 class=\"title\"><a href=\"/course/view.php?id=$course->id\"$visibility_klaphammer>$course->fullname</a></h2>$categorypath$teacher_info$secretary_info$role_info$recentactivity</div>\n";
}

function block_mycourses_language_filter($string, $delimiter=null, $regexp_split=null) {
    // This should be handled by Moodle itself, and is only here until
    // we figure out how to convince Moodle to behave. Thus...
    // TODO: remove in favour of proper Moodle functionality.

    if (is_null($regexp_split)) {$regexp_split = $delimiter;}

    if (!is_null($delimiter)) {
        $s = preg_split($regexp_split, $string);
    } else {
        $s = array();
        $s[] = $string;
    }

    $lang = current_language();
    $res = array();
    foreach ($s as $part) {
        // First fix clerical errors when people use 'dk' rather than 'da'
        $part = str_replace("lang=\"dk\"", "lang=\"da\"", $part);
        if (strpos($part, "class=\"multilang\"") !== false) {
            $part = preg_replace("/.*<span lang=\"$lang\" class=\"multilang\">(.*?)<\/span>.*/u",
                                 '$1',
                                 $part);
        }
        $res[] = $part;
    }

    if (!is_null($delimiter)) {
        return implode($delimiter, $res);
    } else {
        return $res[0];
    }
}

?>