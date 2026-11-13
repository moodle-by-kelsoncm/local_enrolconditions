Privacidade
===========

Nenhum dado sai do Moodle.

* O ``local_enrolconditions`` não guarda dados pessoais: as árvores de condições pertencem aos métodos de inscrição que as usam.
* O ``enrolcond_userfile`` encontra os usuários listados nos arquivos de usernames: a solicitação de dados desses usuários exporta os nomes dos arquivos, e a exclusão remove a linha deles dos arquivos.
* Os demais tipos de condição não guardam dados pessoais. As listas de consulta leem dados que já estão no Moodle; só administradores as definem (veja :doc:`administration`).
* Os registros das suspensões feitas pelo ``enrol_conditional`` são cobertos pela Privacy API dele.
