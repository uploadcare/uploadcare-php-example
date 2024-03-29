<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;

class FileInfoController extends AbstractController
{
    public function __construct(readonly private Api $api)
    {
    }

    #[Route(path: '/file-list', name: 'file_list')]
    public function list(Request $request): Response
    {
        $parameters = [
            'limit' => $request->query->getInt('limit', 100),
            'orderBy' => $request->get('orderBy', '-datetime_uploaded'),
            'from' => $request->get('from', null),
        ];
        $response = $this->api->file()->listFiles(...\array_values($parameters));

        return $this->render('file_info/index.html.twig', [
            'list' => $response,
            'project' => $this->api->project()->getProjectInfo(),
            'next' => $this->api->file()->getPageRequestParameters($response->getNext()),
            'previous' => $this->api->file()->getPageRequestParameters($response->getPrevious()),
        ]);
    }

    #[Route(path: '/file-info/{uuid<.+>?}', name: 'file_info')]
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
