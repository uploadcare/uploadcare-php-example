<?php declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Uploadcare\File\Tags;

/**
 * Comma-separated text input for a list of file tags.
 *
 * @see https://uploadcare.com/docs/file-tags/
 */
class TagsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            static fn (?array $tags): string => \implode(', ', $tags ?? []),
            static fn (?string $value): array => \array_values(\array_filter(\array_map('trim', \explode(',', (string) $value)), static fn (string $tag): bool => $tag !== '')),
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'help' => 'Comma-separated. Latin letters, digits, hyphen, underscore and dot only.',
            'attr' => ['placeholder' => 'cat, animal, cute'],
            'constraints' => [
                new Assert\Count(max: Tags::MAX_TAGS),
                new Assert\All([
                    new Assert\Length(max: Tags::MAX_TAG_LENGTH),
                    new Assert\Regex(pattern: '/^[A-Za-z0-9._\-]+$/', message: 'Tag {{ value }} contains unsupported characters.'),
                ]),
            ],
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
