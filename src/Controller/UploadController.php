<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\{Extension\Core\Type\FileType, Extension\Core\Type\TextType};
use Symfony\Component\HttpFoundation\{File\UploadedFile, Request, Response};
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;
use Uploadcare\Api;
use Uploadcare\Interfaces\File\{CollectionInterface, FileInfoInterface};

/**
 * @Route(path="/upload", name="upload_file")
 */
class UploadController extends AbstractController
{
    /**
     * @var Api
     */
    private Api $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function __invoke(Request $request): Response
    {
        $data = [
            'file' => null,
            'mime-type' => null,
            'filename' => null,
            'store' => 'auto',
        ];
        $form = $this->createFormBuilder($data)
            ->add('file', FileType::class, [
                'label' => 'File for upload',
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                    ]),
                ],
            ])
            ->add('mime-type', TextType::class, ['required' => false])
            ->add('filename', TextType::class, ['required' => false])
            ->add('store', TextType::class, ['required' => false])
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            if (!$file instanceof UploadedFile) {
                throw new BadRequestHttpException('Unable to upload file');
            }
            $fileName = $form->get('filename')->getData();
            if ($fileName === null) {
                $fileName = \pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME).'.'.$file->guessClientExtension();
            }

            try {
                $fileInfo = $this->api->uploader()->fromPath($file->getPathname(), $form->get('mime-type')->getData(), $fileName, $form->get('store')->getData());
            } catch (\Exception $e) {
                throw new BadRequestHttpException($e->getMessage());
            }

            $this->addFlash('success', \sprintf('File uploaded successfully and gets a %s uuid', $fileInfo->getUuid()));

            return $this->redirectToRoute('upload_file');
        }

        return $this->render('upload/index.html.twig', [
            'files' => $this->getFiles(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @return CollectionInterface|FileInfoInterface[]
     */
    protected function getFiles(): CollectionInterface
    {
        return $this->api->file()->listFiles()->getResults();
    }
}
