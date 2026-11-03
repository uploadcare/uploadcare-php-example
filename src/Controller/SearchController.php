<?php declare(strict_types=1);

namespace App\Controller;

use App\DTO\FileSearch;
use App\Form\FileSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;
use Uploadcare\Exception\{HttpException, SearchException, TagsException};
use Uploadcare\Interfaces\Api\SearchApiInterface;
use Uploadcare\Search\SearchQuery;

/**
 * File search by full-text conditions, exact values, ranges and tags.
 *
 * @see https://uploadcare.com/docs/file-search/
 */
#[Route(path: '/search', name: 'file_search')]
class SearchController extends AbstractController
{
    public function __construct(private readonly Api $api)
    {
    }

    public function __invoke(Request $request): Response
    {
        $search = new FileSearch();
        $form = $this->createForm(FileSearchType::class, $search);
        $form->handleRequest($request);

        $result = null;
        $offset = \min(\max(0, $request->query->getInt('offset')), SearchApiInterface::MAX_WINDOW - 1);
        if ($request->query->count() > 0 && $form->isSubmitted() && $form->isValid()) {
            // `offset + limit` must not exceed 1000, so the last page of the window is shorter
            $limit = \min($search->getLimit(), SearchApiInterface::MAX_WINDOW - $offset);
            try {
                $result = $this->api->search()->search($this->buildQuery($search), $limit, $offset);
            } catch (SearchException | TagsException | HttpException $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('search/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
            'previous' => $result !== null && $offset > 0 ? \max(0, $offset - $search->getLimit()) : null,
            'next' => $result !== null && $this->hasNextPage($offset + $search->getLimit(), $result->getTotal()) ? $offset + $search->getLimit() : null,
        ]);
    }

    /**
     * Each filled form field becomes a condition, and all conditions are combined with AND.
     */
    private function buildQuery(FileSearch $search): SearchQuery
    {
        $query = new SearchQuery();

        if (!empty($search->getQuery())) {
            $query->query($search->getQuery());
        }
        if (!empty($search->getFilename())) {
            $query->phrase(SearchQuery::FIELD_ORIGINAL_FILENAME, $search->getFilename());
        }
        if (!empty($search->getMimeType())) {
            $query->exact(SearchQuery::FIELD_MIME_TYPE, $search->getMimeType());
        }

        $query
            ->tagsAny($search->getTagsAny())
            ->tagsAll($search->getTagsAll())
            ->tagsNone($search->getTagsNone());

        if ($search->getIsImage() !== null) {
            $query->isImage($search->getIsImage());
        }
        if ($search->getUploadedFrom() !== null) {
            $query->datetimeUploaded(SearchQuery::GTE, $search->getUploadedFrom());
        }
        if ($search->getUploadedTo() !== null) {
            $query->datetimeUploaded(SearchQuery::LT, $search->getUploadedTo());
        }
        if ($search->getSizeFrom() !== null) {
            $query->size(SearchQuery::GTE, $search->getSizeFrom());
        }
        if ($search->getSizeTo() !== null) {
            $query->size(SearchQuery::LTE, $search->getSizeTo());
        }
        if ($search->isFuzziness()) {
            $query->fuzziness();
        }
        // Filter-only queries have no relevance score, so their order is undefined without a sort
        if ($search->getSort() !== null) {
            $query->sort($search->getSort());
        }

        return $query;
    }

    /**
     * Only the first 1000 results can be paged through, narrow the query to see the rest.
     */
    private function hasNextPage(int $nextOffset, int $total): bool
    {
        return $nextOffset < $total && $nextOffset < SearchApiInterface::MAX_WINDOW;
    }
}
