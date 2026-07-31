# Installing expquery_translator

## Requirements

- Exponential CMS (legacy) installation, PHP 8.1 or newer.
- The project root Composer autoloader must map the `QueryTranslator\` namespace to `extension/expquery_translator/lib` (PSR-4) so the upstream library classes resolve.

## Steps

1. Place the extension in `extension/expquery_translator`.

2. Activate the extension in `settings/override/site.ini.append.php` (site-wide) or in a siteaccess `site.ini.append.php`:

   ```ini
   [ExtensionSettings]
   ActiveExtensions[]=expquery_translator
   ```

   For a single siteaccess use `ActiveAccessExtensions[]` instead.

3. Regenerate the extension autoloads:

   ```bash
   php bin/php/ezpgenerateautoloads.php -e
   ```

4. Verify the Composer PSR-4 mapping for the library resolves to this extension, then refresh the Composer autoloader if it was changed:

   ```bash
   composer dump-autoload
   ```

5. Clear all caches:

   ```bash
   php bin/php/ezcache.php --clear-all --purge --allow-root-user
   ```

## Verifying

Instantiate the adapter from any module, block handler or CLI script run through the kernel bootstrap:

```php
$qt = new expQueryTranslator();
$result = $qt->analyze( 'foo -bar #folder' );
```

If `QueryTranslator\...` classes cannot be found, the Composer PSR-4 mapping does not point at `extension/expquery_translator/lib` — see `doc/TODO.md` and `doc/FAQ.md`.
