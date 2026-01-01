<?php
namespace BucheggerOnline\Publicrelations\Utility;

use TYPO3\CMS\Core\PageTitle\AbstractPageTitleProvider;

class TitleProvider extends AbstractPageTitleProvider {

  public function setTitle(string $title)
    {
        $this->title = $title;
    }

}
