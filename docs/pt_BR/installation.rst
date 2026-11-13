Instalação
==========

Requisitos: Moodle 5.3 LTS.

#. Copie este plugin para ``public/local/enrolconditions``. Os sete tipos de condição vêm junto, em ``public/local/enrolconditions/condition``.
#. Acesse *Administração do site > Notificações* e conclua a instalação.
#. Instale o `enrol_conditional <https://github.com/moodle-by-kelsoncm/enrol_conditional>`_ em ``public/enrol/conditional``, o método de inscrição que usa estas condições (veja a `documentação <https://moodle-by-kelsoncm.github.io/enrol_conditional/>`_ dele).

Todos os tipos de condição ficam habilitados depois da instalação. Veja :doc:`administration` para desabilitar os que o site não usa.

Por que um plugin local
-----------------------

O Moodle não permite subplugins em plugins de inscrição, então os tipos de condição ficam neste plugin, e não no ``enrol_conditional``. Isso também permite que outros plugins reaproveitem o motor.

Desinstalação
-------------

Desinstale primeiro o ``enrol_conditional``: ele depende deste plugin. Um tipo de condição pode ser desinstalado sozinho em *Manage conditions*; os métodos de inscrição que ainda o usam tratam a condição como desconhecida (veja :doc:`conditions`).
