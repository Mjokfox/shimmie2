<?php

declare(strict_types=1);

namespace Shimmie2;

class GitPull extends Extension
{
    public const KEY = "git_pull";
    public function get_priority(): int
    {
        return 1;
    }
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        global $page;
        $html = SHM_SIMPLE_FORM(
            make_link("admin/git_pull"),
            SHM_SUBMIT('Pull from git'),
        );
        $page->add_block(new Block("Git Pull", $html));
    }
    public function onAdminAction(AdminActionEvent $event): void
    {
        global $database;
        switch ($event->action) {
            case "git_pull":
                $output = $this->execGitPull();
                Log::warning("admin", $output, $output);
                $event->redirect = true;
                break;
        }
    }

    private function execGitPull(): string
    {
        $command = new CommandBuilder("git");
        $command->add_args("pull");
        return $command->execute();
    }
}
