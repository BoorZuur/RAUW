<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BlockedKeyword;
use App\Models\Category;
use App\Models\ContentFlag;
use App\Models\Department as DepartmentModel;
use App\Models\District;
use App\Models\DomainNotification;
use App\Models\Issue;
use App\Models\IssueAttachment;
use App\Models\IssueComment;
use App\Models\IssueMessage;
use App\Models\IssueParticipant;
use App\Models\IssueResolution;
use App\Models\IssueStatusHistory;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\OfficerSession;
use App\Models\ReportSnapshot;
use App\Models\UserReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_domain_models_can_be_created_with_factories(): void
    {
        $district = District::factory()->create();
        // Categories now attach to departments through the pivot table rather
        // than a `department` enum column.
        $department = DepartmentModel::factory()->create();
        $category = Category::factory()->withDepartments($department)->create();
        $officer = Officer::factory()->withDistricts([$district])->create();
        $manager = Manager::factory()->withDistricts([$district])->create();
        $issue = Issue::factory()->create([
            'category_id' => $category->id,
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        $models = [
            $district,
            $category,
            $officer,
            $manager,
            $issue,
            IssueComment::factory()->create(['issue_id' => $issue->id]),
            IssueMessage::factory()->create(['issue_id' => $issue->id]),
            IssueParticipant::factory()->create(['issue_id' => $issue->id]),
            IssueStatusHistory::factory()->create(['issue_id' => $issue->id, 'changed_by_officer_id' => $officer->id]),
            IssueResolution::factory()->create(['issue_id' => $issue->id]),
            IssueAttachment::factory()->create(['issue_id' => $issue->id]),
            ContentFlag::factory()->create(['issue_id' => $issue->id, 'flagged_by_officer_id' => $officer->id, 'reviewed_by_manager_id' => $manager->id]),
            BlockedKeyword::factory()->create(['added_by_manager_id' => $manager->id]),
            DomainNotification::factory()->create(['issue_id' => $issue->id]),
            ReportSnapshot::factory()->create(['generated_by_manager_id' => $manager->id]),
            UserReview::factory()->create(['reviewed_by_manager_id' => $manager->id]),
            OfficerSession::factory()->create(['officer_id' => $officer->id]),
            AuditLog::factory()->create(),
        ];

        foreach ($models as $model) {
            $this->assertModelExists($model);
        }
    }

    public function test_report_snapshot_metrics_are_flexible_and_cast_to_array(): void
    {
        $snapshot = ReportSnapshot::factory()->create([
            'metrics' => [
                'priority_counts' => ['laag' => 2],
                'custom_metric' => ['value' => 123],
            ],
        ]);

        $snapshot->refresh();

        $this->assertIsArray($snapshot->metrics);
        $this->assertSame(2, $snapshot->metrics['priority_counts']['laag']);
        $this->assertSame(123, $snapshot->metrics['custom_metric']['value']);
    }
}
