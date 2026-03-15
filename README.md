# 🧠 認知の手すりチェック Lite

**4つの認知指数の傾向を測るセルフチェックツール**です。自己理解や日常の戦略設計に活用できます。

- **VCI（言語理解）**：抽象化・言語化・説明力
- **PRI（知覚推理）**：規則発見・構造把握
- **WMI（ワーキングメモリー）**：情報保持・同時処理
- **PSI（処理速度）**：素早い情報処理・切替の速さ

> **免責事項**：このツールは標準化された心理検査ではありません。年齢別ノームがないためIQは算出できません。結果は自己理解・戦略設計の参考指標として使用してください。

---

## 技術スタック

| 区分 | 技術 |
|------|------|
| バックエンド | PHP 8.4 / Laravel 12 / DDD アーキテクチャ |
| フロントエンド | Vue 3 / Pinia / Vue Router / Tailwind CSS v4 |
| ビルドツール | Vite 7 |
| データベース | MySQL 8.4 |
| インフラ | Docker / Nginx / PHP-FPM |
| テスト（PHP） | PHPUnit / PHPStan / PHP_CodeSniffer |
| テスト（JS） | Vitest / Vue Test Utils |

---

## 動作確認済み環境

- macOS 13+（Ventura / Sonoma / Sequoia）
- Windows 11（WSL2 + Ubuntu 22.04 推奨）
- Docker Desktop 4.x 以上

---

## 必要なソフトウェア

### Mac

| ソフトウェア | インストール方法 |
|-------------|----------------|
| Docker Desktop | https://www.docker.com/products/docker-desktop/ |
| Git | `xcode-select --install` または https://git-scm.com |
| Node.js 22+ | https://nodejs.org または `brew install node` |
| Make | Xcode Command Line Tools に同梱（追加インストール不要） |

### Windows

**WSL2（推奨）** または **PowerShell / コマンドプロンプト** の2通りが使えます。

#### WSL2 を使う場合（推奨）

| ソフトウェア | インストール方法 |
|-------------|----------------|
| WSL2 + Ubuntu | PowerShell で `wsl --install` を実行 |
| Docker Desktop | https://www.docker.com/products/docker-desktop/ （WSL2 統合を有効にする） |
| Git | WSL2 内で `sudo apt install git` |
| Node.js 22+ | WSL2 内で `sudo apt install nodejs npm` または nvm を使用 |
| Make | WSL2 内で `sudo apt install make` |

> WSL2 での操作は以下「セットアップ手順（Mac / WSL2）」に従ってください。

#### PowerShell / コマンドプロンプトを使う場合

| ソフトウェア | インストール方法 |
|-------------|----------------|
| Docker Desktop | https://www.docker.com/products/docker-desktop/ |
| Git | https://git-scm.com/download/win |
| Node.js 22+ | https://nodejs.org |
| Make（必要な場合） | https://gnuwin32.sourceforge.net/packages/make.htm または `choco install make` |

> Make が使えない場合は「Makeを使わない場合のコマンド」セクションを参照してください。

---

## セットアップ手順（Mac / WSL2）

### 1. リポジトリをクローン

```bash
git clone <リポジトリURL>
cd like-wais-checker
```

### 2. 初回セットアップ（一発コマンド）

```bash
make setup
```

内部で以下が自動実行されます：
1. `.env.example` → `.env` のコピー
2. Docker イメージのビルド
3. `composer install`
4. `php artisan key:generate`
5. マイグレーション（`php artisan migrate`）
6. シーディング（`php artisan db:seed`）
7. フロントエンドビルド（`npm run build`）

### 3. アプリにアクセス

```
http://localhost:8080
```

---

## セットアップ手順（Windows PowerShell / コマンドプロンプト）

### 1. リポジトリをクローン

```powershell
git clone <リポジトリURL>
cd like-wais-checker
```

### 2. .env ファイルを作成

```powershell
copy .env.example .env
```

### 3. Docker イメージをビルド・起動

```powershell
docker compose up -d --build
```

### 4. 依存関係のインストールと初期化

```powershell
docker compose exec app composer install --no-interaction --prefer-dist --optimize-autoloader
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

### 5. フロントエンドのビルド

```powershell
npm install
npm run build
```

### 6. アプリにアクセス

```
http://localhost:8080
```

---

## Makefile コマンド一覧

> ※ Mac / WSL2 のみ使用可能。Windows PowerShell では「Docker コマンド直接実行」セクションを参照。

```bash
# コンテナ操作
make up              # コンテナを起動
make down            # コンテナを停止・削除
make restart         # コンテナを再起動
make build           # イメージを再ビルドして起動
make logs            # ログをリアルタイム表示
make shell           # app コンテナのシェルに入る

# セットアップ・DB操作
make setup           # 初回セットアップ（全工程を自動実行）
make migrate         # マイグレーション実行
make migrate-fresh   # テーブルを全削除して再マイグレーション
make seed            # シーダー実行
make migrate-seed    # migrate:fresh + seed をまとめて実行
make frontend        # フロントエンドをビルド

# テスト・品質チェック
make test            # PHPUnit テスト実行
make test-js         # Vitest（フロントエンド）テスト実行
make test-js-coverage  # Vitest カバレッジレポート生成
make phpstan         # PHPStan（静的解析 level 8）
make phpcs           # PHP_CodeSniffer（コーディング規約チェック）
make phpcbf          # PHP_CodeSniffer 自動修正
make lint            # phpcs + phpstan をまとめて実行
```

---

## Makeを使わない場合のコマンド（Windows PowerShell / コマンドプロンプト）

```powershell
# コンテナ起動
docker compose up -d

# コンテナ停止
docker compose down

# マイグレーション
docker compose exec app php artisan migrate --force

# シーディング
docker compose exec app php artisan db:seed --force

# PHPUnit テスト
docker compose exec app php artisan test

# app コンテナのシェルに入る
docker compose exec app sh

# ログ確認
docker compose logs -f

# フロントエンド ビルド
npm run build

# フロントエンド テスト（Vitest）
npm test

# フロントエンド カバレッジ
npm run test:coverage
```

---

## 開発フロー

### フロントエンド（Vite 開発サーバー）

```bash
# Mac / WSL2
npm run dev

# Windows PowerShell
npm run dev
```

その後 `http://localhost:5173` にアクセスするとホットリロードが有効になります。

### データをリセットして再シードする

```bash
# Mac / WSL2
make migrate-seed

# Windows PowerShell
docker compose exec app php artisan migrate:fresh --force
docker compose exec app php artisan db:seed --force
```

---

## テスト

### PHP（バックエンド）

```bash
# Mac / WSL2
make test

# Windows PowerShell
docker compose exec app php artisan test
```

68テスト・107アサーションをカバーしています。

### JavaScript（フロントエンド）

```bash
# Mac / WSL2 / Windows PowerShell（共通）
npm test            # 全テスト実行
npm run test:watch  # 監視モード（開発中）
npm run test:coverage  # カバレッジレポート生成
```

111テスト（ストア・API・コンポーネント・ビュー）をカバーしています。

---

## ディレクトリ構成

```
like-wais-checker/
├── app/
│   ├── Application/          # ユースケース・DTO
│   ├── Domain/               # エンティティ・値オブジェクト・リポジトリIF
│   ├── Infrastructure/       # Eloquent モデル・リポジトリ実装
│   └── Interfaces/Http/      # コントローラー・リクエスト
├── database/
│   ├── migrations/           # テーブル定義
│   └── seeders/              # 問題データ（144問）
├── docker/
│   ├── app/                  # PHP-FPM 設定
│   ├── nginx/                # Nginx 設定
│   └── mysql/                # MySQL 設定
├── resources/
│   ├── css/                  # Tailwind CSS
│   └── js/
│       ├── views/            # Vue ページコンポーネント
│       ├── components/       # 問題タイプ別コンポーネント
│       ├── stores/           # Pinia ストア
│       ├── api/              # Axios API クライアント
│       ├── router/           # Vue Router
│       └── tests/            # Vitest テスト
├── routes/
│   ├── api.php               # API ルート
│   └── web.php               # SPA エントリポイント
├── docker-compose.yml
├── Makefile
└── vite.config.js
```

---

## 検査の流れ

```
トップページ
  ↓ 「検査を始める」
免責事項・確認チェックリスト
  ↓ 全チェック → 「同意して開始する」
サブテスト A（言語整理）
  ↓ 完了
サブテスト B（構造理解）
  ↓ 完了
サブテスト C（保持操作）
  ↓ 完了
サブテスト D（速度耐性）
  ↓ 完了
結果レポート
  └── 4指数スコア / 強み / 負荷ポイント / 戦略 / 次の一手
```

> セッションは `sessionStorage` に保存されるため、ページリロードや一時離席後も途中から再開できます。

---

## よくある問題

### `Cannot connect to the Docker daemon`

Docker Desktop が起動していません。アプリケーションから Docker Desktop を起動してから再試行してください。

### `make: command not found`（Windows）

WSL2 環境では `sudo apt install make` でインストールできます。
PowerShell の場合は「Makeを使わない場合のコマンド」セクションのコマンドを直接実行してください。

### ポート ９000 が使用中

`.env` の `APP_URL` と `docker-compose.yml` のポートを変更してください。

```yaml
# docker-compose.yml の nginx サービス
ports:
  - "9090:80"  # 8080 → 9090 に変更
```

### マイグレーションでエラーが出る

DB コンテナが起動しているか確認してください：

```bash
docker compose ps
```

DB コンテナ（`wais_mysql`）が `healthy` になるまで待ってから再実行してください。

---

## ライセンス

MIT License
