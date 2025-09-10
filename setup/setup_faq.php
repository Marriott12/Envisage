<?php
// FAQ System Setup
require_once '../config/config.php';
require_once '../config/database.php';

try {
    // Create FAQ table
    $db->execute("
        CREATE TABLE IF NOT EXISTS faqs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            category VARCHAR(100) DEFAULT 'General',
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Insert sample FAQs
    $sample_faqs = [
        [
            'question' => 'What services does Envisage Technology Zambia offer?',
            'answer' => 'We offer a comprehensive range of technology services including web development, mobile app development, digital marketing, graphic design, system integration, and IT consulting. Our team specializes in creating custom solutions that help businesses grow and succeed in the digital landscape.',
            'category' => 'Services',
            'sort_order' => 1
        ],
        [
            'question' => 'How long does it take to develop a website?',
            'answer' => 'The timeline for website development varies depending on the complexity and requirements. A simple business website typically takes 2-4 weeks, while complex e-commerce or custom applications may take 6-12 weeks. We provide detailed project timelines during our initial consultation.',
            'category' => 'Development',
            'sort_order' => 2
        ],
        [
            'question' => 'Do you provide ongoing support and maintenance?',
            'answer' => 'Yes, we offer comprehensive support and maintenance packages for all our projects. This includes regular updates, security patches, content updates, and technical support. We believe in building long-term relationships with our clients.',
            'category' => 'Support',
            'sort_order' => 3
        ],
        [
            'question' => 'What is your pricing structure?',
            'answer' => 'Our pricing is project-based and depends on the scope, complexity, and timeline of your project. We offer competitive rates and flexible payment plans. Contact us for a free consultation and customized quote based on your specific requirements.',
            'category' => 'Pricing',
            'sort_order' => 4
        ],
        [
            'question' => 'Can you help with digital marketing and SEO?',
            'answer' => 'Absolutely! We offer comprehensive digital marketing services including SEO, social media marketing, content marketing, Google Ads, and email marketing. Our team helps businesses increase their online visibility and reach their target audience effectively.',
            'category' => 'Marketing',
            'sort_order' => 5
        ],
        [
            'question' => 'Do you work with businesses outside of Zambia?',
            'answer' => 'Yes, we work with clients globally. While we are based in Lusaka, Zambia, we have successfully completed projects for clients across Africa and internationally. We use modern communication tools to ensure smooth collaboration regardless of location.',
            'category' => 'General',
            'sort_order' => 6
        ]
    ];

    foreach ($sample_faqs as $faq) {
        $db->execute("
            INSERT IGNORE INTO faqs (question, answer, category, sort_order) 
            VALUES (?, ?, ?, ?)
        ", [$faq['question'], $faq['answer'], $faq['category'], $faq['sort_order']]);
    }

    echo "FAQ system setup completed successfully!";

} catch (Exception $e) {
    echo "Error setting up FAQ system: " . $e->getMessage();
}
?>
