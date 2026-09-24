<?php

namespace Database\Seeders;

use App\Enums\AccessType;
use App\Enums\EpisodeLanguage;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Episode;
use App\Models\MembershipPlan;
use App\Models\PremiumBenefit;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Super Admin User & Test Users
        $superAdminEmail = env('INITIAL_SUPER_ADMIN_EMAIL', 'superadmin@lovetalkpodcast.in');
        User::firstOrCreate(
            ['email' => $superAdminEmail],
            [
                'name' => 'Kota RJ Pawan (Host & Admin)',
                'role' => UserRole::SUPER_ADMIN,
                'status' => UserStatus::ACTIVE,
                'avatar_url' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=KotaRJPawanSuperAdmin',
                'last_login_at' => now(),
            ]
        );

        $priya = User::firstOrCreate(
            ['email' => 'priya.k@gmail.com'],
            [
                'name' => 'Priya Kapoor',
                'role' => UserRole::USER,
                'status' => UserStatus::ACTIVE,
                'avatar_url' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=priya.k@gmail.com',
                'last_login_at' => now(),
            ]
        );

        // Seed Active Membership for Priya
        \App\Models\Membership::updateOrCreate(
            ['user_id' => $priya->id],
            [
                'plan_id' => 'plan-youth',
                'status' => 'active',
                'start_date' => now()->subMonths(1),
                'end_date' => now()->addMonths(11),
                'auto_renew' => true,
                'razorpay_subscription_id' => 'sub_test_priya_123',
            ]
        );

        // 2. Membership Plans
        $plans = [
            [
                'id' => 'plan-youth',
                'name' => 'Youth',
                'slug' => 'youth',
                'target_audience' => '18–26 years',
                'price' => 99.00,
                'currency' => 'INR',
                'billing_period' => 'year',
                'discounted_price' => 99.00,
                'is_active' => true,
                'is_featured' => true,
                'badge' => 'Best Value',
                'benefits' => [
                    'All 25 Premium Benefits',
                    'Exclusive Community Access',
                    'Monthly Live Q&A Webinars',
                ],
                'razorpay_plan_id' => 'plan_youth_annual',
            ],
            [
                'id' => 'plan-professional',
                'name' => 'Professional',
                'slug' => 'professional',
                'target_audience' => 'Working Professionals & 26+',
                'price' => 399.00,
                'currency' => 'INR',
                'billing_period' => 'year',
                'discounted_price' => 399.00,
                'is_active' => true,
                'is_featured' => false,
                'badge' => 'Popular',
                'benefits' => [
                    'All 25 Premium Benefits',
                    'Exclusive Community + Channel',
                    'Monthly Live Q&A + Group Coaching',
                    '10% Discount on Consultations',
                ],
                'razorpay_plan_id' => 'plan_professional_annual',
            ],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        // 3. Dynamic 25 Premium Benefits
        $benefitTitles = [
            'Unlimited Unlocked Premium Episodes',
            'Ad-Free Listening Experience',
            'High Quality 320kbps Audio Streams',
            'Full Dual English & Hindi Transcripts',
            'Download Episodes for Offline Playback',
            'Exclusive Monthly Live Q&A Webinars',
            'VIP Telegram Community Channel',
            'Early Access to New Friday Episodes',
            '10% Discount on 1-on-1 Relationship Consultations',
            'Access to "Love Talk 1.0" eBook Edition',
            'Private Listener Discussion Forums',
            'Direct Question Submission for By Kota RJ Pawan',
            'Exclusive Bonus After-Show Audio Clips',
            'Monthly Relationship Worksheets & Guides',
            'Group Coaching Circles',
            'Anxiety & Overthinking Self-Guided Journal',
            'Couples Communication Challenge Tracker',
            'Priority Customer Support',
            'Exclusive Member Spotlight Invites',
            'Custom Playlist Creator',
            'Sponsor Discount Perks',
            'Annual Relationship Wellness Report',
            'Invites to Regional Meetups in India',
            'Self-Connection Meditation Audios',
            'Lifetime Access to Episode Archives',
        ];

        foreach ($benefitTitles as $index => $title) {
            $id = 'benefit-' . ($index + 1);
            PremiumBenefit::updateOrCreate(
                ['id' => $id],
                [
                    'title' => $title,
                    'description' => 'Included with all Love Talk Premium Subscriptions.',
                    'icon' => $index % 2 === 0 ? 'Sparkles' : 'CheckCircle2',
                    'is_enabled' => true,
                    'sort_order' => $index + 1,
                    'plans' => ['youth', 'professional'],
                ]
            );
        }

        // 4. Initial Episodes
        $episodes = [
            [
                'id' => 'ep-14',
                'title' => 'Why Self Connection is the Foundation of Love',
                'episode_number' => 14,
                'slug' => 'why-self-connection-is-the-foundation',
                'description' => 'In this episode, Kota RJ Pawan dives deep into why understanding your own emotional needs, boundaries, and inner child is the prerequisite for building a healthy, lasting relationship.',
                'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
                'audio_duration' => 1840,
                'cover_image' => '/images/episode_1.jpg',
                'language' => EpisodeLanguage::ENGLISH,
                'tags' => ['Self-Love', 'Emotional Wellness', 'Relationships'],
                'publish_date' => now()->subDays(5),
                'is_published' => true,
                'access_type' => AccessType::PREMIUM,
                'preview_duration' => 60,
                'listens_count' => 1240,
            ],
            [
                'id' => 'ep-13',
                'title' => 'Overcoming Relationship Anxiety & Overthinking',
                'episode_number' => 13,
                'slug' => 'overcoming-relationship-anxiety',
                'description' => 'Practical frameworks to silence overthinking spirals, decode attachment styles, and communicate vulnerabilities with your partner without escalating friction.',
                'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3',
                'audio_duration' => 1650,
                'cover_image' => '/images/episode_2.jpg',
                'language' => EpisodeLanguage::BILINGUAL,
                'tags' => ['Anxiety', 'Communication', 'Mindset'],
                'publish_date' => now()->subDays(12),
                'is_published' => true,
                'access_type' => AccessType::FREE,
                'preview_duration' => 60,
                'listens_count' => 3890,
            ],
            [
                'id' => 'ep-12',
                'title' => 'Navigating Boundaries with Indian In-Laws & Extended Families',
                'episode_number' => 12,
                'slug' => 'navigating-boundaries-in-laws',
                'description' => 'Cultural nuances, setting loving yet firm boundaries, and fostering respectful partnerships amidst joint family dynamics.',
                'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3',
                'audio_duration' => 2100,
                'cover_image' => '/images/episode_3.jpg',
                'language' => EpisodeLanguage::HINDI,
                'tags' => ['Family Dynamics', 'Boundaries', 'Marriage'],
                'publish_date' => now()->subDays(19),
                'is_published' => true,
                'access_type' => AccessType::PREMIUM,
                'preview_duration' => 60,
                'listens_count' => 2450,
            ],
            [
                'id' => 'ep-11',
                'title' => 'Dealing with Past Baggage & Healing Together',
                'episode_number' => 11,
                'slug' => 'dealing-with-past-baggage',
                'description' => 'How to bring your past heartbreaks into a new relationship without allowing previous hurts to sabotage present happiness.',
                'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-4.mp3',
                'audio_duration' => 1980,
                'cover_image' => '/images/podcast_cover.jpg',
                'language' => EpisodeLanguage::ENGLISH,
                'tags' => ['Emotional Wellness', 'Self-Love'],
                'publish_date' => now()->subDays(26),
                'is_published' => true,
                'access_type' => AccessType::FREE,
                'preview_duration' => 0,
                'listens_count' => 15600,
            ],
            [
                'id' => 'ep-10',
                'title' => 'शादी और भविष्य के फैसले (Marriage & Financial Compatibility)',
                'episode_number' => 10,
                'slug' => 'marriage-and-financial-compatibility',
                'description' => 'फाइनेंस और मैरिज कमिटमेंट पर खुल कर बात कैसे करें? Kota RJ Pawan outlines essential pre-marriage conversation frameworks for Indian couples.',
                'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-5.mp3',
                'audio_duration' => 2400,
                'cover_image' => '/images/episode_1.jpg',
                'language' => EpisodeLanguage::HINDI,
                'tags' => ['Marriage & Future', 'Hindi Special', 'Communication'],
                'publish_date' => now()->subDays(33),
                'is_published' => true,
                'access_type' => AccessType::FREE,
                'preview_duration' => 0,
                'listens_count' => 31200,
            ],
        ];

        foreach ($episodes as $ep) {
            Episode::updateOrCreate(['slug' => $ep['slug']], $ep);
        }

        // 5. Site Settings
        SiteSetting::set('platform_name', 'Love Talk Podcast');
        SiteSetting::set('tagline', 'Real Conversations on Love, Connection & Relationships');
        SiteSetting::set('host_name', 'Kota RJ Pawan');
        SiteSetting::set('social_telegram', 'https://t.me/lovetalkpodcast');
        SiteSetting::set('social_instagram_channel', 'https://www.instagram.com/channel/AbbCzQrb_rbQL47c/');
        SiteSetting::set('social_facebook_group', 'https://facebook.com/groups/1036267591933452/');
        SiteSetting::set('social_arattai', 'https://aratt.ai/@lovetalk_podcast_by_kotarjpawa');
        SiteSetting::set('social_youtube_community', 'https://youtube.com/@kotarjpawan/community?si=ccqBGKMONX4HK2ug');
        SiteSetting::set('social_youtube_playlist', 'https://youtube.com/playlist?list=PLMMrN7fUIrdSskjkpNFywhhidWODSklsr&si=WTaYQpmlksNBW25G');
        SiteSetting::set('social_facebook', 'https://www.facebook.com/kotarjpawan');
        SiteSetting::set('social_instagram', 'https://www.instagram.com/kotarjpawan?stkn=Y2tkOGhpYTA3enph');
        SiteSetting::set('social_twitter', 'https://x.com/KOTARJPAWAN');
        SiteSetting::set('social_linkedin', 'https://www.linkedin.com/in/kotarjpawan');
    }
}
