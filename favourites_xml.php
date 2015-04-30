<?php

//defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

//require_sesskey();
require_login();

global $DB, $USER;

if (!isset($USER)) {
    header('HTTP/1.1 401 Unauthorized');
    die();
}
if (!isset($_GET["action"])) {
    header('HTTP/1.1 400 Malformed');
    echo "needs 'action' parameter";
    die();
}

switch ($_GET["action"]) {
case "get":
    $sql = "SELECT id, course FROM {mycourses_favourites} WHERE \"user\" = :id";
    $courses = $DB->get_records_sql($sql,
				    array("id"=>$USER->id));
    $c = array();
    foreach ($courses as $course) {$c[] = $course->course;}
    echo "[" . implode(", ", $c) . "]";
    break;
case "favourite":
    $sql = "INSERT INTO {mycourses_favourites} (\"user\", course) "
	. "VALUES (:user, :course)";
    $DB->execute($sql, array("user"=>$USER->id, "course"=>$_GET["course"]));
    break;
case "unfavourite":
    $sql = "DELETE FROM {mycourses_favourites} " .
	"WHERE \"user\" = :user AND course = :course";
    $DB->execute($sql, array("user"=>$USER->id, "course"=>$_GET["course"]));
    break;
default:
    echo "Unknown action: " . $_GET["action"];
}

?>
