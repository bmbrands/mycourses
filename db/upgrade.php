<?php

function xmldb_block_mycourses_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    $result = TRUE;

    if ($oldversion < 2013070702) {
      // Define table semester_categories to be created
        $table = new xmldb_table('semester_categories');

        // Adding fields to table semester_categories
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table semester_categories
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for semester_categories
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table course_semester_category to be created
        $table = new xmldb_table('course_semester_category');

        // Adding fields to table course_semester_category
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('semester_category', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('path', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table course_semester_category
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for course_semester_category
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table category_semester_category to be created.
        $table = new xmldb_table('category_semester_category');

        // Adding fields to table category_semester_category.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('category', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('semester_category', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table category_semester_category.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for category_semester_category.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // mycourses savepoint reached
        upgrade_block_savepoint(true, 2013070702, 'mycourses');
    }

    if ($oldversion < 2014081900) {

        // Define table mycourses_favourites to be created.
        $table = new xmldb_table('mycourses_favourites');

        // Adding fields to table mycourses_favourites.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table mycourses_favourites.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('favourites_user_foreign', XMLDB_KEY_FOREIGN, array('user'), 'user', array('id'));
        $table->add_key('favourites_course_foreign', XMLDB_KEY_FOREIGN, array('course'), 'course', array('id'));

        // Adding indexes to table mycourses_favourites.
        $table->add_index('favourites_user_course', XMLDB_INDEX_UNIQUE, array('user', 'course'));

        // Conditionally launch create table for mycourses_favourites.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Mycourses savepoint reached.
        upgrade_block_savepoint(true, 2014081900, 'mycourses');
    }

    return $result;
}
?>
