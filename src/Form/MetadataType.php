<?php declare(strict_types=1);

namespace App\Form;

use App\DTO\Metadata;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MetadataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('key', TextType::class, [
            'required' => true,
            'label' => false,
            'attr' => ['placeholder' => 'Key', 'class' => 'form-control'],
            'row_attr' => ['class' => 'w-50'],
        ])->add('value', TextType::class, [
            'required' => true,
            'label' => false,
            'attr' => ['placeholder' => 'Value', 'class' => 'form-control'],
            'row_attr' => ['class' => 'w-50'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Metadata::class,
            'attr' => ['class' => 'input-group mb-3'],
        ]);
    }
}
