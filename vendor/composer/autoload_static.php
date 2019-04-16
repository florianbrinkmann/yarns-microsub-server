<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd21b7306e6c9d741e46d742114d3c556
{
    public static $files = array (
        '757772e28a0943a9afe83def8db95bdf' => __DIR__ . '/..' . '/mf2/mf2/Mf2/Parser.php',
    );

    public static $prefixLengthsPsr4 = array (
        'D' => 
        array (
            'Dealerdirect\\Composer\\Plugin\\Installers\\PHPCodeSniffer\\' => 55,
        ),
        'C' => 
        array (
            'Composer\\Installers\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Dealerdirect\\Composer\\Plugin\\Installers\\PHPCodeSniffer\\' => 
        array (
            0 => __DIR__ . '/..' . '/dealerdirect/phpcodesniffer-composer-installer/src',
        ),
        'Composer\\Installers\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/installers/src/Composer/Installers',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd21b7306e6c9d741e46d742114d3c556::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd21b7306e6c9d741e46d742114d3c556::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
