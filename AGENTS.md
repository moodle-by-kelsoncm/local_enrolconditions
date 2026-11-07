# local_enrolconditions - Agent Guidelines

Condition engine of `enrol_conditional`: subplugin type `enrolcond`, condition base class, manager and the hook `collect_conditions`. Supports Moodle 5.3 LTS only.

## Rules

- Conditions are stateless; their configuration is an array stored as JSON by the caller.
- `validate_config()` runs on every save path; never rely only on the form validation.
- Conditions provided through the hook must override `get_type()` with an identifier prefixed with their component.
- Only `lang/en` is shipped. Do not add other language folders.
- Validate with `act` before pushing: code checker and PHPDoc run with zero warnings allowed.
