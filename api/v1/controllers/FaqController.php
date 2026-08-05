<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/Database.php';

class FaqController extends BaseController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Listar todas as FAQs
     * GET /api/v1/admin/faq
     */
    public function index($input, $params) {
        try {
            $faqs = $this->db->fetchAll("
                SELECT f.*, c.name as category_name, c.icon as category_icon
                FROM faqs f
                LEFT JOIN faq_categories c ON f.category_id = c.id
                WHERE f.is_active = 1
                ORDER BY c.sort_order ASC, f.order ASC, f.id ASC
            ");
            
            return $this->success($faqs);
        } catch (Exception $e) {
            return $this->error('Erro ao carregar FAQs: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Listar FAQs por categoria
     * GET /api/v1/admin/faq/category/{id}
     */
    public function getByCategory($id, $input, $params) {
        try {
            $faqs = $this->db->fetchAll("
                SELECT f.*, c.name as category_name
                FROM faqs f
                LEFT JOIN faq_categories c ON f.category_id = c.id
                WHERE f.category_id = :category_id AND f.is_active = 1
                ORDER BY f.order ASC, f.id ASC
            ", [':category_id' => $id]);
            
            return $this->success($faqs);
        } catch (Exception $e) {
            return $this->error('Erro ao carregar FAQs da categoria', null, 500);
        }
    }
    
    /**
     * Obter FAQ específica
     * GET /api/v1/admin/faq/{id}
     */
    public function show($id, $input, $params) {
        try {
            $faq = $this->db->fetchOne("
                SELECT f.*, c.name as category_name
                FROM faqs f
                LEFT JOIN faq_categories c ON f.category_id = c.id
                WHERE f.id = :id
            ", [':id' => $id]);
            
            if (!$faq) {
                return $this->error('FAQ não encontrada', null, 404);
            }
            
            return $this->success($faq);
        } catch (Exception $e) {
            return $this->error('Erro ao carregar FAQ', null, 500);
        }
    }
    
    /**
     * Criar nova FAQ
     * POST /api/v1/admin/faq
     */
    public function create($input, $params) {
        try {
            $required = ['category_id', 'question', 'answer'];
            $validation = $this->validateRequired($input, $required);
            if ($validation !== true) return $validation;
            
            // Verificar se categoria existe
            $category = $this->db->fetchOne("SELECT id FROM faq_categories WHERE id = :id", [':id' => $input['category_id']]);
            if (!$category) {
                return $this->error('Categoria não encontrada', null, 404);
            }
            
            // Obter a próxima ordem
            $maxOrder = $this->db->fetchOne("SELECT MAX(`order`) as max_order FROM faqs WHERE category_id = :category_id", 
                [':category_id' => $input['category_id']]);
            $nextOrder = ($maxOrder['max_order'] ?? 0) + 1;
            
            $faqId = $this->db->insert('faqs', [
                'category_id' => $input['category_id'],
                'question' => $input['question'],
                'answer' => $input['answer'],
                'order' => $input['order'] ?? $nextOrder,
                'is_active' => isset($input['is_active']) ? (int)$input['is_active'] : 1
            ]);
            
            $faq = $this->db->fetchOne("SELECT * FROM faqs WHERE id = :id", [':id' => $faqId]);
            
            return $this->success($faq, 'FAQ criada com sucesso', 201);
        } catch (Exception $e) {
            return $this->error('Erro ao criar FAQ: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Atualizar FAQ
     * PUT /api/v1/admin/faq/{id}
     */
    public function update($id, $input, $params) {
        try {
            $existing = $this->db->fetchOne("SELECT id FROM faqs WHERE id = :id", [':id' => $id]);
            if (!$existing) {
                return $this->error('FAQ não encontrada', null, 404);
            }
            
            $updateData = [];
            if (isset($input['category_id'])) $updateData['category_id'] = $input['category_id'];
            if (isset($input['question'])) $updateData['question'] = $input['question'];
            if (isset($input['answer'])) $updateData['answer'] = $input['answer'];
            if (isset($input['order'])) $updateData['order'] = intval($input['order']);
            if (isset($input['is_active'])) $updateData['is_active'] = (int)$input['is_active'];
            
            if (empty($updateData)) {
                return $this->error('Nenhum dado para atualizar', null, 400);
            }
            
            // Se mudou de categoria, ajustar ordem
            if (isset($updateData['category_id'])) {
                $maxOrder = $this->db->fetchOne("SELECT MAX(`order`) as max_order FROM faqs WHERE category_id = :category_id", 
                    [':category_id' => $updateData['category_id']]);
                $updateData['order'] = ($maxOrder['max_order'] ?? 0) + 1;
            }
            
            $this->db->update('faqs', $updateData, 'id = :id', [':id' => $id]);
            
            return $this->success(null, 'FAQ atualizada com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro ao atualizar FAQ', null, 500);
        }
    }
    
    /**
     * Excluir FAQ
     * DELETE /api/v1/admin/faq/{id}
     */
    public function delete($id, $input, $params) {
        try {
            $existing = $this->db->fetchOne("SELECT id FROM faqs WHERE id = :id", [':id' => $id]);
            if (!$existing) {
                return $this->error('FAQ não encontrada', null, 404);
            }
            
            $this->db->delete('faqs', 'id = :id', [':id' => $id]);
            
            return $this->success(null, 'FAQ removida com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro ao remover FAQ', null, 500);
        }
    }
    
    /**
     * Reordenar FAQs
     * POST /api/v1/admin/faq/reorder
     */
    public function reorder($input, $params) {
        try {
            $orders = $input['orders'] ?? [];
            if (empty($orders)) {
                return $this->error('Nenhuma ordem fornecida', null, 400);
            }
            
            foreach ($orders as $orderData) {
                $this->db->update('faqs', ['order' => $orderData['order']], 'id = :id', [':id' => $orderData['id']]);
            }
            
            return $this->success(null, 'Ordem atualizada com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro ao reordenar FAQs', null, 500);
        }
    }
    
    /**
     * Buscar FAQs
     * GET /api/v1/admin/faq/search?q={term}
     */
    public function search($input, $params) {
        try {
            $term = $_GET['q'] ?? '';
            if (empty($term)) {
                return $this->index($input, $params);
            }
            
            $faqs = $this->db->fetchAll("
                SELECT f.*, c.name as category_name
                FROM faqs f
                LEFT JOIN faq_categories c ON f.category_id = c.id
                WHERE f.question LIKE :term OR f.answer LIKE :term
                ORDER BY f.order ASC
            ", [':term' => "%{$term}%"]);
            
            return $this->success($faqs);
        } catch (Exception $e) {
            return $this->error('Erro ao buscar FAQs', null, 500);
        }
    }
}