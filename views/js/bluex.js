/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/
$(document).ready(function () {
    $("#uniform-id_locality").children("span").hide();
    $("#uniform-id_locality").removeAttr("class");

    $(".resume").each(function () {

        var x = $(this).find("strong").html();

        if (x == "Envio a sucursal") {
            $(this).find(".delivery_option_price").html("");
        }

        var m = $(this).find("strong").parent().html().replace("Tiempo de entrega:&nbsp;", "");
        $(this).find("strong").parent().html(m);
    });

    $('input.delivery_option_radio').each(function () {
        if ($(this).prop("checked") == true) {
            var title = $(this).parent().parent().parent().parent().find("strong").html();

            if (title == "Envio a sucursal") {
                $("button[name=processCarrier]").prop("disabled", true);
                $(".cart-prices").hide();
            }
        }
    });

    $(document).on('change', 'input.delivery_option_radio', function () {
        var title = $(this).parent().parent().parent().parent().children("td").find("strong").html();

        if (title == "Envio a sucursal") {
            $("button[name=processCarrier]").prop("disabled", true);
            $(this).parent().parent().parent().parent().find(".delivery_option_price").html('<span style=\"color: #208931;\" id="loading_relay">Cargando sucursales, por favor espere...</span>');
        } else {
            $("#loading_relay").hide();
            $("button[name=processCarrier]").prop("disabled", false);
            $("#delivery-options").hide();
        }
    });

});
