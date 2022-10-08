<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;

#[Route(path: '/batch-store', name: 'batch_store_file')]
class BatchStoreController extends AbstractController
{
    public function __construct(readonly private Api $api)
    {
    }

    public function __invoke(Request $request): Response
    {
        $data = ['files' => []];
        $form = $this->createFormBuilder($data)
            ->add('files', ChoiceType::class, [
                'choices' => $this->api->file()->listFiles()->getResults(),
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'originalFilename',
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form->get('files')->getData();

            $result = $this->api->file()->batchStoreFile($files);

            return $this->render('batch_store/result.html.twig', [
                'result' => $result,
            ]);
        }

        return $this->render('batch_store/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
