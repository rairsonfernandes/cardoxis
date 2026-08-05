<?php
require_once __DIR__ . '/Model.php';

class VehicleModel extends Model {
    protected $table = 'vehicles';
    
    public function getWithDriver($id = null) {
        if ($id) {
            $sql = "SELECT v.*, d.name as driver_name, d.phone as driver_phone 
                    FROM vehicles v 
                    LEFT JOIN drivers d ON v.driver_id = d.id 
                    WHERE v.id = :id";
            return $this->db->fetchOne($sql, [':id' => $id]);
        }
        
        $sql = "SELECT v.*, d.name as driver_name 
                FROM vehicles v 
                LEFT JOIN drivers d ON v.driver_id = d.id 
                ORDER BY v.created_at DESC";
        return $this->db->fetchAll($sql);
    }
    
    public function getByCompany($companyId, $page = 1, $perPage = 15) {
        return $this->paginate($page, $perPage, ['company_id' => $companyId]);
    }
    
    public function getMaintenances($vehicleId) {
        $sql = "SELECT * FROM maintenances WHERE vehicle_id = :vehicle_id ORDER BY scheduled_date DESC";
        return $this->db->fetchAll($sql, [':vehicle_id' => $vehicleId]);
    }
}