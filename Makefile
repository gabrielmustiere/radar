.PHONY: help init install update down serve serve-d stop \
        db-create db-drop db-reset db-test db-validate migrate migration migrate-rollback fixtures cache-clear \
        phpunit phpunit-coverage phpunit-coverage-text phpunit-coverage-clover phpunit-filter \
        playwright playwright-headed playwright-file \
        fmt fmt-check mago-lint mago-fix analyze lint build quality clean
.DEFAULT_GOAL := help

# Colors
RESET   := \033[0m
BOLD    := \033[1m
GREEN   := \033[32m
YELLOW  := \033[33m
BLUE    := \033[34m
CYAN    := \033[36m
MAGENTA := \033[35m

help: ## Affiche cette aide
	@echo ""
	@echo "$(BOLD)$(CYAN)════════════════════════════════════════════════════════$(RESET)"
	@echo "$(BOLD)$(CYAN)                  SYMFONY TEMPLATE                   $(RESET)"
	@echo "$(BOLD)$(CYAN)════════════════════════════════════════════════════════$(RESET)"
	@echo ""
	@awk 'BEGIN {FS = ":.*?## "; section = ""} \
		/^##/ { \
			if ($$0 ~ /^## [A-Z]/) { \
				gsub(/^## /, "", $$0); \
				gsub(/ ##$$/, "", $$0); \
				if ($$0 != "") { \
					if (section != "") print ""; \
					section = $$0; \
					printf "$(BOLD)$(YELLOW)▸ %s$(RESET)\n", section; \
				} \
			} \
		} \
		/^[a-zA-Z0-9_-]+:.*?## / { \
			printf "  $(GREEN)%-22s$(RESET) %s\n", $$1, $$2 \
		}' $(MAKEFILE_LIST)
	@echo ""
	@echo "$(BOLD)$(CYAN)════════════════════════════════════════════════════════$(RESET)"
	@echo ""

##
## INSTALLATION
##

init: install db-reset ## Installation complète (deps + DB + fixtures)

install: ## Installe les dépendances PHP, JS et Playwright
	@echo "$(BLUE)📦 Installation des dépendances Composer...$(RESET)"
	symfony composer install
	@echo "$(BLUE)📦 Installation des dépendances NPM...$(RESET)"
	npm install
	@echo "$(BLUE)📦 Installation de Playwright (Chromium)...$(RESET)"
	symfony php vendor/bin/playwright-install chromium

update: ## Met à jour les dépendances Composer et NPM
	@echo "$(BLUE)🔄 Mise à jour des dépendances Composer...$(RESET)"
	symfony composer update
	@echo "$(BLUE)🔄 Mise à jour des dépendances NPM...$(RESET)"
	npm update

##
## SERVEUR
##

serve: stop ## Lance le serveur Symfony
	@echo "$(BLUE)🚀 Démarrage du serveur Symfony...$(RESET)"
	symfony serve

serve-d: stop ## Lance le serveur Symfony en arrière-plan
	@echo "$(BLUE)🚀 Démarrage du serveur Symfony (daemon)...$(RESET)"
	symfony serve -d

stop: ## Arrête le serveur Symfony
	@echo "$(YELLOW)⏹️  Arrêt du serveur Symfony...$(RESET)"
	symfony server:stop

##
## DATABASE
##

db-create: ## Crée le fichier SQLite si besoin (auto-créé au premier migrate)
	@echo "$(BLUE)📦 Création du fichier SQLite...$(RESET)"
	@mkdir -p var && touch var/data.db

db-drop: ## Supprime les fichiers SQLite (dev + test)
	@echo "$(YELLOW)🗑️  Suppression des fichiers SQLite...$(RESET)"
	@rm -f var/data.db var/data_test.db

db-reset: db-drop migrate fixtures ## Recrée la base from scratch (drop + migrate + fixtures)
	@echo "$(GREEN)✅ Base de données réinitialisée!$(RESET)"

db-test: ## Prépare la base de test (migrations + fixtures en env test)
	@echo "$(BLUE)📦 Préparation de la base de test...$(RESET)"
	symfony console doctrine:migrations:migrate -n --env=test
	symfony console doctrine:fixtures:load -n --env=test

db-validate: ## Valide le schéma Doctrine
	@echo "$(BLUE)✔️  Validation du schéma Doctrine...$(RESET)"
	symfony console doctrine:schema:validate

cache-clear: ## Vide le cache Symfony
	@echo "$(BLUE)🧹 Nettoyage du cache...$(RESET)"
	symfony console cache:clear

##
## MIGRATIONS
##

migrate: ## Applique les migrations Doctrine
	@echo "$(BLUE)🔄 Application des migrations...$(RESET)"
	symfony console doctrine:migrations:migrate -n

migration: ## Génère une nouvelle migration depuis le diff d'entités
	@echo "$(BLUE)📝 Création d'une migration...$(RESET)"
	symfony console make:migration

migrate-rollback: ## Annule la dernière migration
	@echo "$(YELLOW)⏪ Annulation de la dernière migration...$(RESET)"
	symfony console doctrine:migrations:migrate prev -n

fixtures: ## Charge les fixtures Doctrine
	@echo "$(BLUE)📊 Chargement des fixtures...$(RESET)"
	symfony console doctrine:fixtures:load -n

##
## TESTS
##

phpunit: db-test ## Lance les tests PHPUnit (Unit + Functional)
	@echo "$(BLUE)🧪 Lancement des tests PHPUnit...$(RESET)"
	symfony php bin/phpunit

phpunit-coverage: db-test ## Tests avec couverture HTML (var/coverage/index.html)
	@echo "$(BLUE)🧪 Tests avec couverture HTML...$(RESET)"
	XDEBUG_MODE=coverage symfony php bin/phpunit --coverage-html var/coverage --coverage-filter=src

phpunit-coverage-text: db-test ## Tests avec résumé couverture console (rapide)
	@echo "$(BLUE)🧪 Tests avec résumé couverture...$(RESET)"
	XDEBUG_MODE=coverage symfony php bin/phpunit --coverage-text=php://stdout --coverage-filter=src

phpunit-coverage-clover: db-test ## Tests avec rapport clover.xml
	@echo "$(BLUE)🧪 Tests avec rapport clover...$(RESET)"
	XDEBUG_MODE=coverage symfony php bin/phpunit --coverage-clover var/coverage/clover.xml --coverage-filter=src

ifeq (phpunit-filter,$(firstword $(MAKECMDGOALS)))
  FILTER_ARG := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
  $(eval $(FILTER_ARG):;@:)
endif
phpunit-filter: db-test ## Lance un test par nom (ex: make phpunit-filter LoginTest)
	@if [ -z "$(FILTER_ARG)" ]; then echo "$(YELLOW)Usage: make phpunit-filter NomDuTest$(RESET)"; exit 1; fi
	@echo "$(BLUE)🧪 Tests avec filtre: $(FILTER_ARG)...$(RESET)"
	@symfony php bin/phpunit --filter "$(FILTER_ARG)"

playwright: ## Lance les tests E2E Playwright (headless)
	@echo "$(BLUE)🧪 Lancement des tests Playwright...$(RESET)"
	symfony php bin/phpunit --testsuite=e2e

playwright-headed: ## Tests E2E Playwright avec navigateur visible
	@echo "$(BLUE)🧪 Lancement des tests Playwright (mode visible)...$(RESET)"
	PW_HEADLESS=0 symfony php bin/phpunit --testsuite=e2e

ifeq (playwright-file,$(firstword $(MAKECMDGOALS)))
  PW_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
  $(eval $(PW_ARGS):;@:)
endif
playwright-file: ## Lance un test E2E ciblé. Ex: make playwright-file tests/E2E/LoginTest.php
	@if [ -z "$(PW_ARGS)" ]; then echo "$(YELLOW)Usage: make playwright-file <chemin>$(RESET)"; exit 1; fi
	@echo "$(BLUE)🧪 Lancement du test Playwright : $(PW_ARGS)...$(RESET)"
	@symfony php bin/phpunit $(PW_ARGS)

##
## QUALITÉ
##

fmt: ## Formate le code PHP (Mago)
	@echo "$(BLUE)🔧 Formatage du code...$(RESET)"
	symfony php vendor/bin/mago fmt

fmt-check: ## Vérifie le formatage sans modifier (Mago)
	@echo "$(BLUE)🔍 Vérification du formatage...$(RESET)"
	symfony php vendor/bin/mago fmt --check

mago-lint: ## Linter Mago en lecture seule
	@echo "$(BLUE)🔍 Lint avec Mago...$(RESET)"
	symfony php vendor/bin/mago lint

mago-fix: ## Applique les correctifs sûrs du linter Mago
	@echo "$(BLUE)🔧 Correctifs du linter Mago...$(RESET)"
	symfony php vendor/bin/mago lint --fix --format-after-fix

analyze: ## Analyse statique Mago
	@echo "$(BLUE)🔍 Analyse statique avec Mago...$(RESET)"
	symfony php vendor/bin/mago analyze

lint: fmt-check mago-lint analyze ## Lint en lecture seule (Mago : format, lint, analyse)

build: ## Build des assets (Tailwind + AssetMapper)
	@echo "$(BLUE)🏗️  Build des assets...$(RESET)"
	symfony console tailwind:build --minify
	symfony console asset-map:compile

quality: fmt mago-fix analyze build ## Lance toute la QA en mode dev (Mago : format, correctifs, analyse + build)

##
## NETTOYAGE
##

clean: ## Nettoie les fichiers temporaires
	@echo "$(YELLOW)🧹 Nettoyage des fichiers temporaires...$(RESET)"
	@rm -rf var/cache/* var/log/* var/coverage/* .phpunit.cache test-failures
	@echo "$(GREEN)✅ Nettoyage terminé!$(RESET)"
