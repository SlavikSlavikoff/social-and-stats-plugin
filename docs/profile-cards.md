# Настройка карточек профиля

Плагин Social Profile теперь внедряет собственный блок статистики в стандартное представление `/profile`, добавляя карточки в существующий массив `$cards`, который Azuriom рендерит в нижней части `profile.index`. Компоновщик представления (`SocialProfileServiceProvider::registerProfileViewExtensions`) передаёт данные через `$socialProfileStats` и публикует две карточки:

- `socialprofile::partials.profile.cards` — сводка из пяти показателей.
- `socialprofile::partials.profile.violations` — опциональная карточка с нарушениями (появляется только при наличии записей).

## Как переопределить в теме

1. **Скопируйте частичные шаблоны.** Поместите нужный partial в тему, например `resources/themes/<theme>/views/vendor/socialprofile/partials/profile/cards.blade.php`. После этого можно править сетку, иконки и подписи. Плагин подключает их через пространство имён `socialprofile::`, поэтому vendor-override работает из коробки.

2. **Управляйте массивом `$cards`.** В сервис-провайдере темы повесьте свой компоновщик:
   ```php
   View::composer('profile.index', function ($view) {
       $cards = $view->getData()['cards'] ?? [];
       // Удалите или переставьте карточки Social Profile.
       $view->with('cards', $cards);
   });
   ```
   Так можно выводить метрики в другом месте, объединять с блоками темы или вовсе скрывать их.

3. **Переопределите CSS.** Подключите собственный стиль после `plugins/socialprofile/assets/css/style.css` или перенесите классы (`.socialprofile-card`, `.stats-card`, `.stat-row`) в SCSS темы и оформите заново.

4. **Используйте сырые данные.** В `$socialProfileStats` приходят модели `score`, `activity`, `coins`, `stats`, `trust`, а также коллекция `violations`. Можно построить любые виджеты с этими данными, выводя дополнительные поля — например, убийства/смерти или объём холда монет.

Пример из стандартного partial:

```php
$statRows = [
    ['label' => __('...'), 'value' => number_format($score->score)],
    ...
];
```

Добавляйте или удаляйте показатели по вкусу.

Используя эти хуки и partials, вы полностью контролируете внешний вид карточек Social Profile на `/profile`, не трогая базовые шаблоны. Можно перестроить Blade-разметку, подключить утилиты Tailwind/Bootstrap или заменить таблицу на другой компонент — главное, управлять массивом `$cards` или переопределять соответствующие partials, и плагин автоматически подхватит изменения темы.
