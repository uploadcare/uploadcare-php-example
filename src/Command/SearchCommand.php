<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Uploadcare\Api;
use Uploadcare\Search\SearchQuery;

/**
 * Search project files.
 *
 * @see https://uploadcare.com/docs/file-search/
 */
#[AsCommand(name: 'app:search', description: 'Search files by text, MIME type, size and tags')]
class SearchCommand extends Command
{
    protected static string $help = <<<TEXT
<fg=green;options=bold>Command for searching files through REST API</>

All given conditions are combined with AND:
<fg=green>app:search --query=invoice --mime-type=application/pdf --sort=-datetime_uploaded</>
<fg=green>app:search --tag-any=cat --tag-any=dog --tag-none=draft --images</>

Text conditions (<fg=green>query</>, <fg=green>filename</>) need at least 4 characters.
Filter-only searches have no relevance order, so pass <fg=green>--sort</> for them.

Use <fg=green>--all</> to follow the next pages with <fg=yellow>SearchApi::nextPage</>, up to 1000 results.

New uploads and tag changes may take up to 15 seconds to show up in the results.
TEXT;

    public function __construct(private readonly Api $api)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(self::$help)
            ->addOption('query', null, InputOption::VALUE_REQUIRED, 'Full-text search across filename, metadata and MIME type')
            ->addOption('filename', null, InputOption::VALUE_REQUIRED, 'Full-text search in the original filename')
            ->addOption('mime-type', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Exact MIME type, several values are combined with OR')
            ->addOption('tag-any', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'File has at least one of these tags')
            ->addOption('tag-all', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'File has all of these tags')
            ->addOption('tag-none', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'File has none of these tags')
            ->addOption('images', null, InputOption::VALUE_NEGATABLE, 'Images only, or --no-images to exclude them')
            ->addOption('min-size', null, InputOption::VALUE_REQUIRED, 'Minimal file size in bytes')
            ->addOption('max-size', null, InputOption::VALUE_REQUIRED, 'Maximal file size in bytes')
            ->addOption('uploaded-after', null, InputOption::VALUE_REQUIRED, 'Uploaded at or after this date, e.g. 2024-01-01')
            ->addOption('uploaded-before', null, InputOption::VALUE_REQUIRED, 'Uploaded before this date')
            ->addOption('fuzzy', null, InputOption::VALUE_NONE, 'Typo-tolerant text matching (slower)')
            ->addOption('sort', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'score, datetime_uploaded, size or original_filename, prefix with - for descending order')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Files per page, from 1 to 100', '20')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Fetch all pages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $query = $this->buildQuery($input);
            $page = $this->api->search()->search($query, (int) $input->getOption('limit'));
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $io->title(\sprintf('Found files: <info>%d</info>', $page->getTotal()));
        $rows = [];
        do {
            foreach ($page->getResults() as $file) {
                $rows[] = [$file->getOriginalFilename(), $file->getUuid(), $file->getMimeType(), (string) $file->getTags()];
            }
        } while ($input->getOption('all') && ($page = $this->api->search()->nextPage($page)) !== null);

        $io->table(['File name', 'Uuid', 'Mime type', 'Tags'], $rows);

        return Command::SUCCESS;
    }

    protected function buildQuery(InputInterface $input): SearchQuery
    {
        $query = new SearchQuery();

        if (($text = $input->getOption('query')) !== null) {
            $query->query($text);
        }
        if (($filename = $input->getOption('filename')) !== null) {
            $query->phrase(SearchQuery::FIELD_ORIGINAL_FILENAME, $filename);
        }
        if (($mimeTypes = $input->getOption('mime-type')) !== []) {
            $query->exact(SearchQuery::FIELD_MIME_TYPE, $mimeTypes);
        }

        $query
            ->tagsAny($input->getOption('tag-any'))
            ->tagsAll($input->getOption('tag-all'))
            ->tagsNone($input->getOption('tag-none'));

        if (($images = $input->getOption('images')) !== null) {
            $query->isImage($images);
        }
        if (($minSize = $input->getOption('min-size')) !== null) {
            $query->size(SearchQuery::GTE, (int) $minSize);
        }
        if (($maxSize = $input->getOption('max-size')) !== null) {
            $query->size(SearchQuery::LTE, (int) $maxSize);
        }
        if (($after = $input->getOption('uploaded-after')) !== null) {
            $query->datetimeUploaded(SearchQuery::GTE, new \DateTimeImmutable($after));
        }
        if (($before = $input->getOption('uploaded-before')) !== null) {
            $query->datetimeUploaded(SearchQuery::LT, new \DateTimeImmutable($before));
        }
        if ($input->getOption('fuzzy')) {
            $query->fuzziness();
        }
        if (($sort = $input->getOption('sort')) !== []) {
            $query->sort(...$sort);
        }

        return $query;
    }
}
