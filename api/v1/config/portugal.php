<?php
/**
 * Configurações para Portugal
 */

class PortugalConfig {
    
    // Moeda
    const CURRENCY = 'EUR';
    const CURRENCY_SYMBOL = '€';
    
    // Formatação
    const DATE_FORMAT = 'd/m/Y';
    const DATETIME_FORMAT = 'd/m/Y H:i:s';
    const DECIMAL_SEPARATOR = ',';
    const THOUSAND_SEPARATOR = '.';
    
    // Documentos Portugueses
    const DOCUMENT_TYPES = [
        'CC' => 'Cartão de Cidadão',
        'NIF' => 'Número de Identificação Fiscal',
        'NISS' => 'Número de Identificação da Segurança Social',
        'NUIT' => 'Número de Identificação Tributária'
    ];
    
    // Matrículas Portuguesas
    const LICENSE_PLATE_FORMAT = '/^[A-Z]{2}-\d{2}-[A-Z]{2}$/'; // Formato: AA-00-AA
    
    // Categorias de Carta de Condução (Portugal)
    const DRIVER_LICENSE_CATEGORIES = ['AM', 'A1', 'A2', 'A', 'B1', 'B', 'C1', 'C', 'D1', 'D', 'BE', 'C1E', 'CE', 'D1E', 'DE'];
    
    // Combustíveis
    const FUEL_TYPES = ['Gasolina 95', 'Gasolina 98', 'Gasóleo', 'GPL', 'Elétrico', 'Híbrido'];
    
    // Distritos de Portugal
    const DISTRICTS = [
        'Aveiro', 'Beja', 'Braga', 'Bragança', 'Castelo Branco', 'Coimbra', 'Évora', 'Faro', 
        'Guarda', 'Leiria', 'Lisboa', 'Portalegre', 'Porto', 'Santarém', 'Setúbal', 'Viana do Castelo', 
        'Vila Real', 'Viseu', 'Açores', 'Madeira'
    ];
    
    // Impostos
    const VAT_RATE = 0.23; // IVA em Portugal = 23%
    const IRS_RATES = [0.145, 0.21, 0.28, 0.35, 0.37, 0.45, 0.48];
    
    // Funções de formatação
    public static function formatMoney($value) {
        return self::CURRENCY_SYMBOL . ' ' . number_format($value, 2, self::DECIMAL_SEPARATOR, self::THOUSAND_SEPARATOR);
    }
    
    public static function formatDate($date) {
        return date(self::DATE_FORMAT, strtotime($date));
    }
    
    public static function formatDocument($document) {
        // Format NIF: 123456789 -> 123 456 789
        if (strlen($document) === 9 && is_numeric($document)) {
            return substr($document, 0, 3) . ' ' . substr($document, 3, 3) . ' ' . substr($document, 6, 3);
        }
        return $document;
    }
    
    public static function validateNIF($nif) {
        // Validação simples de NIF português
        $nif = preg_replace('/[^0-9]/', '', $nif);
        if (strlen($nif) !== 9) return false;
        
        $checkSum = 0;
        for ($i = 0; $i < 8; $i++) {
            $checkSum += $nif[$i] * (9 - $i);
        }
        $checkDigit = 11 - ($checkSum % 11);
        $checkDigit = ($checkDigit >= 10) ? 0 : $checkDigit;
        
        return $checkDigit == $nif[8];
    }
    
    public static function validateLicensePlate($plate) {
        // Valida matrícula portuguesa: AA-00-AA
        return preg_match(self::LICENSE_PLATE_FORMAT, $plate);
    }
}