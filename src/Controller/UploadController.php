<?php declare(strict_types=1);


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Uploadcare\Api;

class UploadController extends AbstractController
{
    /**
     * @var Api
     */
    private Api $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function index(Request $request): Response
    {
        // TODO File form

        return new Response('Not implemented yet');
    }
}
