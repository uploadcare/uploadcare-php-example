<?php declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class Metadata
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 64)]
    #[Assert\Regex('[\w\-\.\:]+')]
    private string | null $key = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 512)]
    private string | null $value = null;

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
