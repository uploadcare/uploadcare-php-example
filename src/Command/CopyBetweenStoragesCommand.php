<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Uploadcare\Api;

class CopyBetweenStoragesCommand extends Command
{
    use RenderFileInfoTrait;

    protected static $defaultName = 'app:copy-file';

    private Api $api;

    public function __construct(Api $api, string $name = null)
    {
        parent::__construct($name);
        $this->api = $api;
    }

    protected function configure(): void
    {
        $this->setDescription('Copy file from local storage to connected s3 bucket or vise versa')
            ->addArgument('uuid', InputArgument::REQUIRED, 'File UUID')
            ->addArgument('custom-storage', InputArgument::REQUIRED, 'Custom storage name')
            ->addOption('to-local', 'l', InputOption::VALUE_NONE, 'Copy from remote to local storage')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $uuid = $input->getArgument('uuid');
        if (!\is_string($uuid)) {
            throw new RuntimeException(\sprintf('Argument \'uuid\' must be a string, % given', \gettype($uuid)));
        }

        $info = $this->api->file()->fileInfo($uuid);
        $io->title('The file is');
        $this->renderFileInfo($info, $io);

        $method = $input->getOption('to-local') ? 'copyToLocalStorage' : 'copyToRemoteStorage';
        $result = $this->api->file()->{$method}($info, $input->getArgument('custom-storage'));

        if ($method === 'copyToRemoteStorage') {
            $io->success($result);
        } else {
            $io->title('Copied file');
            $this->renderFileInfo($result, $io);
        }

        return Command::SUCCESS;
    }
}
