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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsGatewayConfigurationType(type: 'barion_payment', label: 'sylius_barion.gateway_factory.barion_payment')]
#[AsTaggedItem('form.type')]
class GatewayConfigType extends AbstractType
{
    private const DEFAULT_ENV = 'test';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            $data = $event->getData();

            if (!\is_array($data)) {
                $data = [];
            }

            $data['env'] ??= self::DEFAULT_ENV;
            $data['payum.translator'] = '@translator';
            $event->setData($data);
        });

        $builder
            ->add('pos_key', TextType::class, [
                'label' => 'sylius_barion.form.gateway_config.pos_key',
                'required' => true,
                'help' => 'sylius_barion.form.gateway_config.pos_key_help',
            ])
            ->add('payee', EmailType::class, [
                'label' => 'sylius_barion.form.gateway_config.payee',
                'required' => true,
                'help' => 'sylius_barion.form.gateway_config.payee_help',
            ])
            ->add('env', ChoiceType::class, [
                'label' => 'sylius_barion.form.gateway_config.env',
                'required' => true,
                'choices' => [
                    'sylius_barion.form.gateway_config.env_test' => 'test',
                    'sylius_barion.form.gateway_config.env_prod' => 'prod',
                ],
            ])
            ->add('payment_type', ChoiceType::class, [
                'label' => 'sylius_barion.form.gateway_config.payment_type',
                'required' => true,
                'choices' => [
                    'sylius_barion.form.gateway_config.payment_type_immediate' => 'immediate',
                    'sylius_barion.form.gateway_config.payment_type_delayed_capture' => 'delayed_capture',
                    'sylius_barion.form.gateway_config.payment_type_reservation' => 'reservation',
                ],
            ])
            ->add('funding_sources', ChoiceType::class, [
                'label' => 'sylius_barion.form.gateway_config.funding_sources',
                'required' => true,
                'multiple' => true,
                'choices' => [
                    'sylius_barion.form.gateway_config.funding_source_all' => FundingSourceType::All->value,
                    'sylius_barion.form.gateway_config.funding_source_bankcard' => FundingSourceType::Bankcard->value,
                    'sylius_barion.form.gateway_config.funding_source_balance' => FundingSourceType::Balance->value,
                    'sylius_barion.form.gateway_config.funding_source_bank_transfer' => FundingSourceType::BankTransfer->value,
                    'sylius_barion.form.gateway_config.funding_source_apple_pay' => FundingSourceType::ApplePay->value,
                    'sylius_barion.form.gateway_config.funding_source_google_pay' => FundingSourceType::GooglePay->value,
                ],
            ])
            ->add('payment_window', TextType::class, [
                'label' => 'sylius_barion.form.gateway_config.payment_window',
                'required' => false,
                'help' => 'sylius_barion.form.gateway_config.payment_window_help',
            ])
            ->add('delayed_capture_period', TextType::class, [
                'label' => 'sylius_barion.form.gateway_config.delayed_capture_period',
                'required' => false,
                'help' => 'sylius_barion.form.gateway_config.delayed_capture_period_help',
            ])
            ->add('reservation_period', TextType::class, [
                'label' => 'sylius_barion.form.gateway_config.reservation_period',
                'required' => false,
                'help' => 'sylius_barion.form.gateway_config.reservation_period_help',
            ])
            ->add('initiate_recurrence', CheckboxType::class, [
                'label' => 'sylius_barion.form.gateway_config.initiate_recurrence',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'sylius_barion.form.gateway_config.label',
            'translation_domain' => 'messages',
            'choice_translation_domain' => 'messages',
            'auto_initialize' => true,
        ]);
    }
}
