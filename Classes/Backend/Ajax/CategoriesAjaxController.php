<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Backend\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CategoriesAjaxController
{
    private static array $titleCache = [];
    private static array $clientNameCache = [];

    public function selectAction(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $params = $body['publicrelations_categories'] ?? [];

        $clientId = (int) ($params['clientId'] ?? 0);
        $searchTerm = trim($params['q'] ?? '');
        $isRecursive = !empty($params['parentRecursive']);
        $stopAtParentId = (int) ($params['parent'] ?? 0);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category');
        $expr = $queryBuilder->expr();
        $query = $queryBuilder->select('uid', 'title', 'parent', 'client')
            ->from('sys_category')
            ->where($expr->eq('deleted', 0));

        if ($clientId > 0) {
            // ### CLIENT-LOGIK ###
            // Hauptfilter ist die Client-ID.
            $query->andWhere($expr->eq('client', $queryBuilder->createNamedParameter($clientId, Connection::PARAM_INT)));
            // Optionaler Zusatzfilter ist der Suchbegriff.
            if ($searchTerm !== '') {
                $query->andWhere($expr->like('title', $queryBuilder->createNamedParameter('%' . $searchTerm . '%')));
            }
        } else {
            if (mb_strlen($searchTerm) < 2) {
                return new JsonResponse([]);
            }
            $query->andWhere($expr->like('title', $queryBuilder->createNamedParameter('%' . $searchTerm . '%')));
            $query->andWhere(
                $expr->or(
                    $expr->eq('client', 0),
                    $expr->isNull('client')
                )
            );
        }

        $categories = $query->executeQuery()->fetchAllAssociative();
        $options = [];

        foreach ($categories as $category) {
            $options[] = [
                'value' => $category['uid'],
                // ### KORREKTUR: Die stopAtParentId wird jetzt hier übergeben ###
                'text' => $isRecursive
                    ? $this->getHierarchicalTitle($category['uid'], $category['title'], (int) $category['parent'], $stopAtParentId)
                    : $category['title'],
                'clientName' => (int) $category['client'] > 0 ? $this->getClientName((int) $category['client']) : null,
            ];
        }

        return new JsonResponse($options);
    }

    /**
     * Findet rekursiv alle untergeordneten Kategorien einer gegebenen Parent-ID.
     *
     * @param int $parentId Die ID der Start-Kategorie.
     * @return array Eine flache Liste aller gefundenen Nachfahren.
     */
    private function findAllDescendants(int $parentId): array
    {
        $allDescendants = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category');

        $directChildren = $queryBuilder
            ->select('uid', 'title', 'parent', 'client')
            ->from('sys_category')
            ->where($queryBuilder->expr()->eq('parent', $queryBuilder->createNamedParameter($parentId, Connection::PARAM_INT)))
            ->andWhere($queryBuilder->expr()->eq('deleted', 0))
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($directChildren as $child) {
            $allDescendants[] = $child;
            // Rekursiver Aufruf für die Kinder des Kindes
            $grandChildren = $this->findAllDescendants((int) $child['uid']);
            if (!empty($grandChildren)) {
                $allDescendants = array_merge($allDescendants, $grandChildren);
            }
        }

        return $allDescendants;
    }

    // Die folgenden Funktionen bleiben unverändert und sind bereits korrekt.
    private function getClientName(int $clientId): ?string
    {
        if (isset(self::$clientNameCache[$clientId])) {
            return self::$clientNameCache[$clientId];
        }
        $name = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_publicrelations_domain_model_client')
            ->select('short_name')
            ->from('tx_publicrelations_domain_model_client')
            ->where('uid = ' . $clientId)
            ->executeQuery()->fetchOne();

        self::$clientNameCache[$clientId] = $name ?: null;
        return self::$clientNameCache[$clientId];
    }

    private function getHierarchicalTitle(int $uid, string $initialTitle, int $parentId, int $stopAtParentId = 0): string
    {
        if (isset(self::$titleCache[$uid])) {
            return self::$titleCache[$uid];
        }

        $path = [$initialTitle];
        $currentParentId = $parentId;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_category');

        // Die Schleife läuft nur, solange der Parent nicht der Stop-Parent ist
        while ($currentParentId > 0 && $currentParentId !== $stopAtParentId) {
            $parentRow = $queryBuilder
                ->select('title', 'parent')
                ->from('sys_category')
                ->where($queryBuilder->expr()->eq('uid', $currentParentId))
                ->executeQuery()
                ->fetchAssociative();

            if ($parentRow) {
                array_unshift($path, $parentRow['title']);
                $currentParentId = (int) $parentRow['parent'];
            } else {
                break;
            }
        }

        $result = implode(' | ', $path);
        self::$titleCache[$uid] = $result;

        return $result;
    }
}