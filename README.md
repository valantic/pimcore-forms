# Pimcore Forms by valantic

[![Latest Version on Packagist](https://img.shields.io/packagist/v/valantic/pimcore-forms.svg?style=flat-square)](https://packagist.org/packages/valantic/pimcore-forms)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![PHP Checks](https://github.com/valantic/pimcore-forms/actions/workflows/php.yml/badge.svg)](https://github.com/valantic/pimcore-forms/actions/workflows/php.yml)

**NO support is provided!**

This package is developed by [valantic CEC Schweiz](https://www.valantic.com/en/services/digital-business/) and is under active development.

## Setup

```
composer require valantic/pimcore-forms
```

Then, activate the bundle in the Pimcore Admin UI.

## Usage

### Configuration: `app/config/forms.yml`

```yaml
valantic_pimcore_forms:
  forms:
    contact:
      outputs:
        mail:
          type: email
          options:
            to: info@example.com
            document: /system/emails/
        pimcore_object:
          type: data_object
          options:
            class: ContactFormSubmission
            path: '/Forms'
      fields:
        name:
          type: TextType
          options:
            label: Name
          constraints:
            - NotBlank
        email:
          type: EmailType
          options:
            label: Email
          constraints:
            - NotBlank
            - Email
        message:
          type: TextareaType
          options:
            label: Message
          constraints:
            - NotBlank
            - Length:
                min: 20
        submit:
          type: SubmitType
```

### Areabrick

An Areabrick is provided for use in CMS documents.

### Controller + Twig

#### Action

```php
public function contactAction(\Valantic\PimcoreFormsBundle\Service\FormService $formService): \Symfony\Component\HttpFoundation\Response
{
    return $this->render('contact_form.html.twig', [
        'form' => $formService->buildForm('contact')->createView(),
    ]);
}
```

#### Twig

```twig
{% include '@ValanticPimcoreForms/form.html.twig' %}
```

### Twig (HTML)

```twig
{% include '@ValanticPimcoreForms/form.html.twig' with {'form': valantic_form_html('contact')} %}
```

### Twig (JSON)

```twig
{% include '@ValanticPimcoreForms/form.html.twig' with {'form': valantic_form_json('contact')} %}
```

## License

In order to comply with [Pimcore's updated licensing policy](https://pimcore.com/en/resources/blog/breaking-free-pimcore-says-goodbye-to-gpl-and-enters-a-new-era-with-pocl), this bundle is (now) published under the GPLv3 license for compatibility Pimcore Platform Version 2024.4 and will be re-licensed under the POCL license as soon as it is compatible with Pimcore Platform Version 2025.1.

If you have any questiosn regarding licensing, please reach out to us at [info@cec.valantic.ch](mailto:info@cec.valantic.ch).
