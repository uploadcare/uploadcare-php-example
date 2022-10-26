<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;

class GroupController extends AbstractController
{
    public function __construct(readonly private Api $api)
    {
    }

    #[Route(path: '/groups', name: 'groups_list')]
    public function index(): Response
    {
        return $this->render('groups/index.html.twig', [
            'groups' => $this->api->group()->listGroups(100, false),
        ]);
    }

    #[Route(path: '/group-info/{uuid<.+>?}', name: 'group_info')]
    public function info(string $uuid): Response
    {
        return $this->render('groups/info.html.twig', [
            'group' => $this->api->group()->groupInfo($uuid),
        ]);
    }

    #[Route(path: '/group-store/{uuid<.+>?}', name: 'group_store')]
    public function storeGroup(string $uuid): Response
    {
        $this->api->group()->storeGroup($uuid);

        return $this->redirectToRoute('group_info', ['uuid' => $uuid]);
    }

    #[Route(path: '/group-create', name: 'group_create')]
    public function createGroup(Request $request): Response
    {
        $data = ['files' => []];
        $form = $this->createFormBuilder($data)
            ->add('files', ChoiceType::class, [
                'choices' => $this->api->file()->listFiles(orderBy: '-datetime_uploaded')->getResults(),
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'originalFilename',
            ])->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form->getData()['files'] ?? [];

            if (empty($files)) {
                $this->addFlash('danger', 'You not sent any files');

                return $this->redirectToRoute('group_create');
            }
            $group = $this->api->group()->createGroup($files);

            return $this->redirectToRoute('group_info', ['uuid' => $group->getId()]);
        }

        return $this->render('groups/create_new.html.twig', ['form' => $form->createView()]);
    }
}
