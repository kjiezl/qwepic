<?php

namespace App\Form;

use App\Entity\Album;
use App\Entity\Photo;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a title for this photo',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'The title should be at least {{ limit }} characters long',
                        'max' => 255,
                        'maxMessage' => 'The title cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('caption', null, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'The caption cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Photo file',
                'mapped' => false,
                'required' => !($options['is_edit'] ?? false),
                'constraints' => [
                    new Image([
                        'maxSize' => '10M',
                        'mimeTypesMessage' => 'Please upload a valid image file.',
                    ]),
                ],
            ])
            ->add('is_public')
            ->add('album', EntityType::class, (function () use ($options) {
                $fieldOptions = [
                    'class' => Album::class,
                    'choice_label' => static function (Album $album): string {
                        return $album->getTitle() ?: ('Album #'.$album->getId());
                    },
                ];

                if (isset($options['allowed_albums']) && $options['allowed_albums'] !== null) {
                    $fieldOptions['choices'] = $options['allowed_albums'];
                }

                return $fieldOptions;
            })())
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Photo::class,
            'allowed_albums' => null,
            'is_edit' => false,
        ]);
    }
}
