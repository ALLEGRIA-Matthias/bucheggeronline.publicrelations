<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use BucheggerOnline\Publicrelations\Domain\Repository\SlideRepository;

class SlideController extends AbstractPublicrelationsController
{
    public function __construct(
        private readonly SlideRepository $slideRepository
    ) {
    }

    public function listAction(): ResponseInterface
    {
        $slides = $this->slideRepository->findAll();
        $this->view->assign('slides', $slides);

        return $this->frontendResponse();
    }
}
