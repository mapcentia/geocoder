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
use \GuzzleHttp\Client;


class Process extends Controller
{
    public function get_index(): array
    {
        $table = "sundhedstilbud.sundhedstilbud";
        $arr = [];
        $client = new Client([
            'timeout' => 10.0,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
        $key = App::$param["googleApiKey"];

        $update = new Update();
        $rows = $update->select($table);

        if (!$rows["success"]) {
            $response['success'] = false;
            $response['message'] = $rows["message"];
            $response['code'] = "400";
            return $response;
        }

        $update->addGeomField($table);

        foreach ($rows["data"] as $row) {
            $address = $row["adresse"];
            $url = "https://maps.googleapis.com/maps/api/geocode/json?key={$key}&address={$address}";
            try {
                $place = $client->get($url);
            } catch (\Exception $e) {
                $response['success'] = false;
                $response['message'] = $e->getMessage();
                $response['code'] = $e->getCode();
                return $response;
            }

            $result = json_decode((string)$place->getBody(), true);
            $result["address"] = $address;
            $arr[] = $result;
            $geocodedGeom = $result["results"][0]["geometry"]["location"];
            $formattedAddress = str_replace(", Denmark", "", $result["results"][0]["formatted_address"]);
            $updateRes = $update->update($table, $row["gid"], [$geocodedGeom["lat"], $geocodedGeom["lng"]], $formattedAddress);
            if (!$updateRes["success"]) {
                $response['success'] = false;
                $response['message'] = $updateRes["message"];
                $response['gid'] = $updateRes["gid"];
                $response['code'] = "400";
                return $response;
            }
        }
        $response['success'] = true;
        $response['data'] = $arr;
        return $response;
    }
}