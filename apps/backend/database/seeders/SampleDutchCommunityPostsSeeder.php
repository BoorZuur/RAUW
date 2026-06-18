<?php

namespace Database\Seeders;

use App\Models\CommunityPost;
use App\Models\CommunityPostAttachment;
use App\Models\District;
use App\Models\Officer;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

/**
 * Seeds a small batch of realistic Dutch community posts (articles)
 * with some random image attachments.
 */
class SampleDutchCommunityPostsSeeder extends Seeder
{
    private array $postTemplates = [
        [
            'title' => 'Bijeenkomst buurtpreventie',
            'content' => "Beste bewoners, aanstaande donderdag organiseren wij een bijeenkomst over buurtpreventie in het Wijkcentrum. De wijkagent zal aanwezig zijn om tips te geven over inbraakpreventie en het herkennen van verdachte situaties. We hopen jullie in grote getale te zien! Koffie en thee staan klaar vanaf 19:00 uur.",
        ],
        [
            'title' => 'Nieuwe speeltoestellen geplaatst',
            'content' => "Goed nieuws! We zijn gestart met de plaatsing van nieuwe speeltoestellen in het park. Er komt onder andere een groot klimrek en een nieuwe veilige schommel. De werkzaamheden duren ongeveer een week. Let op: tijdens deze periode is een deel van de speeltuin veiligheidshalve afgezet.",
        ],
        [
            'title' => 'Tijdelijke wegafsluiting wegens asfaltering',
            'content' => "Let op: van maandag tot en met woensdag is de hoofdweg deels afgesloten in verband met asfalteringswerkzaamheden. Er is een omleiding ingesteld (volg de gele borden). Houd in de spits rekening met extra reistijd. Bedankt voor uw begrip en medewerking.",
        ],
        [
            'title' => 'Gezellige buurtbarbecue aankomend weekend',
            'content' => "Om de zomer goed in te luiden, nodigen we alle bewoners uit voor een gezellige buurtbarbecue op het plein. Wij zorgen voor het eten, de barbecues en een gezellig muziekje. Neem gerust uw eigen kleedje of stoel mee. We starten zaterdag rond 16:00 uur.",
        ],
        [
            'title' => 'Gezocht: vrijwilligers voor de schoonmaakactie',
            'content' => "Aanstaande zaterdag steken we de handen uit de mouwen tijdens de wijkopschoondag. We zoeken nog vrijwilligers die willen helpen om onze straten en groenstroken zwerfafvalvrij te maken. Grijpers en vuilniszakken worden geregeld. Na afloop is er voor iedereen koffie en gebak!",
        ],
        [
            'title' => 'Gewijzigde tijden inloopspreekuur wijkagent',
            'content' => "Het wekelijkse inloopspreekuur van uw wijkagent is verplaatst. Vanaf volgende maand bent u welkom op de donderdagmiddag tussen 14:00 en 16:00 in het stadhuis. U kunt hier zonder afspraak binnenlopen voor vragen, advies of een praatje.",
        ],
        [
            'title' => 'Waarschuwing: Pas op voor babbeltrucs',
            'content' => "De afgelopen weken hebben we meerdere meldingen ontvangen van oplichters die met een babbeltruc proberen woningen binnen te komen. Ze doen zich soms voor als medewerkers van de watermaatschappij of thuiszorg. Laat nooit zomaar onbekenden binnen en bel bij twijfel altijd de politie via 112.",
        ],
        [
            'title' => 'Grofvuil ophaaldag',
            'content' => "Volgende week dinsdag komt de gemeente weer grofvuil ophalen. U kunt uw spullen aanmelden via de website van de gemeente. Zorg ervoor dat het grofvuil niet te vroeg buiten wordt gezet om zwerfafval te voorkomen. Alleen aangemeld vuil wordt meegenomen.",
        ]
    ];

    public function run(): void
    {
        $faker = Faker::create('nl_NL');

        $officers = Officer::all();
        $districts = District::all();

        if ($officers->isEmpty()) {
            $this->command->error('No officers found. Run ProductionOfficerSeeder first.');
            return;
        }

        if ($districts->isEmpty()) {
            $this->command->error('No districts found. Run DistrictSeeder first.');
            return;
        }

        $count = 0;
        $attachmentCount = 0;

        foreach ($this->postTemplates as $template) {
            $officer = $officers->random();
            $district = $districts->random();

            $post = CommunityPost::create([
                'officer_id' => $officer->id,
                'district_id' => $district->id,
                'title' => $template['title'],
                'content' => $template['content'],
                'visibility' => 'visible',
            ]);

            $count++;

            // 60% chance to have an image attachment
            if ($faker->boolean(60)) {
                $width = $faker->randomElement([800, 1024, 1200]);
                $height = $faker->randomElement([600, 768, 800]);
                $seed = $faker->lexify('?????');
                
                CommunityPostAttachment::create([
                    'community_post_id' => $post->id,
                    'file_path' => 'community-post-attachments/' . $faker->uuid() . '.jpg',
                    'file_url' => "https://picsum.photos/seed/{$seed}/{$width}/{$height}",
                    'original_name' => 'afbeelding-' . $faker->word() . '.jpg',
                    'file_type' => 'image/jpeg',
                    'file_size' => $faker->numberBetween(50000, 2500000),
                    'uploaded_at' => now()->subDays($faker->numberBetween(0, 10)),
                ]);

                $attachmentCount++;
            }
        }

        $this->command->info("Created {$count} realistic Dutch community posts with {$attachmentCount} sample attachments.");
    }
}
