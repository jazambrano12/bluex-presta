<?php
/**
 * Tenemos la classe BxPackage
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BxPackageModule
 * @package   BxPackage
 * @Version   0.1.0
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

require_once dirname(__FILE__) . '/BxConfig.php';

/**
 * BxPackage
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxPackageModule
 * @package  BxPackage
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */
class BxPackage
{
    public $total_weight = 0;
    public $total_height = 0;
    public $total_depth = 0;
    public $total_width = 0;

    public function __construct($product_list)
    {
        $this->product_list = $product_list;
    }

    public function getSizes()
    {
        $return = array();
        $dimensions = array();
        $weight = 0;
        foreach ($this->product_list as $product) {
            $dim = array(
                'depth' => $this->getDepth($product),
                'height' => $this->getHeight($product),
                'width' => $this->getWidth($product),
            );
            for ($i = 0; $i < $product['product_quantity']; $i++) {
                $dimensions[] = $dim;
                $weight += $this->getWeight($product);
            }
        }

        $dimensions = explode('x', $this->getPacketEstimatedSize($dimensions));

        $return[] = array(
            "alto" => $dimensions[0],
            "ancho" => $dimensions[1],
            "largo" => $dimensions[2],
            "peso" => $weight,
        );

        return $return;
    }

    private function getDepth($product)
    {
        if ($product['depth'] == 0) {
            $depth = Configuration::get("BX_DEF_DEPTH");
        } else {
            $depth = $product['depth'];
        }

        return $depth;
    }

    private function getHeight($product)
    {
        if ($product['height'] == 0) {
            $height = Configuration::get("BX_DEF_HEIGHT");
        } else {
            $height = $product['height'];
        }

        return $height;
    }

    private function getWidth($product)
    {
        if ($product['width'] == 0) {
            $width = Configuration::get("BX_DEF_WIDTH");
        } else {
            $width = $product['width'];
        }

        return $width;
    }

    private function getWeight($product)
    {
        if ($product['weight'] == 0) {
            $weight = Configuration::get("BX_DEF_WEIGHT");
        } else {
            $weight = $product['weight'];
        }

        return $weight;
    }

    public function getEstimatedPackage()
    {
        $dimensions = array();
        foreach ($this->product_list as $product) {
            $dim = array(
                'depth' => $this->getDepth($product),
                'height' => $this->getHeight($product),
                'width' => $this->getWidth($product),
            );
            for ($i = 0; $i < $product['product_quantity']; $i++) {
                $dimensions[] = $dim;
            }
        }

        return $this->getPacketEstimatedSize($dimensions);
    }

    public static function getPacketEstimatedSize($dimensiones)
    {
        $estimation_method = Configuration::get("BX_PACKET_ESTIMATION_METHOD");

        switch ($estimation_method) {
            case BxConfig::ESTIMATION_SUM_DIMS:
                self::sumDimEstimation($dimensiones);
                break;
            case BxConfig::ESTIMATION_MAX_DIMS:
                self::maxDimEstimation($dimensiones);
                break;
            case BxConfig::ESTIMATION_DEFAULT_PACKET:
                self::defaultPacketEstimation();
                break;
        }
    }

    private static function sumDimEstimation($dimensiones)
    {
        foreach ($dimensiones as &$product_dimensions) {
            sort($product_dimensions);
        }

        array_multisort($dimensiones);

        if (count($dimensiones) == 1) {
            $paquete = implode('x', $dimensiones[0]);
        } else {
            $all_equal_size = true;
            for ($i = 0; $i < count($dimensiones) - 1; $i++) {
                if ($dimensiones[$i] != $dimensiones[$i + 1]) {
                    $all_equal_size = false;
                }
            }

            if ($all_equal_size) {
                $partOne = ($dimensiones[0][0] * count($dimensiones));
                $partTwo = dimensiones[0][1];
                $partThree = dimensiones[0][2];

                $paquete = $partOne . 'x' . $partTwo . 'x' . $partThree;
            } else {
                $volumen = 0;
                foreach ($dimensiones as $pdimen) {
                    $volumen += $pdimen[0] * $pdimen[1] * $pdimen[2];
                }

                $cube_size = ceil(pow($volumen, 1 / 3));
                $paquete = $cube_size . 'x' . $cube_size . 'x' . $cube_size;
            }
        }

        return $paquete;
    }

    private static function maxDimEstimation($dimensiones)
    {

        $allDimen = array();
        foreach ($dimensiones as $producto_dimension) {
            foreach ($producto_dimension as $value) {
                $allDimen[] = $value;
            }
        }
        rsort($allDimen);
        return $allDimen[0] . 'x' . $allDimen[1] . 'x' . $allDimen[2];
    }

    private static function defaultPacketEstimation()
    {
        return Configuration::get('BX_PACKET_ESTIMATION_DEFAULT');
    }
}
