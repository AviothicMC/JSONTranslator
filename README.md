# JSONTranslator
A simple JSON translator

## Usage
```php
$tl = \aviothic\translator\JSONTranslator::init("path", "defaultLanguage");
$tl->load();
$t = $tl->translate($key, $language = null, $replacements = []); 
```

```php
public function translate(string $key, ?string $language = null, array $replacements = []): string;
```
