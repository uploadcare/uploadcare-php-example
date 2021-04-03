<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;
use Uploadcare\Exception\HttpException;
use Uploadcare\File;

#[Route(path: '/delete/{uuid<.+>}', name: 'delete_file', methods: ['POST'])]
class DeleteFileController extends AbstractController
{
    private Api $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @param string $uuid file ID
     */
    public function __invoke(string $uuid): Response
    {
        if (!\uuid_is_valid($uuid)) {
            throw new BadRequestHttpException(\sprintf('Id string \'%s\' not valid', $uuid));
        }
        try {
            $file = $this->api->file()->fileInfo($uuid);
        } catch (HttpException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if (!$file instanceof File) {
            $this->api->file()->deleteFile($file);
        } else {
            $file->delete();
        }

        $this->addFlash('success', 'File deleted');

        return $this->redirectToRoute('file_list');
    }
}
