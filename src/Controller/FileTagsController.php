<?php declare(strict_types=1);

namespace App\Controller;

use App\Form\TagsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;
use Uploadcare\Response\TagsResponse;

/**
 * Add, remove and replace file tags.
 *
 * @see https://uploadcare.com/docs/file-tags/
 */
#[Route(path: '/file-tags/{uuid<.+>}', name: 'file_tags')]
class FileTagsController extends AbstractController
{
    public function __construct(private readonly Api $api)
    {
    }

    public function __invoke(string $uuid, Request $request): Response
    {
        if (!\uuid_is_valid($uuid)) {
            throw new BadRequestHttpException(\sprintf('Uuid \'%s\' not valid', $uuid));
        }

        try {
            $file = $this->api->file()->fileInfo($uuid);
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        // Add and/or remove tags in a single atomic call
        $updateForm = $this->createNamedForm('update_tags', ['add' => [], 'delete' => []])
            ->add('add', TagsType::class, ['label' => 'Tags to add'])
            ->add('delete', TagsType::class, ['label' => 'Tags to remove', 'attr' => ['placeholder' => 'old-tag']])
            ->getForm();

        // Replace the whole tag set, an empty list clears all tags
        $replaceForm = $this->createNamedForm('replace_tags', ['tags' => $file->getTags()->toArray()])
            ->add('tags', TagsType::class, ['label' => 'New tag list', 'help' => 'Tags that are not in this list will be deleted. Leave it empty to clear all tags.'])
            ->getForm();

        $updateForm->handleRequest($request);
        if ($updateForm->isSubmitted() && $updateForm->isValid()) {
            return $this->tagsChanged($uuid, fn (): TagsResponse => $this->api->tags()->updateTags(
                $uuid,
                $updateForm->get('add')->getData(),
                $updateForm->get('delete')->getData(),
            ));
        }

        $replaceForm->handleRequest($request);
        if ($replaceForm->isSubmitted() && $replaceForm->isValid()) {
            return $this->tagsChanged($uuid, fn (): TagsResponse => $this->api->tags()->replaceTags(
                $uuid,
                $replaceForm->get('tags')->getData(),
            ));
        }

        return $this->render('file_tags/index.html.twig', [
            'file' => $file,
            'update_form' => $updateForm->createView(),
            'replace_form' => $replaceForm->createView(),
        ]);
    }

    /**
     * @param callable(): TagsResponse $operation
     */
    private function tagsChanged(string $uuid, callable $operation): Response
    {
        try {
            $result = $operation();
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $this->addFlash('success', \sprintf(
            'Tags saved. Added: %s. Removed: %s.',
            \implode(', ', $result->getAdded()) ?: 'none',
            \implode(', ', $result->getDeleted()) ?: 'none',
        ));

        return $this->redirectToRoute('file_tags', ['uuid' => $uuid]);
    }

    private function createNamedForm(string $name, array $data): FormBuilderInterface
    {
        return $this->container->get('form.factory')->createNamedBuilder($name, data: $data);
    }
}
