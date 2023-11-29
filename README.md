# PHP Number Utility

This library provides a set of utility functions for working with numbers in PHP, including formatting as currency, percentages, ordinals, and more.

## Installation

```bash
composer require friendsofphp/number
```

## Usage

### Basic usage

```php
use FOP\Number\Number;

// Format a number
$formattedNumber = Number::format(1234567.89);

// Spell out a number
$spelledNumber = Number::spell(1234);

// Get the ordinal form of a number
$ordinalNumber = Number::ordinal(42);

// Format a number as a percentage
$percentage = Number::percentage(0.75);

// Format a number as currency
$currency = Number::currency(1234.56, 'EUR');

// Format file size
$fileSize = Number::fileSize(1024);

// Get a human-readable representation of a number
$humanReadable = Number::forHumans(1234567.89);
```

### Advanced usage

```php
use FOP\Number\Number;

// Set a custom locale
Number::useLocale('fr');

// Use the custom locale for formatting
$formattedNumber = Number::format(1234.56);

// Change the precision when formatting
$preciseNumber = Number::format(1234.56789, 2);

// Use a custom locale for currency formatting
$currencyFormatted = Number::currency(1234.56, 'GBP', 'fr');
```

## Information

### License

This package is open-sourced software licensed under the [MIT License](LICENSE).

### Attributions

The Number utility is ported from Laravel, licensed under the MIT Licence.
