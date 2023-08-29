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

**Export database diagram**
```bash
php artisan docs:diagram
```
_Check file in storage/app/export/database/_

**For using chat GPT translate document**

Add to .**env**:
OPENAI_API_KEY=sk-xxxxx

**Export API document**
```bash
php artisan docs:api-spec url-collection-postman-required --environment=path/postman-environment.json
```
_Note that: postman enviroment must include token login_
_Check file in storage/app/export/api/_

**Export database document**
```bash
php artisan docs:database
```
_Check file in storage/app/export/database/_

