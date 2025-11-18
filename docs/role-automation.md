# Role Automation Roadmap

## Purpose

We plan to automate reactions to Azuriom role changes (e.g., auto whitelist add/remove, auto-ban) based on flexible configuration. The current codebase only installs a placeholder listener (see `SocialProfileServiceProvider::registerRoleListener()`).

## Pending TODO (from service provider)

```php
// TODO:
// - Сделать конфигурацию переходов ролей наподобие:
//   [
//     ['from' => ['X'], 'to' => ['Y'], 'action' => 'whitelist_add'],
//     ['from' => ['*'], 'to' => ['Z'], 'action' => 'auto_ban'],
//     ['from' => ['Y'], 'to' => ['X'], 'action' => 'whitelist_remove'],
//   ]
// - Поддержать перечисление ID через запятую и wildcard '*'.
// - На основе конфигурации выполнять автоматизацию (вайтлист, бан и т. п.).
```

## Implementation Plan

1. **Configuration file**  
   Define transitions in `config/socialprofile_roles.php`, allowing:
   - `from` & `to` arrays (IDs or `*`).
   - Named `action` (e.g., `whitelist_add`, `whitelist_remove`, `auto_ban`).
   - Optional metadata (e.g., target whitelist name, ban reason).

2. **Observer logic**  
   Listen to role changes on users (preferably `User::updated` and `isDirty('role_id')`), match transitions by old/new role, and dispatch jobs.

3. **Action handlers**  
   Implement service classes or jobs per action to interact with whitelists, bans, etc. Provide extension hooks so projects can register their own handler classes.

4. **Editing transitions**  
   Configuration uses simple arrays with IDs (comma-separated strings interpreted at boot). Example:

   ```php
   [
       'from' => ['1', '2'],
       'to' => ['3'],
       'action' => 'whitelist_add',
       'meta' => ['list' => 'vip'],
   ]
   ```

5. **Testing roadmap**
   - Unit tests for transition matching and parsing the config.
   - Feature tests simulating role change on a user.

This file should be updated once the roadmap is implemented. For now it documents expectations and provides guidance for future work. 
