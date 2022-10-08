<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;

class CheckStatusController extends AbstractController
{
    public function __construct(readonly private Api $api)
    {
    }

    #[Route(path: '/check-upload/status/{token}', name: 'upload_check_status')]
    public function checkUploadStatus(string $token): Response
    {
        $status = $this->api->uploader()->checkStatus($token);
        $css = match ($status) {
            'waiting' => 'secondary',
            'progress' => 'info',
            'success' => 'success',
            'error' => 'danger',
            default => 'dark',
        };

        return $this->render('upload/file-status.html.twig', [
            'status' => $status,
            'alert' => $this->getCss($status),
        ]);
    }

    #[Route(path: '/check-aws/status/{token}', name: 'recognition_check_status')]
    public function checkRecognitionStatus(string $token): Response
    {
        $status = $this->api->addons()->checkAwsRecognition($token);

        return $this->render('upload/file-status.html.twig', [
            'status' => $status,
            'alert' => $this->getCss($status),
        ]);
    }

    #[Route(path: '/check-remove-background/status/{token}', name: 'remove_bg_status')]
    public function checkRemoveBgStatus(string $token): Response
    {
        $status = $this->api->addons()->checkRemoveBackground($token);

        return $this->render('upload/file-status.html.twig', [
            'status' => $status,
            'alert' => $this->getCss($status),
        ]);
    }

    private function getCss(string $status): string
    {
        return match ($status) {
            'in_progress' => 'info',
            'done' => 'success',
            'error' => 'danger',
            default => 'dark',
        };
    }
}
