<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sasco\LibreDTE;

/**
 * Clase para acciones genéricas asociadas al SII de Chile
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-07-15
 */
class Sii
{

    private static $wsdl = [
        'url' => 'https://{servidor}.sii.cl/DTEWS/{servicio}.jws?WSDL',
        'servidor' => ['palena', 'maullin'], ///< servidores 0: producción, 1: certificación
    ];
    const PRODUCCION = 0; ///< Constante para indicar ambiente de producción
    const CERTIFICACION = 1; ///< Constante para indicar ambiente de desarrollo

    private static $retry = 10; ///< Veces que se reintentará conectar a SII al usar el servicio web

    /**
     * Método para obtener el WSDL
     *
     * \code{.php}
     *   $wsdl = \sasco\LibreDTE\Sii::wsdl('CrSeed'); // WSDL para pedir semilla
     * \endcode
     *
     * Para forzar el uso del WSDL de certificación hay dos maneras, una es
     * pasando un segundo parámetro al método get con valor Sii::CERTIFICACION:
     *
     * \code{.php}
     *   $wsdl = \sasco\LibreDTE\Sii::wsdl('CrSeed', \sasco\LibreDTE\Sii::CERTIFICACION);
     * \endcode
     *
     * La otra manera, para evitar este segundo parámetro, es crear la constante
     * _LibreDTE_CERTIFICACION_ con valor true antes de ejecutar cualquier
     * llamada a la biblioteca:
     *
     * \code{.php}
     *   define('_LibreDTE_CERTIFICACION_', true);
     * \endcode
     *
     * @param servicio Servicio por el cual se está solicitando su WSDL
     * @param ambiente Ambiente a usar: Sii::PRODUCCION o Sii::CERTIFICACION
     * @return URL del WSDL del servicio según ambiente solicitado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-07-15
     */
    public static function wsdl($servicio, $ambiente = null)
    {
        // determinar ambiente que se debe usar
        if ($ambiente===null) {
            if (defined('_LibreDTE_CERTIFICACION_'))
                $ambiente = (int)_LibreDTE_CERTIFICACION_;
            else
                $ambiente = self::PRODUCCION;
        }
        // entregar WSDL
        return str_replace(
            ['{servidor}', '{servicio}'],
            [self::$wsdl['servidor'][$ambiente], $servicio],
            self::$wsdl['url']
        );
    }

    /**
     * Método para realizar una solicitud al servicio web del SII
     * @param wsdl Nombre del WSDL que se usará
     * @param request Nombre de la función que se ejecutará en el servicio web
     * @param args Argumentos que se pasarán al servicio web
     * @param retry Intentos que se realizarán como máximo para obtener respuesta
     * @return Respuesta del servicio web consultado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-07-27
     */
    public static function request($wsdl, $request, $args = null, $retry = null)
    {
        if (is_numeric($args)) {
            $retry = (int)$args;
            $args = null;
        }
        if (!$retry)
            $retry = self::$retry;
        if ($args and !is_array($args)) {
            $args = [$args];
        }
        $soap = new \SoapClient(self::wsdl($wsdl));
        for ($i=0; $i<$retry; $i++) {
            try {
                if ($args) {
                    $body = call_user_func_array([$soap, $request], $args);
                } else {
                    $body = $soap->$request();
                }
                break;
            } catch (\Exception $e) {
                $body = null;
            }
        }
        return $body;
    }

}
