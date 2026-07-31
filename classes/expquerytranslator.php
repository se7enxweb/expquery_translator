<?php
use QueryTranslator\Languages\Galach\TokenExtractor\Full;
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\Values\Token\Phrase;
use QueryTranslator\Languages\Galach\Values\Token\Tag;
use QueryTranslator\Languages\Galach\Values\Token\User;
use QueryTranslator\Languages\Galach\Values\Token\Word;

class expQueryTranslator
{
    protected $tokenizer;

    public function __construct()
    {
        $this->tokenizer = new Tokenizer( new Full() );
    }

    public function analyze( $query )
    {
        $result = array(
            'include' => array(),
            'exclude' => array(),
            'tags' => array(),
            'users' => array(),
            'raw' => trim( $query ),
        );

        if ( trim( $query ) === '' )
            return $result;

        $sequence = $this->tokenizer->tokenize( $query );
        $excludeNext = false;

        foreach ( $sequence->tokens as $token )
        {
            if ( $token instanceof Word || $token instanceof Phrase || $token instanceof Tag || $token instanceof User )
            {
                $value = $this->getTokenValue( $token );
                if ( $excludeNext )
                {
                    $result['exclude'][] = $value;
                    $excludeNext = false;
                }
                elseif ( $token instanceof Tag )
                {
                    $result['tags'][] = $value;
                }
                elseif ( $token instanceof User )
                {
                    $result['users'][] = $value;
                }
                else
                {
                    $result['include'][] = $value;
                }
            }
            elseif ( $token->type === Tokenizer::TOKEN_PROHIBITED
                || $token->type === Tokenizer::TOKEN_LOGICAL_NOT
                || $token->type === Tokenizer::TOKEN_LOGICAL_NOT_2 )
            {
                $excludeNext = true;
            }
        }

        return $result;
    }

    public function filterNodes( $nodes, $query )
    {
        $analysis = $this->analyze( $query );
        if ( empty( $analysis['include'] )
            && empty( $analysis['exclude'] )
            && empty( $analysis['tags'] )
            && empty( $analysis['users'] ) )
            return $nodes;

        $filtered = array();
        foreach ( $nodes as $node )
        {
            if ( !$node instanceof eZContentObjectTreeNode )
                continue;

            if ( !$this->nodeMatches( $node, $analysis ) )
                continue;

            $filtered[] = $node;
        }

        return $filtered;
    }

    public function buildSubtreeParams( $query, $baseParams = array() )
    {
        $analysis = $this->analyze( $query );
        $params = $baseParams;

        if ( !empty( $analysis['tags'] ) )
        {
            $params['ClassFilterType'] = 'include';
            $params['ClassFilterArray'] = $analysis['tags'];
        }

        return $params;
    }

    protected function getTokenValue( $token )
    {
        if ( $token instanceof Phrase )
            return trim( $token->phrase );
        if ( $token instanceof Tag )
            return trim( $token->tag );
        if ( $token instanceof User )
            return trim( $token->user );
        if ( $token instanceof Word )
            return trim( $token->word );

        return trim( $token->lexeme );
    }

    protected function nodeMatches( eZContentObjectTreeNode $node, $analysis )
    {
        $object = $node->attribute( 'object' );
        $haystack = strtolower( $node->attribute( 'name' ) );
        if ( $object instanceof eZContentObject )
        {
            $haystack .= ' ' . strtolower( $object->attribute( 'class_identifier' ) );
            $haystack .= ' ' . strtolower( $object->attribute( 'class_name' ) );
        }

        foreach ( $analysis['include'] as $word )
        {
            if ( stripos( $haystack, $word ) === false )
                return false;
        }

        foreach ( $analysis['exclude'] as $word )
        {
            if ( stripos( $haystack, $word ) !== false )
                return false;
        }

        foreach ( $analysis['tags'] as $tag )
        {
            if ( !$object || $object->attribute( 'class_identifier' ) !== $tag )
                return false;
        }

        return true;
    }
}
