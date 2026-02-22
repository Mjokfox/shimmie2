<?php

declare(strict_types=1);

namespace Shimmie2;

final class DiscordBot extends Extension
{
    public const KEY = "discord_bot";
    public const VERSION_KEY = "ext_comments_version";
    /** @var array<array<string, mixed>> */
    private array $data = [];

    public function get_priority(): int
    {
        return 51;
    }

    #[EventListener]
    public function onInitExt(InitExtEvent $event): void
    {
        $event->add_shutdown_handler(function () {
            $this->send_all_data();
        });
    }

    #[EventListener(priority: 51)]
    public function onCommentPosting(CommentPostingEvent $event): void
    {
        $this->add_data($this->data_builder(
            "comment",
            "create",
            [
                "username" => $event->user->name,
                "post_id" => $event->image_id,
                "comment_id" => $event->id,
                "message" => $event->comment,
            ]
        ));
    }

    #[EventListener(priority: 51)]
    public function onCommentEditing(CommentEditingEvent $event): void
    {
        $this->add_data($this->data_builder(
            "comment",
            "edit",
            [
                "username" => $event->user->name,
                "post_id" => $event->image_id,
                "comment_id" => $event->comment_id,
                "message" => $event->comment
            ]
        ));
    }

    #[EventListener(priority: 51)]
    public function onCommentDeletion(CommentDeletionEvent $event): void
    {
        $this->add_data($this->data_builder(
            "comment",
            "delete",
            [
                "comment_id" => $event->comment_id
            ]
        ));
    }

    #[EventListener(priority: 51)]
    public function onImageFinished(MediaFinishedEvent $event): void
    {
        $this->add_data($this->data_builder(
            "post",
            "create",
            [
                "post_id" => $event->post->id,
                "username" => Ctx::$user->name,
                "hash" => $event->post->hash,
                "mime" => $event->post->get_mime()->__toString(),
                "size" => $event->post->filesize
            ]
        ));
    }

    #[EventListener(priority: 51)]
    public function onImageReplace(MediaReplaceEvent $event): void
    {
        $this->add_data($this->data_builder(
            "post",
            "edit",
            [
                "post_id" => $event->image->id,
                "username" => Ctx::$user->name,
                "hash" => $event->image->hash,
                "mime" => $event->image->get_mime()->__toString(),
                "size" => $event->image->filesize
            ]
        ));
    }

    #[EventListener(priority: 51)]
    public function onImageDeletion(PostDeletionEvent $event): void
    {
        $this->add_data($this->data_builder(
            "post",
            "delete",
            [
                "post_id" => $event->image->id,
                "hash" => $event->image->hash
            ]
        ));
    }

    #[EventListener(priority: 51)]
    public function onUserCreation(UserCreationEvent $event): void
    {
        $user = $event->get_user();
        $this->add_data($this->data_builder(
            "user",
            "create",
            [
                "username" => $user->name,
            ]
        ));
    }

    #[EventListener(priority: 51)]
    public function onUserDeletion(UserDeletionEvent $event): void
    {
        $this->add_data($this->data_builder(
            "user",
            "delete",
            [
                "username" => User::by_id($event->id)->name,
            ]
        ));
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function data_builder(string $section, string $type, array $fields): array
    {
        return [
            "section" => $section,
            "type" => $type,
            "fields" => $fields
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function add_data(array $data): void
    {
        $this->data[] = $data;
    }

    private function send_all_data(): void
    {
        foreach ($this->data as $data) {
            $this->send_data($data);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function send_data(array $data): void
    {
        $hosts = Ctx::$config->get(DiscordBotConfig::HOST);
        if (!$hosts) {
            return;
        }

        $hosts = explode(";", $hosts);

        foreach ($hosts as $host) {
            try {
                $parts = explode(":", $host);
                $addr = $parts[0];
                $port = (int)$parts[1];
                $fp = fsockopen("udp://$addr", $port, $errno, $errstr);
                if (!$fp) {
                    return;
                }
                fwrite($fp, \Safe\json_encode($data));
                fclose($fp);
            } catch (\Exception $e) {
                // nah we dont care
            }
        }
    }
}
