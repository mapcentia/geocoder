<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2018 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

namespace app\extensions\geocoder\models;

use app\inc\Model;
use phpDocumentor\Reflection\Types\Integer;


class Update extends Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function select(string $table): array
    {
        $sql = "SELECT * FROM {$table}";

        $res = $this->prepare($sql);

        try {
            $res->execute();
            $rows = $this->fetchAll($res);
        } catch (\PDOException $e) {
            return [
                "success" => false,
                "message" => $e->getMessage(),
                ];
        }
        return [
            "success" => true,
            "data" => $rows
        ];
    }

    public function update(string $table, int $gid, array $coords, string $address): array
    {
        $sql = "UPDATE {$table} SET google_address='{$address}', the_geom=st_geomfromtext('POINT({$coords[1]} {$coords[0]})', 4326) WHERE gid=:gid";

        $res = $this->prepare($sql);

        try {
            $res->execute(["gid" => $gid]);
        } catch (\PDOException $e) {
            return [
                "gid" => $gid,
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
        return [
            "success" => true
        ];
    }

    public function addGeomField(string $table) {
        $sql= "ALTER TABLE ${table} ADD the_geom geometry('POINT', 4326)";
        $res = $this->prepare($sql);

        try {
            $res->execute();
        } catch (\PDOException $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
        return [
            "success" => true
        ];
    }
}