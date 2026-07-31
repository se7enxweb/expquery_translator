# Using expquery_translator

All examples use real class and method names from `classes/expquerytranslator.php`.

## analyze() — break a Galach string into structured arrays

```php
$qt = new expQueryTranslator();
$analysis = $qt->analyze( 'foo -bar #folder "new york"' );

// $analysis['include'] => array( 'foo', 'new york' )
// $analysis['exclude'] => array( 'bar' )
// $analysis['tags']    => array( 'folder' )
// $analysis['users']   => array()
// $analysis['raw']     => 'foo -bar #folder "new york"'
```

An empty or whitespace-only query returns the same structure with empty buckets, so callers do not need a separate guard.

## filterNodes() — filter an array of tree nodes by a query string

Node name, class identifier and class name are used for matching. `include` terms are AND-ed, `exclude` terms drop a node on match, and `#tags` must equal the node's class identifier. Entries that are not `eZContentObjectTreeNode` instances are skipped. If the query yields no terms at all, the input array is returned unchanged.

```php
$qt = new expQueryTranslator();

$parentNodeId = 2;
$nodes = eZContentObjectTreeNode::subTreeByNodeID( array(
    'Limit'  => 100,
    'Offset' => 0,
    'SortBy' => array( 'name', true ),
), $parentNodeId );

$filtered = $qt->filterNodes( $nodes, 'news -old #article' );

foreach ( $filtered as $node )
{
    echo $node->attribute( 'name' ) . "\n";
}
```

## buildSubtreeParams() — turn tags into fetch parameters

`#tags` become `ClassFilterType` / `ClassFilterArray` entries on top of your base parameters. Words and phrases do not change the fetch parameters — apply them afterwards with `filterNodes()`.

```php
$qt = new expQueryTranslator();

$params = $qt->buildSubtreeParams( '#article', array(
    'Limit'  => 25,
    'Offset' => 0,
    'SortBy' => array( 'name', true ),
) );

$articles = eZContentObjectTreeNode::subTreeByNodeID( $params, 43 );
```

## Scenario: fetch-then-filter in a Layouts block handler

Narrow the database fetch with `buildSubtreeParams()`, then post-filter the words:

```php
public function getValues( $block )
{
    $params = is_array( $block ) && isset( $block['parameters'] ) ? $block['parameters'] : array();
    $query = isset( $params['filter'] ) ? $params['filter'] : '';
    $parentNodeId = isset( $params['parent_node_id'] ) ? (int)$params['parent_node_id'] : 2;

    $qt = new expQueryTranslator();
    $treeParams = $qt->buildSubtreeParams( $query, array(
        'Limit'  => 10,
        'Offset' => 0,
        'SortBy' => array( 'name', true ),
    ) );

    $nodes = eZContentObjectTreeNode::subTreeByNodeID( $treeParams, $parentNodeId );
    if ( $query !== '' )
    {
        $nodes = $qt->filterNodes( $nodes, $query );
    }

    return array( 'nodes' => $nodes, 'query' => $query );
}
```

## Scenario: filter a manual Layouts collection

```php
$collectionId = 123;
$qt = new expQueryTranslator();

$items = expLayoutsCollectionItem::fetchByCollection( $collectionId );
$nodes = array();
foreach ( $items as $item )
{
    $node = eZContentObjectTreeNode::fetch( (int)$item->attribute( 'node_id' ) );
    if ( $node instanceof eZContentObjectTreeNode )
        $nodes[] = $node;
}

$filtered = $qt->filterNodes( $nodes, 'annual -draft #report' );
```

## Scenario: filter an admin content list from request input

```php
$http = eZHTTPTool::instance();
$qt = new expQueryTranslator();

$nodes = eZContentObjectTreeNode::subTreeByNodeID( array( 'Limit' => 50 ), 2 );
$filtered = $qt->filterNodes( $nodes, trim( $http->variable( 'SearchText', '' ) ) );
```

## Scenario: module view with search

```php
$http = eZHTTPTool::instance();
$parentNodeId = isset( $Params['ParentNodeID'] ) ? (int)$Params['ParentNodeID'] : 2;
$searchText = trim( $http->variable( 'Search', '' ) );

$qt = new expQueryTranslator();
$params = $qt->buildSubtreeParams( $searchText, array(
    'Limit'  => 50,
    'Offset' => 0,
    'SortBy' => array( 'name', true ),
) );
$nodes = eZContentObjectTreeNode::subTreeByNodeID( $params, $parentNodeId );
$nodes = $qt->filterNodes( $nodes, $searchText );

$tpl = eZTemplate::factory();
$tpl->setVariable( 'nodes', $nodes );
$tpl->setVariable( 'search_text', $searchText );

$Result = array();
$Result['content'] = $tpl->fetch( 'design:your_module/search.tpl' );
return $Result;
```

## Scenario: use the upstream library directly

For full Galach parsing beyond what the adapter extracts:

```php
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\TokenExtractor\Full;

$tokenizer = new Tokenizer( new Full() );
$sequence = $tokenizer->tokenize( 'foo -bar #baz "hello world"' );

foreach ( $sequence->tokens as $token )
{
    echo $token->lexeme . "\n";
}
```

The `QueryTranslator\Languages\Galach\Parser` class (also in `lib/`) builds a full syntax tree when boolean logic and grouping are needed.

More scenarios are collected in the extension root `EXAMPLES.md`.

## Customization

### Settings layer

This extension ships no INI file and reads no configuration; its behaviour is controlled entirely by the arguments passed to the adapter methods. Fetch limits, offsets, sorting and parent nodes are supplied by the caller through `buildSubtreeParams()` base parameters and `subTreeByNodeID()` calls, so integrators tune those in their own extension settings, not here.

### Template layer

The extension ships no templates and no design directory, so there is nothing to override in the design cascade. Presentation of filtered results is owned by the calling template or module.

### PHP layer

`expQueryTranslator` is designed for subclassing; these are the safe extension points:

- `getTokenValue( $token )` (protected) — change how token values are normalised (for example, keep the domain of `title:foo` terms).
- `nodeMatches( eZContentObjectTreeNode $node, $analysis )` (protected) — change what a node is matched against (for example, include attribute contents or honour the `users` bucket).
- The constructor wires `new Tokenizer( new Full() )`; a subclass constructor can install a different `QueryTranslator\Languages\Galach\TokenExtractor\*` to change the recognised syntax.

```php
class myQueryTranslator extends expQueryTranslator
{
    protected function nodeMatches( eZContentObjectTreeNode $node, $analysis )
    {
        // custom matching, then fall back to the default rules
        return parent::nodeMatches( $node, $analysis );
    }
}
```

Register the subclass in your own extension's `autoloads/` class map and use it in place of `expQueryTranslator`. The upstream `lib/` code should never be modified in place — it is kept identical to `netgen/query-translator`.
