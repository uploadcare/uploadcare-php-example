<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;

#[Route(path: '/remove-background/{uuid}', name: 'remove_background')]
class RemoveBackgroundController extends AbstractController
{
    public function __construct(readonly private Api $api, readonly private string $publicKey)
    {
    }

    public function __invoke(string $uuid): Response
    {
        if ($this->publicKey === 'demopublickey') {
            return $this->render('file_info/forbidden.html.twig');
        }

        return $this->redirectToRoute('remove_bg_status', [
            'token' => $this->api->addons()->requestRemoveBackground($uuid),
        ]);
    }
}
