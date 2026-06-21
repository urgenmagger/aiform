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
| `MAIL_MAILER` | Драйвер отправки (smtp/log) | log |
| `MAIL_HOST` | SMTP-хост | — |
| `MAIL_PORT` | SMTP-порт | 587 |
| `MAIL_USERNAME` | SMTP-логин | — |
| `MAIL_PASSWORD` | SMTP-пароль | — |
| `MAIL_ENCRYPTION` | SMTP-шифрование | tls |
| `MAIL_FROM_ADDRESS` | Обратный адрес | noreply@aiform.local |
| `CONTACT_OWNER_EMAIL` | Email владельца для уведомлений | — |

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
- [x] Реальная email-отправка (ContactOwnerMail, ContactUserCopyMail)
- [x] Middleware ApiRequestLogger (логирует method, path, status, ip, duration)
- [x] React/Vite frontend с формой (react-hook-form + zod)
- [x] Docker Compose (Laravel + PostgreSQL + React)
- [x] OpenAPI-черновик (`docs/openapi.yaml`)

## Что осталось реализовать (следующие этапы)

- [ ] Подключение реального DeepSeek AI в `AiAnalysisService`
- [ ] Swagger UI / Scalar для документации API
- [ ] Админка (Filament/Nova) для просмотра обращений
- [ ] Авторизация (Sanctum)
- [ ] Очереди (Redis + Laravel Horizon) для AI и email
- [ ] Деплой на VPS (Docker Compose + Caddy/nginx)
- [ ] Тесты (PHPUnit)

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

### Локальная разработка (без SMTP)

По умолчанию `MAIL_MAILER=log` — письма пишутся в `storage/logs/laravel.log` вместо реальной отправки.
Для проверки логики без SMTP-сервера этого достаточно.

---

<details>
<summary>📋 Тестовое задание</summary>

### Тестовое задание (Backend-ориентированно)

**Срок выполнения:** до 22 июня

#### Задача

Разработать бэкенд-сервис для лендинг-презентации разработчика с полноценной API-частью и интеграцией AI-инструментов.

#### Обязательная часть

Нам важно увидеть:
- как вы пишете backend-код
- как проектируете API
- как используете AI-инструменты в backend
- как организуете проект и архитектуру
- как обрабатываете и логируете ошибки

#### Что должно быть реализовано

##### 1. Backend API

**REST API для формы обратной связи:**
- Эндпоинт: `POST /api/contact`
- Валидация входных данных (имя, телефон, email, комментарий)
- Отправка email-уведомлений:
  - письмо владельцу сайта
  - копия письма пользователю
- Обработка ошибок с соответствующими HTTP-статусами
- Rate limiting (защита от спама) — можно реализовать через файловое кеширование или переменные окружения
- Логирование всех запросов в файл

**AI-интеграция (обязательно):**

Минимум одна AI-функция на backend:
- Анализ тональности комментария
- Автоматическая генерация ответа на обращение
- Классификация типов запросов
- Или любая другая AI-функция

- Использование OpenAI API, Anthropic API или другого AI-провайдера
- Graceful fallback (если AI недоступен, сервис продолжает работать)

**Дополнительные API-эндпоинты (по желанию):**
- `GET /api/health` — проверка статуса сервиса
- `GET /api/metrics` — статистика обращений (можно хранить в файле)

##### 2. Технические требования

**Backend (на выбор):**
- Вариант PHP:
  - PHP 8.1+
  - Любой фреймворк: Laravel / Symfony / Slim / Lumen
  - Или чистый PHP с реализацией роутинга
  - Composer для управления зависимостями
- Вариант Python:
  - Python 3.9+
  - Любой фреймворк: Django / FastAPI / Flask
  - Или чистый Python с реализацией роутинга
  - pip/poetry для управления зависимостями

**Хранение данных:**
- Можно использовать файловую систему (JSON, текстовые файлы) для хранения:
  - Логов запросов
  - Статистики
  - Rate limiting данных
- База данных не обязательна, но если хотите показать навыки работы с БД — это будет плюсом

**Infrastructure (обязательно):**
- Переменные окружения (`.env`)
- Логирование в файл
- Обработка ошибок (глобальный error handler)
- CORS настроен правильно
- Swagger/OpenAPI документация (или аналогичная)

##### 3. Проектирование

- Архитектура: слоистая структура (Controllers → Services → Repositories/Handlers)

#### Что предоставить

- GitHub репозиторий
- Чистая структура проекта
- README с подробной документацией
- Примеры запросов к API (Postman коллекция или curl)
- **Деплой:**
  - Ссылка на рабочий API (Render, Railway, AnyHost, ваша локальная машина с ngrok или любой хостинг)
  - Если деплой невозможен — предоставьте инструкцию для запуска локально

#### Что написать в README

1. Как запустить проект:
   - Инструкция по установке и запуску
   - Настройка переменных окружения
   - Команды для установки зависимостей
2. Стек технологий:
   - Backend: язык, фреймворк, библиотеки
   - AI: какие инструменты использованы
3. Архитектура:
   - Структура проекта
   - Паттерны проектирования
   - Объяснение выбора технологий
4. Реализация API:
   - Описание эндпоинтов
   - Примеры запросов/ответов
   - Валидация и обработка ошибок
5. AI-интеграция:
   - Какие AI-инструменты и для чего
   - Как реализован fallback
   - Промпты, которые использовали
6. Что сделано с помощью AI:
   - Какие части кода генерировались
   - Какие промпты использовали
   - Что пришлось исправлять вручную
7. Хранение данных:
   - Как реализовано хранение логов
   - Как реализован rate limiting
   - Где хранится статистика

#### Что будем оценивать

- Качество backend-кода (основной фокус)
- Архитектура и организация кода
- Обработка ошибок
- Безопасность (валидация, санитизация)
- Простота и чистота кода
- Работа с API
  - RESTful принципы
  - Статус-коды
  - Документация
- AI-интеграция
  - Креативность использования
  - Надежность (fallback механизмы)
- Структура проекта
  - Чистота и организация
  - Конфигурация
- Фронтенд (если есть — большой плюс)
  - Качество верстки
  - Взаимодействие с API
  - UX/UI
- Самостоятельность и решения
  - Почему выбрали те или иные технологии
  - Умение аргументировать решения

#### Важно

- Backend без API и обработки ошибок — не считается выполненным
- API без AI-интеграции — тоже не считается выполненным
- Нам важно увидеть полный цикл: запрос → валидация → бизнес-логика → AI → отправка → ответ

</details>

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

