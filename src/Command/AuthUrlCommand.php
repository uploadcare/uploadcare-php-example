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
use Uploadcare\AuthUrl\AuthUrlConfig;
use Uploadcare\Interfaces\ConfigurationInterface;
use Uploadcare\Interfaces\File\FileInfoInterface;

class AuthUrlCommand extends Command
{
    protected static $defaultName = 'app:auth-url';

    protected static string $help = <<<TEXT
<fg=green;options=bold>Command generate Auth URL with token for custom Akamai CDN</>

Documentation about auth url and secure delivery — https://uploadcare.com/docs/security/secure_delivery/
If you want to generate secure (auth) URL, you must set the <fg=yellow>AUTH_URL_SECRET_KEY</> and <fg=yellow>AUTH_URL_CDN_HOST</> variables.

Pass the file uuid as argument to this command.
TEXT;

    private Api $api;

    public function __construct(ConfigurationInterface $configuration, AuthUrlConfig $authUrlConfig, string $name = null)
    {
        parent::__construct($name);
        $configuration->setAuthUrlConfig($authUrlConfig);
        $this->api = new Api($configuration);
    }

    protected function configure(): void
    {
        $this
            ->setHelp(self::$help)
            ->setDescription('Generates Auth URL with Akamai CDN')
            ->addArgument('file-id', InputArgument::REQUIRED)
            ->addOption('window', null, InputOption::VALUE_OPTIONAL, 'Time window for URL', 300)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $this->api->file()->fileInfo($input->getArgument('file-id'));
        $window = $input->getOption('window');
        if (!$file instanceof FileInfoInterface) {
            throw new RuntimeException('Cannot find file');
        }
        $io->writeln($this->api->file()->generateSecureUrl($file, $window));

        return Command::SUCCESS;
    }
}
