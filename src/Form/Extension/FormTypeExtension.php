<?php

namespace Valantic\PimcoreFormsBundle\Form\Extension;

use Limenius\Liform\Transformer\ExtensionInterface;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\WeekType;
use Symfony\Component\Form\FormInterface;

class FormTypeExtension implements ExtensionInterface
{
    public function apply(FormInterface $form, array $schema): array
    {
        if ($form->getConfig()->getType()->getInnerType() instanceof FormType) {
            return $schema;
        }

        $type = get_class($form->getConfig()->getType()->getInnerType());

        $formType = null;

        if ($type === ChoiceType::class) {
            // https://symfony.com/doc/current/reference/forms/types/choice.html#select-tag-checkboxes-or-radio-buttons
            $expanded = $form->getConfig()->getOption('expanded');
            $multiple = $form->getConfig()->getOption('multiple');

            if (!$expanded && !$multiple) {
                $formType = 'select.single';
            }
            if (!$expanded && $multiple) {
                $formType = 'select.multiple';
            }
            if ($expanded && !$multiple) {
                $formType = 'radio';
            }
            if ($expanded && $multiple) {
                $formType = 'checkboxes';
            }
        }

        $mapping = [
            BirthdayType::class => 'birthday',
            ButtonType::class => 'button',
            CheckboxType::class => 'checkbox',
            ChoiceType::class => 'choice',
            CollectionType::class => 'collection',
            ColorType::class => 'color',
            CountryType::class => 'country',
            CurrencyType::class => 'currency',
            DateIntervalType::class => 'dateinterval',
            DateTimeType::class => 'datetime',
            DateType::class => 'date',
            EmailType::class => 'email',
            FileType::class => 'file',
            HiddenType::class => 'hidden',
            IntegerType::class => 'integer',
            LanguageType::class => 'language',
            LocaleType::class => 'locale',
            MoneyType::class => 'money',
            NumberType::class => 'number',
            PasswordType::class => 'password',
            PercentType::class => 'percent',
            RangeType::class => 'range',
            RepeatedType::class => 'repeated',
            ResetType::class => 'button.reset',
            SearchType::class => 'search',
            SubmitType::class => 'button.submit',
            TelType::class => 'tel',
            TextType::class => 'text',
            TextareaType::class => 'textarea',
            TimeType::class => 'time',
            TimezoneType::class => 'timezone',
            UrlType::class => 'url',
            WeekType::class => 'week',
        ];

        $schema['form_type'] = $formType ?: ($mapping[$type] ?? $type);

        return $schema;
    }
}

