# Contributing

We love pull requests from everyone. By participating in this project, you agree to abide by the thoughtbot [code of conduct](https://thoughtbot.com/open-source-code-of-conduct).

Fork, then clone the repo:

    git clone git@github.com:your-username/Capsule

Set up the Capsule's dependencies:

```
cd Capsule
composer install
```

Make sure the tests pass:

```
phpunit
```

Make your change. Add tests for your change. Make the tests pass:

```
phpunit
```

All pull requests must adhere to [PSR-2][style] coding style guide.

To make sure your programming adheres to PSR-2, it is highly reccomended that you install [PHP Codesniffer](https://github.com/squizlabs/PHP_CodeSniffer)

```
composer global require "squizlabs/php_codesniffer=*"
```

Now you can run the following commands:

```
phpcs src
phpcs tests
```

View PHP Codesniffers full [documentation](https://github.com/squizlabs/PHP_CodeSniffer) for more information.

Push to your fork and [submit a pull request](https://github.com/Dastur1970/Capsule/compare/).

At this point you're waiting on us. We like to at least comment on pull requests
within three business days (and, typically, one business day). We may suggest
some changes or improvements or alternatives.

Some things that will increase the chance that your pull request is accepted:

* Write tests.
* Follow [PSR-2][style] style guide.

[style]: http://www.php-fig.org/psr/psr-2/
