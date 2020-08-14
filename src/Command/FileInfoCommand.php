<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Uploadcare\Api;

class FileInfoCommand extends Command
{
    use RenderFileInfoTrait;

    protected static string $help = <<<TEXT
<fg=green;options=bold>Command for retrieve file info from Uploadcare API</>
TEXT;

    protected static $defaultName = 'app:file-info';

    private Api $api;

    public function __construct(Api $api, string $name = null)
    {
        parent::__construct($name);
        $this->api = $api;
    }

    protected function configure(): void
    {
        $this->setHelp(self::$help)
            ->addArgument('uuid', InputArgument::OPTIONAL, 'File uuid to retrieve', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getArgument('uuid') === null) {
            return $this->listFiles($io);
        }

        return $this->fileInfo($input->getArgument('uuid'), $io);
    }

    protected function listFiles(SymfonyStyle $io): int
    {
        $files = $this->api->file()->listFiles();
        $io->title(\sprintf('Total files: <info>%s</info>', $files->getTotal()));

        $table = new Table($io);
        $table->setHeaders(['File name', 'Uuid']);
        foreach ($files->getResults() as $file) {
            $table->addRow([$file->getOriginalFilename(), $file->getUuid()]);
        }
        $table->render();

        return Command::SUCCESS;
    }

    protected function fileInfo(string $uuid, SymfonyStyle $io): int
    {
        $io->title(\sprintf('File <info>%s</info> information', $uuid));
        try {
            $file = $this->api->file()->fileInfo($uuid);
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
        $this->renderFileInfo($file, $io);

        return Command::SUCCESS;
    }
}
