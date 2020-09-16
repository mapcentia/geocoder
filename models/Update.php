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

    public function select(string $table, string $geomField, string $field, bool $force = false): array
    {
        $sql = "SELECT * FROM {$table} WHERE {$field} IS NOT NULL";
        if (!$force) {
            $sql .= " AND {$geomField} IS NULL";
        }
        $sql .= " LIMIT 1";
        $res = $this->prepare($sql);
        try {
            $res->execute();
        } catch (\PDOException $e) {
            return [
                "success" => false,
                "message" => $e->getMessage(),
            ];
        }
        $rows = $this->fetchAll($res);

        return [
            "success" => true,
            "data" => $rows
        ];
    }

    public function update(string $table, int $gid, array $coords, string $address, $geomField, $googleField): array
    {
        $sql = "UPDATE {$table} SET {$googleField}='{$address}', {$geomField}=st_geomfromtext('POINT({$coords[1]} {$coords[0]})', 4326) WHERE gid=:gid";

        $res = $this->prepare($sql);

        try {
            $res->execute(["gid" => $gid]);
        } catch (\PDOException $e) {
            return [
                "google_address" => $address,
                "success" => false
            ];
        }
        return [
            "google_address" => $address,
            "success" => true
        ];
    }

    public function addGeomField(string $table, string $field): array
    {
        $sql = "ALTER TABLE {$table} ADD {$field} geometry('POINT', 4326)";
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
            "success" => true,
        ];
    }
    public function addGoogleField(string $table, string $field): array
    {
        $sql = "ALTER TABLE {$table} ADD {$field} varchar(255)";
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
            "success" => true,
        ];
    }
}