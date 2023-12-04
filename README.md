# PHP Number Utility

This library provides a set of utility functions for working with numbers in PHP, including formatting as currency, percentages, ordinals, and more.

## Installation

```bash
composer require friendsofphp/number
```

## Usage

### Basic usage

```php
use FriendsOfPhp\Number\Number;

// Format a number
echo Number::format(1234567.89); // 1,234,567.89

// Spell out a number
echo Number::format(1234567.89); // one thousand two hundred thirty-four

// Get the ordinal form of a number
echo Number::format(1234567.89); // 42nd

// Format a number as a percentage
echo Number::format(1234567.89); // 1%

// Format a number as currency
echo Number::format(1234567.89); // €1,234.56

// Format file size
echo Number::format(1234567.89); // 1 KB

// Get a human-readable representation of a number
echo Number::format(1234567.89); // 1 million

// Get the abbreviated form of a number
echo Number::format(1234567.89); // 1M
```

### Advanced usage

```php
use FriendsOfPhp\Number\Number;

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
