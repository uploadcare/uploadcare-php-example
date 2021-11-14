<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;
use Uploadcare\Interfaces\Response\WebhookInterface;

class WebhooksController extends AbstractController
{
    private Api $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    #[Route(path: '/webhooks', name: 'webhooks_list')]
    public function index(): Response
    {
        return $this->render('webhook/index.html.twig', [
            'data' => $this->api->webhook()->listWebhooks(),
        ]);
    }

    private function getWebhookById(int $id): ?WebhookInterface
    {
        $all = $this->api->webhook()->listWebhooks();
        $items = $all->filter(fn (WebhookInterface $webhook) => $webhook->getId() === $id);
        if (!$items->isEmpty()) {
            return $items->first() ?: null;
        }

        return null;
    }

    #[Route(path: '/webhook-create', name: 'webhook_create')]
    public function createWebhook(Request $request): Response
    {
        $data = [
            'target_url' => null,
            'is_active' => true,
            'signing_secret' => null,
        ];

        $form = $this->createFormBuilder($data)
            ->add('target_url', UrlType::class)
            ->add('is_active', CheckboxType::class, ['required' => false])
            ->add('signing_secret', TextType::class, ['required' => false])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $targetUrl = $form->get('target_url')->getData();
            $isActive = $form->get('is_active')->getData();
            $signingSecret = $form->get('signing_secret')->getData();

            $result = $this->api->webhook()->createWebhook($targetUrl, $isActive, $signingSecret);
            if ($result instanceof WebhookInterface) {
                return $this->redirectToRoute('webhooks_info', ['id' => $result->getId()]);
            }

            throw new BadRequestHttpException('Cannot create webhook');
        }

        return $this->render('webhook/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: "/webhook-info/{id<\d+>}", name: 'webhooks_info')]
    public function webhookInfo(int $id): Response
    {
        $item = $this->getWebhookById($id);
        if (!$item instanceof WebhookInterface) {
            throw new NotFoundHttpException(\sprintf('Webhook %s not found', $id));
        }

        return $this->render('webhook/info.html.twig', [
            'webhook' => $item,
        ]);
    }

    #[Route(path: "/webhook-update/{id<\d+>}", name: 'webhook_update')]
    public function updateWebhook(int $id, Request $request): Response
    {
        $item = $this->getWebhookById($id);
        if (!$item instanceof WebhookInterface) {
            throw new NotFoundHttpException(\sprintf('Webhook %s not found', $id));
        }
        $updatedData = [
            'target_url' => $item->getTargetUrl(),
            'event' => $item->getEvent(),
            'is_active' => $item->isActive(),
            'signing_secret' => $item->getSigningSecret(),
        ];

        $form = $this->createFormBuilder($updatedData)
            ->add('target_url', UrlType::class)
            ->add('event', TextType::class, ['disabled' => true])
            ->add('is_active', CheckboxType::class, ['required' => false])
            ->add('signing_secret', TextType::class, ['required' => false])
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->api->webhook()->updateWebhook($id, $data);

            return $this->redirectToRoute('webhooks_info', ['id' => $id]);
        }

        return $this->render('webhook/update.html.twig', [
            'form' => $form->createView(),
            'item' => $item,
        ]);
    }

    #[Route(path: "/delete-webhook/{id<\d+>}", name: 'delete_webhook', methods: ['POST'])]
    public function deleteWebhook(int $id): Response
    {
        $element = $this->getWebhookById($id);
        if (!$element instanceof WebhookInterface) {
            throw new NotFoundHttpException(\sprintf('Webhook %s not found', $id));
        }
        $result = $this->api->webhook()->deleteWebhook($element->getTargetUrl());
        if ($result) {
            $this->addFlash('success', \sprintf('Webhook %s successfully deleted', $id));
        } else {
            $this->addFlash('warning', \sprintf('Webhook %s IS NOT deleted', $id));
        }

        return $this->redirectToRoute('webhooks_list');
    }
}
