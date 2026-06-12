.PHONY: up down bash test migrate migrate-all seed logs lint fresh

up:
	docker compose up -d

down:
	docker compose down

bash:
	docker compose exec backend bash

test:
	docker compose exec backend php artisan test

test-filter:
	docker compose exec backend php artisan test --filter=$(filter)

migrate:
	docker compose exec backend php artisan migrate

migrate-all:
	docker compose exec backend php artisan migrate:tenants

seed:
	docker compose exec backend php artisan db:seed --class=TenantDemoSeeder

fresh:
	docker compose exec backend php artisan migrate:fresh
	docker compose exec backend php artisan tenant:create "Demo" demo
	docker compose exec backend php artisan migrate:tenants
	docker compose exec backend php artisan db:seed --class=TenantDemoSeeder

logs:
	docker compose logs -f backend

logs-worker:
	docker compose logs -f worker

lint:
	docker compose exec backend ./vendor/bin/pint --test
	cd frontend && npm run lint && npm run typecheck

queue-restart:
	docker compose exec backend php artisan queue:restart

cache-clear:
	docker compose exec backend php artisan cache:clear
	docker compose exec backend php artisan config:clear
	docker compose exec backend php artisan route:clear

tinker:
	docker compose exec backend php artisan tinker

tenant-create:
	docker compose exec backend php artisan tenant:create "$(name)" $(subdomain)

tenant-list:
	docker compose exec backend php artisan tenant:list
