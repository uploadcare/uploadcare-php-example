<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\{
    Command\Command,
    Exception\RuntimeException,
    Helper\Table,
    Input\InputArgument,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface,
    Style\SymfonyStyle
};
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Uploadcare\Api;

/**
 * Upload file with pre-configured API.
 */
class UploadFileCommand extends Command
{
    use RenderFileInfoTrait;

    protected static string $help = <<<TEXT
<fg=green;options=bold>Command for upload file to Uploadcare trough file API</>

This command uses a pre-configured in container Uploadcare API instance.
See <fg=blue>config/services.yaml</> for configuration example.

Use this command as
<fg=green>app:upload-file /path/to/file --mode=MODE</>

where <fg=green>/path/to/file</> is path to existing local file or remote URL and <fg=green>MODE</> is one of upload modes:
- <fg=green>path</> — library will use <fg=yellow>fromPath</> File API method;
- <fg=green>resource</> — command will opens your file as resource and library will use <fg=yellow>fromResource</> method;
- <fg=green>url</> — use this option for upload file from remote URL (and pass this url as argument, indeed)

You can use relative to project root directory path for your file.

You can define any API-related option with this command:
- <fg=green>mime-type</> — target mime-type for file
- <fg=green>filename</> — target filename
- <fg=green>store</> — store mode for file ('auto' as default)
TEXT;

    protected static $defaultName = 'app:upload-file';

    private Api $api;
    private ParameterBagInterface $parameterBag;

    /**
     * @param Api $api Pre-configured main Uploadcare API instance
     */
    public function __construct(Api $api, ParameterBagInterface $parameterBag, string $name = null)
    {
        parent::__construct($name);
        $this->api = $api;
        $this->parameterBag = $parameterBag;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Command for upload file to Uploadcare trough file API')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to uploaded file')
            ->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'Way to upload file', 'path')
            ->addOption('mime-type', null, InputOption::VALUE_OPTIONAL, 'Target MIME-type')
            ->addOption('filename', null, InputOption::VALUE_OPTIONAL, 'Target filename')
            ->addOption('store', null, InputOption::VALUE_OPTIONAL, 'Store file in storage', 'auto')
            ->setHelp(self::$help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->validateInput($input);
        $path = $input->getArgument('path');
        $mode = $input->getOption('mode');
        switch ($mode) {
            case 'resource':
                $this->asResource($path, $input, $output);
                break;
            case 'path':
                $this->asPath($path, $input, $output);
                break;
            case 'url':
                $this->asUrl($path, $input, $output);
                break;
        }

        return Command::SUCCESS;
    }

    /**
     * Upload file from resource.
     */
    protected function asResource(string $path, InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(\sprintf('Try to upload <info>%s</info> as resource', $path));
        $handle = \fopen($path, 'rb');
        $io->writeln('Resource created');

        $table = new Table($io);
        foreach (\stream_get_meta_data($handle) as $dataKey => $dataValue) {
            $table->addRow([$dataKey, \var_export($dataValue, true)]);
        }
        $table->render();
        try {
            $result = $this->api->uploader()->fromResource($handle, $input->getOption('mime-type'), $input->getOption('filename'), $input->getOption('store'));
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $io->success('File uploaded');
        $this->renderFileInfo($result, $io);
    }

    /**
     * Upload file from path.
     */
    protected function asPath(string $path, InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(\sprintf('Try to upload <info>%s</info> from path', $path));

        try {
            $result = $this->api->uploader()->fromPath($path, $input->getOption('mime-type'), $input->getOption('filename'), $input->getOption('store'));
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $io->success('File uploaded');
        $this->renderFileInfo($result, $io);
    }

    /**
     * Upload file from remote URL.
     */
    protected function asUrl(string $path, InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(\sprintf('Try to upload <info>%s</info> from url', $path));

        try {
            $result = $this->api->uploader()->fromUrl($path, $input->getOption('mime-type'), $input->getOption('filename'), $input->getOption('store'));
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $io->success('File uploaded');
        $this->renderFileInfo($result, $io);
    }

    /**
     * Validate user input.
     */
    protected function validateInput(InputInterface $input): void
    {
        switch ($input->getOption('mode')) {
            case 'path':
            case 'resource':
                $path = $input->getArgument('path');
                if (\strpos($path, '/') !== 0) {
                    $path = \sprintf('%s/%s', \rtrim($this->parameterBag->get('kernel.project_dir'), '/'), $path);
                }

                if (!\is_file($path) || !\is_readable($path)) {
                    throw new RuntimeException(\sprintf('Unable to read file from \'%s\'', $path));
                }
            break;
            case 'url':
                if (\strpos($input->getArgument('path'), 'http') !== 0) {
                    throw new RuntimeException(\sprintf('You should use valid URL for file in \'url\' upload mode, \'%s\' given', $input->getArgument('path')));
                }
            break;
        }
    }
}
