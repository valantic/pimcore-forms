# Extension Examples

This directory contains practical examples demonstrating how to extend the Pimcore Forms bundle with custom functionality. Each example includes a working implementation and comprehensive test coverage.

## Available Examples

### 1. Custom Output Handler - Slack Notifications

**Location:** `CustomOutput/SlackNotificationOutput.php`

Sends form submissions to a Slack channel via webhook.

**Use Case:** Real-time notifications of form submissions to your team's Slack workspace.

**Implementation:**
```php
<?php

namespace App\Form\Output;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Valantic\PimcoreForms\Output\OutputInterface;
use Valantic\PimcoreForms\Output\OutputStatus;

class SlackNotificationOutput implements OutputInterface
{
    public function __construct(private HttpClientInterface $httpClient) {}

    public function execute(\Symfony\Component\Form\FormInterface $form, array $config): OutputStatus
    {
        // Send to Slack webhook
    }
}
```

**Service Registration:**
```yaml
services:
  app.output.slack:
    class: App\Form\Output\SlackNotificationOutput
    arguments:
      - '@http_client'
    tags:
      - { name: 'valantic.pimcore_forms.output', key: 'slack' }
```

**Configuration:**
```yaml
valantic_pimcore_forms:
  forms:
    contact:
      outputs:
        - type: slack
          webhookUrl: 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL'
          channel: '#notifications'
          username: 'Form Bot'
          icon: ':robot_face:'
```

**Features:**
- Formatted messages with all form fields
- Configurable channel, username, and icon
- Error handling and timeout protection
- Humanized field names

---

### 2. Custom Choice Provider - Database Options

**Location:** `CustomChoiceProvider/DatabaseChoiceProvider.php`

Loads dropdown options dynamically from a database table.

**Use Case:** Dropdowns that need to be populated from external data (departments, countries, products, etc.).

**Implementation:**
```php
<?php

namespace App\Form\Choices;

use Doctrine\DBAL\Connection;
use Valantic\PimcoreForms\Choices\ChoicesInterface;

class DatabaseChoiceProvider implements ChoicesInterface
{
    public function __construct(
        private Connection $connection,
        private string $table,
        private string $valueColumn,
        private string $labelColumn,
    ) {}

    public function getChoices(): array
    {
        // Query database and return choices
    }
}
```

**Service Registration:**
```yaml
services:
  database_departments:
    class: App\Form\Choices\DatabaseChoiceProvider
    arguments:
      - '@doctrine.dbal.default_connection'
      - 'departments'  # table name
      - 'id'           # value column
      - 'name'         # label column
    tags:
      - { name: 'valantic.pimcore_forms.choices', key: 'database_departments' }
```

**Configuration:**
```yaml
valantic_pimcore_forms:
  forms:
    contact:
      fields:
        - name: department
          type: choice
          options:
            choices: '@database_departments'
```

**Features:**
- Configurable table and columns
- Custom sort order
- Error handling (returns empty on failure)
- Proper Symfony choice format

---

### 3. Custom Input Handler - Query String Pre-population

**Location:** `CustomInputHandler/QueryStringInputHandler.php`

Pre-fills form fields from URL query parameters.

**Use Case:** Marketing campaigns, referral tracking, or pre-filling forms from email links.

**Implementation:**
```php
<?php

namespace App\Form\InputHandler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Valantic\PimcoreForms\InputHandler\InputHandlerInterface;

class QueryStringInputHandler implements InputHandlerInterface
{
    public function handle(Request $request, FormInterface $form, array $config): void
    {
        // Map query params to form fields
    }
}
```

**Service Registration:**
```yaml
services:
  app.input_handler.query_string:
    class: App\Form\InputHandler\QueryStringInputHandler
    tags:
      - { name: 'valantic.pimcore_forms.input_handler', key: 'query_string' }
```

**Configuration:**
```yaml
valantic_pimcore_forms:
  forms:
    contact:
      inputHandlers:
        - type: query_string
          mapping:
            utm_source: source
            utm_campaign: campaign
            ref: referralCode
```

**Example URL:**
```
/contact?utm_source=newsletter&utm_campaign=spring2024&ref=FRIEND123
```

**Features:**
- Flexible parameter mapping
- Merges with existing form data
- Skips non-existent fields
- Only processes mapped parameters

---

### 4. Custom Redirect Handler - Conditional Redirects

**Location:** `CustomRedirectHandler/ConditionalRedirectHandler.php`

Redirects to different URLs based on form data or submission status.

**Use Case:** Multi-path workflows where different form values lead to different thank-you pages.

**Implementation:**
```php
<?php

namespace App\Form\RedirectHandler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Valantic\PimcoreForms\RedirectHandler\RedirectHandlerInterface;

class ConditionalRedirectHandler implements RedirectHandlerInterface
{
    public function getRedirectUrl(
        Request $request,
        FormInterface $form,
        bool $success,
        array $config
    ): ?string {
        // Check conditions and return appropriate URL
    }
}
```

**Service Registration:**
```yaml
services:
  app.redirect_handler.conditional:
    class: App\Form\RedirectHandler\ConditionalRedirectHandler
    tags:
      - { name: 'valantic.pimcore_forms.redirect_handler', key: 'conditional' }
```

**Configuration:**
```yaml
valantic_pimcore_forms:
  forms:
    contact:
      redirectHandlers:
        - type: conditional
          conditions:
            - field: inquiry_type
              value: sales
              url: /thank-you/sales
            - field: inquiry_type
              value: support
              url: /thank-you/support
          defaultUrl: /thank-you
          errorUrl: /error
```

**Features:**
- Multiple condition support
- Case-insensitive string matching
- Array value support (multi-choice)
- Default and error URLs
- Success/failure handling

---

## Running Example Tests

All examples include comprehensive test coverage. Run the tests to verify functionality:

```bash
# Run all example tests
ddev exec vendor/bin/phpunit tests/Examples

# Run specific example tests
ddev exec vendor/bin/phpunit tests/Examples/CustomOutput/SlackNotificationOutputTest.php
ddev exec vendor/bin/phpunit tests/Examples/CustomChoiceProvider/DatabaseChoiceProviderTest.php
ddev exec vendor/bin/phpunit tests/Examples/CustomInputHandler/QueryStringInputHandlerTest.php
ddev exec vendor/bin/phpunit tests/Examples/CustomRedirectHandler/ConditionalRedirectHandlerTest.php
```

---

## Creating Your Own Extensions

### General Pattern

1. **Implement the appropriate interface:**
   - `OutputInterface` for output handlers
   - `ChoicesInterface` for choice providers
   - `InputHandlerInterface` for input handlers
   - `RedirectHandlerInterface` for redirect handlers

2. **Register your service with the appropriate tag:**
   - `valantic.pimcore_forms.output`
   - `valantic.pimcore_forms.choices`
   - `valantic.pimcore_forms.input_handler`
   - `valantic.pimcore_forms.redirect_handler`

3. **Use in form configuration** via the `type` or service reference.

### Interface Reference

#### OutputInterface
```php
interface OutputInterface
{
    public function execute(FormInterface $form, array $config): OutputStatus;
}
```

#### ChoicesInterface
```php
interface ChoicesInterface
{
    public function getChoices(): array;
}
```

#### InputHandlerInterface
```php
interface InputHandlerInterface
{
    public function handle(Request $request, FormInterface $form, array $config): void;
}
```

#### RedirectHandlerInterface
```php
interface RedirectHandlerInterface
{
    public function getRedirectUrl(
        Request $request,
        FormInterface $form,
        bool $success,
        array $config
    ): ?string;
}
```

---

## Best Practices

### Error Handling
- Always wrap external calls (HTTP, database, etc.) in try-catch blocks
- Return meaningful error messages
- Use logging for debugging production issues

### Testing
- Mock external dependencies (HTTP clients, database connections)
- Test both success and failure scenarios
- Verify edge cases (empty data, missing config, etc.)

### Configuration
- Validate required config parameters
- Provide sensible defaults
- Document all available options

### Performance
- Cache expensive operations when possible
- Use timeouts for external API calls
- Consider async processing for slow operations

---

## Additional Resources

- [Bundle Documentation](../../README.md)
- [Service Configuration](../../src/DependencyInjection/)
- [Test Examples](../../tests/)
- [Main Source Code](../../src/)

---

## Contributing

If you create a useful extension, consider contributing it back to the project! Please follow the existing code style and include comprehensive tests.
