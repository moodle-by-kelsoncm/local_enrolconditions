# Changelog

All notable changes to the Enrolment conditions plugin will be documented in this file.

## 0.1.0 - Unreleased

### Added
- Subplugin type `enrolcond` (folder `condition/`) with plugin info, enable/disable and settings pages.
- Base class `\local_enrolconditions\condition\base` and `\local_enrolconditions\condition\manager`.
- Hook `\local_enrolconditions\hook\collect_conditions`, so other plugins can provide conditions.
- Condition management page under *Site administration > Plugins > Enrolments*.
- Conditions `enrolcond_date` (period), `enrolcond_profile` (standard or custom profile field), `enrolcond_cohort` (cohort member, re-checked on cohort membership events) and `enrolcond_coursecompleted` (another course completed, re-checked on `course_completed`).
- Conditions `enrolcond_userlist` (usernames typed in the condition), `enrolcond_userfile` (CSV or text file kept in the course, replaceable, with a daily cleanup of unused files and a privacy provider) and `enrolcond_sqlquery` (lists defined by administrators as SELECT queries returning `username`, cached, never run inside an open transaction).
- A condition that throws an exception is unknown: it blocks new self enrolments and never suspends.
- `base::config_to_form_data_in_context()` and `base::config_after_submission()`, for conditions that keep data outside their configuration.
- `base::restore_config()` and `tree::for_restore()`: on restores from another site, the cohort and course completed conditions point to nothing and fail until edited.
