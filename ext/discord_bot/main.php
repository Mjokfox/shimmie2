<?php

declare(strict_types=1);

namespace Shimmie2;

final class DiscordBot extends Extension
{
    public const KEY = "discord_bot";
    public const VERSION_KEY = "ext_comments_version";


    public function get_priority(): int
    {
        return 51;
    }
    public function onCommentPosting(CommentPostingEvent $event): void
    {
        $this->send_data($this->data_builder(
            "comment",
            "create",
            [
                "username" => $event->user->name,
                "post_id" => $event->image_id,
                "comment_id" => $event->comment_id,
                "message" => $event->comment,
            ]
        ));
    }

    public function onCommentEditing(CommentEditingEvent $event): void
    {
        $this->send_data($this->data_builder(
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

    public function onCommentDeletion(CommentDeletionEvent $event): void
    {
        $this->send_data($this->data_builder(
            "comment",
            "delete",
            [
                "comment_id" => $event->comment_id
            ]
        ));
    }

    public function onImageAddition(ImageAdditionEvent $event): void
    {
        $this->send_data($this->data_builder(
            "image",
            "create",
            [
                "post_id" => $event->image->id,
                "username" => Ctx::$user->name,
                "hash" => $event->image->hash,
                "mime" => $event->image->get_mime()->__toString(),
                "size" => $event->image->filesize
            ]
        ));
    }

    public function onImageReplace(ImageReplaceEvent $event): void
    {
        $this->send_data($this->data_builder(
            "image",
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

    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        $this->send_data($this->data_builder(
            "image",
            "delete",
            [
                "post_id" => $event->image->id,
                "hash" => $event->image->hash
            ]
        ));
    }

    public function onUserCreation(UserCreationEvent $event): void
    {
        $user = $event->get_user();
        $this->send_data($this->data_builder(
            "user",
            "create",
            [
                "username" => $user->name,
            ]
        ));
    }

    public function onUserDeletion(UserDeletionEvent $event): void
    {
        $this->send_data($this->data_builder(
            "user",
            "delete",
            [
                "username" => $event->id,
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
    private function send_data(array $data): void
    {
        $host = Ctx::$config->get(DiscordBotConfig::HOST);
        if (!$host) {
            return;
        }

        try {
            $parts = explode(":", $host);
            $host = $parts[0];
            $port = (int)$parts[1];
            $fp = fsockopen("udp://$host", $port, $errno, $errstr);
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
