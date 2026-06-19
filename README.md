# Aiform

Backend-ориентированное приложение на Laravel + React для приёма контактных обращений с AI-анализом.

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
│   └── openapi.yaml            # OpenAPI-спецификация (черновик)
├── backend/                    # Laravel
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/    # ContactController, HealthController, MetricsController
│   │   │   ├── Middleware/     # ApiRequestLogger
│   │   │   └── Requests/      # ContactFormRequest (валидация)
│   │   ├── Models/            # ContactRequest (Eloquent)
│   │   └── Services/          # ContactService, AiAnalysisService, ContactMailService
│   ├── database/migrations/   # Миграции
│   ├── routes/api.php         # API-маршруты
│   └── Dockerfile
└── frontend/                  # React + Vite
    └── src/
        ├── components/        # ContactForm
        ├── api/               # contactApi
        ├── schemas/           # Zod-схема валидации
        └── types/
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
  "message": "Contact request accepted",
  "id": 1,
  "ai_analysis": {
    "category": "other",
    "sentiment": "neutral",
    "priority": "normal",
    "summary": "AI analysis stub",
    "ai_available": false
  }
}
```

## Что уже сделано

- [x] Структура Laravel (Controllers, Services, Middleware, Requests, Model)
- [x] Routes: `/api/health`, `/api/metrics`, `/api/contact`
- [x] Валидация ContactFormRequest (name, phone, email, comment)
- [x] Сохранение обращений в PostgreSQL (миграция `contact_requests`)
- [x] Заглушка AiAnalysisService (возвращает фиктивные данные)
- [x] Заглушка ContactMailService (пишет в лог вместо отправки)
- [x] Middleware ApiRequestLogger (логирует method, path, status, ip, duration)
- [x] React/Vite frontend с формой (react-hook-form + zod)
- [x] Docker Compose (Laravel + PostgreSQL + React)
- [x] OpenAPI-черновик (`docs/openapi.yaml`)

## Что осталось реализовать (следующие этапы)

- [ ] Подключение реального DeepSeek AI в `AiAnalysisService`
- [ ] Реальная email-отправка (SMTP/Resend/Mailgun) в `ContactMailService`
- [ ] Swagger UI / Scalar для документации API
- [ ] Админка (Filament/Nova) для просмотра обращений
- [ ] Авторизация (Sanctum)
- [ ] Очереди (Redis + Laravel Horizon) для AI и email
- [ ] Деплой на VPS (Docker Compose + Caddy/nginx)
- [ ] Тесты (PHPUnit)
