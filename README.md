## Installation

You can install the package via composer.

```bash
composer require onetechasia/laravel-export-docs
```

## Requirements

This package requires the `graphviz` tool.

You can install Graphviz on MacOS via homebrew:

```bash
brew install graphviz
```

Or, if you are using Homestead:

```bash
sudo apt-get install graphviz
```

```bash
php artisan db:diagram
```
```bash
php artisan db:diagram output.png
```
