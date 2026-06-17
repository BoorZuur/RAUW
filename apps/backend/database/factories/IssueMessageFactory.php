<?php

namespace Database\Factories;

use App\Enums\IssueMessageSenderType;
use App\Enums\IssueMessageType;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\IssueMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueMessage>
 */
class IssueMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'issue_id' => Issue::factory(),
            'issue_chat_id' => null,
            'message_type' => IssueMessageType::Message,
            'sender_type' => IssueMessageSenderType::User,
            'user_id' => User::factory(),
            'officer_id' => null,
            'content' => fake()->paragraph(),
            'meta' => null,
            'is_flagged' => false,
            'is_read' => false,
            'created_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (IssueMessage $message): void {
            if ($message->issue_chat_id !== null) {
                return;
            }

            $issueId = $message->issue_id;
            if ($issueId instanceof Issue) {
                $issueId = $issueId->getKey();
            }

            $userId = $message->user_id;
            if ($userId instanceof User) {
                $userId = $userId->getKey();
            }

            $chat = IssueChat::factory()->create([
                'issue_id' => $issueId,
                'user_id' => $userId ?? User::factory()->create()->getKey(),
            ]);

            $message->issue_chat_id = $chat->getKey();
        });
    }

    public function system(string $content = 'Chat gesloten omdat de melding is afgerond.', array $meta = ['code' => 'chat_closed_status_gesloten']): static
    {
        return $this->state(fn (): array => [
            'message_type' => IssueMessageType::System,
            'sender_type' => IssueMessageSenderType::System,
            'user_id' => null,
            'officer_id' => null,
            'content' => $content,
            'meta' => $meta,
            'is_read' => true,
        ]);
    }
}
