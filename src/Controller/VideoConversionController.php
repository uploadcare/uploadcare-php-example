<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Uploadcare\Api;
use Uploadcare\Conversion\VideoEncodingRequest;
use Uploadcare\Interfaces\Conversion\ConvertedItemInterface;

class VideoConversionController extends AbstractController
{
    private Api $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    #[Route(path: '/convert-video/{videoId<.+>?}', name: 'video_convert_request')]
    public function conversionRequest(string $videoId, Request $request): Response
    {
        $conversionRequest = new VideoEncodingRequest();
        $file = $this->api->file()->fileInfo($videoId);

        $form = $this->createFormBuilder($conversionRequest)
            ->add('horizontalSize', NumberType::class, ['required' => false])
            ->add('verticalSize', NumberType::class, ['required' => false])
            ->add('resizeMode', ChoiceType::class, [
                'choices' => $this->getResizeModes(),
            ])
            ->add('quality', ChoiceType::class, [
                'choices' => $this->getQualities(),
            ])
            ->add('targetFormat', TextType::class)
            ->add('startTime', TextType::class, ['required' => false])
            ->add('endTime', TextType::class, ['required' => false])
            ->add('thumbs', NumberType::class)
            ->add('throwError', CheckboxType::class, ['required' => false])
            ->add('store', CheckboxType::class, ['required' => false])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->api->conversion()->convertVideo($file, $form->getData());
            if ($result instanceof ConvertedItemInterface) {
                return $this->redirectToRoute('video_conversion_result', ['token' => $result->getToken()]);
            }

            return $this->render('video_conversion/conversion_problem.html.twig', [
                'result' => $result,
            ]);
        }

        return $this->render('video_conversion/conversion_request.html.twig', [
            'file' => $file,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: "/convert-video-result/{token<\d+>}", name: 'video_conversion_result')]
    public function conversionResult(int $token): Response
    {
        $result = $this->api->conversion()->videoJobStatus($token);

        return $this->render('video_conversion/conversion_result.html.twig', [
            'result' => $result,
        ]);
    }

    private function getQualities(): array
    {
        $values = ['normal', 'better', 'best', 'lighter', 'lightest'];
        $labels = \array_map(static fn (string $value) => \ucfirst($value), $values);

        return \array_combine($labels, $values);
    }

    private function getResizeModes(): array
    {
        $values = ['preserve_ratio', 'change_ratio', 'scale_crop', 'add_padding'];
        $labels = \array_map(static fn (string $mode) => \ucfirst(\str_ireplace('_', ' ', $mode)), $values);

        return \array_combine($labels, $values);
    }
}
