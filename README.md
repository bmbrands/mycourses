# mycourses
Fast course overview for Moodle.

MyCourses is a block for Moodle to replace or supplement the built-in
Course Overview. Highlight features include:

* Courses are tabbed by category, e.g. by semester
* Courses may be favourited for easy access to the most important ones
* The course list is searchable


## WORK IN PROGRESS: THIS SOFTWARE IS NOT YET READY FOR GENERAL CONSUMPTION

MyCourses has been in production use at Aalborg University, Denmark,
for nearly two years (April, 2015), and has performed very well.
However, there are a few obscacles in the way of it being ready for
the world.

First and foremost it depends on PostgreSQL as the underlying RDBMS.
Several SQL queries require recursive SELECTs, a feature which is not
present in e.g. MySQL. It also depends on a custom PostgreSQL-specific
aggregate function.

Secondly, it requires that certain things be entered into the database
manually, some before installation and some in the course of normal
operation. Slick user interfaces are nice but conspicuously absent.

Thirdly, the code is generally a mess. When I wrote my first lines of
code on this project, I'd only just heard of Moodle. Limited
documentation, an aggressive deadline and later other assignments have
meant that there's room for improvement.

Despair not, though; help is on the way! Other Moodle experts have
expressed an interest in cleaning up and using the code. If you want
to contribute, please get in touch.


Martin Sand Christensen
Aalborg University