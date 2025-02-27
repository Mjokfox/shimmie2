<?php

declare(strict_types=1);

namespace Shimmie2;

class DescriptionSetEvent extends Event
{
    public int $image_id;
    public User $user;
    public string $description;

    public function __construct(int $image_id, User $user, string $description)
    {
        parent::__construct();
        $this->image_id = $image_id;
        $this->user = $user;
        $this->description = $description;
    }
}


class PostDescription extends Extension
{
    /** @var PostDescriptionTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["description"] = ImagePropType::STRING;
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;
        if ($this->get_version(PostDescriptionConfig::VERSION) < 1) {
            $database->execute("ALTER TABLE images ADD COLUMN description VARCHAR(512)");

            $this->set_version(PostDescriptionConfig::VERSION, 1);

            log_info("Post description", "extension installed");
        }
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        global $config, $page, $user, $database;
        $desc = $event->get_param('description');
        if ($user->can(PostSourcePermission::EDIT_IMAGE_SOURCE)) {
            /** @var DescriptionSetEvent $cpe */
            $cpe = send_event(new DescriptionSetEvent($event->image->id, $user, $desc));
            $database->execute("UPDATE images SET description=:description WHERE id=:id", ["description" => substr($cpe->description, 0, 512), "id" => $event->image->id]);
        }
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_description_editor_html($event->image), 11);
    }

    public function onUploadSpecificBuilding(UploadSpecificBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_upload_specific_html($event->suffix), 51);
    }
}
