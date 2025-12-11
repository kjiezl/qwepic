<?php

namespace App\Form;

use App\Entity\Album;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AlbumType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a title for this album',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'The title should be at least {{ limit }} characters long',
                        'max' => 255,
                        'maxMessage' => 'The title cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('description', null, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'The description cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('coverImageFile', FileType::class, [
                'label' => 'Cover image (optional)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '10M',
                        'mimeTypesMessage' => 'Please upload a valid image file.',
                    ]),
                ],
            ])
            ->add('photoFiles', FileType::class, [
                'label' => 'Album photos (optional)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    new All([
                        'constraints' => [
                            new Image([
                                'maxSize' => '10M',
                                'mimeTypesMessage' => 'Please upload valid image files.',
                            ]),
                        ],
                    ]),
                ],
            ])
            ->add('is_public')
        ;

        if ($options['show_photographer_field']) {
            $builder->add('photographer', EntityType::class, [
                'class' => User::class,
                'choice_label' => static function (User $user): string {
                    return $user->getUsername() ?? $user->getEmail() ?? ('#'.$user->getId());
                },
                'choices' => $options['photographer_choices'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Album::class,
            'show_photographer_field' => true,
            'photographer_choices' => [],
        ]);
    }
}
