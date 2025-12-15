<?php

namespace App\Form;

use App\Entity\Album;
use App\Entity\Photo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class BookingCompleteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('album', EntityType::class, [
                'class' => Album::class,
                'choices' => $options['albums'],
                'choice_label' => 'title',
                'required' => false,
                'placeholder' => '— Select an album (optional) —',
                'label' => 'Attach Album',
            ])
            ->add('photos', EntityType::class, [
                'class' => Photo::class,
                'choices' => $options['photos'],
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'label' => 'Attach Photos',
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $album = $form->get('album')->getData();
            $photos = $form->get('photos')->getData();

            if (empty($album) && (empty($photos) || count($photos) === 0)) {
                $form->addError(new FormError('You must attach at least one album or one photo to complete the booking.'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'albums' => [],
            'photos' => [],
        ]);

        $resolver->setAllowedTypes('albums', 'array');
        $resolver->setAllowedTypes('photos', 'array');
    }
}
