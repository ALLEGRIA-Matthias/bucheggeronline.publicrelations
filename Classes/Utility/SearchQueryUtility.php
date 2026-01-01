<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Utility;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class SearchQueryUtility
{
    /**
     * Analysiert einen erweiterten Suchstring und wendet Constraints auf das Query an.
     * Unterstützt: Suchfelder, logische Operatoren, Wildcards, Sortierung, Paginierung.
     *
     * @param QueryInterface $query
     * @param string $search
     * @param array $fieldMap Definition erlaubter Felder für field:value-Suchen
     * @param array $defaultFields Felder, die bei nicht feldspezifischen Begriffen verwendet werden
     */
    public static function apply(
        QueryInterface $query,
        string $search,
        array $fieldMap,
        array $defaultFields = [],
        string $clientFilter = 'all'
    ): QueryInterface {
        $orderings = [];
        $limit = null;
        $offset = null;

        preg_match_all('/(\"[^\"]+\"|[\w\-]+:\"[^\"]+\"|[\w\-]+:[^\s]+|\S+)/', $search, $matches);
        $tokens = $matches[1];

        $include = [];
        $exclude = [];
        $specific = [];

        foreach ($tokens as $token) {
            $token = trim(trim($token), '"');
            $token = str_replace(['*', '?'], ['%', '_'], trim($token));

            if (stripos($token, 'order:') === 0) {
                $parts = explode(':', $token);
                if (count($parts) === 3) {
                    $orderings[$parts[1]] = strtolower($parts[2]) === 'desc'
                        ? QueryInterface::ORDER_DESCENDING
                        : QueryInterface::ORDER_ASCENDING;
                }
                continue;
            } elseif (stripos($token, 'limit:') === 0) {
                $limit = (int) substr($token, 6);
                continue;
            } elseif (stripos($token, 'offset:') === 0) {
                $offset = (int) substr($token, 7);
                continue;
            }

            if (strpos($token, ':') !== false) {
                [$fieldKey, $value] = explode(':', $token, 2);
                $value = trim($value, '"');
                if (isset($fieldMap[$fieldKey])) {
                    $specific[] = self::orConstraints($query, $value, $fieldMap[$fieldKey]);
                }
                continue;
            }

            if (str_starts_with($token, '-')) {
                $term = substr($token, 1);
                $exclude[] = self::orConstraints($query, $term, $fieldMap['*'] ?? $defaultFields);
                continue;
            }

            $include[] = self::orConstraints($query, $token, $fieldMap['*'] ?? $defaultFields);
        }

        $constraints = [];

        switch ($clientFilter) {
            case 'no_clients':
                $constraints[] = $query->equals('client', 0);
                break;
            case 'only_clients':
                $constraints[] = $query->greaterThan('client', 0);
                break;
        }

        if ($include)
            $constraints[] = $query->logicalAnd(...$include);
        if ($specific)
            $constraints[] = $query->logicalAnd(...$specific);
        if ($exclude)
            $constraints[] = $query->logicalNot($query->logicalOr(...$exclude));

        if (!empty($constraints)) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        if (!empty($orderings)) {
            $query->setOrderings($orderings);
        }
        if ($limit !== null) {
            $query->setLimit($limit);
        }
        if ($offset !== null) {
            $query->setOffset($offset);
        }

        return $query;
    }

    /**
     * Baut ein OR-Konstrukt aus mehreren Feldern mit LIKE.
     */
    protected static function orConstraints(QueryInterface $query, string $term, array $fields): object
    {
        $constraints = [];
        foreach ($fields as $field) {
            $constraints[] = $query->like($field, '%' . $term . '%');
        }
        return $query->logicalOr(...$constraints);
    }
}
