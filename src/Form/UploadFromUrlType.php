<?php declare(strict_types=1);

namespace App\Form;

use App\DTO\UrlFileUpload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, CollectionType, TextType, UrlType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UploadFromUrlType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('url', UrlType::class, ['required' => true])
            ->add('filename', TextType::class, ['required' => false])
            ->add('checkForDuplicates', CheckboxType::class, ['required' => false])
            ->add('metadata', CollectionType::class, [
                'entry_type' => MetadataType::class,
                'allow_add' => true,
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => UrlFileUpload::class]);
    }
}
