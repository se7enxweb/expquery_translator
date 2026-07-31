# Copy-and-paste examples for expquery_translator

All examples assume this extension is active and `vendor/autoload.php` is loaded by the Exponential CMS kernel.

---

## 1. Basic analyze

```php
<?php
$qt = new expQueryTranslator();
$analysis = $qt->analyze('foo -bar #folder "hello world"');

echo 'Include: ' . implode(', ', $analysis['include']) . "\n";
echo 'Exclude: ' . implode(', ', $analysis['exclude']) . "\n";
echo 'Tags:    ' . implode(', ', $analysis['tags']) . "\n";
echo 'Users:   ' . implode(', ', $analysis['users']) . "\n";
?>
```

Expected output:

```
Include: foo, hello world
Exclude: bar
Tags:    folder
Users:
```

---

## 2. Filter an array of eZ nodes by search text

```php
<?php
$qt = new expQueryTranslator();

$parentNodeId = 2;
$params = array(
    'Limit'  => 100,
    'Offset' => 0,
    'SortBy' => array('name', true),
);
$nodes = eZContentObjectTreeNode::subTreeByNodeID($params, $parentNodeId);

$searchText = 'news -old #article';
$filtered = $qt->filterNodes($nodes, $searchText);

foreach ($filtered as $node) {
    echo $node->attribute('name') . "\n";
}
?>
```

---

## 3. Filter a Exponential Layouts manual collection

```php
<?php
$collectionId = 123;
$qt = new expQueryTranslator();

$items = expLayoutsCollectionItem::fetchByCollection($collectionId);
$nodes = array();
foreach ($items as $item) {
    $nodeId = (int)$item->attribute('node_id');
    $node = eZContentObjectTreeNode::fetch($nodeId);
    if ($node instanceof eZContentObjectTreeNode) {
        $nodes[] = $node;
    }
}

$filtered = $qt->filterNodes($nodes, 'annual -draft #report');
?>
```

---

## 4. Build eZ search parameters from tags

```php
<?php
$qt = new expQueryTranslator();
$baseParams = array(
    'Limit'  => 25,
    'Offset' => 0,
    'SortBy' => array('name', true),
);

$parentNodeId = 43;
$params = $qt->buildSubtreeParams('#article', $baseParams);
$articles = eZContentObjectTreeNode::subTreeByNodeID($params, $parentNodeId);
?>
```

---

## 5. Custom Exponential Layouts block handler that uses a query filter

`extension/your_extension/classes/explayoutssearchableblockhandler.php`

```php
<?php
class expLayoutsSearchableBlockHandler extends expLayoutsAbstractContentBlockHandler
{
    public function getParameters()
    {
        $params = $this->getCommonParameters();
        $params['filter'] = array(
            'name'    => 'Search filter',
            'type'    => 'string',
            'default' => '',
        );
        $params['parent_node_id'] = array(
            'name'    => 'Parent node ID',
            'type'    => 'integer',
            'default' => 2,
        );
        return $params;
    }

    public function getValues($block)
    {
        $params = is_array($block) && isset($block['parameters']) ? $block['parameters'] : array();
        $filter = isset($params['filter']) ? $params['filter'] : '';
        $parentNodeId = isset($params['parent_node_id']) ? (int)$params['parent_node_id'] : 2;

        $qt = new expQueryTranslator();
        $treeParams = $qt->buildSubtreeParams($filter, array(
            'Limit'  => 10,
            'Offset' => 0,
            'SortBy' => array('name', true),
        ));

        $nodes = eZContentObjectTreeNode::subTreeByNodeID($treeParams, $parentNodeId);
        if ($filter !== '') {
            $nodes = $qt->filterNodes($nodes, $filter);
        }

        $result = $this->fetchItems($params, $block);
        $result['nodes'] = $nodes;
        $result['filter'] = $filter;
        return $result;
    }
}
?>
```

---

## 6. Module view: search inside a folder

`modules/your_module/search.php`

```php
<?php
$http = eZHTTPTool::instance();
$module = $Params['Module'];
$parentNodeId = isset($Params['ParentNodeID']) ? (int)$Params['ParentNodeID'] : 2;
$searchText = trim($http->variable('Search', ''));

$qt = new expQueryTranslator();
$baseParams = array(
    'Limit'  => 50,
    'Offset' => 0,
    'SortBy' => array('name', true),
);
$params = $qt->buildSubtreeParams($searchText, $baseParams);
$nodes = eZContentObjectTreeNode::subTreeByNodeID($params, $parentNodeId);
$nodes = $qt->filterNodes($nodes, $searchText);

$tpl = eZTemplate::factory();
$tpl->setVariable('nodes', $nodes);
$tpl->setVariable('search_text', $searchText);

$Result = array();
$Result['content'] = $tpl->fetch('design:your_module/search.tpl');
return $Result;
?>
```

---

## 7. CLI test without a browser

```bash
php -r "
require '/var/www/vhosts/alpha.se7enx.com/doc/alpha.se7enx.com/vendor/autoload.php';
require '/var/www/vhosts/alpha.se7enx.com/doc/alpha.se7enx.com/extension/expquery_translator/classes/expquerytranslator.php';
\$qt = new expQueryTranslator();
var_dump(\$qt->analyze('foo -bar #folder \"hello world\"'));
"
```

---

## 8. Use the upstream tokenizer directly

```php
<?php
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\TokenExtractor\Full;

$tokenizer = new Tokenizer(new Full());
$seq = $tokenizer->tokenize('foo -bar #folder "hello world"');

foreach ($seq->tokens as $token) {
    echo get_class($token) . ' => ' . $token->lexeme . "\n";
}
?>
```
