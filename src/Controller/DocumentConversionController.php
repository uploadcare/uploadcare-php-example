<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;
use Uploadcare\Conversion\DocumentConversionRequest;
use Uploadcare\Interfaces\Conversion\ConvertedItemInterface;
use Uploadcare\Interfaces\File\FileInfoInterface;

class DocumentConversionController extends AbstractController
{
    private Api $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @Route(path="/convert-document", name="document_conversion")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function convertDocument(Request $request): Response
    {
        $convRequest = new DocumentConversionRequest();
        $form = $this->createFormBuilder($convRequest)
            ->add('file', ChoiceType::class, [
                'mapped' => false,
                'multiple' => false,
                'choices' => $this->api->file()->listFiles()->getResults(),
                'choice_label' => 'originalFilename',
            ])
            ->add('targetFormat', ChoiceType::class, [
                'choices' => $this->formatChoices(),
            ])
            ->add('throwError', CheckboxType::class, ['required' => false])
            ->add('store', CheckboxType::class, ['required' => false])
            ->add('pageNumber', NumberType::class, ['required' => false])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            if (!$file instanceof FileInfoInterface) {
                throw new BadRequestHttpException('Unknown type of file');
            }
            $result = $this->api->conversion()->convertDocument($file, $form->getData());
            if ($result instanceof ConvertedItemInterface) {
                return $this->render('document_conversion/conversion_result.html.twig', [
                    'result' => $result,
                ]);
            }

            return $this->render('document_conversion/conversion_problem.html.twig', ['result' => $result]);
        }

        return $this->render('document_conversion/index.html.twig', ['form' => $form->createView()]);
    }

    private function formatChoices(): array
    {
        $source = ['doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'rtf', 'txt', 'pdf', 'jpg', 'png'];
        $names = \array_map(static fn (string $name) => \strtoupper($name), $source);

        return array_combine($names, $source);
    }
}
