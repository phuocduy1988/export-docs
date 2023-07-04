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
```bash
php artisan docs:diagram output-optional.png
```

**Export API document**
```bash
php artisan docs:api-spec url-collection-postman-required --environment=path/postman-environment.json
```
_Note that: postman enviroment must include token login_

**Export database document**
```bash
php artisan docs:database --filename=name-of-file-optional
```
