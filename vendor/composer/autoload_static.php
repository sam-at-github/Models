<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8dd819e19bd58a7ad5e1445f51167fc5
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'Models\\' => 7,
        ),
        'J' => 
        array (
            'JsonSchema\\' => 11,
            'JsonDocs\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Models\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
        'JsonSchema\\' => 
        array (
            0 => __DIR__ . '/..' . '/sam-at-github/phpjsonschema',
        ),
        'JsonDocs\\' => 
        array (
            0 => __DIR__ . '/..' . '/sam-at-github/phpjsonschema/JsonDocs',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit8dd819e19bd58a7ad5e1445f51167fc5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit8dd819e19bd58a7ad5e1445f51167fc5::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
