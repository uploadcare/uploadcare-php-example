<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;

#[Route(path: '/recognize-objects/{uuid}', name: 'recognize_objects')]
class RecognizeObjectsController extends AbstractController
{
    public function __construct(readonly private Api $api, readonly private string $publicKey)
    {
    }

    public function __invoke(string $uuid): Response
    {
        if ($this->publicKey === 'demopublickey') {
            return $this->render('file_info/forbidden.html.twig');
        }

        $token = $this->api->addons()->requestAwsRecognition($uuid);

        return $this->redirectToRoute('recognition_check_status', ['token' => $token]);
    }
}
