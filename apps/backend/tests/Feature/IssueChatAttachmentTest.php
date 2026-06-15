<?php

namespace Tests\Feature;

use App\Enums\IssueMessageSenderType;
use App\Enums\IssueStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\IssueMessage;
use App\Models\IssueMessageAttachment;
use App\Models\Officer;
use App\Models\User;
use App\Support\Issues\IssueChatMessageAttachments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IssueChatAttachmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function authHeaders(object $actor): array
    {
        return ['Authorization' => 'Bearer '.$actor->createToken('test')->plainTextToken];
    }

    /**
     * @return array{issue: Issue, officer: Officer, owner: User, chat: IssueChat}
     */
    private function openChatContext(): array
    {
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $owner = User::factory()->create();
        $officer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $issue = Issue::factory()->withStatus(IssueStatus::InProgress)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        $chat = IssueChat::factory()->open()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'opened_by_officer_id' => $officer->id,
        ]);

        return compact('issue', 'officer', 'owner', 'chat');
    }

    public function test_send_message_can_upload_attachments(): void
    {
        Storage::fake(IssueChatMessageAttachments::DISK);

        ['issue' => $issue, 'officer' => $officer, 'chat' => $chat] = $this->openChatContext();

        $file = UploadedFile::fake()->image('evidence.jpg');

        $response = $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/chats/{$chat->id}/messages", [
                'content' => 'See attached',
                'files' => [$file],
            ]);

        $response->assertCreated()
            ->assertJsonPath('content', 'See attached')
            ->assertJsonCount(1, 'attachments');

        $message = IssueMessage::query()->firstOrFail();
        $attachment = $message->attachments()->firstOrFail();

        Storage::disk(IssueChatMessageAttachments::DISK)->assertExists($attachment->file_path);
    }

    public function test_attachment_only_message_is_allowed(): void
    {
        Storage::fake(IssueChatMessageAttachments::DISK);

        ['issue' => $issue, 'owner' => $owner, 'chat' => $chat] = $this->openChatContext();

        $file = UploadedFile::fake()->image('photo.png');

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/issues/{$issue->id}/chats/{$chat->id}/messages", [
                'files' => [$file],
            ])
            ->assertCreated()
            ->assertJsonPath('content', null)
            ->assertJsonCount(1, 'attachments');
    }

    public function test_send_message_rejects_more_than_three_attachments(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'chat' => $chat] = $this->openChatContext();

        $files = [
            UploadedFile::fake()->image('1.jpg'),
            UploadedFile::fake()->image('2.jpg'),
            UploadedFile::fake()->image('3.jpg'),
            UploadedFile::fake()->image('4.jpg'),
        ];

        $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/chats/{$chat->id}/messages", [
                'content' => 'Too many',
                'files' => $files,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['files']);
    }

    public function test_chat_partner_can_download_attachment(): void
    {
        Storage::fake(IssueChatMessageAttachments::DISK);

        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner, 'chat' => $chat] = $this->openChatContext();

        $message = IssueMessage::factory()->create([
            'issue_id' => $issue->id,
            'issue_chat_id' => $chat->id,
            'sender_type' => IssueMessageSenderType::Officer,
            'officer_id' => $officer->id,
            'user_id' => null,
        ]);

        $attachment = IssueMessageAttachment::factory()->create([
            'issue_message_id' => $message->id,
            'file_path' => IssueChatMessageAttachments::DIRECTORY.'/'.$chat->id.'/proof.jpg',
            'original_name' => 'proof.jpg',
        ]);

        Storage::disk(IssueChatMessageAttachments::DISK)->put($attachment->file_path, 'binary-data');

        $this->withHeaders($this->authHeaders($owner))
            ->getJson("/api/issues/{$issue->id}/chats/{$chat->id}/messages/{$message->id}/attachments/{$attachment->id}/download")
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=proof.jpg');
    }

    public function test_outsider_cannot_download_chat_attachment(): void
    {
        Storage::fake(IssueChatMessageAttachments::DISK);

        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner, 'chat' => $chat] = $this->openChatContext();
        $outsider = User::factory()->create();

        $message = IssueMessage::factory()->create([
            'issue_id' => $issue->id,
            'issue_chat_id' => $chat->id,
            'sender_type' => IssueMessageSenderType::User,
            'user_id' => $owner->id,
            'officer_id' => null,
        ]);

        $attachment = IssueMessageAttachment::factory()->create([
            'issue_message_id' => $message->id,
            'file_path' => IssueChatMessageAttachments::DIRECTORY.'/'.$chat->id.'/secret.jpg',
            'original_name' => 'secret.jpg',
        ]);

        Storage::disk(IssueChatMessageAttachments::DISK)->put($attachment->file_path, 'binary-data');

        $this->withHeaders($this->authHeaders($outsider))
            ->getJson("/api/issues/{$issue->id}/chats/{$chat->id}/messages/{$message->id}/attachments/{$attachment->id}/download")
            ->assertForbidden()
            ->assertJsonPath('code', 'not_chat_participant');
    }
}
