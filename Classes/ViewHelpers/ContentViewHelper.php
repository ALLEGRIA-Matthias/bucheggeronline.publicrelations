<?php
namespace BucheggerOnline\Publicrelations\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ContentViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('content', 'string', 'content', true);
        $this->registerArgument('salutation', 'string', 'salutation', true);
        $this->registerArgument('responselink', 'string', 'response link', true);
    }

    public function render(): string
    {
        $content = $this->arguments['content'];
        $content = str_replace('###anrede###', $this->arguments['salutation'], $content);

        return str_replace(
            '###rueckmeldung_link###',
            '<a href="' . $this->arguments['responselink'] . '" target="_blank" class="text-primary">Rückmeldung zur Einladung</a>',
            $content
        );
    }
}