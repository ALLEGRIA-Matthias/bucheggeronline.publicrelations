<?php
namespace BucheggerOnline\Publicrelations\Domain\Model\Dto;
class PreviewSelector
{
    public string $variantCode = 'invite';
    public function getVariantCode(): string
    {
        return $this->variantCode;
    }
    public function setVariantCode(string $code): void
    {
        $this->variantCode = $code;
    }
}