<?php

declare(strict_types=1);

namespace Shimmie2;

class GitPull extends Extension
{
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        global $page;
        $html = (string)SHM_SIMPLE_FORM(
            "admin/git_pull",
            SHM_SUBMIT('Pull from git'),
        );
        $page->add_block(new Block("Git Pull", $html));
    }
    public function onAdminAction(AdminActionEvent $event): void
    {
        global $database;
        switch($event->action) {
            case "git_pull":
                $this->execGitPull();
                $event->redirect = true;
                break;
        }
    }

    private function execGitPull() : void {
        $command = new CommandBuilder("git");
        $command->add_flag("pull");
        $command->execute(true);
    }
}
