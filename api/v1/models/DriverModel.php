<?php
require_once __DIR__ . '/Model.php';

class DriverModel extends Model {
    protected $table = 'drivers';
    
    public function getWithVehicle($id = null) {
        $db = Database::getInstance();
        
        if ($id) {
            $sql = "SELECT d.*, 
                           v.id as vehicle_id, v.plate as vehicle_plate, v.brand as vehicle_brand, v.model as vehicle_model 
                    FROM drivers d 
                    LEFT JOIN vehicles v ON d.id = v.driver_id 
                    WHERE d.id = :id";
            return $db->fetchOne($sql, [':id' => $id]);
        }
        
        $sql = "SELECT d.*, COUNT(v.id) as vehicles_count 
                FROM drivers d 
                LEFT JOIN vehicles v ON d.id = v.driver_id 
                GROUP BY d.id 
                ORDER BY d.created_at DESC";
        return $db->fetchAll($sql);
    }
    
    public function getByCompany($companyId, $page = 1, $perPage = 15) {
        return $this->paginate($page, $perPage, ['company_id' => $companyId]);
    }
}