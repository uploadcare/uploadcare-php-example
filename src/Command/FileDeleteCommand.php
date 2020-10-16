<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Uploadcare\Api;
use Uploadcare\File;

class FileDeleteCommand extends Command
{
    use RenderFileInfoTrait;

    protected static $defaultName = 'app:file-delete';

    private Api $api;

    public function __construct(Api $api, string $name = null)
    {
        parent::__construct($name);
        $this->api = $api;
    }

    protected function configure(): void
    {
        $this->setDescription('Remove file from Uploadcare')
            ->addArgument('uuid', InputArgument::REQUIRED, 'File UUID');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $uuid = $input->getArgument('uuid');
        if (!\is_string($uuid)) {
            throw new RuntimeException(\sprintf('Argument \'uuid\' must be a string, % given', \gettype($uuid)));
        }
        $info = $this->api->file()->fileInfo($uuid);
        $io->title('The file is');
        $this->renderFileInfo($info, $io);

        $choice = $io->confirm('Are you sure to delete this file?', false);
        if (!$choice) {
            $io->writeln('Ok then');

            return Command::SUCCESS;
        }

        $result = $info instanceof File ? $info->delete() : $this->api->file()->deleteFile($info);

        $io->title('The file was');
        $this->renderFileInfo($result, $io);

        return Command::SUCCESS;
    }
}
