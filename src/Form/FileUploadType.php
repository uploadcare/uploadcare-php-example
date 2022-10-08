<?php declare(strict_types=1);

namespace App\Form;

use App\DTO\FileUpload;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, CollectionType, FileType, TextType};
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class FileUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('filename', TextType::class, ['required' => false])
            ->add('mimeType', TextType::class, ['required' => false])
            ->add('store', ChoiceType::class, [
                'choices' => [
                    'Auto' => 'auto',
                    'Store' => '1',
                    'Don\'t store' => '0',
                ],
                'required' => true,
            ])
            ->add('file', FileType::class, [
                'label' => 'File',
                'mapped' => false,
                'required' => true,
                'constraints' => [new File()],
            ])->add('metadata', CollectionType::class, [
                'entry_type' => MetadataType::class,
                'allow_add' => true,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => FileUpload::class]);
    }
}
