<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EducationPost;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EducationPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = [
            [
                'title' => 'BPC-157',
                'description' => 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation, and promote faster recovery from injuries.',
                'overview' => '<p>BPC-157 is a synthetic pentadecapeptide derived from a protective protein found in the gastric juice. Research has investigated its role in tissue regeneration, angiogenesis, and inflammation modulation.</p>',
                'content' => '<p>BPC-157 has been studied for its potential therapeutic applications in various research contexts.</p>',
                'image' => 'bpc-157.png',
                'rating' => 5.00,
                'rating_count' => 345,
                'key_effects' => [
                    'Soft tissue and tendon repair',
                    'Gastrointestinal protection and healing',
                    'Neurological recovery models',
                    'Angiogenic responses in wound healing',
                ],
                'accordion_sections' => [
                    [
                        'title' => 'What is BPC-157?',
                        'content' => '<p>BPC-157 (Body Protection Compound-157) is a synthetic peptide consisting of 15 amino acids. It is derived from a protective protein naturally found in gastric juice. Research suggests that BPC-157 may retain the healing properties of the original protein, making it a subject of interest in various research applications.</p>
                        
                        <p>The peptide is thought to work through multiple mechanisms, including promoting angiogenesis (formation of new blood vessels), modulating inflammatory responses, and supporting cellular repair processes. These properties have led researchers to investigate BPC-157 for potential applications in tissue repair and recovery.</p>',
                    ],
                    [
                        'title' => 'BPC-157 Peptide Structure',
                        'content' => '<p>BPC-157 is a pentadecapeptide, meaning it consists of 15 amino acids. The specific sequence and structure of BPC-157 contribute to its stability and biological activity. Understanding the peptide structure is important for researchers studying its mechanisms of action and potential applications.</p>
                        
                        <p>The synthetic nature of BPC-157 allows for consistent production and research use, while maintaining the biological properties of the naturally occurring protective compound.</p>',
                    ],
                    [
                        'title' => 'BPC-157 Research Peptide',
                        'content' => '<p>As a research peptide, BPC-157 is used in laboratory settings to investigate its potential effects and mechanisms. Research has explored various applications, including tissue repair, gastrointestinal protection, and recovery models.</p>
                        
                        <p>It is important to note that BPC-157 is intended for research purposes only. All research should be conducted in accordance with applicable regulations and ethical guidelines.</p>',
                    ],
                    [
                        'title' => 'Future of BPC-157 Research',
                        'content' => '<p>Ongoing research continues to explore the potential applications and mechanisms of BPC-157. Future studies may provide further insights into its therapeutic potential and optimal usage protocols.</p>
                        
                        <p>Researchers are investigating various aspects of BPC-157, including dosage optimization, administration methods, and potential combination therapies. As research progresses, our understanding of this peptide continues to evolve.</p>',
                    ],
                ],
                'shop_url' => '/products',
                'published_at' => Carbon::now()->subDays(1),
            ],
            [
                'title' => 'What is BPC-157?',
                'description' => 'Understanding the basics of BPC-157, a synthetic peptide with potential healing properties.',
                'overview' => '<p>BPC-157 is a synthetic peptide that has been studied for its potential role in tissue repair and recovery processes.</p>',
                'content' => '<p>This educational post provides comprehensive information about BPC-157 and its research applications.</p>',
                'image' => 'bpc-157.png',
                'rating' => 4.85,
                'rating_count' => 128,
                'key_effects' => [
                    'Tissue regeneration support',
                    'Inflammation modulation',
                    'Angiogenesis promotion',
                ],
                'accordion_sections' => [
                    [
                        'title' => 'Introduction to BPC-157',
                        'content' => '<p>BPC-157 is a research peptide that has gained attention for its potential therapeutic properties.</p>',
                    ],
                ],
                'shop_url' => null,
                'published_at' => Carbon::now()->subDays(5),
            ],
            [
                'title' => 'Safe Handling',
                'description' => 'Essential safety protocols and handling procedures for working with peptides in research settings.',
                'overview' => '<p>Proper handling and storage of peptides is crucial for maintaining their integrity and ensuring research safety.</p>',
                'content' => '<p>This guide covers essential safety protocols for peptide research.</p>',
                'image' => 'bpc-157.png',
                'rating' => 4.90,
                'rating_count' => 95,
                'key_effects' => [
                    'Storage protocols',
                    'Handling procedures',
                    'Safety guidelines',
                ],
                'accordion_sections' => [
                    [
                        'title' => 'Storage Requirements',
                        'content' => '<p>Proper storage is essential for maintaining peptide stability and effectiveness.</p>',
                    ],
                ],
                'shop_url' => null,
                'published_at' => Carbon::now()->subDays(10),
            ],
            [
                'title' => 'Stacking with Others',
                'description' => 'Understanding how to combine different peptides in research protocols and potential synergistic effects.',
                'overview' => '<p>Peptide stacking involves combining multiple peptides in research protocols to investigate potential synergistic effects.</p>',
                'content' => '<p>This educational content explores peptide stacking strategies and considerations.</p>',
                'image' => 'bpc-157.png',
                'rating' => 4.75,
                'rating_count' => 67,
                'key_effects' => [
                    'Synergistic combinations',
                    'Protocol considerations',
                    'Research applications',
                ],
                'accordion_sections' => [
                    [
                        'title' => 'Peptide Combinations',
                        'content' => '<p>Understanding how different peptides may interact in research settings.</p>',
                    ],
                ],
                'shop_url' => null,
                'published_at' => Carbon::now()->subDays(15),
            ],
        ];

        foreach ($posts as $postData) {
            $post = new EducationPost();
            $post->title = $postData['title'];
            $post->slug = Str::slug($postData['title']);
            $post->description = $postData['description'];
            $post->overview = $postData['overview'];
            $post->content = $postData['content'];
            $post->image = $postData['image'];
            $post->rating = $postData['rating'];
            $post->rating_count = $postData['rating_count'];
            $post->key_effects = $postData['key_effects'];
            $post->accordion_sections = $postData['accordion_sections'];
            $post->shop_url = $postData['shop_url'];
            $post->published_at = $postData['published_at'];
            $post->save();
        }

        $this->command->info('Created ' . count($posts) . ' education posts.');
    }
}
