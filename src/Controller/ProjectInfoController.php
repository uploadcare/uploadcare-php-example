<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;

/**
 * @Route(path="/", name="project_info")
 */
class ProjectInfoController extends AbstractController
{
    private Api $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function __invoke(): Response
    {
        $info = $this->api->project()->getProjectInfo();

        return $this->render('project/index.html.twig', ['project' => $info]);
    }
}
