<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2020 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

use \app\inc\Route;

Route::add("extensions/geocoder/api/process/{db}",  function () {
    $db = Route::getParam("db");
    \app\models\Database::setDb($db);
});