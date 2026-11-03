<?php declare(strict_types=1);

namespace App\Form;

use App\DTO\FileSearch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, ChoiceType, DateType, IntegerType, TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Uploadcare\Interfaces\Api\SearchApiInterface;

class FileSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $date = ['required' => false, 'widget' => 'single_text', 'input' => 'datetime_immutable'];

        $builder
            ->add('query', TextType::class, ['required' => false, 'label' => 'Full-text query', 'help' => 'Filename, metadata and MIME type at once. At least 4 characters.'])
            ->add('filename', TextType::class, ['required' => false, 'label' => 'Filename contains', 'help' => 'At least 4 characters.'])
            ->add('mimeType', TextType::class, ['required' => false, 'label' => 'Exact MIME type', 'attr' => ['placeholder' => 'image/jpeg']])
            ->add('tagsAny', TagsType::class, ['label' => 'Has any of tags'])
            ->add('tagsAll', TagsType::class, ['label' => 'Has all of tags'])
            ->add('tagsNone', TagsType::class, ['label' => 'Has none of tags'])
            ->add('isImage', ChoiceType::class, [
                'choices' => ['Images only' => true, 'Everything but images' => false],
                'choice_value' => static fn (?bool $isImage): string => $isImage === null ? '' : ($isImage ? '1' : '0'),
                'required' => false,
                'placeholder' => 'Any file',
                'label' => 'File type',
            ])
            ->add('uploadedFrom', DateType::class, $date + ['label' => 'Uploaded from'])
            ->add('uploadedTo', DateType::class, $date + ['label' => 'Uploaded before'])
            ->add('sizeFrom', IntegerType::class, ['required' => false, 'label' => 'Size from, bytes'])
            ->add('sizeTo', IntegerType::class, ['required' => false, 'label' => 'Size up to, bytes'])
            ->add('sort', ChoiceType::class, [
                'choices' => [
                    'Newest first' => '-datetime_uploaded',
                    'Oldest first' => 'datetime_uploaded',
                    'Largest first' => '-size',
                    'Smallest first' => 'size',
                    'Filename A–Z' => 'original_filename',
                    'Filename Z–A' => '-original_filename',
                ],
                'required' => false,
                'placeholder' => 'Relevance',
            ])
            ->add('fuzziness', CheckboxType::class, ['required' => false, 'label' => 'Typo-tolerant (slower)'])
            ->add('limit', IntegerType::class, ['label' => 'Files per page', 'empty_data' => (string) SearchApiInterface::DEFAULT_LIMIT])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FileSearch::class,
            'method' => 'GET',
            'csrf_protection' => false,
            // The page offset is passed next to the form fields
            'allow_extra_fields' => true,
        ]);
    }

    /**
     * Keep query string short: `?query=…&tagsAny=…` instead of `?file_search[query]=…`.
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}
