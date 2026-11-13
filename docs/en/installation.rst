Installation
============

Requirements: Moodle 5.3 LTS.

#. Copy this plugin to ``public/local/enrolconditions``. The seven condition types come with it, in ``public/local/enrolconditions/condition``.
#. Visit *Site administration > Notifications* and finish the installation.
#. Install `enrol_conditional <https://github.com/moodle-by-kelsoncm/enrol_conditional>`_ in ``public/enrol/conditional``, the enrolment method that uses these conditions (see its `documentation <https://moodle-by-kelsoncm.github.io/enrol_conditional/>`_).

All condition types are enabled after the installation. See :doc:`administration` to disable the ones the site does not use.

Why a local plugin
------------------

Moodle does not allow subplugins in enrolment plugins, so the condition types live in this plugin instead of in ``enrol_conditional``. It also lets other plugins reuse the engine.

Uninstalling
------------

Uninstall ``enrol_conditional`` first: it depends on this plugin. A single condition type can be uninstalled in *Manage conditions*; enrolment methods that still use it treat the condition as unknown (see :doc:`conditions`).
