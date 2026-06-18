<?php

namespace Database\Seeders;

use App\Actions\Issues\CreateIssue;
use App\Enums\ActorType;
use App\Enums\IssueStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\IssueComment;
use App\Models\Officer;
use App\Models\OfficerIssueResolution;
use App\Models\OfficerIssueUpdate;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a small batch of realistic Dutch issues with random names/addresses
 * in Rotterdam. Safe to run in production for demo purposes.
 */
class SampleDutchIssuesSeeder extends Seeder
{
    private array $issueTemplates = [
        [
            'title' => 'Geluidsoverlast van buren midden in de nacht',
            'content' => 'Onze buren hebben voor de derde keer deze week doordeweeks een feestje tot diep in de nacht. De muziek staat ontzettend hard en er wordt geschreeuwd. Dit zorgt voor veel slapeloze nachten.',
            'category_name' => 'Geluidsoverlast',
        ],
        [
            'title' => 'Auto staat volledig op de stoep geparkeerd',
            'content' => 'Er staat een grote zwarte SUV al twee dagen midden op de stoep. Mensen met een kinderwagen of rolstoel kunnen er onmogelijk langs en moeten de gevaarlijke autoweg op.',
            'category_name' => 'Parkeren op stoep',
        ],
        [
            'title' => 'Grofvuil gedumpt naast de ondergrondse container',
            'content' => 'Er heeft iemand een oud matras, een kapotte stoel en meerdere vuilniszakken naast de container gezet. Het trekt ongedierte aan en de straat ziet er heel erg onverzorgd uit.',
            'category_name' => 'Illegaal dumpen vuil',
        ],
        [
            'title' => 'Hondenpoep op het kinderspeelveld',
            'content' => 'Het speelveldje ligt weer vol met hondenpoep. Ondanks het verbodsbordje laten veel mensen hun hond hier uit. Kinderen kunnen niet veilig spelen.',
            'category_name' => 'Hondenoverlast',
        ],
        [
            'title' => 'Groep hangjongeren zorgt voor intimidatie',
            'content' => 'Een groep jongeren hangt elke avond bij de ingang van de supermarkt. Ze vallen voorbijgangers lastig, roepen nare dingen en laten overal lachgaspatronen en blikjes achter.',
            'category_name' => 'Jeugdoverlast',
        ],
        [
            'title' => 'Bushokje compleet vernield',
            'content' => 'Het glas van het bushokje is gisteravond ingeslagen. Er liggen overal glasscherven op de stoep en het fietspad, wat erg gevaarlijk is.',
            'category_name' => 'Vernieling',
        ],
        [
            'title' => 'Zwaar vuurwerk afgestoken overdag',
            'content' => 'Er wordt de hele middag al zwaar illegaal vuurwerk afgestoken in de wijk. Het geeft enorme knallen en zorgt voor veel schrik bij ouderen en huisdieren.',
            'category_name' => 'Vuurwerkoverlast',
        ],
        [
            'title' => 'Lachgascilinders en ballonnen op straat',
            'content' => 'Er liggen tientallen lege lachgas patronen en kapotte ballonnen op de parkeerplaats. Dit geeft een ontzettend rommelig beeld en is slecht voor het milieu.',
            'category_name' => 'Lachgas',
        ],
        [
            'title' => 'Graffiti op de muur van het buurthuis',
            'content' => 'Afgelopen nacht is er een grote onleesbare graffiti tag op de zijmuur van ons buurthuis gespoten. Graag zo snel mogelijk verwijderen.',
            'category_name' => 'Beklading',
        ],
        [
            'title' => 'Auto zonder kaart op gehandicaptenparkeerplaats',
            'content' => 'Er staat een bestelbusje geparkeerd op de gehandicaptenparkeerplaats zonder geldige kaart. Hierdoor kunnen mensen die de plek echt nodig hebben er niet staan.',
            'category_name' => 'Parkeren mindervaliden',
        ],
        [
            'title' => 'Mensen slapen in het portiek van de flat',
            'content' => 'Sinds enkele dagen slapen er twee dakloze personen in ons portiek. Ze laten rommel achter en bewoners voelen zich onveilig als ze in de avond thuiskomen.',
            'category_name' => 'Buitenslapers',
        ],
        [
            'title' => 'Bedelaar valt winkelend publiek lastig',
            'content' => 'Een man is erg agressief aan het bedelen voor de supermarkt. Als mensen niks geven wordt hij boos en begint hij te schelden.',
            'category_name' => 'Bedelen',
        ],
        [
            'title' => 'Meeuwen trekken vuilniszakken open',
            'content' => 'Mensen hebben hun vuilniszakken veel te vroeg buiten gezet. De meeuwen hebben alles opengetrokken en nu waait het vuilnis door de hele straat.',
            'category_name' => 'Illegaal dumpen vuil',
        ],
        [
            'title' => 'Harde muziek uit auto op parkeerplaats',
            'content' => 'Er staat een auto met ronkende motor op de parkeerplaats en de muziek staat keihard. De trillingen zijn tot in de woonkamer te voelen.',
            'category_name' => 'Geluidsoverlast',
        ],
        [
            'title' => 'Wildplassers in de steeg',
            'content' => 'Elk weekend in de nacht wordt de steeg naast ons huis gebruikt als openbaar toilet door stappers. De stank is inmiddels niet meer te harden.',
            'category_name' => 'Vandalisme', // Using Vandalisme as fallback for this general nuisance
        ]
    ];

    public function run(): void
    {
        $faker = Faker::create('nl_NL');

        $categories = Category::all()->keyBy('name');
        
        // Ensure we have a district to fall back to, or pick randomly
        $districts = District::all();
        $officers = Officer::all();
        
        if ($districts->isEmpty()) {
            $this->command->error('No districts found. Run DistrictSeeder first.');
            return;
        }

        $password = Hash::make('password123'); // Default password for sample users

        $commenters = [];
        for ($i = 0; $i < 5; $i++) {
            $commenters[] = User::create([
                'username' => $faker->unique()->userName(),
                'email' => $faker->unique()->safeEmail(),
                'password' => $password,
                'is_active' => true,
            ]);
        }

        foreach ($this->issueTemplates as $template) {
            $category = $categories->get($template['category_name']);
            
            if (! $category) {
                // Fallback to the first available category if exact name not found
                $category = $categories->first();
            }

            $district = $districts->random();
            
            // Generate Rotterdam-like address
            $postalCode = '30' . $faker->numberBetween(11, 89) . ' ' . strtoupper($faker->lexify('??'));
            $address = $faker->streetName() . ' ' . $faker->buildingNumber() . ', Rotterdam';
            
            // Rotterdam approximate coordinates
            $latitude = $faker->latitude(51.88, 51.98);
            $longitude = $faker->longitude(4.40, 4.55);

            // Create a random Dutch reporter
            $reporter = User::create([
                'username' => $faker->unique()->userName(),
                'email' => $faker->unique()->safeEmail(),
                'password' => $password,
                'is_active' => true,
            ]);

            $issue = (new CreateIssue)->create($reporter, [
                'title' => $template['title'],
                'content' => $template['content'],
                'category_id' => $category->id,
                'district_id' => $district->id,
                'postal_code' => $postalCode,
                'address' => $address,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'is_anonymous' => $faker->boolean(20), // 20% chance to be anonymous
            ]);

            if ($officers->isNotEmpty()) {
                $chance = $faker->numberBetween(1, 100);
                
                if ($chance <= 30) {
                    $officer = $officers->random();
                    
                    $issue->update([
                        'status' => IssueStatus::InProgress,
                        'assigned_officer_id' => $officer->id,
                    ]);
                    
                    OfficerIssueUpdate::create([
                        'issue_id' => $issue->id,
                        'officer_id' => $officer->id,
                        'title' => 'Melding in behandeling',
                        'content' => $faker->randomElement([
                            'We hebben de locatie geïnspecteerd en een aannemer ingeschakeld. Naar verwachting is dit binnen enkele werkdagen opgelost.',
                            'Bedankt voor de melding. We hebben het doorgegeven aan het wijkteam en zij pakken dit zo snel mogelijk op.',
                            'Onze medewerkers zijn op de hoogte gesteld en we hebben dit ingepland voor komende week.'
                        ]),
                    ]);
                } elseif ($chance <= 60) {
                    $officer = $officers->random();
                    
                    $issue->update([
                        'status' => IssueStatus::Resolved, 
                        'assigned_officer_id' => $officer->id,
                        'resolved_at' => now(),
                    ]);
                    
                    // Add an initial progress update before it was resolved
                    OfficerIssueUpdate::create([
                        'issue_id' => $issue->id,
                        'officer_id' => $officer->id,
                        'title' => 'Melding in behandeling',
                        'content' => 'We hebben uw melding in goede orde ontvangen en hebben een team aangestuurd om dit te onderzoeken.',
                    ]);

                    OfficerIssueResolution::create([
                        'issue_id' => $issue->id,
                        'officer_id' => $officer->id,
                        'title' => 'Probleem is verholpen',
                        'content' => $faker->randomElement([
                            'Het probleem is inmiddels succesvol verholpen door onze stadsreiniging. Bedankt voor uw melding!',
                            'De situatie is geïnspecteerd en we hebben direct actie ondernomen om het op te lossen. De locatie is weer netjes en veilig.',
                            'Onze aannemer heeft de reparatiewerkzaamheden succesvol afgerond. Mocht u nog verdere overlast ervaren, horen we het graag.'
                        ]),
                    ]);
                }
            }

            if ($faker->boolean(50)) {
                $numComments = $faker->numberBetween(1, 3);
                $commentTemplates = [
                    'Helemaal mee eens, ik heb hier ook heel veel last van.',
                    'Schandalig dat dit gebeurt in onze wijk. Hopelijk doet de gemeente er snel wat aan.',
                    'Ik heb dit gisteren ook gezien, echt niet normaal.',
                    'Bedankt voor het melden, ik wilde het net zelf doorgeven.',
                    'Dit speelt al weken, wordt tijd dat er actie wordt ondernomen.',
                    'Belachelijk! We betalen genoeg belasting om dit schoon te houden.',
                    'Goed dat dit wordt aangekaart.',
                ];

                for ($i = 0; $i < $numComments; $i++) {
                    $commenter = $faker->randomElement($commenters);
                    
                    IssueComment::create([
                        'issue_id' => $issue->id,
                        'author_type' => ActorType::User,
                        'user_id' => $commenter->id,
                        'content' => $faker->randomElement($commentTemplates),
                        'is_anonymous' => $faker->boolean(40),
                    ]);
                }
            }
        }
        
        $this->command->info('Created ' . count($this->issueTemplates) . ' realistic Dutch sample issues in Rotterdam.');
    }
}
