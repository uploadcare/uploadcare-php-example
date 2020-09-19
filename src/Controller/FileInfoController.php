<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;

class FileInfoController extends AbstractController
{
    private Api $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @Route(path="/file-list", name="file_list")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function list(Request $request): Response
    {
        $parameters = [
            'limit' => $request->get('limit', 100),
            'orderBy' => $request->get('orderBy', 'datetime_uploaded'),
            'from' => $request->get('from', null),
        ];

        return $this->render('file_info/index.html.twig', [
            'list' => $this->api->file()->listFiles(...\array_values($parameters)),
            'project' => $this->api->project()->getProjectInfo(),
        ]);
    }

    /**
     * @Route(path="/file-info/{uuid<.+>?}", name="file_info")
     *
     * @param string $uuid
     *
     * @return Response
     */
    public function show(string $uuid): Response
    {
        if (!\uuid_is_valid($uuid)) {
            throw new BadRequestHttpException(\sprintf('Uuid \'%s\' not valid', $uuid));
        }

        try {
            $file = $this->api->file()->fileInfo($uuid);
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return $this->render('file_info/info.html.twig', ['file' => $file]);
    }
}
