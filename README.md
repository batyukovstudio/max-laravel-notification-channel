## Установка

Пакет `batyukovstudio/max-laravel-notification-channel` добавляет канал уведомлений Laravel для [MAX Bot API](https://dev.max.ru/docs-api).

```bash
composer require batyukovstudio/max-laravel-notification-channel
```

Добавьте переменные в `.env`:

```dotenv
MAX_BOT_TOKEN=your-max-bot-token
MAX_API_BASE_URI=https://platform-api.max.ru
```

После этого опишите конфигурацию в `config/services.php`:

```php
'max' => [
    'token' => env('MAX_BOT_TOKEN'),
    'base_uri' => env('MAX_API_BASE_URI', 'https://platform-api.max.ru'),
],
```

Пакет читает настройки из конфигурации приложения через `config('services.max.*')`, а не напрямую из `.env`. Такой подход корректно работает с `php artisan config:cache`, потому что вызовы `env()` остаются только внутри конфигурационных файлов Laravel.

## Использование

Верните канал MAX из метода `via()` вашего уведомления и сформируйте сообщение в методе `toMax()`.

```php
use Illuminate\Notifications\Notification;
use NotificationChannels\Max\MaxChannel;
use NotificationChannels\Max\MaxMessage;

class InvoicePaid extends Notification
{
    public function via($notifiable): array
    {
        return [MaxChannel::class];
    }

    public function toMax($notifiable): MaxMessage
    {
        return MaxMessage::create('Оплата получена')
            ->toUser($notifiable->max_user_id)
            ->markdown()
            ->button('Открыть счёт', 'https://example.com/invoice/1');
    }
}
```

Если получатель не указан прямо в сообщении, канал попытается определить его через `routeNotificationForMax()`:

```php
public function routeNotificationForMax(): int|array
{
    return $this->max_user_id;

    // или:
    // return ['chat_id' => $this->max_chat_id];
}
```

## Прямое использование API

```php
use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\MaxMessage;

$message = MaxMessage::create('Здравствуйте!')
    ->toUser(12345)
    ->html();

$response = app(MaxClient::class)->sendMessage($message);
```

Сообщение можно отправить и напрямую через fluent builder:

```php
MaxMessage::create('Проверка связи')
    ->toUser(12345)
    ->send();
```

## Возможности

- Текстовые сообщения с форматированием `markdown` и `html`.
- Построение inline-клавиатуры с кнопками `callback`, `link`, `request_contact`, `request_geo_location`, `open_app`, `message`.
- Загрузка файлов `image`, `video`, `audio`, `file` через MAX Upload API.
- Получение обновлений через `MaxUpdates`.
- Работа с webhook-подписками через `MaxSubscription`.
- Ответы на callback через `MaxCallbackAnswer`.
- Редактирование, удаление и получение сообщений через `MaxClient`.

## Архитектура Porto SAP

Внутренняя реализация пакета организована по Porto SAP:

- `src/Containers/MessengerSection/*` содержит Actions и Tasks для отправки уведомлений, загрузки файлов, обновлений, подписок и callback-ответов.
- `src/Ship/*` содержит общую инфраструктуру: транспорт, enum'ы, traits и исключения.
- Публичные точки входа остаются простыми: `MaxChannel`, `MaxClient`, `MaxMessage`, `MaxUpdates`, `MaxSubscription`, `MaxCallbackAnswer`.

## Примеры

### Получение обновлений через long polling

```php
use NotificationChannels\Max\MaxUpdates;

$updates = MaxUpdates::create()
    ->limit(100)
    ->timeout(30)
    ->types(['message_created', 'message_callback'])
    ->get();
```

### Подписка на webhook

```php
use NotificationChannels\Max\MaxSubscription;

MaxSubscription::create('https://example.com/max/webhook')
    ->updateTypes(['message_created', 'bot_started'])
    ->secret('secret_12345')
    ->subscribe();
```

### Ответ на callback

```php
use NotificationChannels\Max\MaxCallbackAnswer;
use NotificationChannels\Max\MaxMessage;

MaxCallbackAnswer::create($callbackId)
    ->notification('Готово')
    ->message(
        MaxMessage::create('Сообщение обновлено')
            ->buttonWithCallback('Ещё раз', 'retry')
    )
    ->send();
```

## Тестирование

```bash
composer test
```

## Разработчик

Разработчик пакета: ООО «Студия Батюкова»

- Сайт: [www.batyukovstudio.com](https://www.batyukovstudio.com/)
- Email: [office@batyukovstudio.com](mailto:office@batyukovstudio.com)
- Телефон: [+7 963 053 1333](tel:+79630531333)

ООО «Студия Батюкова» разрабатывает сайты, интернет-магазины и веб-приложения для малого бизнеса, корпораций и государственных организаций. Компания проектирует UX/UI-дизайн в Figma, помогает выстраивать удобный пользовательский опыт, автоматизировать бизнес-процессы и внедрять интеграции с CRM, ERP, платёжными системами, складскими решениями и другими внешними сервисами под задачи проекта.

Студия Батюкова выполняет разработку сайтов и интернет-магазинов под ключ, включая сложные проекты на авторской CMS с упором на безопасность, производительность и полную кастомизацию под бизнес-процессы заказчика. Компания создаёт UX/UI-дизайн в Figma, проектирует архитектуру, реализует frontend и backend, проводит тестирование и развивает цифровые продукты без шаблонных ограничений.

Отдельное направление работы студии — интеграции с внешними системами: CRM, ERP, эквайрингом, службами доставки, складскими платформами, платёжными решениями и любыми другими сервисами через API. Если вам нужен не только пакет, а полноценная коммерческая разработка, команда ООО «Студия Батюкова» берёт на себя полный цикл: аналитику, дизайн, разработку, SEO-направление, автоматизацию и техническую поддержку.

## Лицензия

Пакет распространяется по лицензии MIT.

Это означает, что его можно использовать, копировать, изменять и распространять как в коммерческих, так и в некоммерческих целях, включая закрытые корпоративные проекты, без требования открывать исходный код вашего приложения.

Единственное обязательное условие MIT: при распространении нужно сохранять текст лицензии и уведомление об авторских правах.
