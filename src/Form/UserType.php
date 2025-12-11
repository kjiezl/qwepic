<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;
        /** @var User|null $user */
        $user = $builder->getData();

        $builder
            ->add('username')
            ->add('email')
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => !$isEdit,
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
            ])
            ->add('primaryRole', ChoiceType::class, [
                'choices' => [
                    'User' => 'ROLE_USER',
                    'Photographer' => 'ROLE_PHOTOGRAPHER',
                    'Admin' => 'ROLE_ADMIN',
                ],
                // Single-select: one primary role at a time
                'multiple' => false,
                'expanded' => true,
                'required' => true,
                'mapped' => false,
                'data' => $this->getPrimaryRoleFromUser($user),
            ])
        ;
    }

    private function getPrimaryRoleFromUser(?User $user): ?string
    {
        if (!$user instanceof User) {
            return 'ROLE_USER';
        }

        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'ROLE_ADMIN';
        }

        if (in_array('ROLE_PHOTOGRAPHER', $roles, true)) {
            return 'ROLE_PHOTOGRAPHER';
        }

        return 'ROLE_USER';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
