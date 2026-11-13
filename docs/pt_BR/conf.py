import moodle_docs_theme

project = "moodle-local_enrolconditions"
copyright = "2026, Kelson C. M."
author = "Kelson C. M."
release = "1.0.0"

extensions = [
    "sphinx.ext.githubpages",
    "moodle_docs_theme",
]

templates_path = []
exclude_patterns = ["_build", "Thumbs.db", ".DS_Store"]

language = "pt_BR"

html_theme = "moodle_docs_theme"
html_theme_path = [moodle_docs_theme.get_html_theme_path()]

html_theme_options = {
    "project_name": "moodle-local_enrolconditions",
    "tagline": "Motor de condições da autoinscrição condicional do Moodle",
    "github_url": "https://github.com/moodle-by-kelsoncm/local_enrolconditions",
    "github_repo": "moodle-by-kelsoncm/local_enrolconditions",
    "github_version": "main",
    "doc_path": "docs/pt_BR/",
    "show_edit_on_github": True,
    "enable_dark_mode": True,
    "enable_language_selector": True,
    "navigation_links": "Início|index, Instalação|installation, Administração|administration, Condições|conditions, Desenvolvimento|developers, Privacidade|privacy",
}

html_static_path = []

# Plugin icon (docs/img), also used on the Moodle Marketplace page.
html_logo = "../img/icon.svg"
html_favicon = "../img/favicon.ico"
