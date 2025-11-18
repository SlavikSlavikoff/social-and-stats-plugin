# Profile Cards Customization

The Social Profile plugin now injects its own statistics block into the default `/profile` view by appending cards to the existing `$cards` array that Azuriom renders near the bottom of `profile.index`. The view composer (`SocialProfileServiceProvider::registerProfileViewExtensions`) shares data through `$socialProfileStats` and pushes two cards:

- `socialprofile::partials.profile.cards` – summary card with five stats.
- `socialprofile::partials.profile.violations` – optional, only when recent violations exist.

## How to override via a theme

1. **Override the partials:** Copy the partial you want to change into your theme, e.g. `resources/themes/<theme>/views/vendor/socialprofile/partials/profile/cards.blade.php`. Adjust layout, icons, etc. The plugin loads them via the `socialprofile::` namespace, so vendor overrides work.

2. **Manage the cards array:** In a theme service provider you can hook another composer:
   ```php
   View::composer('profile.index', function ($view) {
       $cards = $view->getData()['cards'] ?? [];
       // Remove Social Profile cards or reorder them.
       $view->with('cards', $cards);
   });
   ```
   This lets you insert the metrics somewhere else, merge with other IU blocks or disable them entirely.

3. **CSS overrides:** Add your own stylesheet after `plugins/socialprofile/assets/css/style.css` or copy the classes (`.socialprofile-card`, `.stats-card`, `.stat-row`) into your theme SCSS and restyle them.

4. **Use the raw data:** `$socialProfileStats` contains `score`, `activity`, `coins`, `stats`, `trust` models plus `violations` collection. You can render custom widgets with the same data, including additional fields like kills/deaths or coin hold.

Example snippet from the default partial:

```php
$statRows = [
    ['label' => __('...'), 'value' => number_format($score->score)],
    ...
];
```

Feel free to add/remove entries or display additional fields (kills/deaths, coin holds, etc.).

By using these hooks and partials you can control how the Social Profile cards appear on `/profile` without modifying core views. Customize Blade structure, drop in Tailwind/Bootstrap utilities, or replace the table with something else – as long as you manipulate the `$cards` array or override the relevant partial, the plugin will respect your theme. 
