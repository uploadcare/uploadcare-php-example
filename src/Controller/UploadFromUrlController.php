<?php declare(strict_types=1);

namespace App\Controller;

use App\DTO\UrlFileUpload;
use App\Form\UploadFromUrlType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;

#[Route(path: '/upload-from-url', name: 'upload_from_url')]
class UploadFromUrlController extends AbstractController
{
    public function __construct(readonly private Api $api)
    {
    }

    public function __invoke(Request $request): Response
    {
        $data = new UrlFileUpload();
        $form = $this->createForm(UploadFromUrlType::class, $data);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $metadata = [];
            foreach ($data->getMetadata() as $metaItem) {
                $metadata[$metaItem->getKey()] = $metaItem->getValue();
            }
            if ($data->isCheckForDuplicates()) {
                $metadata = ['checkDuplicates' => true];
            }

            try {
                $token = $this->api->uploader()->fromUrl(
                    url: $data->getUrl(),
                    filename: $data->getFilename(),
                    metadata: $metadata,
                );
            } catch (\Throwable $e) {
                throw new BadRequestHttpException($e->getMessage());
            }

            $this->addFlash('success', \sprintf('Process started. Id token is %s', $token));

            return $this->redirectToRoute('upload_check_status', ['token' => $token]);
        }

        return $this->render('upload/from-url.html.twig', [
            'list' => $this->api->file()->listFiles(),
            'form' => $form->createView(),
        ]);
    }
}
