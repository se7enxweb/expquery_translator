# expquery_translator TODO

Code-observed gaps; no promises attached.

- The project root Composer autoloader currently maps `QueryTranslator\` to `extension/sevenx_query_translator/lib` (pre-rename path) in `vendor/composer/autoload_psr4.php`. The mapping needs to be repointed to `extension/expquery_translator/lib` and the Composer autoloader regenerated, or `new expQueryTranslator()` fails with a class-not-found error for the upstream tokenizer.
- `analyze()` collects `@user` tokens into `$analysis['users']`, but `filterNodes()` never filters on them.
- `AND` / `OR` operators and grouping are tokenized but not evaluated; filtering is AND-only. Wiring `QueryTranslator\Languages\Galach\Parser` into the adapter would enable real boolean queries.
- Domain-prefixed terms (`title:foo`) lose their domain; the word is matched against the generic haystack (node name, class identifier, class name) instead of the named field.
- `filterNodes()` matches only node name and class metadata; attribute-level matching is not implemented.
- `extension.xml~` backup file is still in the extension root and could be removed.
