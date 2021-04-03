<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;

#[Route(path: '/batch-delete', name: 'batch_delete_file')]
class BatchDeleteController extends AbstractController
{
    private Api $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
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
            $result = $this->api->file()->batchDeleteFile($files);

            return $this->render('batch_delete/result.html.twig', [
                'result' => $result,
            ]);
        }

        return $this->render('batch_delete/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
