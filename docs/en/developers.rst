Developers
==========

A condition is a class that extends ``\local_enrolconditions\condition\base``. It is stateless: the configuration of each use comes in ``$config``, an array stored as JSON in the condition tree of the enrolment method.

Providing a condition
---------------------

As a subplugin
   Create ``public/local/enrolconditions/condition/<name>/`` with ``version.php`` (component ``enrolcond_<name>``), ``lang/en/enrolcond_<name>.php`` (string ``pluginname``, the name shown to teachers) and ``classes/condition.php`` with the class ``\enrolcond_<name>\condition``. The type stored in the trees is ``<name>``. The subplugin appears in *Manage conditions*, where it can be disabled.

From any plugin, through a hook
   Register a callback in the ``db/hooks.php`` of your plugin and add the condition. Its ``get_type()`` must return an identifier that starts with your component (for example ``local_myplugin_grade``), and ``get_name()`` the name shown to teachers. Conditions added through the hook are always enabled.

   .. code-block:: php

      // db/hooks.php
      $callbacks = [
          [
              'hook' => \local_enrolconditions\hook\collect_conditions::class,
              'callback' => [\local_myplugin\hook_callbacks::class, 'collect_conditions'],
          ],
      ];

      // classes/hook_callbacks.php
      public static function collect_conditions(\local_enrolconditions\hook\collect_conditions $hook): void {
          $hook->add_condition(new \local_myplugin\grade_condition());
      }

   ``add_condition()`` throws a ``coding_exception`` when the type does not start with the component of the class or was already added. Hook callbacks are cached: bump the version of your plugin after changing ``db/hooks.php``.

The base class
--------------

Required methods:

``is_met(int $userid, stdClass $instance, array $config): bool``
   Whether the user meets the condition. ``$instance`` is the record of the enrolment method. Throw an exception when the condition cannot be decided: its result is then *unknown* (see :doc:`conditions`).

``get_description(array $config, bool $met): string``
   Text shown to the user; with ``$met = false``, the text shown when self enrolment is refused (for example "Your Department is \"Mathematics\""). Plain text or HTML already formatted.

``config_form_definition(MoodleQuickForm $mform): void``
   Adds the configuration fields. The form shows one condition at a time, in a modal, so the fields need no prefix.

``config_from_form_data(stdClass $data): array``
   Turns the submitted data into the stored configuration (scalars and arrays only).

``validate_config(array $config): array``
   Errors keyed by form field, empty when valid. It runs on every save, including trees sent by JavaScript and trees restored from backups, so do not rely on the form validation.

Optional methods:

``config_to_form_data(array $config): array`` and ``config_to_form_data_in_context(array $config, \context $context): array``
   Default values of the form for a stored configuration; the second one receives the course context, for example to prepare a draft file area.

``config_after_submission(array $config, stdClass $data, \context $context): array``
   Called once when the dialog is saved; persists data kept outside the configuration, such as files.

``config_form_validation(array $data, array $files): array``
   Form validation; by default it calls ``validate_config()``.

``get_observed_events(): array``
   Events (fully qualified class names) that may change the result for a user. ``enrol_conditional`` observes every event and checks the enrolments of the event's related user (or of its user) in the methods whose conditions observe it. No ``db/events.php`` is needed in the plugin that provides the condition.

``restore_config(array $config, bool $samesite): array``
   Configuration for a course restored from a backup. Override when it refers to records the backup does not carry (cohorts, other courses): from another site, make the condition fail instead of pointing to an unrelated record.

``get_summary(array $config): string``
   Short text shown in the editor; by default ``get_description($config, false)``.

``get_type(): string`` and ``get_name(): string``
   Identifier and name. Subplugins inherit them; conditions added through the hook must override both.

Using the engine in another plugin
----------------------------------

``enrol_conditional`` is the only user of the engine today, but the classes are public:

.. code-block:: php

   use local_enrolconditions\tree;

   $tree = tree::from_json($json);              // invalid_tree_exception when the structure is invalid.
   $errors = $tree->validate($previoustypes);   // Types and configurations; empty when valid.
   $result = $tree->evaluate($userid, $instance);
   if ($result->is_met()) {
       // ...
   } else if ($result->is_unknown()) {
       // ...
   }
   $failures = $result->get_failures();         // Explanation for the user.
   $events = $tree->get_observed_events();

``\local_enrolconditions\condition\manager`` lists the installed and enabled types (``get_installed_types()``, ``get_enabled_types()``, ``is_enabled()``) and returns a condition by type (``get_condition()``). ``tree::for_restore()`` adapts the configurations for a restored course.

Tree format
-----------

.. code-block:: json

   {"op": "and", "negate": false, "children": [
       {"type": "profile", "negate": false,
        "config": {"field": "department", "operator": "isequalto", "value": "Mathematics"}},
       {"op": "or", "negate": false, "children": [
           {"type": "cohort", "negate": false, "config": {"cohortid": 1}},
           {"type": "coursecompleted", "negate": false, "config": {"courseid": 2}}
       ]}
   ]}

A group has ``op`` (``and`` or ``or``), ``negate`` and ``children``; a condition has ``type``, ``negate`` and ``config``. The root is always a group. Limits: 5 levels and 100 nodes.
