<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;

#[Route(path: '/upload-from-url', name: 'upload_from_url')]
class UploadFromUrlController extends AbstractController
{
    public function __construct(private Api $api)
    {
    }

    public function __invoke(Request $request): Response
    {
        $data = [
            'url' => null,
            'filename' => null,
        ];
        $form = $this->createFormBuilder($data)
            ->add('url', TextType::class, [
                'label' => 'File URL',
                'required' => true,
            ])
            ->add('filename', TextType::class, ['required' => false])
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $fileInfo = $this->api->uploader()->fromUrl(url: $form->get('url')->getData(), filename: $form->get('filename')->getData());
            } catch (\Throwable $e) {
                throw new BadRequestHttpException($e->getMessage());
            }

            $this->addFlash('success', \sprintf('File uploaded successfully and gets a %s uuid', $fileInfo->getUuid()));

            return $this->redirectToRoute('file_info', ['uuid' => $fileInfo->getUuid()]);
        }

        return $this->render('upload/from-url.html.twig', [
            'list' => $this->api->file()->listFiles(),
            'form' => $form->createView(),
        ]);
    }
}
