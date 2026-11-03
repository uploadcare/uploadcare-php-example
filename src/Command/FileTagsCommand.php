<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Uploadcare\Api;
use Uploadcare\Response\TagsResponse;

/**
 * Read and modify file tags.
 *
 * @see https://uploadcare.com/docs/file-tags/
 */
#[AsCommand(name: 'app:file-tags', description: 'Show, add, remove or replace file tags')]
class FileTagsCommand extends Command
{
    protected static string $help = <<<TEXT
<fg=green;options=bold>Command for managing file tags through REST API</>

Without options the command shows the current tags of the file:
<fg=green>app:file-tags 1822793d-5cdb-418e-8545-6bd6a5dd74bd</>

Add and remove tags in a single atomic call (removal is applied first, then addition):
<fg=green>app:file-tags UUID --add=cat --add=animal --delete=dog</>

Replace the whole tag set, or clear it:
<fg=green>app:file-tags UUID --replace=cat --replace=cute</>
<fg=green>app:file-tags UUID --clear</>

Tags are lowercased, trimmed and deduplicated by the API. A file can have up to 50 tags,
each up to 100 characters: Latin letters, digits, hyphen, underscore and dot.
TEXT;

    public function __construct(private readonly Api $api)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(self::$help)
            ->addArgument('uuid', InputArgument::REQUIRED, 'File uuid')
            ->addOption('add', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Tag to add')
            ->addOption('delete', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Tag to remove')
            ->addOption('replace', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Tag of the new tag set')
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Remove all tags');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $uuid = $input->getArgument('uuid');
        $add = $input->getOption('add');
        $delete = $input->getOption('delete');
        $replace = $input->getOption('replace');
        $clear = $input->getOption('clear');

        if (($add !== [] || $delete !== []) && ($replace !== [] || $clear)) {
            throw new RuntimeException('Use either --add/--delete or --replace/--clear, not both');
        }

        try {
            if ($replace !== [] || $clear) {
                $this->renderResult($this->api->tags()->replaceTags($uuid, $replace), $io);
            } elseif ($add !== [] || $delete !== []) {
                $this->renderResult($this->api->tags()->updateTags($uuid, $add, $delete), $io);
            } else {
                $tags = $this->api->tags()->getTags($uuid);
                $io->title(\sprintf('File <info>%s</info> has %d tag(s)', $uuid, \count($tags)));
                $io->listing($tags->toArray());
            }
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        return Command::SUCCESS;
    }

    protected function renderResult(TagsResponse $result, SymfonyStyle $io): void
    {
        $io->success('Tags saved');
        $io->table(['Property', 'Value'], [
            ['Tags', (string) $result->getTags() ?: 'No tags'],
            ['Added', \implode(',', $result->getAdded()) ?: '--'],
            ['Removed', \implode(',', $result->getDeleted()) ?: '--'],
        ]);
    }
}
