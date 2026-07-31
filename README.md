# expquery_translator

Galach search query parser and node filter for Exponential CMS (legacy). Ported from `netgen/query-translator`. It provides a small, dependency-free Galach query parser that can be used to build search filters for collections, block handlers and admin views.

## What is included

- `lib/` — the upstream `query-translator` library (Galach language, `QueryTranslator\` namespace). It is pure PHP and has no Symfony/Doctrine dependencies; it is autoloaded through a Composer PSR-4 mapping in the project root.
- `classes/expquerytranslator.php` — a legacy adapter that turns Galach search strings into usable `eZContentObjectTreeNode` filters and `eZContentObjectTreeNode::subTreeByNodeID` parameters.
- `autoloads/expquery_translator_autoload.php` — class map for the adapter.

## Key classes

| Class | File | Purpose |
| --- | --- | --- |
| `expQueryTranslator` | `classes/expquerytranslator.php` | Adapter: `analyze()`, `filterNodes()`, `buildSubtreeParams()` |
| `QueryTranslator\Languages\Galach\Tokenizer` | `lib/Languages/Galach/Tokenizer.php` | Upstream Galach tokenizer |
| `QueryTranslator\Languages\Galach\TokenExtractor\Full` | `lib/Languages/Galach/TokenExtractor/Full.php` | Full token extractor (words, phrases, tags, users, operators) |

## Galach syntax

The `Full` token extractor supports:

- `foo` — a word
- `"hello world"` — a phrase
- `#tag` — a tag (mapped to a content class identifier in the adapter)
- `@user` — a user
- `-foo` or `NOT foo` — excluded term
- `+foo` or just `foo` — required term
- `foo AND bar`, `foo OR bar` — operators are tokenized; the adapter currently treats all listed terms as required by default
- `title:foo` — domain-prefixed term; the adapter extracts the word and matches it against node name, class identifier and class name

## Adapter API

```php
$qt = new expQueryTranslator();

$analysis = $qt->analyze( 'foo -bar #folder "new york"' );
$filtered = $qt->filterNodes( $nodes, 'news -old #article' );
$params   = $qt->buildSubtreeParams( '#article', array( 'Limit' => 25 ) );
```

See `doc/USAGE.md` for full, verified examples and `EXAMPLES.md` for additional copy-and-paste snippets.

## Provenance

The `lib/` directory is the upstream `netgen/query-translator` library, unmodified. Only the `expQueryTranslator` adapter in `classes/` is custom. Tags (`#tag`) are mapped to content class identifiers because that is the most common filtering need in this legacy stack.

## Documentation

- `INSTALL.md` — activation and autoload setup
- `doc/USAGE.md` — verified code examples and customization guide
- `EXAMPLES.md` — extended copy-and-paste snippets
- `doc/FAQ.md` — common questions
- `doc/TODO.md` — known gaps
- `doc/SUPPORT.md` — how to get help
