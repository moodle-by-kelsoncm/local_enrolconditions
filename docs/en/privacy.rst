Privacy
=======

No data leaves Moodle.

* ``local_enrolconditions`` stores no personal data: the condition trees belong to the enrolment methods that use them.
* ``enrolcond_userfile`` finds the users listed in the files of usernames: a data request of those users exports the names of the files, and a deletion removes their line from the files.
* The other condition types store no personal data. The lists from a query read data that is already in Moodle; only administrators define them (see :doc:`administration`).
* The records of the suspensions made by ``enrol_conditional`` are covered by its own Privacy API.
