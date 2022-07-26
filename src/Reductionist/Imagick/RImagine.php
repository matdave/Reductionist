<?php

namespace Reductionist\Imagick;

use Exception;
use Imagick;
use ImagickException;
use ImagickPixel;
use Imagine\Exception\RuntimeException;
use Imagine\Image\BoxInterface;
use Imagine\Image\Palette\CMYK;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\Grayscale;
use Imagine\Image\Palette\RGB;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Metadata\MetadataBag;
use Imagine\Imagick\Font;

abstract class RImagine implements ImagineInterface
{
    protected static RGB $rgb;
    protected static MetadataBag $emptyBag;


    public function __construct()
    {
        self::$rgb = new RGB();
        self::$emptyBag = new MetadataBag();
    }


    public function open($path)
    {
        return new RImage($path, self::$rgb, self::$emptyBag);
    }


    public function create(BoxInterface $size, ColorInterface $color = null)
    {
        $width = $size->getWidth();
        $height = $size->getHeight();

        $color = self::getColor($color);

        try {
            $pixel = new ImagickPixel($color['color']);
            $pixel->setColorValue(Imagick::COLOR_OPACITY, $color['alpha']);

            $magick = new Imagick();
            $magick->newImage($width, $height, $pixel);
            $magick->setImageMatte(true);
            $magick->setImageBackgroundColor($pixel);

            $pixel->clear();
            $pixel->destroy();

            return new RImage($magick, $color['palette'], self::$emptyBag, array($width, $height));
        } catch (Exception $e) {
            throw new RuntimeException("Imagick: Could not create empty image {$e->getMessage()}", $e->getCode(), $e);
        }
    }


    public static function createPalette($cs)
    {
        if ($cs === Imagick::COLORSPACE_SRGB || $cs === Imagick::COLORSPACE_RGB) {
            return self::$rgb;
        } elseif ($cs === Imagick::COLORSPACE_CMYK) {
            return new CMYK();
        } elseif ($cs === Imagick::COLORSPACE_GRAY) {
            return new Grayscale();
        } else {
            throw new RuntimeException('Imagick: Only RGB, CMYK and Grayscale colorspaces are curently supported');
        }
    }


    public static function getColor($color)
    {
        if ($color === null) {
            $palette = self::$rgb;
            $color = '#ffffff';
            $alpha = 0;
        } else {
            $palette = $color->getPalette();
            $alpha = $color->getAlpha() / 100;
        }
        return array(
            'palette' => $palette,
            'color' => (string)$color,
            'alpha' => $alpha
        );
    }


    public function load($string)
    {
        try {
            $magick = new Imagick();
            $magick->readImageBlob($string);
            $magick->setImageMatte(true);
            $palette = self::createPalette($magick->getImageColorspace());
        } catch (ImagickException $e) {
            throw new RuntimeException("Imagick: Could not load image from string. {$e->getMessage()}", $e->getCode(), $e);
        }
        return new RImage($magick, $palette, self::$emptyBag);
    }


    public function read($resource)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Variable does not contain a stream resource');
        }

        try {
            $magick = new Imagick();
            $magick->readImageFile($resource);
        } catch (ImagickException $e) {
            throw new RuntimeException("Imagick: Could not read image from resource. {$e->getMessage()}", $e->getCode(), $e);
        }

        $palette = self::createPalette($magick->getImageColorspace());
        return new RImage($magick, $palette, self::$emptyBag);
    }


    public function font($file, $size, ColorInterface $color)
    {
        return new Font(new Imagick(), $file, $size, $color);
    }
}
