<?php
/**
 * CARDOXIS - Configurações da Landing Page  RF
 */

// CONFIGURAÇÕES DA PÁGINA (SEO)

$pageConfig = [
    'seo' => [
        'title' => 'CARDOXIS - Gestão Inteligente de Frota | Plataforma Enterprise',
        'description' => 'Plataforma completa para gestão de frotas. Controlo de veículos, motoristas, documentos, seguros, multas, manutenções e combustível. Reduza custos e aumente a eficiência.',
        'keywords' => 'gestão de frota, veículos, motoristas, documentos, seguros, multas, manutenções, combustível, controlo de frotas, fleet management, Portugal',
        'author' => 'CARDOXIS',
        'theme_color' => '#0052CC',
        'canonical' => 'https://cardoxis.com'
    ]
];

// CONFIGURAÇÕES DA EMPRESA

$companyConfig = [
    'name' => 'CARDOXIS',
    'email' => 'contato@cardoxis.com',
    'phone' => '+351 300 123 456',
    'phone_display' => '300 123 456',
    'address' => 'Avenida da Liberdade, 1250 - Lisboa, Portugal',
    'year' => date('Y')
];

// PALAVRAS PARA O TYPEWRITER 

$typewriterWords = [
    'Frotas com Eficiência',
    'Manutenções Preventivas',
    'Documentos Organizados',
    'Seguros em Dia',
    'Multas Controladas',
    'Consumo Otimizado',
    'Gestão de Motoristas',
    'Relatórios Completos'
];

// FUNCIONALIDADES DO SISTEMA

$features = [
    [
        'icon' => 'fas fa-car',
        'title' => 'Gestão de Veículos',
        'description' => 'Cadastro completo de veículos com histórico, documentação e controlo de quilometragem. Gerencie toda a sua frota num só lugar.'
    ],
    [
        'icon' => 'fa-users',
        'title' => 'Gestão de Motoristas',
        'description' => 'Controlo total sobre motoristas, licenças, documentos e histórico. Atribua motoristas aos veículos e acompanhe o seu desempenho.'
    ],
    [
        'icon' => 'fa-file-alt',
        'title' => 'Gestão de Documentos',
        'description' => 'Armazene e organize todos os documentos da sua frota. Receba alertas automáticos de vencimento e mantenha tudo em dia.'
    ],
    [
        'icon' => 'fa-shield-alt',
        'title' => 'Gestão de Seguros',
        'description' => 'Controlo de apólices, datas de vencimento e sinistros. Tenha visibilidade completa dos seguros da sua frota.'
    ],
    [
        'icon' => 'fa-money-bill-wave',
        'title' => 'Gestão de Multas',
        'description' => 'Registo e acompanhamento de multas com notificações automáticas. Gerencie prazos e pagamentos de forma eficiente.'
    ],
    [
        'icon' => 'fa-gas-pump',
        'title' => 'Gestão de Combustível',
        'description' => 'Controlo inteligente de abastecimentos, consumo e custos. Analise a eficiência por veículo e reduza gastos.'
    ],
    [
        'icon' => 'fa-tools',
        'title' => 'Manutenções Programadas',
        'description' => 'Planeie e acompanhe manutenções preventivas e corretivas. Receba alertas e mantenha a sua frota sempre em dia.'
    ],
    [
        'icon' => 'fa-bell',
        'title' => 'Alertas Inteligentes',
        'description' => 'Notificações automáticas para vencimentos, manutenções e prazos importantes. Nunca mais perca uma data limite.'
    ],
    [
        'icon' => 'fa-chart-bar',
        'title' => 'Relatórios Completos',
        'description' => 'Relatórios detalhados sobre veículos, motoristas, custos e desempenho. Tome decisões baseadas em dados concretos.'
    ]
];

// FUNCIONALIDADES DO DASHBOARD

$dashboardFeatures = [
    'Visão geral da frota em tempo real',
    'Indicadores-chave de desempenho (KPIs)',
    'Alertas e notificações centralizados',
    'Acesso rápido a todas as funcionalidades',
    'Gráficos e análises interativas',
    'Atualizações automáticas'
];

// ESTATÍSTICAS

$stats = [
    ['number' => '10K+', 'label' => 'Veículos Monitorizados', 'icon' => 'fa-truck'],
    ['number' => '98%', 'label' => 'Satisfação dos Clientes', 'icon' => 'fa-smile'],
    ['number' => '500+', 'label' => 'Empresas Parceiras', 'icon' => 'fa-building'],
    ['number' => '35%', 'label' => 'Redução de Custos', 'icon' => 'fa-chart-line']
];

// DEPOIMENTOS

$testimonials = [
    [
        'name' => 'Carlos Ferreira',
        'position' => 'Coordenador de Frota',
        'company' => 'Delivery Express',
        'content' => 'O CARDOXIS revolucionou a nossa gestão de frotas. Reduzimos custos operacionais em 35% e aumentámos a eficiência da nossa equipa. A gestão de documentos e manutenções ficou muito mais simples e organizada.',
        'rating' => 5,
        'initial' => 'CF'
    ],
    [
        'name' => 'João Silva',
        'position' => 'Diretor de Operações',
        'company' => 'Transportadora Expresso',
        'content' => 'Implementámos o CARDOXIS há 6 meses e os resultados são impressionantes. A visibilidade que temos sobre a frota e os motoristas mudou completamente a nossa forma de trabalhar. Recomendo a todas as empresas do setor.',
        'rating' => 5,
        'initial' => 'JS'
    ],
    [
        'name' => 'Maria Santos',
        'position' => 'CEO',
        'company' => 'LogTech Solutions',
        'content' => 'A plataforma transformou completamente a nossa gestão. Agora temos controlo total sobre veículos, motoristas, documentos e manutenções. O suporte é excecional e as atualizações constantes trazem sempre novidades.',
        'rating' => 5,
        'initial' => 'MS'
    ]
];

// PLANOS

$plans = [
    [
        'name' => 'Básico',
        'price' => 'Demonstração',
        'currency' => '',
        'period' => '',
        'description' => 'Ideal para pequenas frotas',
        'features' => [
            'Até 30 veículos',
            'Até 30 motoristas',
            'Gestão de documentos',
            'Suporte por email'
        ],
        'popular' => false,
        'cta_text' => 'Começar Agora',
        'cta_url' => 'register'
    ],
    [
        'name' => 'Profissional',
        'price' => 'Demonstração',
        'currency' => '',
        'period' => '',
        'description' => 'Para empresas em crescimento',
        'features' => [
            'Até 30 veículos',
            'Até 30 motoristas',
            'Gestão completa',
            'Relatórios avançados',
            'API integrada',
            'Suporte prioritário'
        ],
        'popular' => true,
        'cta_text' => 'Começar Agora',
        'cta_url' => 'register'
    ],
    [
        'name' => 'Enterprise',
        'price' => 'Demonstração',
        'currency' => '',
        'period' => '',
        'description' => 'Para grandes corporações',
        'features' => [
            'Veículos ilimitados',
            'Motoristas ilimitados',
            'Todos os recursos',
            'Suporte 24/7',
            'Customizações',
            'Treinamento exclusivo'
        ],
        'popular' => false,
        'cta_text' => 'Falar Connosco',
        'cta_url' => 'contact'
    ]
];

// SCHEMA.ORG STRUCTURED DATA

$schemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => $companyConfig['name'],
    'description' => $pageConfig['seo']['description'],
    'applicationCategory' => 'BusinessApplication',
    'operatingSystem' => 'Web, iOS, Android',
    'offers' => [
        '@type' => 'Offer',
        'price' => '0',
        'priceCurrency' => 'EUR',
        'availability' => 'https://schema.org/InStock'
    ],
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => '4.8',
        'ratingCount' => '342'
    ]
];

// VARIÁVEIS PARA A VIEW


$title = $pageConfig['seo']['title'];
$description = $pageConfig['seo']['description'];
$keywords = $pageConfig['seo']['keywords'];
$theme_color = $pageConfig['seo']['theme_color'];
$canonical = $pageConfig['seo']['canonical'];