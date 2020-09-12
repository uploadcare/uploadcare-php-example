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
    private Api $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @Route(path="/groups", name="groups_list")
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->render('groups/index.html.twig', [
            'groups' => $this->api->group()->listGroups(),
        ]);
    }

    /**
     * @Route(path="/group-info/{uuid<.+>?}", name="group_info")
     *
     * @param string $uuid
     *
     * @return Response
     */
    public function info(string $uuid): Response
    {
        return $this->render('groups/info.html.twig', [
            'group' => $this->api->group()->groupInfo($uuid),
        ]);
    }

    /**
     * @Route(path="/group-store/{uuid<.+>?}", name="group_store")
     *
     * @param string $uuid
     *
     * @return Response
     */
    public function storeGroup(string $uuid): Response
    {
        $this->api->group()->storeGroup($uuid);

        return $this->redirectToRoute('group_info', ['uuid' => $uuid]);
    }

    /**
     * @Route(path="/group-create", name="group_create")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createGroup(Request $request): Response
    {
        $data = ['files' => []];
        $form = $this->createFormBuilder($data)
            ->add('files', ChoiceType::class, [
                'choices' => $this->api->file()->listFiles()->getResults(),
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
