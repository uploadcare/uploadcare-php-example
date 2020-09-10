<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        $group = $this->api->group()->groupInfo($uuid);
        $filesInfo = $this->api->uploader()->groupInfo($uuid);

        return $this->render('groups/info.html.twig', [
            'group' => $group,
            'files' => $filesInfo,
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
}
