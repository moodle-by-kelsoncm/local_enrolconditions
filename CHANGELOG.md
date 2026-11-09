# Changelog

All notable changes to the Enrolment conditions plugin will be documented in this file.

## 0.1.0 - Unreleased

### Added
- Subplugin type `enrolcond` (folder `condition/`) with plugin info, enable/disable and settings pages.
- Base class `\local_enrolconditions\condition\base` and `\local_enrolconditions\condition\manager`.
- Hook `\local_enrolconditions\hook\collect_conditions`, so other plugins can provide conditions.
- Condition management page under *Site administration > Plugins > Enrolments*.
- Conditions `enrolcond_date` (period), `enrolcond_profile` (standard or custom profile field), `enrolcond_cohort` (cohort member, re-checked on cohort membership events) and `enrolcond_coursecompleted` (another course completed, re-checked on `course_completed`).
