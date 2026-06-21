# Aiform

Backend-ориентированное приложение на Laravel + React для приёма контактных обращений с AI-анализом.

## Quick test

Live demo (frontend + API): **[api.urgenmagger.ru](http://api.urgenmagger.ru)**

```bash
# Health check
curl http://api.urgenmagger.ru/api/health

# Send contact form (see AI analysis in response)
curl -X POST http://api.urgenmagger.ru/api/contact \
  -H "Content-Type: application/json" \
  -d '{"name":"Ivan","phone":"+79991234567","email":"ivan@example.com","comment":"Need an online store on Laravel"}'

# Rate limit — first 5 pass, 6th returns 429
for i in $(seq 1 6); do
  curl -s -o /dev/null -w "Request $i: %{http_code}\n" \
    -X POST http://api.urgenmagger.ru/api/contact \
    -H "Content-Type: application/json" \
    -d "{\"name\":\"R$i\",\"phone\":\"+7\",\"email\":\"r$i@t.com\",\"comment\":\"test\"}"
done

# OpenAPI spec
curl http://api.urgenmagger.ru/docs/openapi.yaml   # Paste into https://editor.swagger.io
```

### What to check

| Component | How |
|---|---|
| Form + AI | Open [api.urgenmagger.ru](http://api.urgenmagger.ru), fill and submit |
| Validation | Empty fields, invalid email → 422 |
| Rate limiting | 5 requests → 429 on 6th (env: `CONTACT_RATE_LIMIT=5`) |
| AI graceful fallback | `AI_ENABLED=false` in `.env` → `ai_available: false` |
| OpenAPI docs | [openapi.yaml](http://api.urgenmagger.ru/docs/openapi.yaml) |
| Tests | `docker compose exec backend php vendor/bin/phpunit` (13 tests) |
| Request logs | `docker compose exec backend cat storage/logs/laravel.log` |
| Code & architecture | `backend/app/` — Controllers → Services → Models |

## Стек

- **Backend:** Laravel 11, PHP 8.3
- **Frontend:** React 18, TypeScript, Vite
- **Database:** PostgreSQL 16
- **Config:** .env
- **Контейнеризация:** Docker Compose

## Структура проекта

```
aiform/
├── docker-compose.yml          # Запуск всех сервисов
├── .env.example                # Пример env-переменных
├── README.md
├── docs/
│   └── openapi.yaml            # OpenAPI-спецификация
├── backend/                    # Laravel
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/    # ContactController, HealthController, MetricsController
│   │   │   ├── Middleware/     # ApiRequestLogger, ContactRateLimitMiddleware
│   │   │   └── Requests/      # ContactFormRequest (валидация)
│   │   ├── Models/            # ContactRequest (Eloquent)
│   │   └── Services/          # ContactService, ContactAiAnalysisService, ContactMailService
│   ├── database/migrations/   # Миграции
│   ├── routes/api.php         # API-маршруты
│   └── Dockerfile
└── frontend/                  # React + Vite
    └── src/
        ├── components/        # ContactForm, AiAnalysisCard
        ├── api/               # contactApi
        ├── schemas/           # Zod-схема валидации
        └── types/             # ContactPayload, ContactResponse, AiAnalysis
```

## Команды запуска

### Через Docker (рекомендуется)

```bash
cp .env.example .env          # скопировать и при необходимости поправить
docker compose up -d          # запуск всех сервисов
docker compose exec backend php artisan migrate   # прогнать миграции
```

- **Backend:** http://localhost:8080
- **Frontend:** http://localhost:5173

### Локально (без Docker)

Требуется: PHP 8.3+, Composer, Node.js 20+, PostgreSQL.

```bash
# Backend
cd backend
cp .env.example .env
composer install
php artisan migrate
php artisan serve --port=8080

# Frontend (другой терминал)
cd frontend
npm install
npm run dev
```

## Env-переменные

| Переменная | Назначение | По умолчанию |
|---|---|---|
| `APP_PORT` | Порт Laravel | 8080 |
| `DB_HOST` | Хост PostgreSQL | postgres |
| `DB_PORT` | Порт PostgreSQL | 5432 |
| `DB_DATABASE` | Имя БД | aiform |
| `DB_USERNAME` | Пользователь БД | aiform |
| `DB_PASSWORD` | Пароль БД | secret |
| `FRONTEND_PORT` | Порт Vite dev-сервера | 5173 |
| `MAIL_MAILER` | Драйвер отправки (smtp/log) | log |
| `MAIL_HOST` | SMTP-хост | — |
| `MAIL_PORT` | SMTP-порт | 587 |
| `MAIL_USERNAME` | SMTP-логин | — |
| `MAIL_PASSWORD` | SMTP-пароль | — |
| `MAIL_ENCRYPTION` | SMTP-шифрование | tls |
| `MAIL_FROM_ADDRESS` | Обратный адрес | noreply@aiform.local |
| `CONTACT_OWNER_EMAIL` | Email владельца для уведомлений | — |
| `CONTACT_RATE_LIMIT` | Макс. запросов к /api/contact в окне | 5 |
| `CONTACT_RATE_WINDOW_SECONDS` | Окно rate limiting в секундах | 60 |
| `CACHE_DRIVER` | Драйвер кеша (file/database/array) | file |
| `AI_ENABLED` | Включить AI-анализ (true/false) | false |
| `AI_PROVIDER` | AI-провайдер (deepseek) | deepseek |
| `AI_API_KEY` | Ключ AI API | — |
| `AI_BASE_URL` | Базовый URL AI API | https://api.deepseek.com |
| `AI_MODEL` | Модель AI | deepseek-chat |
| `AI_TIMEOUT_SECONDS` | Таймаут запроса к AI | 10 |

## Endpoints

| Метод | Путь | Описание |
|---|---|---|
| GET | `/api/health` | Проверка здоровья сервиса |
| GET | `/api/metrics` | Метрики (аптайм, память, кол-во обращений) |
| POST | `/api/contact` | Приём контактного обращения |

### POST /api/contact

```json
{
  "name": "Иван Петров",
  "phone": "+7 (999) 123-45-67",
  "email": "ivan@example.com",
  "comment": "Нужен сайт для бизнеса"
}
```

Ответ 201:
```json
{
  "success": true,
  "message": "Contact request accepted",
  "id": 1,
  "ai_analysis": {
    "category": "other",
    "sentiment": "neutral",
    "priority": "normal",
    "summary": "AI analysis fallback",
    "ai_available": false
  },
  "mail_sent": true
}
```

### Rate limiting

`POST /api/contact` защищён rate limiter (middleware `ContactRateLimitMiddleware`).
Лимит настраивается через `CONTACT_RATE_LIMIT` и `CONTACT_RATE_WINDOW_SECONDS`.
Ключ — `contact-form:{ip}`, счётчик хранится в кеше (`CACHE_DRIVER`).

При превышении возвращается 429:

```json
{
  "success": false,
  "message": "Too many contact requests. Please try again later."
}
```

By default `CONTACT_RATE_LIMIT=5` and `CONTACT_RATE_WINDOW_SECONDS=60`. The first 5 requests are accepted, the 6th returns 429.

```bash
for i in $(seq 1 6); do
  curl -s -o /dev/null -w "Request $i: %{http_code}\n" \
    -X POST http://api.urgenmagger.ru/api/contact \
    -H "Content-Type: application/json" \
    -d "{\"name\":\"Test $i\",\"phone\":\"+79991234567\",\"email\":\"test$i@example.com\",\"comment\":\"Rate limit test\"}"
done
```

## CORS

CORS is configured for API routes and controlled through environment variables.

```env
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://api.urgenmagger.ru
```

Preflight check:

```bash
curl -i -X OPTIONS http://api.urgenmagger.ru/api/contact \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, Accept"
```

Unknown origins do not receive `Access-Control-Allow-Origin`, so browser requests from them are blocked.

## What's done

- [x] Laravel API structure (Controllers, Services, Middleware, Requests, Model)
- [x] Validation (ContactFormRequest)
- [x] PostgreSQL storage (migration `contact_requests`)
- [x] AI analysis with DeepSeek API, graceful fallback
- [x] Email notifications (ContactOwnerMail, ContactUserCopyMail)
- [x] Request logging (ApiRequestLogger middleware)
- [x] Rate limiting (ContactRateLimitMiddleware)
- [x] React/Vite frontend with react-hook-form + zod
- [x] Docker Compose (Laravel + PostgreSQL + React)
- [x] OpenAPI documentation (`docs/openapi.yaml`)
- [x] PHPUnit Feature tests (13 tests)
- [x] VPS deployment with Caddy reverse proxy

## Хранение данных

| Данные | Где хранится |
|---|---|
| Обращения (contact requests) | PostgreSQL (таблица `contact_requests`, миграция) |
| Логи запросов | `storage/logs/laravel.log` (LOG_CHANNEL=single) |
| Статистика | `GET /api/metrics` — агрегируется из БД (`count(*)`) и PHP-рантайма |
| Rate limiting | Файловый кеш (`CACHE_DRIVER=file`, ключ `contact-form:{ip}`) |
| Конфигурация | `.env` + `config/*.php` |

База данных используется для демонстрации навыков работы с БД. Для rate limiting и кеша — файловая система через стандартный `Cache` facade.

## AI Integration

AI-анализ комментариев через DeepSeek API (OpenAI-совместимый протокол). Сервис: `app/Services/Ai/ContactAiAnalysisService.php`.

Настройка в `.env`:

```env
AI_ENABLED=true
AI_PROVIDER=deepseek
AI_API_KEY=sk-твой-ключ
AI_BASE_URL=https://api.deepseek.com
AI_MODEL=deepseek-chat
AI_TIMEOUT_SECONDS=10
```

Системный промпт анализирует комментарий и возвращает JSON с полями:
- `category` — `job_offer | question | collaboration | support | spam | other`
- `sentiment` — `positive | neutral | negative`
- `priority` — `low | normal | high | urgent`
- `summary` — краткое описание на русском, до 160 символов

Graceful fallback: если `AI_ENABLED=false` или `AI_API_KEY` не задан, сервис возвращает:

```json
{
  "category": "other",
  "sentiment": "neutral",
  "priority": "normal",
  "summary": "AI analysis fallback",
  "ai_available": false
}
```

Успешный AI-ответ:

```json
{
  "category": "job_offer",
  "sentiment": "positive",
  "priority": "normal",
  "summary": "Пользователь хочет обсудить разработку CRM.",
  "ai_available": true
}
```

AI output валидируется backend-ом — невалидные значения заменяются на fallback по каждому полю отдельно.

## Настройка email

В `.env` (backend) заполните:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=user@example.com
MAIL_PASSWORD=app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Aiform"

CONTACT_OWNER_EMAIL=owner@example.com
```

### Проверка через curl

```bash
curl -X POST http://localhost:8080/api/contact \
  -H 'Content-Type: application/json' \
  -d '{"name":"Тест","phone":"+79991234567","email":"test@example.com","comment":"Тестовое обращение"}'
```

Ответ:
```json
{
  "success": true,
  "message": "Contact request accepted",
  "id": 1,
  "ai_analysis": {...},
  "mail_sent": true
}
```

### Поведение при ошибке SMTP

- Обращение сохраняется в БД всегда (до отправки email)
- Ошибка отправки логируется в `storage/logs/laravel.log`
- `mail_sent: false` в ответе — email не ушёл, но contact сохранён
- HTTP-статус: 201 (успех) в любом случае

### Local / demo mode (without SMTP)

By default `MAIL_MAILER=log` is used — emails are written to `storage/logs/laravel.log` instead of being sent through SMTP. Set `MAIL_MAILER=smtp` and fill in SMTP credentials to enable real email delivery.

## OpenAPI documentation

OpenAPI 3.0.3 spec: **[openapi.yaml](http://api.urgenmagger.ru/docs/openapi.yaml)**

Paste the URL into [Swagger Editor](https://editor.swagger.io) to browse the full schema with examples.

## Deployment
API is deployed at: **[api.urgenmagger.ru](http://api.urgenmagger.ru)**

### Infrastructure

- VPS: 157.22.252.36
- Caddy reverse proxy → aiform backend (8080) + static frontend
- PostgreSQL 16
- Docker Compose (`docker-compose.prod.yml`)

### HTTPS note

Current deployment is available over HTTP. HTTPS is planned after direct DNS routing to the VPS or SSL termination on the hosting provider side is completed. The application itself is ready to run behind a reverse proxy.

## Testing

Project uses Laravel Feature tests based on PHPUnit.

Run tests:

```bash
docker compose exec backend php vendor/bin/phpunit
```

Covered scenarios:

- successful contact form submission
- request validation (required fields, invalid email, empty JSON)
- XSS-like input handling
- AI graceful fallback
- email sending with `Mail::fake()`
- rate limiting
- health endpoint
- metrics endpoint

