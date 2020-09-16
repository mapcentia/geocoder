<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2020 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

namespace app\extensions\geocoder\api;

use app\conf\App;
use app\extensions\geocoder\models\Update;
use app\inc\Controller;
use app\inc\Input;
use app\inc\Util;
use app\models\Table;
use \GuzzleHttp\Client;


class Process extends Controller
{
    public function get_index()
    {
        Util::disableOb();
        header('Content-type: text/plain; charset=utf-8');

        $table = Input::get("table");
        $field = Input::get("field");
        $addGeomField = Input::get("geomfield");
        $googleField = Input::get("googlefield");

        if (empty($table) || empty($field)) {
            return ["success" => false, "message" => "table and field must be set"];
        }
        $tableObj = new Table($table);
        $update = new Update();

        $client = new Client([
            'timeout' => 10.0,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
        $key = App::$param["googleApiKey"];

        if (!empty($addGeomField)) {
            $res = $update->addGeomField($table, $addGeomField);
            if (!$res["success"]) {
                $response['success'] = false;
                $response['message'] = $res["message"];
                $response['code'] = "400";
                return $response;
            }
        }
        if (!empty($googleField) && !$tableObj->doesColumnExist($table, $googleField)["exists"]) {
            $res = $update->addGoogleField($table, $googleField);
            if (!$res["success"]) {
                $response['success'] = false;
                $response['message'] = $res["message"];
                $response['code'] = "400";
                return $response;
            }
        }

        $geomField = $tableObj->getGeometryColumns($table, "f_geometry_column");
        $priKey = $tableObj->getPrimeryKey($table)["attname"];

        $rows = $update->select($table, $geomField, $field);
        if (!$rows["success"]) {
            $response['success'] = false;
            $response['message'] = $rows["message"];
            $response['code'] = "400";
            return $response;
        }

        foreach ($rows["data"] as $row) {
            $address = $row[$field];
            $url = "https://maps.googleapis.com/maps/api/geocode/json?key={$key}&address={$address}";
            try {
                $place = $client->get($url);
            } catch (\Exception $e) {
                echo str_pad(json_encode([
                        "google_address" => $address,
                        "success" => false,
                    ]), 4096) . "\n";
                flush();
                ob_flush();
                continue;
            }

            $result = json_decode((string)$place->getBody(), true);
            $result["address"] = $address;
            $arr[] = $result;
            $geocodedGeom = $result["results"][0]["geometry"]["location"];
            $formattedAddress = str_replace(", Denmark", "", $result["results"][0]["formatted_address"]);
            $updateRes = $update->update($table, $row[$priKey], [$geocodedGeom["lat"], $geocodedGeom["lng"]], $formattedAddress, $geomField, $googleField);
            echo str_pad(json_encode($updateRes), 4096) . "\n";
            flush();
            ob_flush();
        }
    }
}