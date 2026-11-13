Desenvolvimento
===============

Uma condição é uma classe que estende ``\local_enrolconditions\condition\base``. Ela não guarda estado: a configuração de cada uso chega em ``$config``, um array guardado como JSON na árvore de condições do método de inscrição.

Fornecendo uma condição
-----------------------

Como subplugin
   Crie ``public/local/enrolconditions/condition/<nome>/`` com ``version.php`` (componente ``enrolcond_<nome>``), ``lang/en/enrolcond_<nome>.php`` (string ``pluginname``, o nome mostrado aos professores) e ``classes/condition.php`` com a classe ``\enrolcond_<nome>\condition``. O tipo guardado nas árvores é ``<nome>``. O subplugin aparece em *Manage conditions*, onde pode ser desabilitado.

A partir de qualquer plugin, pelo hook
   Registre um callback no ``db/hooks.php`` do seu plugin e acrescente a condição. O ``get_type()`` dela deve devolver um identificador que comece com o seu componente (por exemplo ``local_meuplugin_nota``), e o ``get_name()``, o nome mostrado aos professores. As condições acrescentadas pelo hook ficam sempre habilitadas.

   .. code-block:: php

      // db/hooks.php
      $callbacks = [
          [
              'hook' => \local_enrolconditions\hook\collect_conditions::class,
              'callback' => [\local_meuplugin\hook_callbacks::class, 'collect_conditions'],
          ],
      ];

      // classes/hook_callbacks.php
      public static function collect_conditions(\local_enrolconditions\hook\collect_conditions $hook): void {
          $hook->add_condition(new \local_meuplugin\condicao_nota());
      }

   O ``add_condition()`` lança ``coding_exception`` quando o tipo não começa com o componente da classe ou já foi acrescentado. Os callbacks de hook ficam em cache: incremente a versão do seu plugin depois de alterar o ``db/hooks.php``.

A classe base
-------------

Métodos obrigatórios:

``is_met(int $userid, stdClass $instance, array $config): bool``
   Se o usuário cumpre a condição. ``$instance`` é o registro do método de inscrição. Lance uma exceção quando não for possível decidir: o resultado fica *desconhecido* (veja :doc:`conditions`).

``get_description(array $config, bool $met): string``
   Texto mostrado ao usuário; com ``$met = false``, o texto mostrado quando a autoinscrição é recusada (por exemplo "Your Department is \"Mathematics\""). Texto simples ou HTML já formatado.

``config_form_definition(MoodleQuickForm $mform): void``
   Acrescenta os campos de configuração. O formulário mostra uma condição por vez, numa janela modal, então os campos não precisam de prefixo.

``config_from_form_data(stdClass $data): array``
   Transforma os dados enviados na configuração guardada (só escalares e arrays).

``validate_config(array $config): array``
   Erros indexados pelo campo do formulário; vazio quando válido. Roda em todo salvamento, inclusive de árvores enviadas pelo JavaScript e restauradas de backups, então não dependa da validação do formulário.

Métodos opcionais:

``config_to_form_data(array $config): array`` e ``config_to_form_data_in_context(array $config, \context $context): array``
   Valores padrão do formulário para uma configuração guardada; o segundo recebe o contexto do curso, por exemplo para preparar uma área de rascunho de arquivos.

``config_after_submission(array $config, stdClass $data, \context $context): array``
   Chamado uma vez, quando a janela é salva; guarda dados que ficam fora da configuração, como arquivos.

``config_form_validation(array $data, array $files): array``
   Validação do formulário; por padrão chama ``validate_config()``.

``get_observed_events(): array``
   Eventos (nomes completos das classes) que podem mudar o resultado para um usuário. O ``enrol_conditional`` observa todos os eventos e reavalia as inscrições do usuário relacionado ao evento (ou do usuário do evento) nos métodos cujas condições o observam. O plugin que fornece a condição não precisa de ``db/events.php``.

``restore_config(array $config, bool $samesite): array``
   Configuração para um curso restaurado de backup. Sobrescreva quando ela aponta para registros que o backup não leva (coortes, outros cursos): vinda de outro site, faça a condição falhar em vez de apontar para um registro sem relação.

``get_summary(array $config): string``
   Texto curto mostrado no editor; por padrão ``get_description($config, false)``.

``get_type(): string`` e ``get_name(): string``
   Identificador e nome. Os subplugins herdam os dois; as condições acrescentadas pelo hook devem sobrescrever ambos.

Usando o motor em outro plugin
------------------------------

Hoje só o ``enrol_conditional`` usa o motor, mas as classes são públicas:

.. code-block:: php

   use local_enrolconditions\tree;

   $tree = tree::from_json($json);              // invalid_tree_exception quando a estrutura é inválida.
   $errors = $tree->validate($tiposanteriores); // Tipos e configurações; vazio quando válido.
   $result = $tree->evaluate($userid, $instance);
   if ($result->is_met()) {
       // ...
   } else if ($result->is_unknown()) {
       // ...
   }
   $failures = $result->get_failures();         // Explicação para o usuário.
   $events = $tree->get_observed_events();

O ``\local_enrolconditions\condition\manager`` lista os tipos instalados e habilitados (``get_installed_types()``, ``get_enabled_types()``, ``is_enabled()``) e devolve uma condição pelo tipo (``get_condition()``). O ``tree::for_restore()`` adapta as configurações para um curso restaurado.

Formato da árvore
-----------------

.. code-block:: json

   {"op": "and", "negate": false, "children": [
       {"type": "profile", "negate": false,
        "config": {"field": "department", "operator": "isequalto", "value": "Mathematics"}},
       {"op": "or", "negate": false, "children": [
           {"type": "cohort", "negate": false, "config": {"cohortid": 1}},
           {"type": "coursecompleted", "negate": false, "config": {"courseid": 2}}
       ]}
   ]}

Um grupo tem ``op`` (``and`` ou ``or``), ``negate`` e ``children``; uma condição tem ``type``, ``negate`` e ``config``. A raiz é sempre um grupo. Limites: 5 níveis e 100 nós.
