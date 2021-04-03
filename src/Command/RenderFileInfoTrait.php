<?php

namespace App\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Uploadcare\Interfaces\File\FileInfoInterface;
use Uploadcare\Interfaces\File\ImageInfoInterface;
use Uploadcare\Interfaces\File\VideoInfoInterface;

trait RenderFileInfoTrait
{
    /**
     * Show File info.
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
                ($dpi = $imageInfo->getDpi()) !== null ? \implode('×', $dpi) : 'No data',
            ],
        ]);
    }

    /**
     * Show Video info.
     */
    protected function renderVideoInfo(VideoInfoInterface $videoInfo, SymfonyStyle $io): void
    {
        $io->title('Video Info');

        $io->table(['Property', 'Value'], [
            ['Duration', $videoInfo->getDuration()],
            ['Format', $videoInfo->getFormat()],
            ['Bitrate', $videoInfo->getBitrate()],
            ['Width', $videoInfo->getVideo()->getWidth()],
            ['Height', $videoInfo->getVideo()->getHeight()],
            ['Frame rate', $videoInfo->getVideo()->getFrameRate()],
            ['Codec', $videoInfo->getVideo()->getCodec()],
        ]);
    }
}
