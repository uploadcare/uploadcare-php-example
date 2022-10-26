<?php declare(strict_types=1);

namespace App\DTO;

use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Symfony\Component\Validator\Constraints as Assert;

class FileUpload
{
    private string | null $filename = null;
    private string | null $mimeType = null;
    #[Assert\Choice(choices: ['auto', '0', '1'])]
    private string $store = 'auto';
    private Collection $metadata;

    public function __construct()
    {
        $this->metadata = new ArrayCollection();
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getStore(): string
    {
        return $this->store;
    }

    public function setStore(string $store): FileUpload
    {
        $this->store = $store;

        return $this;
    }

    /**
     * @return Collection<int, Metadata>
     */
    public function getMetadata(): Collection
    {
        return $this->metadata;
    }

    public function addMetadata(Metadata $metadata): self
    {
        if (!$this->metadata->contains($metadata)) {
            $this->metadata->add($metadata);
        }

        return $this;
    }

    public function removeMetadata(Metadata $metadata): self
    {
        if ($this->metadata->contains($metadata)) {
            $this->metadata->removeElement($metadata);
        }

        return $this;
    }
}
