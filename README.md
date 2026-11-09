# moodle-local_enrolconditions

[English](#english) · [Português (Brasil)](#português-brasil)

Documentation / Documentação: <https://moodle-by-kelsoncm.github.io/enrol_conditional/>

## English

Condition engine for [`enrol_conditional`](https://github.com/moodle-by-kelsoncm/enrol_conditional). It declares the `enrolcond` subplugin type (folder `condition/`), the base class `\local_enrolconditions\condition\base` and the hook `\local_enrolconditions\hook\collect_conditions`, through which any other plugin can provide conditions too. Administrators enable and disable the conditions in *Site administration > Plugins > Enrolments > Enrolment conditions > Manage conditions*. Supports Moodle 5.3 LTS.

Conditions included:

| Subplugin | Met by |
|---|---|
| `enrolcond_date` | the period between two dates |
| `enrolcond_profile` | a standard or custom profile field compared with a value |
| `enrolcond_cohort` | the members of a cohort |
| `enrolcond_coursecompleted` | the users who completed another course |
| `enrolcond_userlist` | the usernames typed in the condition (up to 5,000) |
| `enrolcond_userfile` | the usernames of a CSV or text file kept in the course (up to 200,000) |
| `enrolcond_sqlquery` | the usernames returned by a query defined by an administrator |

Install: copy this repository to `public/local/enrolconditions` and visit *Site administration > Notifications*.

### Writing a condition

Extend `\local_enrolconditions\condition\base` and implement `is_met()`, `get_description()`, `config_form_definition()`, `config_from_form_data()` and `validate_config()`. Optional methods: `config_to_form_data()`, `config_form_validation()`, `get_observed_events()` (events after which enrolments are checked again), `restore_config()` (ids that do not survive a restore on another site) and `config_after_submission()` (data kept outside the configuration, such as files).

- As a subplugin: `condition/<name>/classes/condition.php`, class `enrolcond_<name>\condition`.
- From any plugin: register a callback for `\local_enrolconditions\hook\collect_conditions` in `db/hooks.php` and call `$hook->add_condition(new \<component>\my_condition())`. The type returned by `get_type()` must start with the component.

`is_met()` may throw an exception when it cannot decide: the condition is then *unknown*, which blocks new self enrolments but never suspends anybody.

## Português (Brasil)

Motor de condições do [`enrol_conditional`](https://github.com/moodle-by-kelsoncm/enrol_conditional). Declara o tipo de subplugin `enrolcond` (pasta `condition/`), a classe base `\local_enrolconditions\condition\base` e o hook `\local_enrolconditions\hook\collect_conditions`, pelo qual qualquer outro plugin também pode fornecer condições. O administrador habilita e desabilita as condições em *Administração do site > Plugins > Inscrições > Enrolment conditions > Manage conditions*. Suporta Moodle 5.3 LTS.

Condições incluídas: data, campo de perfil, coorte, curso concluído, lista digitada de usernames, arquivo de usernames guardado no curso e listas de usernames definidas pelo administrador como consultas SQL (veja a tabela acima e a documentação).

Instalação: copie este repositório para `public/local/enrolconditions` e acesse *Administração do site > Notificações*.

Para criar uma condição, estenda `\local_enrolconditions\condition\base` (veja *Writing a condition* acima), como subplugin em `condition/<nome>` ou registrando-a pelo hook `collect_conditions` a partir de qualquer plugin.

## Why a local plugin / Por que um plugin local

Moodle only accepts subplugins under `mod`, `editor`, `tool` and `local` plugins (`core_component::$supportsubplugins`), so the enrolment method cannot declare them itself. / O Moodle só aceita subplugins em plugins `mod`, `editor`, `tool` e `local`, então o método de inscrição não pode declará-los.

## Support / Suporte

- Issue tracker / Rastreador de issues: <https://github.com/moodle-by-kelsoncm/local_enrolconditions/issues>
- Translations / Traduções: only English is shipped; other languages go through AMOS / o pacote traz só inglês; outros idiomas via AMOS.

## License / Licença

GNU GPL v3 or later. See [LICENSE.md](LICENSE.md).
