<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Form\Type;

use Barion\Enumerations\FundingSourceType;
use Sylius\Bundle\PaymentBundle\Attribute\AsGatewayConfigurationType;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsGatewayConfigurationType(type: 'barion_payment', label: 'Barion Payment Gateway')]
#[AsTaggedItem('form.type')]
final class GatewayConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pos_key', TextType::class, [
                'label' => 'POS key',
                'required' => true,
                'help' => 'Secret POSKey from the Barion merchant portal.',
            ])
            ->add('payee', EmailType::class, [
                'label' => 'Payee email',
                'required' => true,
                'help' => 'Barion wallet email that receives the payment.',
            ])
            ->add('env', ChoiceType::class, [
                'label' => 'Environment',
                'required' => true,
                'choices' => [
                    'Test (sandbox)' => 'test',
                    'Production' => 'prod',
                ],
            ])
            ->add('payment_type', ChoiceType::class, [
                'label' => 'Payment type',
                'required' => true,
                'choices' => [
                    'Immediate' => 'immediate',
                    'Delayed capture' => 'delayed_capture',
                    'Reservation' => 'reservation',
                ],
            ])
            ->add('funding_sources', ChoiceType::class, [
                'label' => 'Allowed funding sources',
                'required' => true,
                'multiple' => true,
                'choices' => [
                    'All' => FundingSourceType::All->value,
                    'Bank card' => FundingSourceType::Bankcard->value,
                    'Barion wallet' => FundingSourceType::Balance->value,
                    'Bank transfer' => FundingSourceType::BankTransfer->value,
                    'Apple Pay' => FundingSourceType::ApplePay->value,
                    'Google Pay' => FundingSourceType::GooglePay->value,
                ],
            ])
            ->add('payment_window', TextType::class, [
                'label' => 'Payment window',
                'required' => false,
                'help' => 'ISO-8601 duration, e.g. 00:30:00.',
            ])
            ->add('delayed_capture_period', TextType::class, [
                'label' => 'Delayed capture period',
                'required' => false,
                'help' => 'ISO-8601 period, e.g. P7D.',
            ])
            ->add('reservation_period', TextType::class, [
                'label' => 'Reservation period',
                'required' => false,
                'help' => 'ISO-8601 period, e.g. P7D.',
            ])
            ->add('initiate_recurrence', CheckboxType::class, [
                'label' => 'Initiate recurring payment',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'Barion Payment Gateway Configuration',
            'auto_initialize' => true,
        ]);
    }
}
