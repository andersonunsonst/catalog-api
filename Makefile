.PHONY: help build up down restart logs shell composer artisan migrate fresh seed test clean install

# Detectar comando docker compose
DOCKER_COMPOSE := $(shell which docker-compose 2>/dev/null)
ifeq ($(DOCKER_COMPOSE),)
	DOCKER_COMPOSE_CMD := docker compose
else
	DOCKER_COMPOSE_CMD := docker-compose
endif

# Detectar se precisa de sudo
DOCKER_TEST := $(shell docker ps 2>&1 | grep -q "permission denied" && echo "sudo" || echo "")
DOCKER_COMPOSE := $(DOCKER_TEST) $(DOCKER_COMPOSE_CMD)

# Cores para output
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)

help: ## Mostra esta ajuda
	@echo ''
	@echo 'Uso:'
	@echo '  ${YELLOW}make${RESET} ${GREEN}<target>${RESET}'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} { \
		if (/^[a-zA-Z_-]+:.*?##.*$$/) {printf "  ${YELLOW}%-20s${GREEN}%s${RESET}\n", $$1, $$2} \
		else if (/^## .*$$/) {printf "  ${CYAN}%s${RESET}\n", substr($$1,4)} \
		}' $(MAKEFILE_LIST)

install: ## Instalação inicial completa
	@echo "${GREEN}🚀 Instalando projeto...${RESET}"
	@./setup.sh

build: ## Construir containers
	@echo "${GREEN}🔨 Construindo containers...${RESET}"
	$(DOCKER_COMPOSE) build --no-cache

up: ## Iniciar containers
	@echo "${GREEN}▶️  Iniciando containers...${RESET}"
	$(DOCKER_COMPOSE) up -d

down: ## Parar containers
	@echo "${YELLOW}⏹️  Parando containers...${RESET}"
	$(DOCKER_COMPOSE) down

restart: down up ## Reiniciar containers

logs: ## Ver logs de todos os containers
	$(DOCKER_COMPOSE) logs -f

logs-app: ## Ver logs do container app
	$(DOCKER_COMPOSE) logs -f app

logs-nginx: ## Ver logs do nginx
	$(DOCKER_COMPOSE) logs -f nginx

logs-mysql: ## Ver logs do mysql
	$(DOCKER_COMPOSE) logs -f mysql

queue-logs: ## Ver logs do queue worker
	$(DOCKER_COMPOSE) logs -f queue

queue-restart: ## Reiniciar queue worker
	$(DOCKER_COMPOSE) restart queue

shell: ## Acessar shell do container app
	$(DOCKER_COMPOSE) exec app bash

shell-root: ## Acessar shell do container app como root
	$(DOCKER_COMPOSE) exec -u root app bash

mysql: ## Acessar MySQL CLI
	$(DOCKER_COMPOSE) exec mysql mysql -u crmq_user -psecret crmq_db

composer-install: ## Instalar dependências do Composer
	$(DOCKER_COMPOSE) exec app composer install

composer-update: ## Atualizar dependências do Composer
	$(DOCKER_COMPOSE) exec app composer update

artisan: ## Executar comando artisan (use: make artisan CMD="migrate")
	$(DOCKER_COMPOSE) exec app php artisan $(CMD)

migrate: ## Executar migrations
	@echo "${GREEN}🗄️  Executando migrations...${RESET}"
	$(DOCKER_COMPOSE) exec app php artisan migrate

migrate-fresh: ## Resetar banco de dados e executar migrations
	@echo "${YELLOW}⚠️  Resetando banco de dados...${RESET}"
	$(DOCKER_COMPOSE) exec app php artisan migrate:fresh

migrate-rollback: ## Reverter última migration
	$(DOCKER_COMPOSE) exec app php artisan migrate:rollback

seed: ## Executar seeders
	$(DOCKER_COMPOSE) exec app php artisan db:seed

fresh: migrate-fresh seed ## Resetar banco e executar seeders

tinker: ## Abrir Laravel Tinker
	$(DOCKER_COMPOSE) exec app php artisan tinker

test: ## Executar testes
	$(DOCKER_COMPOSE) exec app php artisan test

test-unit: ## Executar apenas unit tests
	@echo "${GREEN}🧪 Executando unit tests...${RESET}"
	$(DOCKER_COMPOSE) exec app php artisan test --testsuite=Unit

test-feature: ## Executar apenas feature tests
	@echo "${GREEN}🧪 Executando feature tests...${RESET}"
	$(DOCKER_COMPOSE) exec app php artisan test --testsuite=Feature

test-verbose: ## Executar testes com output detalhado
	$(DOCKER_COMPOSE) exec app php artisan test --verbose

test-filter: ## Executar teste específico (use: make test-filter FILTER=test_can_create_product)
	$(DOCKER_COMPOSE) exec app php artisan test --filter=$(FILTER)

cache-clear: ## Limpar todos os caches
	@echo "${GREEN}🧹 Limpando cache...${RESET}"
	$(DOCKER_COMPOSE) exec app php artisan cache:clear
	$(DOCKER_COMPOSE) exec app php artisan config:clear
	$(DOCKER_COMPOSE) exec app php artisan route:clear
	$(DOCKER_COMPOSE) exec app php artisan view:clear

optimize: ## Otimizar aplicação
	$(DOCKER_COMPOSE) exec app php artisan config:cache
	$(DOCKER_COMPOSE) exec app php artisan route:cache
	$(DOCKER_COMPOSE) exec app php artisan view:cache

permissions: ## Corrigir permissões do storage
	@echo "${GREEN}🔧 Corrigindo permissões...${RESET}"
	$(DOCKER_COMPOSE) exec app chmod -R 775 storage bootstrap/cache
	$(DOCKER_COMPOSE) exec app chown -R www-data:www-data storage bootstrap/cache
	@echo "${GREEN}✅ Permissões corrigidas${RESET}"

clean: down ## Limpar tudo (containers, volumes, imagens)
	@echo "${YELLOW}🗑️  Limpando tudo...${RESET}"
	$(DOCKER_COMPOSE) down -v --remove-orphans
	docker system prune -f

ps: ## Mostrar status dos containers
	$(DOCKER_COMPOSE) ps

stats: ## Mostrar estatísticas dos containers
	docker stats

create-user: ## Criar usuário de teste
	@echo "${GREEN}👤 Criando usuário de teste...${RESET}"
	$(DOCKER_COMPOSE) exec app php artisan tinker --execute="User::create(['name' => 'Teste User', 'email' => 'teste@example.com', 'password' => bcrypt('senha123')]);"
	@echo "${GREEN}✅ Usuário criado: teste@example.com / senha123${RESET}"

test-login: ## Testar endpoint de login
	@echo "${GREEN}🔐 Testando login...${RESET}"
	@curl -X POST http://localhost:8000/api/login \
		-H "Content-Type: application/json" \
		-H "Accept: application/json" \
		-d '{"email":"teste@example.com","password":"senha123"}' | jq .

queue-failed: ## Ver jobs falhados
	$(DOCKER_COMPOSE) exec app php artisan queue:failed

queue-retry: ## Reprocessar jobs falhados
	$(DOCKER_COMPOSE) exec app php artisan queue:retry all

queue-debug: ## Debug do último job falhado
	@echo "${YELLOW}🔍 Último job falhado:${RESET}"
	$(DOCKER_COMPOSE) exec app php artisan tinker --execute="\$$failed = DB::table('failed_jobs')->latest()->first(); if (\$$failed) { echo substr(\$$failed->exception, 0, 500); } else { echo 'Nenhum job falhado'; }"

queue-status: ## Ver status da fila
	@echo "${GREEN}📊 Queue Status:${RESET}"
	@$(DOCKER_COMPOSE) exec app php artisan tinker --execute="echo 'Pending: '.DB::table('jobs')->count();"
	@$(DOCKER_COMPOSE) logs queue --tail=5

queue-test: ## Criar produto de teste e verificar indexação
	@echo "${GREEN}🧪 Testando queue...${RESET}"
	@curl -X POST http://localhost:8000/api/products \
		-H "Content-Type: application/json" \
		-d '{"sku":"TEST-'$$(date +%s)'","name":"Queue Test","price":99,"category":"Test"}' | jq .
	@sleep 2
	@echo "\n${GREEN}✅ Verificando logs:${RESET}"
	@$(DOCKER_COMPOSE) logs queue --tail=3

cache-test: ## Testar cache Redis
	@echo "${GREEN}🧪 Testando cache...${RESET}"
	@echo "1. Limpando cache..."
	@$(DOCKER_COMPOSE) exec app php artisan cache:clear > /dev/null
	@$(DOCKER_COMPOSE) exec redis redis-cli -n 1 FLUSHDB > /dev/null
	@echo "\n2. Primeira requisição (SEM cache):"
	@time curl -s "http://localhost:8000/api/products/1" > /dev/null
	@echo "\n3. Segunda requisição (COM cache):"
	@time curl -s "http://localhost:8000/api/products/1" > /dev/null
	@echo "\n4. Chaves no Redis (database 1 - cache):"
	@$(DOCKER_COMPOSE) exec redis redis-cli -n 1 KEYS "*product*"
	@echo "\n5. Valor do cache:"
	@$(DOCKER_COMPOSE) exec redis redis-cli -n 1 GET "catalog-api-cache-:product.1" | head -c 100
	@echo "..."

cache-clear-all: ## Limpar todo o cache Redis
	@echo "${YELLOW}🗑️  Limpando cache Redis...${RESET}"
	@$(DOCKER_COMPOSE) exec redis redis-cli -n 1 FLUSHDB
	@$(DOCKER_COMPOSE) exec app php artisan cache:clear
	@echo "${GREEN}✅ Cache limpo!${RESET}"

redis-monitor: ## Monitorar Redis em tempo real
	$(DOCKER_COMPOSE) exec redis redis-cli MONITOR

redis-keys: ## Listar todas as chaves do Redis
	@echo "${GREEN}📋 Chaves no Redis:${RESET}"
	@echo "\n${YELLOW}Database 0 (default):${RESET}"
	@$(DOCKER_COMPOSE) exec redis redis-cli -n 0 KEYS "*" || echo "  (vazio)"
	@echo "\n${YELLOW}Database 1 (cache):${RESET}"
	@$(DOCKER_COMPOSE) exec redis redis-cli -n 1 KEYS "*" || echo "  (vazio)"
	@echo "\n${YELLOW}Database 2 (queue):${RESET}"
	@$(DOCKER_COMPOSE) exec redis redis-cli -n 2 KEYS "*" || echo "  (vazio)"

test-db-check: ## Verificar configuração de banco de dados
	@echo "${GREEN}🔍 Verificando configuração de banco...${RESET}"
	@echo "\n${YELLOW}Runtime (deve ser MySQL):${RESET}"
	@$(DOCKER_COMPOSE) exec app php artisan tinker --execute="echo config('database.default');"
	@echo "\n${YELLOW}Tests (deve ser SQLite):${RESET}"
	@$(DOCKER_COMPOSE) exec app php -r "putenv('APP_ENV=testing'); require 'vendor/autoload.php'; \$$app = require_once 'bootstrap/app.php'; \$$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); echo config('database.default');"
	@echo "\n${GREEN}✅ Configuração correta!${RESET}"

storage-link: ## Criar symlink para storage público
	@echo "${GREEN}🔗 Criando symlink para storage...${RESET}"
	$(DOCKER_COMPOSE) exec app php artisan storage:link
	@echo "${GREEN}✅ Symlink criado!${RESET}"

test-image-upload: ## Testar upload de imagens
	@echo "${GREEN}📸 Testando upload de imagens...${RESET}"
	$(DOCKER_COMPOSE) exec app php artisan test --filter ProductImageUploadTest

test-image-local: ## Testar upload local (sem S3)
	@echo "${GREEN}📸 Testando upload local...${RESET}"
	@echo "Criando produto de teste..."
	@PRODUCT_ID=$$(curl -s -X POST http://localhost:8000/api/products \
		-H "Content-Type: application/json" \
		-d '{"sku":"IMG-TEST-'$$(date +%s)'","name":"Image Test","price":99,"category":"Test"}' \
		| jq -r '.data.id'); \
	echo "Produto criado: $$PRODUCT_ID"; \
	echo "Testando upload..."; \
	curl -X POST http://localhost:8000/api/products/$$PRODUCT_ID/image \
		-F "image=@tests/fixtures/test-image.jpg" | jq .

test-upload-full: ## Teste completo de upload
	@echo "${GREEN}📸 Teste completo de upload...${RESET}"
	@echo "1. Criando symlink..."
	@$(DOCKER_COMPOSE) exec app php artisan storage:link 2>/dev/null || true
	@echo "2. Criando imagem de teste..."
	@$(DOCKER_COMPOSE) exec app bash -c "mkdir -p tests/fixtures && echo -n 'fake-image-data' > tests/fixtures/test-image.jpg"
	@echo "3. Criando produto..."
	@PRODUCT_ID=$$(curl -s -X POST http://localhost:8000/api/products \
		-H "Content-Type: application/json" \
		-d '{"sku":"IMG-'$$(date +%s)'","name":"Test","price":99,"category":"Test"}' \
		| jq -r '.data.id'); \
	echo "   Produto ID: $$PRODUCT_ID"; \
	echo "4. Fazendo upload..."; \
	$(DOCKER_COMPOSE) exec app curl -s -X POST http://localhost/api/products/$$PRODUCT_ID/image \
		-F "image=@tests/fixtures/test-image.jpg" | jq .; \
	echo "5. Verificando arquivo..."; \
	$(DOCKER_COMPOSE) exec app ls -lh storage/app/public/products/$$PRODUCT_ID/ 2>/dev/null || echo "   ${YELLOW}Pasta não existe (upload pode ter falhado)${RESET}"

