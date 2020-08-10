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
use Uploadcare\Interfaces\File\{FileInfoInterface, ImageInfoInterface, VideoInfoInterface};

/**
 * Upload file with pre-configured API.
 */
class UploadFileCommand extends Command
{
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
TEXT;

    protected static $defaultName = 'app:upload-file';

    private Api $api;
    private ParameterBagInterface $parameterBag;

    /**
     * @param Api                   $api          Pre-configured main Uploadcare API instance
     * @param ParameterBagInterface $parameterBag
     * @param string|null           $name
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
            ->setHelp(self::$help)
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->validateInput($input);
        $path = $input->getArgument('path');
        $mode = $input->getOption('mode');
        switch ($mode) {
            case 'resource':
                $this->asResource($path, $io);
                break;
            case 'path':
                $this->asPath($path, $io);
                break;
            case 'url':
                $this->asUrl($path, $io);
                break;
        }

        return Command::SUCCESS;
    }

    /**
     * Upload file from resource.
     *
     * @param string       $path
     * @param SymfonyStyle $io
     */
    protected function asResource(string $path, SymfonyStyle $io): void
    {
        $io->title(\sprintf('Try to upload <info>%s</info> as resource', $path));
        $handle = \fopen($path, 'rb');
        $io->writeln('Resource created');

        $table = new Table($io);
        foreach (\stream_get_meta_data($handle) as $dataKey => $dataValue) {
            $table->addRow([$dataKey, \var_export($dataValue, true)]);
        }
        $table->render();
        try {
            $result = $this->api->uploader()->fromResource($handle);
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $io->success('File uploaded');
        $this->renderFileInfo($result, $io);
    }

    /**
     * Upload file from path.
     *
     * @param string       $path
     * @param SymfonyStyle $io
     */
    protected function asPath(string $path, SymfonyStyle $io): void
    {
        $io->title(\sprintf('Try to upload <info>%s</info> from path', $path));

        try {
            $result = $this->api->uploader()->fromPath($path);
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $io->success('File uploaded');
        $this->renderFileInfo($result, $io);
    }

    /**
     * Upload file from remote URL.
     *
     * @param string       $path
     * @param SymfonyStyle $io
     */
    protected function asUrl(string $path, SymfonyStyle $io): void
    {
        $io->title(\sprintf('Try to upload <info>%s</info> from url', $path));

        try {
            $result = $this->api->uploader()->fromUrl($path, 'image/jpeg');
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $io->success('File uploaded');
        $this->renderFileInfo($result, $io);
    }

    /**
     * Show File info.
     *
     * @param FileInfoInterface $fileInfo
     * @param SymfonyStyle      $io
     */
    protected function renderFileInfo(FileInfoInterface $fileInfo, SymfonyStyle $io): void
    {
        $io->title('File info');

        $io->table(['Property', 'Value'], [
            ['UUID', $fileInfo->getUuid()],
            ['Datetime Stored', ($stored = $fileInfo->getDatetimeStored()) !== null ? $stored->format(\DateTimeInterface::ATOM) : 'No data'],
            ['Datetime Uploaded', ($uploaded = $fileInfo->getDatetimeUploaded()) !== null ? $uploaded->format(\DateTimeInterface::ATOM) : 'No data'],
            ['Is image', $fileInfo->isImage() ? 'Yes' : 'No'],
            ['Is ready', $fileInfo->isReady() ? 'Yse' : 'No'],
            ['Mime Type', $fileInfo->getMimeType()],
            ['Original file URL', $fileInfo->getOriginalFileUrl()],
            ['Url', $fileInfo->getUrl()],
            ['Original filename', $fileInfo->getOriginalFilename()],
            ['Size', $fileInfo->getSize()],
            ['Source', $fileInfo->getSource()],
        ]);

        if (($imageInfo = $fileInfo->getImageInfo()) !== null) {
            $this->renderImageInfo($imageInfo, $io);
        }

        if (($videoInfo = $fileInfo->getVideoInfo()) !== null) {
            $this->renderVideoInfo($videoInfo, $io);
        }
    }

    /**
     * Show Image info.
     *
     * @param ImageInfoInterface $imageInfo
     * @param SymfonyStyle       $io
     */
    protected function renderImageInfo(ImageInfoInterface $imageInfo, SymfonyStyle $io): void
    {
        $io->title('Image info');

        $io->table(['Property', 'Value'], [
            ['Color Mode', $imageInfo->getColorMode()],
            ['Orientation', $imageInfo->getOrientation()],
            ['Format', $imageInfo->getFormat()],
            ['Is image a sequence', $imageInfo->isSequence() ? 'Yes' : 'No'],
            ['Width', $imageInfo->getWidth()],
            ['Height', $imageInfo->getHeight()],
            [
                'Geo location',
                ($gl = $imageInfo->getGeoLocation()) !== null ? \sprintf('%s, %s', $gl->getLatitude(), $gl->getLongitude()) : 'No data',
            ],
            [
                'DPI',
                ($dpi = $imageInfo->getDpi()) !== null ? \implode('x', $dpi) : 'No data',
            ],
        ]);
    }

    /**
     * Show Video info.
     *
     * @param VideoInfoInterface $videoInfo
     * @param SymfonyStyle       $io
     */
    protected function renderVideoInfo(VideoInfoInterface $videoInfo, SymfonyStyle $io): void
    {
        $io->title('Video Info');

        $io->table(['Property', 'Value'], [
            ['Duration', $videoInfo->getDuration()],
            ['Format', $videoInfo->getFormat()],
            ['Bitrate', $videoInfo->getBitrate()],
            // TODO videoInfo
        ]);
    }

    /**
     * Validate user input.
     *
     * @param InputInterface $input
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
