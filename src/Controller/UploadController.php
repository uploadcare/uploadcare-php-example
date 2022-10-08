<?php declare(strict_types=1);

namespace App\Controller;

use App\DTO\FileUpload;
use App\Form\FileUploadType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;
use Uploadcare\Interfaces\Response\ListResponseInterface;

#[Route(path: '/upload', name: 'upload_file')]
class UploadController extends AbstractController
{
    public function __construct(readonly private Api $api)
    {
    }

    public function __invoke(Request $request): Response
    {
        $fileUpload = new FileUpload();
        $form = $this->createForm(FileUploadType::class, $fileUpload);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            if (!$file instanceof UploadedFile) {
                throw new BadRequestHttpException('Unable to upload file');
            }
            $fileName = $form->get('filename')->getData();
            if (null === $fileName) {
                $fileName = \pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME) . '.' . $file->guessClientExtension();
            }
            $metadata = [];
            foreach ($fileUpload->getMetadata() as $metaItem) {
                $metadata[$metaItem->getKey()] = $metaItem->getValue();
            }

            try {
                $fileInfo = $this->api->uploader()->fromPath(
                    path: $file->getPathname(),
                    mimeType: $fileUpload->getMimeType(),
                    filename: $fileName,
                    store: $fileUpload->getStore(),
                    metadata: $metadata,
                );
            } catch (\Exception $e) {
                throw new BadRequestHttpException($e->getMessage());
            }

            $this->addFlash('success', \sprintf('File uploaded successfully and gets a %s uuid', $fileInfo->getUuid()));

            return $this->redirectToRoute('file_info', ['uuid' => $fileInfo->getUuid()]);
        }

        return $this->render('upload/index.html.twig', [
            'list' => $this->getFiles(),
            'form' => $form->createView(),
        ]);
    }

    protected function getFiles(): ListResponseInterface
    {
        return $this->api->file()->listFiles();
    }
}
