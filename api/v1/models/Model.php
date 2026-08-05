<?php
require_once __DIR__ . '/../config/Database.php';

class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function all($limit = 100, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} LIMIT :limit OFFSET :offset";
        return $this->db->fetchAll($sql, [':limit' => $limit, ':offset' => $offset]);
    }
    
    public function create($data) {
        return $this->db->insert($this->table, $data);
    }
    
    public function update($id, $data) {
        return $this->db->update($this->table, $data, "{$this->primaryKey} = :id", [':id' => $id]);
    }
    
    public function delete($id) {
        return $this->db->delete($this->table, "{$this->primaryKey} = :id", [':id' => $id]);
    }
    
    public function paginate($page = 1, $perPage = 15, $conditions = []) {
        $offset = ($page - 1) * $perPage;
        
        $where = "1=1";
        $params = [];
        
        foreach ($conditions as $key => $value) {
            $where .= " AND {$key} = :{$key}";
            $params[":{$key}"] = $value;
        }
        
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$where}";
        $total = $this->db->fetchOne($countSql, $params)['total'];
        
        $dataSql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;
        
        $data = $this->db->fetchAll($dataSql, $params);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => (int)$total,
                'last_page' => ceil($total / $perPage)
            ]
        ];
    }
}