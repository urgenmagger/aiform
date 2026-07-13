# Aiform

Backend-ориентированное приложение на Laravel + React для приёма контактных обращений с AI-анализом.

## Быстрая проверка

Демо (фронтенд + API): **http://api.urgenmagger.ru**

```bash
# Проверка здоровья
curl http://api.urgenmagger.ru/api/health

# Отправка формы (AI-анализ в ответе)
curl -X POST http://api.urgenmagger.ru/api/contact \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Иван","phone":"+79991234567","email":"ivan@example.com","comment":"Нужен интернет-магазин на Laravel"}'

# Rate limit — первые 5 проходят, 6-й возвращает 429
for i in $(seq 1 6); do
  curl -s -o /dev/null -w "Request $i: %{http_code}\n" \
    -X POST http://api.urgenmagger.ru/api/contact \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{\"name\":\"R$i\",\"phone\":\"+7\",\"email\":\"r$i@t.com\",\"comment\":\"test\"}"
done

# OpenAPI-спецификация
curl http://api.urgenmagger.ru/docs/openapi.yaml   # Вставить в https://editor.swagger.io
```

### Что проверять

| Компонент | Как |
|---|---|
| Форма + AI | Открыть http://api.urgenmagger.ru, заполнить и отправить |
| Валидация | Пустые поля, невалидный email → 422 |
| Rate limiting | 5 запросов → 429 на 6-м (`CONTACT_RATE_LIMIT=5`) |
| AI graceful fallback | `AI_ENABLED=false` в `.env` → `ai_available: false` |
| OpenAPI-документация | [openapi.yaml](http://api.urgenmagger.ru/docs/openapi.yaml) |
| Тесты | `docker compose exec backend php vendor/bin/phpunit` (13 тестов) |
| Логи запросов | `docker compose exec backend cat storage/logs/laravel.log` |
| Код и архитектура | `backend/app/` — Controllers → Services → Models |

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

По умолчанию `CONTACT_RATE_LIMIT=5` и `CONTACT_RATE_WINDOW_SECONDS=60`. Первые 5 запросов принимаются, 6-й возвращает 429. Если до этого уже отправлялись запросы с того же IP, 429 может появиться раньше до истечения окна rate limit.

```bash
for i in $(seq 1 6); do
  curl -s -o /dev/null -w "Request $i: %{http_code}\n" \
    -X POST http://api.urgenmagger.ru/api/contact \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{\"name\":\"Test $i\",\"phone\":\"+79991234567\",\"email\":\"test$i@example.com\",\"comment\":\"Rate limit test\"}"
done
```

## CORS

CORS настроен для API-маршрутов и управляется через переменные окружения.

```env
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://api.urgenmagger.ru
```

Preflight-проверка:

```bash
curl -i -X OPTIONS http://api.urgenmagger.ru/api/contact \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, Accept"
```

Неизвестные origin не получают `Access-Control-Allow-Origin`, поэтому браузерные запросы от них блокируются.

## Что сделано

- [x] Структура Laravel API (Controllers, Services, Middleware, Requests, Model)
- [x] Валидация (ContactFormRequest)
- [x] Хранение в PostgreSQL (миграция `contact_requests`)
- [x] AI-анализ через DeepSeek API с graceful fallback
- [x] Email-уведомления (ContactOwnerMail, ContactUserCopyMail)
- [x] Логирование запросов (ApiRequestLogger middleware)
- [x] Rate limiting (ContactRateLimitMiddleware)
- [x] React/Vite фронтенд с react-hook-form + zod
- [x] Docker Compose (Laravel + PostgreSQL + React)
- [x] OpenAPI-документация (`docs/openapi.yaml`)
- [x] PHPUnit Feature-тесты (13 тестов)
- [x] Деплой на VPS с Caddy reverse proxy

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

AI output валидируется backend-ом. Если AI недоступен, вернул невалидный JSON или неподдерживаемые значения, сервис использует безопасные fallback-значения.

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
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
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

### Локальный / демо-режим (без SMTP)

По умолчанию `MAIL_MAILER=log` — письма пишутся в `storage/logs/laravel.log` вместо реальной отправки через SMTP. Для включения настоящей доставки укажите `MAIL_MAILER=smtp` и заполните SMTP-реквизиты.

## OpenAPI-документация

Спецификация OpenAPI 3.0.3: **[openapi.yaml](http://api.urgenmagger.ru/docs/openapi.yaml)**

Вставьте URL в [Swagger Editor](https://editor.swagger.io) для просмотра полной схемы с примерами.

## Деплой

API задеплоен на: **http://api.urgenmagger.ru**

### Инфраструктура

- VPS deployment with Docker Compose production setup
- Caddy reverse proxy → aiform backend (8080) + статический фронтенд
- PostgreSQL 16
- Docker Compose (`docker-compose.prod.yml`)

### Примечание про HTTPS

Let's Encrypt не используется на данном VPS (IP-only хостинг без реверс-DNS). Caddy обслуживает HTTP на 80 порту без SSL.

## Тестирование

Проект использует Laravel Feature-тесты на базе PHPUnit.

Запуск тестов:

```bash
docker compose exec backend php vendor/bin/phpunit
```

Покрытые сценарии:

- успешная отправка формы
- валидация запроса (обязательные поля, невалидный email, пустой JSON)
- обработка XSS-подобного ввода
- AI graceful fallback
- отправка email с `Mail::fake()`
- rate limiting
- health endpoint
- metrics endpoint

## Что сделано с помощью AI-инструментов

AI-инструменты использовались для:
- подготовки черновиков Laravel services/controllers/tests
- генерации структуры OpenAPI-спецификации
- подготовки curl-примеров и README-разделов
- проверки edge cases для валидации, rate limiting и AI fallback

Вручную проверено и доработано:
- архитектура проекта
- правила валидации
- AI output validation и fallback
- email flow
- Docker/deployment configuration
- PHPUnit Feature tests

