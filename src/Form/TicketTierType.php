<?php

namespace App\Form;

use App\Entity\TicketTier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketTierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('basePrice', IntegerType::class, [
                'help' => 'Credits (base). Final price uses 1% system fee at checkout.',
            ])
            ->add('totalSeats', IntegerType::class)
            ->add('saleStartsAt', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('saleEndsAt', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TicketTier::class,
        ]);
    }
}

