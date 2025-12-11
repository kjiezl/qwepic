<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;
        /** @var User|null $user */
        $user = $builder->getData();

        $passwordConstraints = [
            new Length([
                'min' => 6,
                'minMessage' => 'Password should be at least {{ limit }} characters',
                'max' => 4096,
            ]),
        ];

        if (!$isEdit) {
            array_unshift($passwordConstraints, new NotBlank([
                'message' => 'Please enter a password',
            ]));
        }

        $builder
            ->add('username', null, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a username',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Username should be at least {{ limit }} characters long',
                        'max' => 50,
                        'maxMessage' => 'Username cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('email', null, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter an email address',
                    ]),
                    new Email([
                        'message' => 'Please enter a valid email address',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => !$isEdit,
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
                'constraints' => $passwordConstraints,
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
