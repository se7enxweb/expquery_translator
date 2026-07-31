Exponential Query Translator
===================

General description
-------------------

Exponential Query Translator (extension name: `expquery_translator`) is a Galach
search query parser and node filter for Exponential CMS (eZ Publish Legacy).
It is a port of the well-known `netgen/query-translator` library into the
legacy extension ecosystem and provides the following capabilities:

- Galach query parsing - Parse rich, human-friendly search strings (words,
  phrases, tags, users, exclusions, boolean operators, domain prefixes) into
  structured data your PHP code can act on.
- Node filtering - Filter arrays of `eZContentObjectTreeNode` objects with a
  single search string, matching against node name, class identifier and
  class name.
- Fetch parameter building - Translate `#tag` terms directly into
  `eZContentObjectTreeNode::subTreeByNodeID` class-filter parameters so the
  database narrows the result set before PHP post-filtering runs.
- Zero dependencies - The bundled parser library is pure PHP with no
  Symfony or Doctrine requirements; it drops into any legacy installation.
- Layouts and admin integration - Ready-made patterns for Exponential
  Layouts block handlers, manual collections, admin content lists and
  custom module views with search.

What is included:

- `lib/` - the upstream `query-translator` library (Galach language,
  `QueryTranslator\` namespace). It is pure PHP and has no Symfony/Doctrine
  dependencies; it is autoloaded through a Composer PSR-4 mapping in the
  project root.
- `classes/expquerytranslator.php` - a legacy adapter that turns Galach
  search strings into usable `eZContentObjectTreeNode` filters and
  `eZContentObjectTreeNode::subTreeByNodeID` parameters.
- `autoloads/expquery_translator_autoload.php` - class map for the adapter.

The `lib/` directory is the upstream `netgen/query-translator` library,
unmodified. Only the `expQueryTranslator` adapter in `classes/` is custom.
Tags (`#tag`) are mapped to content class identifiers because that is the
most common filtering need in this legacy stack.

Features
--------

Exponential Query Translator provides the following features in detail:

Key classes

| Class | File | Purpose |
| --- | --- | --- |
| `expQueryTranslator` | `classes/expquerytranslator.php` | Adapter: `analyze()`, `filterNodes()`, `buildSubtreeParams()` |
| `QueryTranslator\Languages\Galach\Tokenizer` | `lib/Languages/Galach/Tokenizer.php` | Upstream Galach tokenizer |
| `QueryTranslator\Languages\Galach\TokenExtractor\Full` | `lib/Languages/Galach/TokenExtractor/Full.php` | Full token extractor (words, phrases, tags, users, operators) |

Galach syntax support

The `Full` token extractor supports the complete Galach search language:

- `foo` - a word
- `"hello world"` - a phrase
- `#tag` - a tag (mapped to a content class identifier in the adapter)
- `@user` - a user
- `-foo` or `NOT foo` - excluded term
- `+foo` or just `foo` - required term
- `foo AND bar`, `foo OR bar` - operators are tokenized; the adapter
  currently treats all listed terms as required by default
- `title:foo` - domain-prefixed term; the adapter extracts the word and
  matches it against node name, class identifier and class name

Adapter behaviour

- `analyze()` breaks a query string into structured `include` / `exclude` /
  `tags` / `users` / `raw` buckets. An empty or whitespace-only query
  returns the same structure with empty buckets, so callers do not need a
  separate guard.
- `filterNodes()` matches nodes on name, class identifier and class name.
  `include` terms are AND-ed, `exclude` terms drop a node on match, and
  `#tags` must equal the node's class identifier. Entries that are not
  `eZContentObjectTreeNode` instances are skipped. If the query yields no
  terms at all, the input array is returned unchanged.
- `buildSubtreeParams()` turns `#tags` into `ClassFilterType` /
  `ClassFilterArray` entries on top of your base fetch parameters. Words and
  phrases do not change the fetch parameters - apply them afterwards with
  `filterNodes()`.

Extensibility

- The adapter exposes protected hook methods (`getTokenValue()`,
  `nodeMatches()`) designed for subclassing.
- The full upstream library (tokenizer, token extractors, the `Parser`
  syntax tree builder) remains available for advanced boolean logic and
  grouping.

Version
-------

- The current version of Exponential Query Translator is 1.0.0
- Last Major update: July 30, 2026

Copyright
---------

- Exponential Query Translator is copyright 1998 - 2026 7x
- See: [LICENSE.md](LICENSE.md) for more information on the terms of the copyright and license

License
-------

Exponential Query Translator is licensed under the GNU General Public License.

The complete license agreement is included in the LICENSE.md file.

Exponential Query Translator is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License or at your
option a later version.

Exponential Query Translator is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

The GNU GPL gives you the right to use, modify and redistribute
Exponential Query Translator under certain conditions. The GNU GPL license
is distributed with the software, see the file LICENSE.md.

It is also available at https://www.gnu.org/licenses/gpl.txt

You should have received a copy of the GNU General Public License
along with Exponential Query Translator in LICENSE.md.  If not, see https://www.gnu.org/licenses/.

Using Exponential Query Translator under the terms of the GNU GPL is free (as in freedom).

For more information or questions please contact info@se7enx.com

Requirements
------------

The following requirements exists for using Exponential Query Translator extension:

Exponential CMS / eZ Publish Legacy version
- Make sure you use Exponential 6 (eZ Publish Legacy) or higher.

PHP version
- Make sure you have PHP 8.1 or higher.

Composer autoloader mapping
- The project root Composer autoloader must map the `QueryTranslator\`
  namespace to `extension/expquery_translator/lib` (PSR-4) so the upstream
  library classes resolve.

Installation
------------

Installation is the standard legacy extension procedure: place the extension
in `extension/expquery_translator`, activate it via `[ExtensionSettings]`
`ActiveExtensions[]` (or `ActiveAccessExtensions[]` for a single siteaccess),
regenerate the extension autoloads, verify the Composer PSR-4 mapping and
clear all caches.

See the complete step-by-step instructions in [INSTALL.md](INSTALL.md).

Usage
-----

The adapter is a single class you can instantiate anywhere the kernel is
bootstrapped - modules, block handlers, cronjobs and CLI scripts:

```php
$qt = new expQueryTranslator();

$analysis = $qt->analyze( 'foo -bar #folder "new york"' );
$filtered = $qt->filterNodes( $nodes, 'news -old #article' );
$params   = $qt->buildSubtreeParams( '#article', array( 'Limit' => 25 ) );
```

`analyze()` returns structured buckets:

```php
// $analysis['include'] => array( 'foo', 'new york' )
// $analysis['exclude'] => array( 'bar' )
// $analysis['tags']    => array( 'folder' )
// $analysis['users']   => array()
// $analysis['raw']     => 'foo -bar #folder "new york"'
```

The recommended pattern is fetch-then-filter: narrow the database fetch with
`buildSubtreeParams()` (tags become class filters), then post-filter the
words and phrases with `filterNodes()`:

```php
$params = $qt->buildSubtreeParams( $query, array(
    'Limit'  => 10,
    'Offset' => 0,
    'SortBy' => array( 'name', true ),
) );
$nodes = eZContentObjectTreeNode::subTreeByNodeID( $params, $parentNodeId );
$nodes = $qt->filterNodes( $nodes, $query );
```

For full Galach parsing beyond what the adapter extracts, use the upstream
library directly:

```php
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\TokenExtractor\Full;

$tokenizer = new Tokenizer( new Full() );
$sequence = $tokenizer->tokenize( 'foo -bar #baz "hello world"' );
```

The `QueryTranslator\Languages\Galach\Parser` class (also in `lib/`) builds a
full syntax tree when boolean logic and grouping are needed.

See [doc/USAGE.md](doc/USAGE.md) for verified, copy-ready examples - Layouts
block handlers, manual collection filtering, admin list filtering, module
views with search - and for the customization guide covering all three
layers: the settings layer (this extension ships no INI file; tunables travel
as method arguments), the template layer (no templates shipped; presentation
is owned by the caller) and the PHP layer (safe subclassing points
`getTokenValue()` and `nodeMatches()`, and swapping the token extractor).
Additional copy-and-paste snippets are collected in [EXAMPLES.md](EXAMPLES.md).

Documentation
-------------

| Document | Description |
| --- | --- |
| [INSTALL.md](INSTALL.md) | Activation, Composer autoload setup and verification |
| [EXAMPLES.md](EXAMPLES.md) | Extended copy-and-paste code snippets |
| [doc/USAGE.md](doc/USAGE.md) | Verified code examples plus the settings / template / PHP customization layers |
| [doc/FAQ.md](doc/FAQ.md) | Answers to the most common questions |
| [doc/TODO.md](doc/TODO.md) | Known gaps and planned improvements |
| [doc/SUPPORT.md](doc/SUPPORT.md) | How and where to get help |
| [LICENSE.md](LICENSE.md) | The complete GNU General Public License agreement |

Troubleshooting
---------------

Read the FAQ
- Some problems are more common than others. The most common ones are listed in the [doc/FAQ.md](doc/FAQ.md).

Use our support systems
- If you have any questions not handled by this document or the FAQ you can reach us through [se7enx.com](https://se7enx.com)
- If you find a bug or defect, please report it to the [Exponential Query Translator: Issue Tracker](https://github.com/se7enxweb/expquery_translator/issues)
