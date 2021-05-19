<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\{Command\Command,
    Exception\InvalidArgumentException,
    Input\InputArgument,
    Input\InputInterface,
    Output\OutputInterface,
    Style\SymfonyStyle};
use Uploadcare\Api;
use Uploadcare\Conversion\DocumentConversionRequest;
use Uploadcare\Interfaces\Conversion\ConvertedItemInterface;
use Uploadcare\Interfaces\File\FileInfoInterface;

class ConvertPdfCommand extends Command
{
    use RenderFileInfoTrait;
    protected const DEFAULT_CDN = 'ucarecdn.com';
    protected const UUID_REGEX = '/[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}/';

    protected static string $help = <<<TEXT
<fg=green;options=bold>Convert PDF file to JPG images (including multi-page PDF)</>

Set <info>source</info> argument to uploadcare url to use existing file or to local file to upload this file to Uploadcare first.
<question>NOTE</question> start argument with <info>file:///</info> in case if you use local file.
TEXT;

    protected static $defaultName = 'app:convert-pdf';
    private string $cdnUrl;

    public function __construct(private Api $api, string $name = null, ?string $cdnUrl = null)
    {
        parent::__construct($name);
        $this->cdnUrl = $cdnUrl ?? self::DEFAULT_CDN;
    }

    protected function configure(): void
    {
        $this->setHelp(self::$help)
            ->setDescription('Convert PDF to JPG')
            ->addArgument('source', InputArgument::REQUIRED, 'Source URL or file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fileAddress = $input->getArgument('source');
        if (!\is_string($fileAddress)) {
            throw new InvalidArgumentException('Argument \'source\' must be a string');
        }

        $file = $this->resolveUcFile($fileAddress);
        if (!\str_contains(\strtolower($file->getMimeType()), 'pdf')) {
            throw new InvalidArgumentException('Source file is not a PDF');
        }

        $this->convertDocument($file, $io);

        return Command::SUCCESS;
    }

    private function convertDocument(FileInfoInterface $file, SymfonyStyle $io): void
    {
        $req = new DocumentConversionRequest(targetFormat: 'jpg', throwError: true, store: true);
        try {
            $result = $this->api->conversion()->convertDocument($file, $req);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(message: $e->getMessage(), previous: $e);
        }

        if ($result instanceof ConvertedItemInterface) {
            $resultFile = $this->api->file()->fileInfo($result->getUuid());

            $this->renderFileInfo($resultFile, $io);
        }
    }

    private function resolveUcFile(string $fileAddress): FileInfoInterface
    {
        if (\str_starts_with($fileAddress, 'file://')) {
            $path = \substr($fileAddress, 7);

            return $this->api->uploader()->fromPath($path);
        }

        if (\str_contains($fileAddress, $this->cdnUrl)) {
            $uuids = [];
            \preg_match(self::UUID_REGEX, $fileAddress, $uuids);
            if (($uuid = $uuids[0] ?? null) === null) {
                throw new InvalidArgumentException('Unable to determine uuid to source');
            }

            return $this->api->file()->fileInfo($uuid);
        }

        if (\str_starts_with($fileAddress, 'https')) {
            return $this->api->uploader()->fromUrl($fileAddress);
        }

        throw new InvalidArgumentException('Unable to determine source');
    }
}
