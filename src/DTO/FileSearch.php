<?php declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Uploadcare\Interfaces\Api\SearchApiInterface;
use Uploadcare\Search\SearchQuery;

/**
 * Conditions of the file search form.
 */
class FileSearch
{
    #[Assert\Length(min: SearchQuery::MIN_TERM_LENGTH)]
    private ?string $query = null;
    #[Assert\Length(min: SearchQuery::MIN_TERM_LENGTH)]
    private ?string $filename = null;
    private ?string $mimeType = null;
    private array $tagsAny = [];
    private array $tagsAll = [];
    private array $tagsNone = [];
    private ?bool $isImage = null;
    private ?\DateTimeImmutable $uploadedFrom = null;
    private ?\DateTimeImmutable $uploadedTo = null;
    #[Assert\PositiveOrZero]
    private ?int $sizeFrom = null;
    #[Assert\PositiveOrZero]
    private ?int $sizeTo = null;
    private bool $fuzziness = false;
    private ?string $sort = null;
    #[Assert\Range(min: 1, max: SearchApiInterface::MAX_LIMIT)]
    private int $limit = SearchApiInterface::DEFAULT_LIMIT;

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(?string $query): self
    {
        $this->query = $query;

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

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getTagsAny(): array
    {
        return $this->tagsAny;
    }

    public function setTagsAny(array $tagsAny): self
    {
        $this->tagsAny = $tagsAny;

        return $this;
    }

    public function getTagsAll(): array
    {
        return $this->tagsAll;
    }

    public function setTagsAll(array $tagsAll): self
    {
        $this->tagsAll = $tagsAll;

        return $this;
    }

    public function getTagsNone(): array
    {
        return $this->tagsNone;
    }

    public function setTagsNone(array $tagsNone): self
    {
        $this->tagsNone = $tagsNone;

        return $this;
    }

    public function getIsImage(): ?bool
    {
        return $this->isImage;
    }

    public function setIsImage(?bool $isImage): self
    {
        $this->isImage = $isImage;

        return $this;
    }

    public function getUploadedFrom(): ?\DateTimeImmutable
    {
        return $this->uploadedFrom;
    }

    public function setUploadedFrom(?\DateTimeImmutable $uploadedFrom): self
    {
        $this->uploadedFrom = $uploadedFrom;

        return $this;
    }

    public function getUploadedTo(): ?\DateTimeImmutable
    {
        return $this->uploadedTo;
    }

    public function setUploadedTo(?\DateTimeImmutable $uploadedTo): self
    {
        $this->uploadedTo = $uploadedTo;

        return $this;
    }

    public function getSizeFrom(): ?int
    {
        return $this->sizeFrom;
    }

    public function setSizeFrom(?int $sizeFrom): self
    {
        $this->sizeFrom = $sizeFrom;

        return $this;
    }

    public function getSizeTo(): ?int
    {
        return $this->sizeTo;
    }

    public function setSizeTo(?int $sizeTo): self
    {
        $this->sizeTo = $sizeTo;

        return $this;
    }

    public function isFuzziness(): bool
    {
        return $this->fuzziness;
    }

    public function setFuzziness(bool $fuzziness): self
    {
        $this->fuzziness = $fuzziness;

        return $this;
    }

    public function getSort(): ?string
    {
        return $this->sort;
    }

    public function setSort(?string $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }
}
