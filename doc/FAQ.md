# expquery_translator FAQ

## Why do I get "Class QueryTranslator\Languages\Galach\Tokenizer not found"?

The upstream library in `lib/` resolves through a Composer PSR-4 mapping in the project root autoloader. Make sure the `QueryTranslator\` prefix maps to `extension/expquery_translator/lib` and run `composer dump-autoload`. The `expQueryTranslator` adapter itself is registered in `autoloads/expquery_translator_autoload.php` and loads through the extension autoload system.

## What does `#tag` match against?

The adapter maps `#tag` to the content class identifier (`nodeMatches()` compares it with `class_identifier`), and `buildSubtreeParams()` turns tags into `ClassFilterArray` entries. It is not related to the eztags extension.

## Does the adapter support OR, grouping or full boolean logic?

No. `analyze()` walks the token sequence and treats every word/phrase as required (`include`), terms after `-`/`NOT` as `exclude`, tags and users as their own buckets. `AND`/`OR` operators are tokenized but not evaluated. For real boolean logic use `QueryTranslator\Languages\Galach\Parser` from `lib/` directly.

## Which node data is searched by filterNodes()?

Only the node name, the content class identifier and the content class name. Attribute contents are not inspected, so this is a lightweight in-memory filter, not a full-text search engine.

## Do I need Solr or an external search service?

No. The library and adapter are pure PHP. `filterNodes()` filters an already-fetched node array; `buildSubtreeParams()` only adjusts `subTreeByNodeID` fetch parameters.

## Are `@user` tokens used for filtering?

`analyze()` extracts them into `$analysis['users']`, but `filterNodes()` currently ignores that bucket. See `TODO.md`.
