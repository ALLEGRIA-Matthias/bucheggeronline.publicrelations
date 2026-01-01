<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Helper;

use BucheggerOnline\Publicrelations\Domain\Repository\HiddenRepositoryInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;

/*
 * Helper class to register hidden objects in extbase session container.
 * That way it's possible to call Controller Actions with hidden objects.
 */
class HiddenObjectHelper
{
    /**
     * @var Session
     */
    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function registerHiddenObjectInExtbaseSession(
        RepositoryInterface $repository,
        RequestInterface $request,
        string $argumentName
    ): void {
        if ($repository instanceof HiddenRepositoryInterface) {
            $objectRaw = $request->getArgument($argumentName);
            if (is_array($objectRaw)) {
                // get object from form ($_POST)
                $object = $repository->findHiddenObject((int)$objectRaw['__identity']);
            } else {
                // get object from UID
                $object = $repository->findHiddenObject((int)$objectRaw);
            }
            $this->session->registerObject($object, $object->getUid());
        }
    }
}
