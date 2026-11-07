# moodle-local_enrolconditions

[English](#english) · [Português (Brasil)](#português-brasil)

## English

Condition engine for [`enrol_conditional`](https://github.com/moodle-by-kelsoncm/enrol_conditional). It declares the `enrolcond` subplugin type (folder `condition/`), the base class `\local_enrolconditions\condition\base` and the hook `\local_enrolconditions\hook\collect_conditions`, through which any other plugin can provide conditions too. Administrators enable and disable the conditions in *Site administration > Plugins > Enrolments > Enrolment conditions > Manage conditions*. Supports Moodle 5.3 LTS.

Install: copy this repository to `public/local/enrolconditions` and visit *Site administration > Notifications*.

> Status: under development.

## Português (Brasil)

Motor de condições do [`enrol_conditional`](https://github.com/moodle-by-kelsoncm/enrol_conditional). Declara o tipo de subplugin `enrolcond` (pasta `condition/`), a classe base `\local_enrolconditions\condition\base` e o hook `\local_enrolconditions\hook\collect_conditions`, pelo qual qualquer outro plugin também pode fornecer condições. O administrador habilita e desabilita as condições em *Administração do site > Plugins > Inscrições > Condições de inscrição > Gerenciar condições*. Suporta Moodle 5.3 LTS.

Instalação: copie este repositório para `public/local/enrolconditions` e acesse *Administração do site > Notificações*.

> Situação: em desenvolvimento.

## Why a local plugin / Por que um plugin local

Moodle only accepts subplugins under `mod`, `editor`, `tool` and `local` plugins (`core_component::$supportsubplugins`), so the enrolment method cannot declare them itself. / O Moodle só aceita subplugins em plugins `mod`, `editor`, `tool` e `local`, então o método de inscrição não pode declará-los.

## License / Licença

GNU GPL v3 or later. See [LICENSE.md](LICENSE.md).
