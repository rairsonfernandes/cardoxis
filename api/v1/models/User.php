<?php
/**
 * User Model
 */

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'uuid', 'company_id', 'name', 'email', 'password', 'role', 
        'phone', 'avatar', 'status', 'settings'
    ];
    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email AND deleted_at IS NULL";
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
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        
        return $user;
    }
    
    /**
     * Create new user
     */
    public function createUser($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->create($data);
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
     * Check if user has permission
     */
    public function hasPermission($userId, $permission) {
        $user = $this->find($userId);
        
        if (!$user) {
            return false;
        }
        
        // Super admin has all permissions
        if ($user['role'] === 'super_admin') {
            return true;
        }
        
        // TODO: Implement permission system
        $rolePermissions = [
            'admin' => ['view_dashboard', 'manage_vehicles', 'manage_drivers', 'manage_documents'],
            'manager' => ['view_dashboard', 'manage_vehicles', 'manage_drivers'],
            'user' => ['view_dashboard']
        ];
        
        return in_array($permission, $rolePermissions[$user['role']] ?? []);
    }
}