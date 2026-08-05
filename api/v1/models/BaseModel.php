<?php
/**
 * Base Model
 */

require_once __DIR__ . '/../config/Database.php';

abstract class BaseModel {
    
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $timestamps = true;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get database connection
     */
    public function getDb() {
        return $this->db;
    }
    
    /**
     * Find record by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    /**
     * Get all records
     */
    public function all($limit = 100, $offset = 0, $orderBy = 'created_at', $orderDir = 'DESC') {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy} {$orderDir} LIMIT :limit OFFSET :offset";
        return $this->db->fetchAll($sql, [':limit' => $limit, ':offset' => $offset]);
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        // Filter only fillable fields
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        
        return $this->db->insert($this->table, $filteredData);
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        
        if (empty($filteredData)) {
            return 0;
        }
        
        return $this->db->update($this->table, $filteredData, "{$this->primaryKey} = :id", [':id' => $id]);
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
        return $this->db->delete($this->table, "{$this->primaryKey} = :id", [':id' => $id]);
    }
    
    /**
     * Paginate results
     */
    public function paginate($page = 1, $perPage = 15, $conditions = []) {
        $offset = ($page - 1) * $perPage;
        
        $where = "1=1";
        $params = [];
        
        foreach ($conditions as $key => $value) {
            $where .= " AND {$key} = :{$key}";
            $params[":{$key}"] = $value;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$where}";
        $total = $this->db->fetchOne($countSql, $params)['total'] ?? 0;
        
        // Get data
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
    
    /**
     * Hide sensitive fields
     */
    public function toArray($data) {
        if (!$data) return $data;
        
        foreach ($this->hidden as $field) {
            if (is_array($data) && isset($data[$field])) {
                unset($data[$field]);
            }
        }
        return $data;
    }
}