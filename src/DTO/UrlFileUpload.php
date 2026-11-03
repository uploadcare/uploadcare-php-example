<?php declare(strict_types=1);

namespace App\DTO;

use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Symfony\Component\Validator\Constraints as Assert;

class UrlFileUpload
{
    #[Assert\NotBlank]
    private string | null $url = null;
    private string | null $filename = null;
    private bool $checkForDuplicates = false;
    private array $tags = [];
    private Collection $metadata;

    public function __construct()
    {
        $this->metadata = new ArrayCollection();
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
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

    public function isCheckForDuplicates(): bool
    {
        return $this->checkForDuplicates;
    }

    public function setCheckForDuplicates(bool $checkForDuplicates): self
    {
        $this->checkForDuplicates = $checkForDuplicates;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): self
    {
        $this->tags = $tags;

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
