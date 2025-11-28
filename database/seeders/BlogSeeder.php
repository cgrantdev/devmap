<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Blog;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $blogs = [
            [
                'title' => 'Safe Handling of Peptides in Laboratory Settings',
                'description' => 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation, and promote faster recovery from injuries.',
                'content' => '<p>Peptides are powerful research compounds that require careful handling and storage. This comprehensive guide covers essential safety protocols for working with peptides in laboratory environments.</p>
                
                <h2>Storage Requirements</h2>
                <p>Proper storage is critical for maintaining peptide integrity. Most peptides should be stored at temperatures between -20°C and -80°C, depending on their specific stability requirements. Always follow manufacturer guidelines for optimal storage conditions.</p>
                
                <h2>Handling Procedures</h2>
                <p>When handling peptides, always wear appropriate personal protective equipment (PPE) including gloves, lab coats, and safety goggles. Work in a well-ventilated area and avoid direct skin contact with peptide solutions.</p>
                
                <h2>Reconstitution Guidelines</h2>
                <p>Reconstitute peptides using sterile water or appropriate buffers as specified. Avoid repeated freeze-thaw cycles, as this can degrade peptide structure and reduce efficacy.</p>
                
                <h2>Documentation</h2>
                <p>Maintain detailed records of all peptide handling, including batch numbers, storage conditions, and usage dates. This documentation is essential for research reproducibility and safety compliance.</p>',
                'image' => '1.jpg',
                'read_time' => '19 Min Read',
                'published_at' => Carbon::now()->subDays(5),
                'is_featured' => true,
            ],
            [
                'title' => 'Research Guidelines for Peptide Studies',
                'description' => 'Understanding proper research methodologies and ethical considerations when conducting peptide-related studies in laboratory settings.',
                'content' => '<p>Conducting research with peptides requires adherence to strict scientific and ethical guidelines. This article outlines best practices for peptide research studies.</p>
                
                <h2>Study Design</h2>
                <p>Well-designed studies require clear objectives, appropriate controls, and sufficient sample sizes. Consider factors such as peptide stability, dosage, and administration methods when designing your research protocol.</p>
                
                <h2>Ethical Considerations</h2>
                <p>All research involving peptides must comply with institutional review board (IRB) requirements and applicable regulations. Ensure proper documentation and approval before beginning any study.</p>
                
                <h2>Data Collection</h2>
                <p>Maintain rigorous data collection standards, including detailed notes on experimental conditions, observations, and results. Use standardized measurement tools and protocols to ensure data reliability.</p>',
                'image' => '1.jpg',
                'read_time' => '15 Min Read',
                'published_at' => Carbon::now()->subDays(10),
                'is_featured' => false,
            ],
            [
                'title' => 'Benefits of BPC-157 in Research',
                'description' => 'Exploring the potential therapeutic benefits of BPC-157, a synthetic peptide that has shown promise in various research applications.',
                'content' => '<p>BPC-157 (Body Protection Compound-157) is a synthetic peptide derived from a protein found in gastric juice. Research suggests it may have several beneficial properties.</p>
                
                <h2>Potential Applications</h2>
                <p>Studies have investigated BPC-157 for its potential role in tissue repair, inflammation reduction, and gastrointestinal protection. The peptide appears to promote healing processes in various tissue types.</p>
                
                <h2>Mechanism of Action</h2>
                <p>BPC-157 is thought to work through multiple pathways, including promoting angiogenesis, modulating inflammatory responses, and supporting cellular repair mechanisms. Further research is needed to fully understand its mechanisms.</p>
                
                <h2>Research Considerations</h2>
                <p>When studying BPC-157, researchers should consider factors such as dosage, administration route, and timing relative to injury or intervention. Proper controls and measurement protocols are essential.</p>',
                'image' => '1.jpg',
                'read_time' => '12 Min Read',
                'published_at' => Carbon::now()->subDays(15),
                'is_featured' => false,
            ],
            [
                'title' => 'BPC-157 in Sports Medicine Research',
                'description' => 'Investigating the potential role of BPC-157 in sports medicine applications and recovery protocols.',
                'content' => '<p>Sports medicine research has shown interest in peptides like BPC-157 for their potential to support recovery and tissue repair in athletic contexts.</p>
                
                <h2>Recovery Applications</h2>
                <p>Research has explored how BPC-157 might support recovery from musculoskeletal injuries common in athletic activities. Studies have investigated its effects on tendon, ligament, and muscle tissue repair.</p>
                
                <h2>Research Protocols</h2>
                <p>When designing studies in sports medicine contexts, researchers must consider factors such as training loads, recovery periods, and performance metrics. Ethical considerations are paramount.</p>
                
                <h2>Future Directions</h2>
                <p>Ongoing research continues to explore the potential applications of BPC-157 in sports medicine. Well-designed studies with proper controls are essential for advancing understanding in this field.</p>',
                'image' => '1.jpg',
                'read_time' => '18 Min Read',
                'published_at' => Carbon::now()->subDays(20),
                'is_featured' => false,
            ],
            [
                'title' => 'Safety and Usage Protocols for Peptides',
                'description' => 'Essential safety guidelines and usage protocols for researchers working with peptide compounds.',
                'content' => '<p>Safety is paramount when working with any research compound. This guide outlines essential safety protocols for peptide handling and usage.</p>
                
                <h2>Personal Protective Equipment</h2>
                <p>Always use appropriate PPE including gloves, lab coats, safety goggles, and face shields when necessary. Ensure all equipment is properly maintained and replaced as needed.</p>
                
                <h2>Laboratory Safety</h2>
                <p>Work in properly ventilated areas with appropriate containment measures. Follow all laboratory safety protocols and emergency procedures. Keep safety data sheets (SDS) readily available.</p>
                
                <h2>Waste Disposal</h2>
                <p>Dispose of peptide waste according to institutional and regulatory guidelines. Never dispose of peptides in regular trash or down drains. Use designated waste containers and follow proper disposal protocols.</p>',
                'image' => '1.jpg',
                'read_time' => '14 Min Read',
                'published_at' => Carbon::now()->subDays(25),
                'is_featured' => false,
            ],
            [
                'title' => 'Laboratory Protocols for Peptide Research',
                'description' => 'Standard operating procedures and best practices for conducting peptide research in laboratory environments.',
                'content' => '<p>Establishing clear laboratory protocols is essential for successful peptide research. This article outlines key protocols and best practices.</p>
                
                <h2>Standard Operating Procedures</h2>
                <p>Develop and document standard operating procedures (SOPs) for all peptide-related activities. These should cover storage, handling, reconstitution, and experimental procedures.</p>
                
                <h2>Quality Control</h2>
                <p>Implement quality control measures to ensure peptide integrity and experimental reliability. This includes proper storage monitoring, batch tracking, and purity verification.</p>
                
                <h2>Documentation Standards</h2>
                <p>Maintain comprehensive documentation of all procedures, observations, and results. Use standardized forms and electronic lab notebooks to ensure consistency and traceability.</p>',
                'image' => '1.jpg',
                'read_time' => '16 Min Read',
                'published_at' => Carbon::now()->subDays(30),
                'is_featured' => false,
            ],
            [
                'title' => 'Understanding Peptide Stability',
                'description' => 'Factors affecting peptide stability and methods for maintaining peptide integrity during storage and handling.',
                'content' => '<p>Peptide stability is crucial for research success. Understanding factors that affect stability can help researchers maintain peptide integrity throughout their studies.</p>
                
                <h2>Environmental Factors</h2>
                <p>Temperature, humidity, light exposure, and pH can all affect peptide stability. Understanding these factors helps researchers choose appropriate storage and handling conditions.</p>
                
                <h2>Storage Best Practices</h2>
                <p>Store peptides according to manufacturer recommendations, typically at low temperatures in dry conditions. Avoid repeated freeze-thaw cycles and protect from light exposure.</p>
                
                <h2>Stability Testing</h2>
                <p>Regular stability testing can help ensure peptide integrity over time. Monitor peptides for signs of degradation and establish expiration protocols based on stability data.</p>',
                'image' => '1.jpg',
                'read_time' => '13 Min Read',
                'published_at' => Carbon::now()->subDays(35),
                'is_featured' => false,
            ],
            [
                'title' => 'Peptide Dosage and Administration Methods',
                'description' => 'Guidelines for determining appropriate peptide dosages and selecting administration methods for research purposes.',
                'content' => '<p>Determining appropriate dosages and administration methods is critical for successful peptide research. This guide covers key considerations.</p>
                
                <h2>Dosage Considerations</h2>
                <p>Dosage selection should be based on existing literature, pilot studies, and specific research objectives. Consider factors such as peptide potency, research model, and desired outcomes.</p>
                
                <h2>Administration Routes</h2>
                <p>Different administration routes (subcutaneous, intramuscular, oral, etc.) may affect peptide bioavailability and efficacy. Choose routes appropriate for your research model and objectives.</p>
                
                <h2>Dosing Schedules</h2>
                <p>Establish clear dosing schedules based on peptide half-life, research objectives, and practical considerations. Document all dosing procedures thoroughly.</p>',
                'image' => '1.jpg',
                'read_time' => '17 Min Read',
                'published_at' => Carbon::now()->subDays(40),
                'is_featured' => false,
            ],
            [
                'title' => 'Quality Control in Peptide Research',
                'description' => 'Implementing quality control measures to ensure reliability and reproducibility in peptide research studies.',
                'content' => '<p>Quality control is essential for maintaining research integrity and ensuring reproducible results in peptide studies.</p>
                
                <h2>Purity Verification</h2>
                <p>Verify peptide purity using appropriate analytical methods such as HPLC or mass spectrometry. Document purity levels and batch information for all peptides used in research.</p>
                
                <h2>Batch Tracking</h2>
                <p>Maintain detailed records of peptide batches, including source, lot numbers, and storage conditions. This information is crucial for troubleshooting and ensuring consistency.</p>
                
                <h2>Validation Protocols</h2>
                <p>Establish validation protocols for all experimental procedures. This includes method validation, equipment calibration, and regular quality checks.</p>',
                'image' => '1.jpg',
                'read_time' => '11 Min Read',
                'published_at' => Carbon::now()->subDays(45),
                'is_featured' => false,
            ],
            [
                'title' => 'Regulatory Considerations for Peptide Research',
                'description' => 'Understanding regulatory requirements and compliance considerations for peptide research activities.',
                'content' => '<p>Compliance with regulatory requirements is essential for conducting peptide research. This article outlines key regulatory considerations.</p>
                
                <h2>Regulatory Framework</h2>
                <p>Familiarize yourself with applicable regulations governing peptide research in your jurisdiction. This may include FDA guidelines, institutional policies, and international standards.</p>
                
                <h2>Documentation Requirements</h2>
                <p>Maintain comprehensive documentation to demonstrate regulatory compliance. This includes protocols, approvals, data records, and safety documentation.</p>
                
                <h2>Compliance Monitoring</h2>
                <p>Establish processes for ongoing compliance monitoring. Regular audits and reviews can help identify and address compliance issues before they become problems.</p>',
                'image' => '1.jpg',
                'read_time' => '20 Min Read',
                'published_at' => Carbon::now()->subDays(50),
                'is_featured' => false,
            ],
        ];

        foreach ($blogs as $blogData) {
            $blog = new Blog();
            $blog->title = $blogData['title'];
            $blog->slug = Str::slug($blogData['title']);
            $blog->description = $blogData['description'];
            $blog->content = $blogData['content'];
            $blog->image = $blogData['image'];
            $blog->read_time = $blogData['read_time'];
            $blog->published_at = $blogData['published_at'];
            $blog->is_featured = $blogData['is_featured'];
            $blog->save();
        }

        $this->command->info('Created ' . count($blogs) . ' blog posts.');
    }
}
