<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ──────────────────────────────────────────────────────
        // 1. QUESTION BANK
        // ──────────────────────────────────────────────────────
        Schema::create('question_bank', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('question_type', 30);
            $table->text('question_text');
            $table->longText('question_html')->nullable();

            $table->text('explanation')->nullable();
            $table->longText('explanation_html')->nullable();

            // Answer data — flexible JSON for all question types
            $table->json('options')->nullable();
            $table->json('correct_answer')->nullable();
            $table->json('matching_pairs')->nullable();
            $table->json('ordering_items')->nullable();
            $table->string('fill_blank_answer')->nullable();
            $table->text('short_answer')->nullable();
            $table->decimal('numeric_answer', 12, 4)->nullable();

            // Scoring & metadata
            $table->decimal('marks', 5, 2)->default(1.00);
            $table->string('difficulty', 20)->default('intermediate');
            $table->string('topic')->nullable();
            $table->string('subtopic')->nullable();
            $table->string('learning_objective')->nullable();
            $table->string('competency')->nullable();
            $table->string('curriculum_reference')->nullable();
            $table->string('grade_level')->nullable();
            $table->json('tags')->nullable();
            $table->json('images')->nullable();

            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'subject_id', 'status'], 'idx_qb_school_subj_status');
            $table->index(['school_id', 'topic'], 'idx_qb_school_topic');
            $table->index(['school_id', 'difficulty'], 'idx_qb_school_diff');
            $table->index(['school_id', 'question_type'], 'idx_qb_school_qtype');
        });

        // ──────────────────────────────────────────────────────
        // 2. DIGITAL ASSESSMENTS
        // ──────────────────────────────────────────────────────
        Schema::create('digital_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();

            // Core details
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('instructions')->nullable();

            // Assessment configuration
            $table->string('assessment_mode', 20)->default('standard');
            $table->string('assessment_category', 20)->default('formative');
            $table->boolean('contributes_to_grade')->default(false);
            $table->unsignedBigInteger('assessment_type_id')->nullable();
            $table->foreign('assessment_type_id')->nullable()->references('id')->on('assessment_types')->nullOnDelete();

            $table->string('difficulty', 20)->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->decimal('total_marks', 7, 2)->default(0);
            $table->decimal('pass_mark', 5, 2)->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->unsignedTinyInteger('attempts_allowed')->default(1);

            // Behavior
            $table->boolean('randomize_questions')->default(false);
            $table->boolean('randomize_options')->default(false);
            $table->boolean('show_feedback')->default(true);
            $table->string('feedback_mode', 25)->default('after_submission');
            $table->boolean('shuffle_question_pool')->default(false);
            $table->boolean('allow_backward_navigation')->default(true);
            $table->boolean('allow_question_skipping')->default(true);
            $table->boolean('calculator_enabled')->default(false);
            $table->boolean('password_protection')->default(false);
            $table->boolean('anti_cheating_enabled')->default(false);

            // Pool config for question selection rules
            $table->json('question_pool_config')->nullable();

            // Availability
            $table->timestamp('availability_start_at')->nullable();
            $table->timestamp('availability_end_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->boolean('late_submission_allowed')->default(false);
            $table->boolean('auto_submit')->default(true);

            // Status
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->json('settings')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'subject_id', 'status'], 'idx_da_school_subj_status');
            $table->index(['school_id', 'section_id', 'status'], 'idx_da_school_sect_status');
            $table->index(['school_id', 'created_by_id'], 'idx_da_school_creator');
            $table->index(['status', 'availability_start_at', 'availability_end_at'], 'idx_da_status_avail');
        });

        // ──────────────────────────────────────────────────────
        // 3. DIGITAL ASSESSMENT QUESTIONS (pivot with config)
        // ──────────────────────────────────────────────────────
        Schema::create('digital_assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_assessment_id')->constrained('digital_assessments')->cascadeOnDelete();
            $table->foreignId('question_bank_id')->constrained('question_bank')->cascadeOnDelete();

            $table->unsignedSmallInteger('question_order');
            $table->decimal('marks_override', 5, 2)->nullable();
            $table->string('pool_name')->nullable();
            $table->unsignedSmallInteger('pool_weight')->nullable();

            $table->timestamps();

            $table->unique(['digital_assessment_id', 'question_bank_id'], 'uniq_daq_assess_qb');
            $table->index(['digital_assessment_id', 'question_order'], 'idx_daq_assess_order');
        });

        // ──────────────────────────────────────────────────────
        // 4. DIGITAL ASSESSMENT ATTEMPTS
        // ──────────────────────────────────────────────────────
        Schema::create('digital_assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('digital_assessment_id')->constrained('digital_assessments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();

            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();

            $table->decimal('score', 7, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->decimal('auto_score', 7, 2)->nullable();
            $table->decimal('manual_score', 7, 2)->nullable();
            $table->decimal('final_score', 7, 2)->nullable();
            $table->decimal('marks_obtained', 7, 2)->nullable();
            $table->decimal('max_possible_marks', 7, 2)->nullable();

            $table->string('status', 25)->default('in_progress');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('suspicious_activity_log')->nullable();
            $table->timestamp('feedback_viewed_at')->nullable();

            $table->timestamps();

            $table->unique(['digital_assessment_id', 'student_id', 'attempt_number'], 'uniq_daat_assess_stud_att');
            $table->index(['school_id', 'digital_assessment_id', 'status'], 'idx_daat_school_da_stat');
            $table->index(['school_id', 'student_id', 'status'], 'idx_daat_school_stud_stat');
            $table->index(['student_id', 'status'], 'idx_daat_student_status');
        });

        // ──────────────────────────────────────────────────────
        // 5. DIGITAL ASSESSMENT RESPONSES
        // ──────────────────────────────────────────────────────
        Schema::create('digital_assessment_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_assessment_attempt_id');
            $table->foreign('digital_assessment_attempt_id', 'fk_dar_attempt')
                ->references('id')->on('digital_assessment_attempts')->cascadeOnDelete();
            $table->foreignId('question_bank_id');
            $table->foreign('question_bank_id', 'fk_dar_qb')
                ->references('id')->on('question_bank')->cascadeOnDelete();

            $table->json('learner_answer')->nullable();
            $table->json('correct_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('marks_awarded', 5, 2)->nullable();
            $table->decimal('marks_possible', 5, 2)->nullable();
            $table->unsignedSmallInteger('time_spent_seconds')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->unsignedTinyInteger('confidence_level')->nullable();

            // Adaptive engine data
            $table->string('question_difficulty_at_time', 20)->nullable();
            $table->text('question_selection_reason')->nullable();

            // Manual marking
            $table->text('teacher_feedback')->nullable();
            $table->timestamp('feedback_viewed_at')->nullable();
            $table->foreignId('marked_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable();

            $table->timestamps();

            $table->unique(['digital_assessment_attempt_id', 'question_bank_id'], 'uniq_dar_attempt_qb');
            $table->index(['question_bank_id', 'is_correct'], 'idx_dar_qb_iscorrect');
        });

        // ──────────────────────────────────────────────────────
        // 6. DIGITAL ASSESSMENT AUTO-SAVES
        // ──────────────────────────────────────────────────────
        Schema::create('digital_assessment_auto_saves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_assessment_attempt_id');
            $table->foreign('digital_assessment_attempt_id', 'fk_daas_attempt')
                ->references('id')->on('digital_assessment_attempts')->cascadeOnDelete();
            $table->foreignId('question_bank_id');
            $table->foreign('question_bank_id', 'fk_daas_qb')
                ->references('id')->on('question_bank')->cascadeOnDelete();

            $table->json('response_data')->nullable();
            $table->timestamp('saved_at');

            $table->timestamps();

            $table->unique(['digital_assessment_attempt_id', 'question_bank_id'], 'uniq_daas_attempt_qb');
        });

        // ──────────────────────────────────────────────────────
        // 7. LEARNER MASTERY
        // ──────────────────────────────────────────────────────
        Schema::create('learner_mastery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();

            $table->string('topic');
            $table->string('subtopic')->nullable();

            $table->decimal('mastery_score', 5, 2)->default(0);
            $table->string('mastery_label', 20)->default('beginning');

            $table->unsignedInteger('total_assessments')->default(0);
            $table->unsignedInteger('correct_responses')->default(0);
            $table->unsignedInteger('total_responses')->default(0);

            $table->timestamp('last_assessed_at')->nullable();

            $table->timestamps();

            $table->unique(['enrollment_id', 'subject_id', 'topic', 'subtopic'], 'uniq_lm_enroll_subj_topic');
            $table->index(['school_id', 'student_id', 'subject_id'], 'idx_lm_school_stud_subj');
            $table->index(['student_id', 'mastery_label'], 'idx_lm_student_label');
        });

        // ──────────────────────────────────────────────────────
        // 8. GAMIFICATION SETTINGS (per school)
        // ──────────────────────────────────────────────────────
        Schema::create('gamification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            $table->boolean('xp_enabled')->default(false);
            $table->boolean('badges_enabled')->default(false);
            $table->boolean('achievements_enabled')->default(false);
            $table->boolean('streaks_enabled')->default(false);
            $table->boolean('challenges_enabled')->default(false);
            $table->boolean('leaderboards_enabled')->default(false);

            $table->unsignedSmallInteger('xp_per_assessment_complete')->default(10);
            $table->unsignedSmallInteger('xp_per_improvement')->default(15);
            $table->unsignedSmallInteger('xp_per_streak_day')->default(5);
            $table->unsignedSmallInteger('xp_per_topic_mastery')->default(25);
            $table->unsignedSmallInteger('xp_per_challenge_complete')->default(20);

            $table->json('streak_qualifying_activities')->nullable();
            $table->json('level_config')->nullable();

            $table->string('leaderboard_scope', 20)->default('class');
            $table->boolean('leaderboard_anonymize')->default(false);
            $table->json('settings')->nullable();

            $table->timestamps();

            $table->unique('school_id', 'uniq_gs_school');
        });

        // ──────────────────────────────────────────────────────
        // 9. LEARNER XP
        // ──────────────────────────────────────────────────────
        Schema::create('learner_xp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            $table->unsignedInteger('total_xp')->default(0);
            $table->unsignedSmallInteger('current_level')->default(1);
            $table->string('current_level_name', 50)->default('Explorer');

            $table->timestamps();

            $table->unique(['school_id', 'student_id'], 'uniq_lxp_school_student');
            $table->index(['school_id', 'total_xp'], 'idx_lxp_school_xp');
        });

        // ──────────────────────────────────────────────────────
        // 10. XP TRANSACTIONS
        // ──────────────────────────────────────────────────────
        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('learner_xp_id')->constrained('learner_xp')->cascadeOnDelete();

            $table->integer('amount');
            $table->string('type', 30);
            $table->text('description')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->timestamp('created_at');

            $table->index(['school_id', 'student_id', 'created_at'], 'idx_xpt_school_stud_time');
            $table->index(['student_id', 'type'], 'idx_xpt_student_type');
        });

        // ──────────────────────────────────────────────────────
        // 11. GAMIFICATION BADGES
        // ──────────────────────────────────────────────────────
        Schema::create('gamification_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->default('heroicon-o-star');
            $table->json('criteria')->nullable();
            $table->unsignedSmallInteger('xp_reward')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->string('visibility', 20)->default('public');

            $table->timestamps();

            $table->index(['school_id', 'is_active'], 'idx_gb_school_active');
        });

        // ──────────────────────────────────────────────────────
        // 12. LEARNER BADGES
        // ──────────────────────────────────────────────────────
        Schema::create('learner_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('gamification_badge_id')->constrained('gamification_badges')->cascadeOnDelete();

            $table->timestamp('earned_at');
            $table->boolean('notified')->default(false);

            $table->timestamps();

            $table->unique(['student_id', 'gamification_badge_id'], 'uniq_lbg_student_badge');
            $table->index(['school_id', 'student_id'], 'idx_lbg_school_student');
        });

        // ──────────────────────────────────────────────────────
        // 13. GAMIFICATION ACHIEVEMENTS
        // ──────────────────────────────────────────────────────
        Schema::create('gamification_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->default('heroicon-o-trophy');
            $table->json('criteria')->nullable();
            $table->unsignedSmallInteger('xp_reward')->default(0);
            $table->string('achievement_type', 30)->default('performance');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);

            $table->timestamps();

            $table->index(['school_id', 'is_active'], 'idx_ga_school_active');
        });

        // ──────────────────────────────────────────────────────
        // 14. LEARNER ACHIEVEMENTS
        // ──────────────────────────────────────────────────────
        Schema::create('learner_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('gamification_achievement_id')->constrained('gamification_achievements')->cascadeOnDelete();

            $table->timestamp('earned_at');
            $table->boolean('notified')->default(false);

            $table->timestamps();

            $table->unique(['student_id', 'gamification_achievement_id'], 'uniq_lach_student_ach');
            $table->index(['school_id', 'student_id'], 'idx_lach_school_student');
        });

        // ──────────────────────────────────────────────────────
        // 15. GAMIFICATION CHALLENGES
        // ──────────────────────────────────────────────────────
        Schema::create('gamification_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('challenge_type', 20)->default('individual');

            $table->foreignId('target_subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('target_topic')->nullable();
            $table->unsignedSmallInteger('target_count')->default(5);
            $table->unsignedSmallInteger('reward_xp')->default(100);
            $table->foreignId('reward_badge_id')->nullable()->constrained('gamification_badges')->nullOnDelete();

            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('settings')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'status', 'start_at', 'end_at'], 'idx_gch_school_status_dates');
        });

        // ──────────────────────────────────────────────────────
        // 16. CHALLENGE PARTICIPANTS
        // ──────────────────────────────────────────────────────
        Schema::create('challenge_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('gamification_challenge_id')->constrained('gamification_challenges')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            $table->unsignedSmallInteger('progress')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('xp_earned')->default(0);

            $table->timestamps();

            $table->unique(['gamification_challenge_id', 'student_id'], 'uniq_cp_challenge_student');
            $table->index(['school_id', 'student_id'], 'idx_cp_school_student');
        });

        // ──────────────────────────────────────────────────────
        // 17. LEARNER STREAKS
        // ──────────────────────────────────────────────────────
        Schema::create('learner_streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            $table->unsignedSmallInteger('current_streak')->default(0);
            $table->unsignedSmallInteger('longest_streak')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->date('streak_start_date')->nullable();
            $table->unsignedInteger('total_active_days')->default(0);

            $table->timestamps();

            $table->unique(['school_id', 'student_id'], 'uniq_lstreak_school_student');
            $table->index(['school_id', 'current_streak'], 'idx_lstreak_school_streak');
        });

        // ──────────────────────────────────────────────────────
        // 18. LEADERBOARD SNAPSHOTS
        // ──────────────────────────────────────────────────────
        Schema::create('leaderboard_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->string('snapshot_type', 20)->default('weekly');
            $table->string('scope_type', 20);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            $table->integer('score')->default(0);
            $table->unsignedSmallInteger('rank_position');
            $table->json('metadata')->nullable();

            $table->timestamp('created_at');

            $table->index(['school_id', 'snapshot_date', 'scope_type', 'scope_id'], 'idx_lbs_school_date_scope');
            $table->index(['school_id', 'snapshot_date', 'student_id'], 'idx_lbs_school_date_stud');
        });

        // ──────────────────────────────────────────────────────
        // 19. QUESTION ANALYTICS
        // ──────────────────────────────────────────────────────
        Schema::create('question_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('question_bank_id')->constrained('question_bank')->cascadeOnDelete();

            $table->unsignedInteger('total_attempts')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('incorrect_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->decimal('percentage_correct', 5, 2)->default(0);
            $table->unsignedInteger('average_response_time_seconds')->default(0);
            $table->decimal('average_confidence', 3, 2)->default(0);
            $table->timestamp('last_calculated_at')->nullable();

            $table->timestamps(false);

            $table->unique(['school_id', 'question_bank_id'], 'uniq_qa_school_qb');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_analytics');
        Schema::dropIfExists('leaderboard_snapshots');
        Schema::dropIfExists('learner_streaks');
        Schema::dropIfExists('challenge_participants');
        Schema::dropIfExists('gamification_challenges');
        Schema::dropIfExists('learner_achievements');
        Schema::dropIfExists('gamification_achievements');
        Schema::dropIfExists('learner_badges');
        Schema::dropIfExists('gamification_badges');
        Schema::dropIfExists('xp_transactions');
        Schema::dropIfExists('learner_xp');
        Schema::dropIfExists('gamification_settings');
        Schema::dropIfExists('learner_mastery');
        Schema::dropIfExists('digital_assessment_auto_saves');
        Schema::dropIfExists('digital_assessment_responses');
        Schema::dropIfExists('digital_assessment_attempts');
        Schema::dropIfExists('digital_assessment_questions');
        Schema::dropIfExists('digital_assessments');
        Schema::dropIfExists('question_bank');
    }
};
