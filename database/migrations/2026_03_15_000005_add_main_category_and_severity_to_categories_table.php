<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'main_category_code')) {
                $table->string('main_category_code', 2)->nullable()->after('name');
            }

            if (!Schema::hasColumn('categories', 'main_category_name')) {
                $table->string('main_category_name')->nullable()->after('main_category_code');
            }

            if (!Schema::hasColumn('categories', 'classification')) {
                $table->string('classification', 20)->nullable()->after('main_category_name');
            }
        });

        $categories = [
            ['name' => 'Not wearing proper school attire and/or LLCC ID inside campus', 'main_category_code' => 'A', 'main_category_name' => 'Offenses Against Security', 'classification' => 'Minor'],
            ['name' => 'Possession of alcoholic drink', 'main_category_code' => 'A', 'main_category_name' => 'Offenses Against Security', 'classification' => 'Major'],
            ['name' => 'Drinking or being under the influence of liquor', 'main_category_code' => 'A', 'main_category_name' => 'Offenses Against Security', 'classification' => 'Major'],
            ['name' => 'Possession of deadly weapons (brass knuckles, stun guns, knives, icepicks, bladed objects)', 'main_category_code' => 'A', 'main_category_name' => 'Offenses Against Security', 'classification' => 'Major'],
            ['name' => 'Possession of firearms, pyrotechnics, or explosives', 'main_category_code' => 'A', 'main_category_name' => 'Offenses Against Security', 'classification' => 'Major'],
            ['name' => 'Deliberate illegal entry into the school premises', 'main_category_code' => 'A', 'main_category_name' => 'Offenses Against Security', 'classification' => 'Major'],
            ['name' => 'Computer hacking and/or identity theft', 'main_category_code' => 'A', 'main_category_name' => 'Offenses Against Security', 'classification' => 'Major'],
            ['name' => 'Possession, distribution, or use of prohibited drugs/controlled substances or paraphernalia', 'main_category_code' => 'A', 'main_category_name' => 'Offenses Against Security', 'classification' => 'Grave'],
            ['name' => 'Being under the influence of prohibited drugs', 'main_category_code' => 'A', 'main_category_name' => 'Offenses Against Security', 'classification' => 'Grave'],

            ['name' => 'Disrespect (egregious conduct, demeaning, intimidating, passive-aggressive behavior)', 'main_category_code' => 'B', 'main_category_name' => 'Offenses Against Persons', 'classification' => 'Major'],
            ['name' => 'Harassment or any form of bullying', 'main_category_code' => 'B', 'main_category_name' => 'Offenses Against Persons', 'classification' => 'Major'],
            ['name' => 'Defamation (slander/libel), malicious imputation, irresponsible use of social media', 'main_category_code' => 'B', 'main_category_name' => 'Offenses Against Persons', 'classification' => 'Major'],
            ['name' => 'Initiating offensive action that provokes violence', 'main_category_code' => 'B', 'main_category_name' => 'Offenses Against Persons', 'classification' => 'Major'],
            ['name' => 'Using profane, abusive, or indecent language against a fellow student or visitor', 'main_category_code' => 'B', 'main_category_name' => 'Offenses Against Persons', 'classification' => 'Major'],
            ['name' => 'Assault resulting in physical injury or death', 'main_category_code' => 'B', 'main_category_name' => 'Offenses Against Persons', 'classification' => 'Grave'],
            ['name' => 'Threatening another with an act amounting to a crime or injury', 'main_category_code' => 'B', 'main_category_name' => 'Offenses Against Persons', 'classification' => 'Grave'],

            ['name' => 'Vandalism or malicious destruction of college/community property', 'main_category_code' => 'C', 'main_category_name' => 'Offenses Against Property', 'classification' => 'Major'],
            ['name' => 'Use of unauthorized software', 'main_category_code' => 'C', 'main_category_name' => 'Offenses Against Property', 'classification' => 'Major'],
            ['name' => 'Theft / robbery / pilferage / extortion or any attempt thereof / unjust enrichment', 'main_category_code' => 'C', 'main_category_name' => 'Offenses Against Property', 'classification' => 'Grave'],

            ['name' => 'Simple misconduct', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Minor'],
            ['name' => 'Littering / unsanitary acts (including spitting)', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Minor'],
            ['name' => 'Smoking outside the campus', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Minor'],
            ['name' => 'Loitering in corridors / creating disturbance / blocking corridors and stairways', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Minor'],
            ['name' => 'Eating in restricted areas', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Minor'],
            ['name' => 'Unauthorized use of classrooms or school facilities', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Minor'],
            ['name' => 'Unauthorized posting of announcements, posters, or streamers', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Minor'],
            ['name' => 'Refusal to submit oneself or belongings for lawful inspection', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Minor'],
            ['name' => 'Unruly behavior (yelling, shouting, horseplay, jumping out of windows, etc.)', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Minor'],
            ['name' => 'Unauthorized use of mobile phones or gadgets during class hours', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Minor'],
            ['name' => 'Playing card games inside the campus', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Minor'],
            ['name' => 'Violation of institute-imposed policies', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Minor'],
            ['name' => 'Smoking inside the campus', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Major'],
            ['name' => 'Deliberate bringing of prohibited items into campus', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Major'],
            ['name' => 'Possession/display/distribution of subversive materials, pictures, videos, films', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Major'],
            ['name' => 'Creating barricades or obstructions', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Major'],
            ['name' => 'Inciting to fight', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Major'],
            ['name' => 'Deliberate disruption of classes or school activities', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Major'],
            ['name' => 'Refusal to undergo random drug testing', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Major'],
            ['name' => 'Bribery or attempt to bribe faculty, staff, or security', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Major'],
            ['name' => 'Willful failure to comply with summonses or disciplinary investigation notices', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Major'],
            ['name' => 'Gambling', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Major'],
            ['name' => 'Habitual/willful violation of policies (more than 2 minor offenses of same nature)', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Major'],
            ['name' => 'Hazing and/or recruitment into unrecognized fraternities, sororities, or organizations', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Grave'],
            ['name' => 'Involvement in fraternity-related disorders', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Grave'],
            ['name' => 'Acts of subversion/insurgency (unauthorized demos, rallies, class boycotts)', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Grave'],
            ['name' => 'Grave misconduct bringing dishonor or discredit to the College', 'main_category_code' => 'D', 'main_category_name' => 'Offenses Against Order', 'classification' => 'Grave'],

            ['name' => 'Violation of test-taking protocol (cheating)', 'main_category_code' => 'E', 'main_category_name' => 'Offenses Involving Dishonesty', 'classification' => 'Major'],
            ['name' => 'Perjury, concealment or omission of material facts', 'main_category_code' => 'E', 'main_category_name' => 'Offenses Involving Dishonesty', 'classification' => 'Major'],
            ['name' => 'Plagiarism', 'main_category_code' => 'E', 'main_category_name' => 'Offenses Involving Dishonesty', 'classification' => 'Major'],
            ['name' => "Dishonesty (lending/using another's ID, Certificate of Enrollment, school attire)", 'main_category_code' => 'E', 'main_category_name' => 'Offenses Involving Dishonesty', 'classification' => 'Major'],
            ['name' => 'Representing the College in off-campus activities without authorization', 'main_category_code' => 'E', 'main_category_name' => 'Offenses Involving Dishonesty', 'classification' => 'Major'],
            ['name' => 'Credit card fraud', 'main_category_code' => 'E', 'main_category_name' => 'Offenses Involving Dishonesty', 'classification' => 'Grave'],
            ['name' => 'Intentionally making false statements or practicing fraud/deception', 'main_category_code' => 'E', 'main_category_name' => 'Offenses Involving Dishonesty', 'classification' => 'Grave'],
            ['name' => 'Forgery/falsification/alteration or misrepresentation of academic or official records', 'main_category_code' => 'E', 'main_category_name' => 'Offenses Involving Dishonesty', 'classification' => 'Grave'],

            ['name' => 'Possession/display/distribution of pornographic or morally offensive materials', 'main_category_code' => 'F', 'main_category_name' => 'OFFENSES AGAINST PUBLIC MORALS', 'classification' => 'Major'],
            ['name' => 'Public display of intimacy (kissing, necking, petting, etc.)', 'main_category_code' => 'F', 'main_category_name' => 'OFFENSES AGAINST PUBLIC MORALS', 'classification' => 'Major'],
            ['name' => 'Acts of lewdness, indecency, or sexual advances toward students or staff', 'main_category_code' => 'F', 'main_category_name' => 'OFFENSES AGAINST PUBLIC MORALS', 'classification' => 'Major'],
            ['name' => 'Acts of lasciviousness or sexual harassment (per R.A. 11313 Safe Spaces Act)', 'main_category_code' => 'F', 'main_category_name' => 'OFFENSES AGAINST PUBLIC MORALS', 'classification' => 'Grave'],
            ['name' => 'Conviction in court for a criminal offense involving moral turpitude', 'main_category_code' => 'F', 'main_category_name' => 'OFFENSES AGAINST PUBLIC MORALS', 'classification' => 'Grave'],

            ['name' => 'Electrical outage, exposed wiring, or power hazard', 'main_category_code' => 'G', 'main_category_name' => 'Technical and Facility Issues', 'classification' => 'Major'],
            ['name' => 'Lighting issues (non-functional lights, dim hallways, emergency lights)', 'main_category_code' => 'G', 'main_category_name' => 'Technical and Facility Issues', 'classification' => 'Minor'],
            ['name' => 'Infrastructure damage (ceiling, walls, floors, doors, windows)', 'main_category_code' => 'G', 'main_category_name' => 'Technical and Facility Issues', 'classification' => 'Major'],
            ['name' => 'Computer, projector, or laboratory equipment malfunction', 'main_category_code' => 'G', 'main_category_name' => 'Technical and Facility Issues', 'classification' => 'Minor'],
            ['name' => 'Network or internet connectivity issue', 'main_category_code' => 'G', 'main_category_name' => 'Technical and Facility Issues', 'classification' => 'Minor'],
            ['name' => 'Water, sanitation, plumbing, or restroom facility issue', 'main_category_code' => 'G', 'main_category_name' => 'Technical and Facility Issues', 'classification' => 'Minor'],
            ['name' => 'Air-conditioning or ventilation issue', 'main_category_code' => 'G', 'main_category_name' => 'Technical and Facility Issues', 'classification' => 'Minor'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['name' => $category['name']],
                [
                    'main_category_code' => $category['main_category_code'],
                    'main_category_name' => $category['main_category_name'],
                    'classification' => $category['classification'],
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'classification')) {
                $table->dropColumn('classification');
            }

            if (Schema::hasColumn('categories', 'main_category_name')) {
                $table->dropColumn('main_category_name');
            }

            if (Schema::hasColumn('categories', 'main_category_code')) {
                $table->dropColumn('main_category_code');
            }
        });
    }
};
