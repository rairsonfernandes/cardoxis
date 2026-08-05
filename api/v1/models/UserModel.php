<?php
require_once __DIR__ . '/BaseModel.php';

class UserModel extends BaseModel {
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'company_id', 'name', 'email', 'password', 'role', 
        'phone', 'avatar', 'status', 'last_login_at'
    ];
    protected $hidden = ['password'];
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email";
        return $this->db->fetchOne($sql, [':email' => $email]);
    }
    
    /**
     * Verify user credentials
     */
    public function verifyCredentials($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        if (!password_verify($password, $user['password'])) {
            return false;
        }
        
        if ($user['status'] !== 'active') {
            return false;
        }
        
        // Update last login
        $this->update($user['id'], [
            'last_login_at' => date('Y-m-d H:i:s')
        ]);
        
        return $user;
    }
    
    /**
     * Create new user
     */
    public function createUser($data) {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Add timestamps
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Filter fillable fields
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        
        return $this->db->insert($this->table, $filteredData);
    }
    
    /**
     * Update user password
     */
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->update($userId, ['password' => $hashedPassword]);
    }
    
    /**
     * Get users by company
     */
    public function getByCompany($companyId, $page = 1, $perPage = 15) {
        return $this->paginate($page, $perPage, ['company_id' => $companyId]);
    }
    
    /**
     * Update last login
     */
    public function updateLastLogin($userId) {
        return $this->update($userId, ['last_login_at' => date('Y-m-d H:i:s')]);
    }
}