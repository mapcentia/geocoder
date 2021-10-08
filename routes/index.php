<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2021 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

use app\inc\Route;
use app\models\Database;


Route::add("extensions/geocoder/api/process/{db}",  function () {
    $db = Route::getParam("db");
    Database::setDb($db);
});