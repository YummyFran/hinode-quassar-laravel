<?php

namespace Database\Seeders;

use App\Enums\ProjectRole;
use App\Models\Card;
use App\Models\Project;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Database\Seeder;

class HinodeDemoProjectSeeder extends Seeder
{
    /**
     * Seed one demo board with realistic ticket-style HTML descriptions.
     */
    public function run(): void
    {
        $email = env('DEMO_SEED_USER_EMAIL', 'test@example.com');
        $user = User::where('email', $email)->first() ?? User::query()->first();

        if (! $user) {
            $this->command?->warn('No users found. Create an account first, then run: php artisan db:seed --class=HinodeDemoProjectSeeder');

            return;
        }

        $alreadySeeded = Project::query()
            ->where('title', 'Website Launch')
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->exists();

        if ($alreadySeeded) {
            $this->command?->info("Demo project already exists for {$user->email}; skipping.");

            return;
        }

        $project = Project::create([
            'title' => 'Website Launch',
            'description' => 'Plan, build, and ship the new marketing site.',
            'total_tasks' => 0,
            'completed_tasks' => 0,
        ]);

        $project->users()->attach($user->id, ['role' => ProjectRole::Owner->value]);

        $board = [
            'To Do' => [
                [
                    'title' => 'Write homepage copy',
                    'completed' => false,
                    'description' => <<<'HTML'
<h2>Homepage draft</h2>
<p>Write the <strong>hero</strong> line, three feature blurbs, and a primary CTA above the fold.</p>
<h3>Acceptance criteria</h3>
<ul>
<li>Tagline stays under 8 words</li>
<li>Benefits-first language (productivity, clarity) — avoid internal jargon</li>
<li>CTA label matches analytics event: <code>cta_get_started</code></li>
</ul>
<p><strong>Due:</strong> before design handoff · <em>Owner:</em> Marketing</p>
HTML,
                ],
                [
                    'title' => 'Gather brand assets',
                    'completed' => false,
                    'description' => <<<'HTML'
<p>Collect logo variants, color tokens, and product screenshots for the launch site.</p>
<ul>
<li>Sunrise logo (SVG + PNG @2x)</li>
<li>Coral accent <strong>#DE6262</strong></li>
<li>Warm accent <em>#FFB88C</em></li>
<li>Three hero screenshots (dashboard, board, todos)</li>
</ul>
<p>Upload to shared drive and link in this card when done.</p>
HTML,
                ],
                [
                    'title' => 'Configure production DNS',
                    'completed' => false,
                    'description' => <<<'HTML'
<h2>DNS cutover checklist</h2>
<ol>
<li>Create <code>A</code> / <code>AAAA</code> records for <strong>www</strong> and apex</li>
<li>Lower TTL to 300s 24h before switch</li>
<li>Verify SSL cert covers apex + www</li>
</ol>
<p>Rollback plan: revert to previous CDN hostname if error rate &gt; 1% for 15 minutes.</p>
HTML,
                ],
            ],
            'Doing' => [
                [
                    'title' => 'Design landing layout',
                    'completed' => false,
                    'description' => <<<'HTML'
<p>Wireframe the <em>hero</em>, features grid, social proof, and footer.</p>
<ol>
<li>Sticky nav with sign-in / sign-up</li>
<li>Full-bleed gradient hero (match Hinode brand)</li>
<li>Feature cards in a responsive grid</li>
</ol>
<p>Link Figma in comments when the first pass is ready for review.</p>
HTML,
                ],
                [
                    'title' => 'Integrate analytics',
                    'completed' => false,
                    'description' => <<<'HTML'
<p>Instrument key funnel events on the marketing site.</p>
<ul>
<li><strong>page_view</strong> — all routes</li>
<li><strong>signup_started</strong> / <strong>signup_completed</strong></li>
<li><strong>demo_project_opened</strong> — from CTA</li>
</ul>
<p>Use the staging property ID until launch day.</p>
HTML,
                ],
            ],
            'Done' => [
                [
                    'title' => 'Choose tech stack',
                    'completed' => true,
                    'description' => <<<'HTML'
<p>Ship the full-stack demo with:</p>
<ul>
<li><strong>Quasar</strong> + Vue 3 on the client</li>
<li><strong>Laravel</strong> API with Sanctum cookie auth</li>
<li>Rich text descriptions via Quill (HTML stored on cards)</li>
</ul>
<p>Decision recorded in ADR-001 — no CDN-only prototype for this repo.</p>
HTML,
                ],
                [
                    'title' => 'Set up CI for client build',
                    'completed' => true,
                    'description' => <<<'HTML'
<p>Pipeline runs <code>npm run build</code> in <code>client/</code> on every PR.</p>
<p>Artifacts: dist folder + lint step. Fail fast on TypeScript/ESLint errors.</p>
HTML,
                ],
            ],
        ];

        foreach ($board as $listTitle => $cards) {
            $list = TaskList::create([
                'project_id' => $project->id,
                'title' => $listTitle,
            ]);

            foreach ($cards as $position => $cardData) {
                Card::create([
                    'task_list_id' => $list->id,
                    'title' => $cardData['title'],
                    'description' => trim($cardData['description']),
                    'position' => $position,
                    'completed' => $cardData['completed'],
                ]);
            }
        }

        $project->recalculateTaskCounts();
        $project->touch();

        $this->command?->info("Seeded “Website Launch” for {$user->email} ({$project->total_tasks} tasks, {$project->completed_tasks} done).");
    }
}
