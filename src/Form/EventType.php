<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, [
                'attr' => ['rows' => 6],
            ])
            ->add('category', TextType::class, [
                'help' => 'Example: Concert, Sports, Theater, Conference',
            ])
            ->add('startsAt', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('timezone', TextType::class, [
                'help' => 'Example: UTC, Asia/Karachi',
            ])
            ->add('isOnline', CheckboxType::class, [
                'required' => false,
            ])
            ->add('venueName', TextType::class, [
                'required' => false,
            ])
            ->add('venueAddress', TextType::class, [
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Draft' => Event::STATUS_DRAFT,
                    'Published' => Event::STATUS_PUBLISHED,
                    'Postponed' => Event::STATUS_POSTPONED,
                    'Completed' => Event::STATUS_COMPLETED,
                    'Cancelled' => Event::STATUS_CANCELLED,
                ],
            ])
            ->add('bannerUpload', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Please upload a valid image (jpg, png, webp).',
                    ),
                ],
                'help' => 'Optional: jpg/png/webp',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}

