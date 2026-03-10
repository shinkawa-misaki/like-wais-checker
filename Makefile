.PHONY: up down restart build setup migrate seed test test-js phpstan phpcs frontend logs shell

# ─── Docker 操作 ─────────────────────────────────────────────────

## コンテナを起動する
up:
	docker compose up -d

## コンテナを停止・削除する
down:
	docker compose down

## コンテナを再起動する
restart:
	docker compose down && docker compose up -d

## イメージをビルドしてから起動する
build:
	docker compose up -d --build

## フロントエンドをビルドする（node コンテナ）
frontend:
	docker compose run --rm node

## アプリコンテナのシェルに入る
shell:
	docker compose exec app sh

## ログを表示する
logs:
	docker compose logs -f

# ─── Laravel セットアップ ─────────────────────────────────────────

## 初回セットアップ（イメージビルド → .env 作成 → composer install → key:generate → migrate → seed → フロント）
setup:
	@test -f .env || (cp .env.example .env && echo ".env を作成しました")
	$(MAKE) build
	docker compose exec app composer install --no-interaction --prefer-dist --optimize-autoloader
	docker compose exec app php artisan key:generate
	$(MAKE) migrate
	$(MAKE) seed
	$(MAKE) frontend
	@echo "\n✅ セットアップ完了！ http://localhost:9000 にアクセスしてください"

## マイグレーションを実行する
migrate:
	docker compose exec app php artisan migrate --force

## マイグレーションをリセットして再実行する
migrate-fresh:
	docker compose exec app php artisan migrate:fresh --force

## シーダーを実行する
seed:
	docker compose exec app php artisan db:seed --force

## マイグレーション + シーダーをまとめて実行
migrate-seed:
	$(MAKE) migrate-fresh
	$(MAKE) seed

# ─── 品質チェック ─────────────────────────────────────────────────

## PHPUnit テストを実行する
test:
	docker compose exec app php artisan test

## Vitest（フロントエンド）テストを実行する
test-js:
	npm test

## Vitest カバレッジレポートを生成する
test-js-coverage:
	npm run test:coverage

## PHPStan (level 8) を実行する
phpstan:
	docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M

## PHP_CodeSniffer を実行する
phpcs:
	docker compose exec app ./vendor/bin/phpcs

## phpcbf で自動修正する
phpcbf:
	docker compose exec app ./vendor/bin/phpcbf

## すべての品質チェックをまとめて実行する
lint:
	$(MAKE) phpcs
	$(MAKE) phpstan
